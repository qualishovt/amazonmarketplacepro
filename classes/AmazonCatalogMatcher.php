<?php
/**
 * Matches PrestaShop products to existing Amazon catalog entries (ASINs)
 * by EAN/UPC, using the Catalog Items API (2022-04-01).
 *
 * A matched ASIN is stored on the staging row and later sent as
 * merchant_suggested_asin so Amazon attaches the offer to the right
 * product page instead of creating a duplicate.
 *
 * Multistore: only the staged rows of the shop the request acts for are
 * matched, with that shop's client.
 *
 * PHP 5.6+ compatible.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonCatalogMatcher
{
    /** Catalog Items API allows up to 20 identifiers per request. */
    const BATCH_SIZE = 20;

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError;
    private $useMock = false;

    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
    }

    public function setMock($enabled)
    {
        $this->useMock = (bool) $enabled;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Match staged products that have an EAN but no ASIN yet.
     *
     * @param int $limit Max products to process this run
     *
     * @return array|false Summary counts, or false on API error
     */
    public function matchStagedProducts($limit = 100)
    {
        $summary = ['candidates' => 0, 'matched' => 0, 'not_found' => 0, 'batches' => 0];

        $rows = Db::getInstance()->executeS(
            'SELECT `seller_sku`, `ps_ean13`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             WHERE `id_shop` = ' . (int) AmzproShop::actingId() . '
               AND `ps_exists` = 1
               AND `is_parent` = 0
               AND `amazon_asin` = \'\'
               AND `ps_ean13` != \'\'
               AND LENGTH(`ps_ean13`) >= 8
             ORDER BY `seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows) || empty($rows)) {
            return $summary;
        }

        $summary['candidates'] = count($rows);

        // ean => list of SKUs carrying it (combinations may share the product EAN)
        $skusByEan = [];
        foreach ($rows as $r) {
            $ean = trim($r['ps_ean13']);
            if (!isset($skusByEan[$ean])) {
                $skusByEan[$ean] = [];
            }
            $skusByEan[$ean][] = $r['seller_sku'];
        }

        $eans = array_keys($skusByEan);
        foreach (array_chunk($eans, self::BATCH_SIZE) as $chunk) {
            ++$summary['batches'];

            $found = $this->lookupAsinsByEan($chunk);
            if ($found === false) {
                return false; // lastError set
            }

            foreach ($chunk as $ean) {
                if (isset($found[$ean])) {
                    foreach ($skusByEan[$ean] as $sku) {
                        $this->storeAsin($sku, $found[$ean]);
                        ++$summary['matched'];
                    }
                } else {
                    $summary['not_found'] += count($skusByEan[$ean]);
                }
            }
        }

        return $summary;
    }

    /**
     * Look up ASINs for a batch of EANs (max 20).
     *
     * @param array $eans
     *
     * @return array|false ean => asin for the ones found, or false on error
     */
    public function lookupAsinsByEan($eans)
    {
        $this->lastError = null;

        if ($this->useMock) {
            // Deterministic sample: "find" every other EAN.
            $out = [];
            $i = 0;
            foreach ($eans as $ean) {
                if (($i++ % 2) === 0) {
                    $out[$ean] = 'B0MOCK' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
                }
            }

            return $out;
        }

        $idType = (Tools::strlen((string) $eans[0]) >= 13) ? 'EAN' : 'UPC';

        $resp = $this->client->request('GET', '/catalog/2022-04-01/items', [
            'identifiers' => implode(',', $eans),
            'identifiersType' => $idType,
            'marketplaceIds' => $this->marketplaceId,
            'includedData' => 'identifiers,summaries',
            'pageSize' => self::BATCH_SIZE,
        ]);

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();

            return false;
        }
        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'searchCatalogItems HTTP ' . $resp['status'] . ': ' . $body;

            return false;
        }

        $out = [];
        $items = isset($resp['body']['items']) ? $resp['body']['items'] : [];
        foreach ($items as $item) {
            if (!isset($item['asin'])) {
                continue;
            }
            // Map the item back to the EAN(s) it was found by.
            if (isset($item['identifiers']) && is_array($item['identifiers'])) {
                foreach ($item['identifiers'] as $marketplaceIdentifiers) {
                    if (!isset($marketplaceIdentifiers['identifiers'])) {
                        continue;
                    }
                    foreach ($marketplaceIdentifiers['identifiers'] as $ident) {
                        if (isset($ident['identifier']) && in_array($ident['identifier'], $eans)
                            && !isset($out[$ident['identifier']])) {
                            $out[$ident['identifier']] = $item['asin'];
                        }
                    }
                }
            }
        }

        return $out;
    }

    private function storeAsin($sku, $asin)
    {
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             SET `amazon_asin` = \'' . pSQL($asin) . '\',
                 `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `seller_sku` = \'' . pSQL($sku) . '\'
               AND `id_shop` = ' . (int) AmzproShop::actingId()
        );
    }
}
