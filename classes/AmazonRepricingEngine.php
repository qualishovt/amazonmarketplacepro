<?php
/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/*
 * Amazon Repricing Engine.
 *
 * Handles:
 * - Fetching competitive pricing data (Buy Box, lowest offers)
 * - Managing pricing rules (match lowest, beat by %, fixed margin, etc.)
 * - Automated repricing based on rules with min/max price safeguards
 * - Buy Box win/loss tracking
 *
 * Uses SP-API Product Pricing API v0.
 *
 * Multistore: fetching, rule application and pushes work on the current
 * shop's listings and write that shop's prices. Pricing rules are shared by
 * every shop (id_shop 0) and a shop can add rules of its own.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonRepricingEngine
{
    /** Rule types */
    public static $RULE_MATCH_LOWEST = 'match_lowest';
    public static $RULE_BEAT_LOWEST = 'beat_lowest';
    public static $RULE_MATCH_BUYBOX = 'match_buybox';
    public static $RULE_BEAT_BUYBOX = 'beat_buybox';
    public static $RULE_FIXED_MARGIN = 'fixed_margin';

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $sellerId;
    private $lastError;
    private $notices = [];

    public function __construct(AmazonSpApiClient $client, $marketplaceId, $sellerId = '')
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
        $this->sellerId = trim((string) $sellerId);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * The Amazon prices the repricer manages in a shop: the SKUs it has
     * pushed a price for, while a pricing rule is active. The product sync
     * sends these prices instead of the shop's, so it does not undo the
     * repricing.
     *
     * @return array sku => price last sent by the repricer
     */
    public static function managedPrices($idShop, $marketplaceId)
    {
        $idShop = (int) $idShop;
        $rows = Db::getInstance()->executeS(
            'SELECT cp.`seller_sku`, cp.`our_price`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
             WHERE cp.`id_shop` = ' . $idShop . '
               AND cp.`marketplace_id` = \'' . pSQL($marketplaceId) . '\'
               AND cp.`last_repriced` IS NOT NULL AND cp.`our_price` > 0
               AND EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule` r
                   WHERE r.`active` = 1
                     AND (r.`marketplace_id` = \'' . pSQL($marketplaceId) . '\' OR r.`marketplace_id` = \'\')
                     AND ' . AmzproShop::sqlShared('r', $idShop) . ')'
        );
        $out = [];
        foreach ((is_array($rows) ? $rows : []) as $r) {
            $out[$r['seller_sku']] = (float) $r['our_price'];
        }

        return $out;
    }

    /**
     * Fetch competitive pricing data for all synced products.
     *
     * Uses the Product Pricing API to get Buy Box prices and lowest offers.
     * Processes in batches of 20 ASINs (API limit).
     *
     * @return array Summary
     */
    public function fetchCompetitivePricing()
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'checked' => 0,
            'updated' => 0,
            'buybox_wins' => 0,
            'buybox_losses' => 0,
            'errors' => 0,
        ];

        $idShop = (int) AmzproShop::actingId();
        // The shop's live listings (Amazon gave them a product type), the
        // ones checked longest ago first, so repeated fetches go round the
        // whole catalogue 200 at a time.
        $products = Db::getInstance()->executeS(
            'SELECT p.`seller_sku`, p.`amazon_asin`, p.`amazon_price`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
                 ON (cp.`id_shop` = p.`id_shop` AND cp.`seller_sku` = p.`seller_sku`
                     AND cp.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\')
             WHERE p.`amazon_asin` <> \'\' AND p.`amazon_product_type` <> \'\'
               AND p.`amazon_exists` = 1 AND p.`is_parent` = 0 AND p.`marketplace_id` = \'\'
               AND p.`id_shop` = ' . $idShop . '
             ORDER BY cp.`date_upd` IS NOT NULL, cp.`date_upd` ASC, p.`seller_sku` ASC
             LIMIT 200'
        );

        if (!is_array($products) || empty($products)) {
            $this->notices[] = AmazonI18n::get()->l('No products with ASINs found. Sync products first.', 'amazonrepricingengine');

            return $summary;
        }

        @set_time_limit(0);
        $needLowest = $this->rulesNeedLowest($idShop);
        $now = date('Y-m-d H:i:s');

        // 20 per request, the API's limit
        foreach (array_chunk($products, 20) as $batch) {
            $skuByAsin = [];
            $fallbackPrice = [];
            foreach ($batch as $p) {
                $skuByAsin[$p['amazon_asin']] = $p['seller_sku'];
                $fallbackPrice[$p['seller_sku']] = (float) $p['amazon_price'];
            }

            $resp = $this->pricingRequest('GET', '/products/pricing/v0/competitivePrice', [
                'MarketplaceId' => $this->marketplaceId,
                'Asins' => implode(',', array_keys($skuByAsin)),
                'ItemType' => 'Asin',
            ]);
            if ($resp === false || $resp['status'] >= 400) {
                $summary['errors'] += count($batch);
                continue;
            }
            $payload = (is_array($resp['body']) && isset($resp['body']['payload'])) ? $resp['body']['payload'] : [];

            $own = $this->fetchOwnOffers(array_values($skuByAsin));
            $lowest = $needLowest ? $this->fetchLowestOffers(array_keys($skuByAsin)) : [];

            foreach ($payload as $item) {
                $asin = isset($item['ASIN']) ? $item['ASIN'] : '';
                if ($asin === '' || !isset($skuByAsin[$asin])) {
                    continue;
                }
                $sku = $skuByAsin[$asin];
                ++$summary['checked'];

                $offer = [
                    'sku' => $sku,
                    'asin' => $asin,
                    'buybox_price' => 0,
                    'buybox_shipping' => 0,
                    'buybox_landed' => 0,
                    'buybox_seller' => '',
                    'is_buybox_winner' => 0,
                    'lowest_price' => 0,
                    'lowest_shipping' => 0,
                    'lowest_landed' => 0,
                    'number_of_offers' => 0,
                    // Our own offer on Amazon, not the shop's price
                    'our_price' => isset($own[$sku]) ? $own[$sku]['price'] : $fallbackPrice[$sku],
                    'our_shipping' => isset($own[$sku]) ? $own[$sku]['shipping'] : 0,
                ];

                $competitive = isset($item['Product']['CompetitivePricing']) ? $item['Product']['CompetitivePricing'] : [];
                if (isset($competitive['CompetitivePrices'])) {
                    foreach ($competitive['CompetitivePrices'] as $cp) {
                        // Id 1 is the Buy Box for new items; 2 is the one for used items.
                        $condition = isset($cp['condition']) ? Tools::strtolower($cp['condition']) : 'new';
                        if ((isset($cp['CompetitivePriceId']) ? (string) $cp['CompetitivePriceId'] : '') !== '1' || $condition !== 'new') {
                            continue;
                        }
                        $listing = isset($cp['Price']['ListingPrice']['Amount']) ? (float) $cp['Price']['ListingPrice']['Amount'] : 0;
                        $shipping = isset($cp['Price']['Shipping']['Amount']) ? (float) $cp['Price']['Shipping']['Amount'] : 0;
                        $landed = isset($cp['Price']['LandedPrice']['Amount']) ? (float) $cp['Price']['LandedPrice']['Amount'] : 0;
                        $offer['buybox_price'] = $listing;
                        $offer['buybox_shipping'] = $shipping;
                        $offer['buybox_landed'] = $landed > 0 ? $landed : $listing + $shipping;
                        $offer['is_buybox_winner'] = !empty($cp['belongsToRequester']) ? 1 : 0;
                    }
                }
                if (isset($competitive['NumberOfOfferListings'])) {
                    foreach ($competitive['NumberOfOfferListings'] as $nol) {
                        if (isset($nol['Count']) && (!isset($nol['condition']) || Tools::strtolower($nol['condition']) === 'new')) {
                            $offer['number_of_offers'] += (int) $nol['Count'];
                        }
                    }
                }
                if (isset($lowest[$asin])) {
                    $offer['lowest_price'] = $lowest[$asin]['price'];
                    $offer['lowest_shipping'] = $lowest[$asin]['shipping'];
                    $offer['lowest_landed'] = $lowest[$asin]['price'] + $lowest[$asin]['shipping'];
                }

                if ($offer['is_buybox_winner']) {
                    ++$summary['buybox_wins'];
                } else {
                    ++$summary['buybox_losses'];
                }

                $this->upsertCompetitivePrice($offer, $now);
                ++$summary['updated'];
            }
        }

        return $summary;
    }

    /**
     * Our own offer for each SKU: item price and shipping, as buyers see it.
     *
     * @param string[] $skus at most 20
     *
     * @return array sku => array('price' => float, 'shipping' => float)
     */
    private function fetchOwnOffers(array $skus)
    {
        $out = [];
        // A comma would split the SKU in the list Amazon expects.
        $skus = array_values(array_filter($skus, function ($sku) {
            return strpos($sku, ',') === false;
        }));
        if (!$skus) {
            return $out;
        }
        $resp = $this->pricingRequest('GET', '/products/pricing/v0/price', [
            'MarketplaceId' => $this->marketplaceId,
            'ItemType' => 'Sku',
            'Skus' => implode(',', $skus),
        ]);
        if ($resp === false || $resp['status'] >= 400 || !is_array($resp['body']) || empty($resp['body']['payload'])) {
            return $out;
        }
        foreach ($resp['body']['payload'] as $item) {
            $sku = isset($item['SellerSKU']) ? (string) $item['SellerSKU'] : '';
            if ($sku === '' || empty($item['Product']['Offers'])) {
                continue;
            }
            foreach ($item['Product']['Offers'] as $o) {
                if (!isset($o['BuyingPrice']['ListingPrice']['Amount'])) {
                    continue;
                }
                $out[$sku] = [
                    'price' => (float) $o['BuyingPrice']['ListingPrice']['Amount'],
                    'shipping' => isset($o['BuyingPrice']['Shipping']['Amount']) ? (float) $o['BuyingPrice']['Shipping']['Amount'] : 0.0,
                ];
                break;
            }
        }

        return $out;
    }

    /**
     * The cheapest new offer from another seller for each ASIN.
     *
     * @param string[] $asins at most 20
     *
     * @return array asin => array('price' => float, 'shipping' => float)
     */
    private function fetchLowestOffers(array $asins)
    {
        $requests = [];
        foreach ($asins as $asin) {
            $requests[] = [
                'uri' => '/products/pricing/v0/items/' . rawurlencode($asin) . '/offers',
                'method' => 'GET',
                'MarketplaceId' => $this->marketplaceId,
                'ItemCondition' => 'New',
                'CustomerType' => 'Consumer',
            ];
        }
        $resp = $this->pricingRequest('POST', '/batches/products/pricing/v0/itemOffers', [], ['requests' => $requests]);
        $out = [];
        if ($resp === false || $resp['status'] >= 400 || !is_array($resp['body']) || empty($resp['body']['responses'])) {
            return $out;
        }
        foreach ($resp['body']['responses'] as $r) {
            $payload = isset($r['body']['payload']) ? $r['body']['payload'] : [];
            $asin = isset($payload['ASIN']) ? $payload['ASIN'] : '';
            if ($asin === '' || empty($payload['Offers'])) {
                continue;
            }
            foreach ($payload['Offers'] as $o) {
                if (!empty($o['MyOffer']) || !isset($o['ListingPrice']['Amount'])) {
                    continue;
                }
                $price = (float) $o['ListingPrice']['Amount'];
                $shipping = isset($o['Shipping']['Amount']) ? (float) $o['Shipping']['Amount'] : 0.0;
                if (!isset($out[$asin]) || $price + $shipping < $out[$asin]['price'] + $out[$asin]['shipping']) {
                    $out[$asin] = ['price' => $price, 'shipping' => $shipping];
                }
            }
        }

        return $out;
    }

    /**
     * Whether an active rule for this shop compares with the lowest offer,
     * which costs one slow extra request per 20 products.
     */
    private function rulesNeedLowest($idShop)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `active` = 1
               AND `rule_type` IN (\'' . pSQL(self::$RULE_MATCH_LOWEST) . '\', \'' . pSQL(self::$RULE_BEAT_LOWEST) . '\')
               AND (`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\' OR `marketplace_id` = \'\')
               AND ' . AmzproShop::sqlShared('', $idShop)
        );
    }

    /**
     * A pricing API call, waited out and retried when Amazon throttles it:
     * these calls allow one request every 2 to 10 seconds.
     */
    private function pricingRequest($method, $path, array $query, $body = null)
    {
        $waits = [2, 5, 10, 10];
        foreach ($waits as $wait) {
            $resp = $this->client->request($method, $path, $query, $body);
            if ($resp === false || (int) $resp['status'] !== 429) {
                return $resp;
            }
            sleep($wait);
        }

        return $this->client->request($method, $path, $query, $body);
    }

    /**
     * Apply pricing rules to generate suggested prices.
     *
     * Reads active rules, matches them to products (by category or all),
     * calculates suggested prices respecting min/max safeguards.
     *
     * @return array Summary
     */
    public function applyPricingRules()
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'rules_applied' => 0,
            'products_evaluated' => 0,
            'prices_suggested' => 0,
            'prices_pushed' => 0,
            'prices_capped' => 0,
        ];

        $idShop = (int) AmzproShop::actingId();

        // Get active rules: the shared ones, then the shop's own (which run
        // last, so they have the final word on a product both cover).
        $rules = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `active` = 1
               AND (`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\' OR `marketplace_id` = \'\')
               AND ' . AmzproShop::sqlShared('', $idShop) . '
             ORDER BY `id_shop` ASC, `id_amazonmarketplacepro_pricing_rule` ASC'
        );

        if (!is_array($rules) || empty($rules)) {
            $this->notices[] = AmazonI18n::get()->l('No active pricing rules found.', 'amazonrepricingengine');

            return $summary;
        }

        $now = date('Y-m-d H:i:s');

        // Only what the rules suggest now is pushed, not an older suggestion.
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` SET `suggested_price` = 0
             WHERE `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\' AND `id_shop` = ' . $idShop
        );

        foreach ($rules as $rule) {
            ++$summary['rules_applied'];

            // Get the shop's products matching this rule
            $where = 'cp.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\' AND cp.`id_shop` = ' . $idShop;
            if ((int) $rule['id_category'] > 0) {
                $where .= ' AND cp.`seller_sku` IN (
                    SELECT `seller_sku` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
                    WHERE `ps_id_category_default` = ' . (int) $rule['id_category'] . '
                      AND `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'\')';
            }

            $prices = Db::getInstance()->executeS(
                'SELECT cp.`seller_sku`, cp.`asin`, cp.`buybox_price`, cp.`buybox_landed`,
                        cp.`lowest_price`, cp.`lowest_landed`, cp.`our_price`, cp.`our_shipping`,
                        cp.`is_buybox_winner`, p.`ps_price`
                 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
                 LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
                     ON (p.`id_shop` = cp.`id_shop` AND p.`seller_sku` = cp.`seller_sku` AND p.`marketplace_id` = \'\')
                 WHERE ' . $where . '
                 ORDER BY cp.`seller_sku` ASC'
            );

            if (!is_array($prices)) {
                continue;
            }

            foreach ($prices as $cp) {
                ++$summary['products_evaluated'];
                $suggested = $this->calculateSuggestedPrice($cp, $rule);
                if ($suggested === false) {
                    continue;
                }

                // Apply min/max safeguards
                $minPrice = (float) $rule['min_price'];
                $maxPrice = (float) $rule['max_price'];

                if ($minPrice > 0 && $suggested < $minPrice) {
                    $suggested = $minPrice;
                    ++$summary['prices_capped'];
                }
                if ($maxPrice > 0 && $suggested > $maxPrice) {
                    $suggested = $maxPrice;
                    ++$summary['prices_capped'];
                }

                // Update suggested price
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` SET
                        `suggested_price` = ' . (float) $suggested . ',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `seller_sku` = \'' . pSQL($cp['seller_sku']) . '\'
                       AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                       AND `id_shop` = ' . $idShop
                );
                ++$summary['prices_suggested'];
            }
        }

        return $summary;
    }

    /**
     * Push suggested prices to Amazon for products where suggested != current.
     *
     * @param int $limit Max products to push per run
     *
     * @return array Summary
     */
    public function pushSuggestedPrices($limit = 25)
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'candidates' => 0,
            'pushed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $idShop = (int) AmzproShop::actingId();
        // The listings belong to the seller account this shop is connected to.
        $sellerId = AmzproShop::isMultistore()
            ? trim((string) AmzproShop::get('AMZPRO_SELLER_ID', $idShop))
            : $this->sellerId;

        if ($sellerId === '') {
            $this->notices[] = AmazonI18n::get()->l('Cannot push prices: your seller ID is missing. Click "Connect to Amazon" in Settings > Connection to fill it in.', 'amazonrepricingengine');

            return $summary;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT cp.`seller_sku`, cp.`suggested_price`, cp.`our_price`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
             WHERE cp.`suggested_price` > 0
               AND cp.`suggested_price` <> cp.`our_price`
               AND cp.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
               AND cp.`id_shop` = ' . $idShop . '
             ORDER BY cp.`seller_sku` ASC
             LIMIT ' . (int) $limit
        );

        if (!is_array($rows)) {
            $rows = [];
        }

        $summary['candidates'] = count($rows);
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $r) {
            $sku = $r['seller_sku'];
            $newPrice = (float) $r['suggested_price'];

            // Change only the price of the existing listing, under the listing's
            // own product type: a full PUT as "PRODUCT" is refused by Amazon
            // for listings of any other type.
            $path = '/listings/2021-08-01/items/' . rawurlencode($sellerId) . '/' . rawurlencode($sku);
            $productType = $this->productTypeFor($path, $sku, $idShop);
            if ($productType === '') {
                ++$summary['failed'];
                $this->notices[] = sprintf(AmazonI18n::get()->l('%1$s: price not sent, the listing was not found on Amazon.', 'amazonrepricingengine'), $sku);
                continue;
            }
            $resp = $this->client->request(
                'PATCH',
                $path,
                ['marketplaceIds' => $this->marketplaceId],
                [
                    'productType' => $productType,
                    'patches' => [[
                        'op' => 'replace',
                        'path' => '/attributes/purchasable_offer',
                        'value' => [[
                            'currency' => AmazonSpApiClient::currencyForMarketplace($this->marketplaceId),
                            'marketplace_id' => $this->marketplaceId,
                            'audience' => 'ALL',
                            'our_price' => [[
                                'schedule' => [['value_with_tax' => $newPrice]],
                            ]],
                        ]],
                    ]],
                ]
            );

            // Amazon answers 200 with status INVALID when it refuses the change.
            $status = ($resp !== false && is_array($resp['body']) && isset($resp['body']['status'])) ? $resp['body']['status'] : '';
            if ($resp !== false && $resp['status'] < 400 && $status === 'ACCEPTED') {
                // Update our_price and last_repriced
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` SET
                        `our_price` = ' . $newPrice . ',
                        `last_repriced` = \'' . pSQL($now) . '\',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `seller_sku` = \'' . pSQL($sku) . '\'
                       AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                       AND `id_shop` = ' . $idShop
                );

                ++$summary['pushed'];
            } else {
                ++$summary['failed'];
                $this->notices[] = $sku . ': ' . $this->refusalReason($resp);
            }
        }

        return $summary;
    }

    /**
     * The listing's product type: from the last product scan, else asked
     * from Amazon.
     *
     * @return string '' when Amazon has no such listing
     */
    private function productTypeFor($path, $sku, $idShop)
    {
        $type = (string) Db::getInstance()->getValue(
            'SELECT `amazon_product_type` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             WHERE `seller_sku` = \'' . pSQL($sku) . '\' AND `id_shop` = ' . (int) $idShop . '
               AND `amazon_product_type` <> \'\'
             ORDER BY `marketplace_id` ASC'
        );
        if ($type !== '') {
            return $type;
        }
        $resp = $this->client->request('GET', $path, ['marketplaceIds' => $this->marketplaceId, 'includedData' => 'summaries']);
        if ($resp === false || $resp['status'] >= 400 || !is_array($resp['body']) || empty($resp['body']['summaries'])) {
            return '';
        }
        foreach ($resp['body']['summaries'] as $s) {
            if (!empty($s['productType'])) {
                return (string) $s['productType'];
            }
        }

        return '';
    }

    /**
     * Amazon's own words for a refused price change.
     */
    private function refusalReason($resp)
    {
        if ($resp === false) {
            return (string) $this->client->getLastError();
        }
        $messages = [];
        if (is_array($resp['body']) && !empty($resp['body']['issues'])) {
            foreach ($resp['body']['issues'] as $issue) {
                if (isset($issue['severity']) && $issue['severity'] !== 'ERROR') {
                    continue;
                }
                $messages[] = isset($issue['message']) ? $issue['message'] : json_encode($issue);
            }
        } elseif (is_array($resp['body']) && !empty($resp['body']['errors'][0]['message'])) {
            $messages[] = $resp['body']['errors'][0]['message'];
        }

        return $messages ? implode(' ', $messages) : 'HTTP ' . $resp['status'] . (isset($resp['body']['status']) ? ' ' . $resp['body']['status'] : '');
    }

    /**
     * Calculate suggested price based on a rule and competitive data.
     *
     * @param array $cp Competitive price row
     * @param array $rule Pricing rule row
     *
     * @return float|false Suggested price or false if no change needed
     */
    private function calculateSuggestedPrice($cp, $rule)
    {
        $ruleType = $rule['rule_type'];
        $adjustment = (float) $rule['price_adjustment'];
        $adjustmentType = $rule['adjustment_type'];

        $buyboxLanded = (float) $cp['buybox_landed'];
        $lowestLanded = (float) $cp['lowest_landed'];
        $ourPrice = (float) $cp['our_price'];
        $ourShipping = (float) $cp['our_shipping'];
        if ($ourPrice <= 0) {
            // No offer of ours on Amazon to reprice
            return false;
        }
        // Holding the Buy Box already: matching or beating it would only
        // undercut ourselves.
        if ((int) $cp['is_buybox_winner']
            && ($ruleType === self::$RULE_MATCH_BUYBOX || $ruleType === self::$RULE_BEAT_BUYBOX)) {
            return false;
        }

        // Competitor prices are landed (item + shipping) and so is the
        // target; our shipping comes on top of the item price we send.
        $targetPrice = 0;

        switch ($ruleType) {
            case self::$RULE_MATCH_LOWEST:
                if ($lowestLanded <= 0) {
                    return false;
                }
                $targetPrice = $lowestLanded;
                break;

            case self::$RULE_BEAT_LOWEST:
                if ($lowestLanded <= 0) {
                    return false;
                }
                $targetPrice = $this->applyAdjustment($lowestLanded, $adjustment, $adjustmentType, 'below');
                break;

            case self::$RULE_MATCH_BUYBOX:
                if ($buyboxLanded <= 0) {
                    return false;
                }
                $targetPrice = $buyboxLanded;
                break;

            case self::$RULE_BEAT_BUYBOX:
                if ($buyboxLanded <= 0) {
                    return false;
                }
                $targetPrice = $this->applyAdjustment($buyboxLanded, $adjustment, $adjustmentType, 'below');
                break;

            case self::$RULE_FIXED_MARGIN:
                // A margin above the shop's price as prepared for Amazon
                if ((float) $cp['ps_price'] <= 0) {
                    return false;
                }
                $targetPrice = $this->applyAdjustment((float) $cp['ps_price'], $adjustment, $adjustmentType, 'above') + $ourShipping;
                break;

            default:
                return false;
        }

        $targetPrice = round($targetPrice - $ourShipping, 2);
        if ($targetPrice <= 0) {
            return false;
        }

        // Don't suggest if already at target
        if (abs($targetPrice - $ourPrice) < 0.01) {
            return false;
        }

        return $targetPrice;
    }

    /**
     * Apply a price adjustment.
     */
    private function applyAdjustment($basePrice, $amount, $type, $direction)
    {
        if ($type === 'percentage') {
            $delta = $basePrice * ($amount / 100);
        } else {
            $delta = $amount;
        }

        if ($direction === 'below') {
            return $basePrice - $delta;
        }

        return $basePrice + $delta;
    }

    /**
     * Upsert competitive price data.
     *
     * @param array $offer sku, asin, buybox_price, buybox_shipping,
     *                     buybox_landed, buybox_seller, is_buybox_winner,
     *                     lowest_price, lowest_shipping, lowest_landed,
     *                     number_of_offers, our_price, our_shipping
     * @param string $now
     */
    private function upsertCompetitivePrice(array $offer, $now)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price`
            (`id_shop`, `seller_sku`, `asin`, `buybox_price`, `buybox_shipping`, `buybox_landed`,
             `buybox_seller`, `is_buybox_winner`, `lowest_price`, `lowest_shipping`,
             `lowest_landed`, `number_of_offers`, `our_price`, `our_shipping`, `marketplace_id`,
             `date_add`, `date_upd`)
            VALUES (
                ' . (int) AmzproShop::actingId() . ',
                \'' . pSQL($offer['sku']) . '\',
                \'' . pSQL($offer['asin']) . '\',
                ' . (float) $offer['buybox_price'] . ',
                ' . (float) $offer['buybox_shipping'] . ',
                ' . (float) $offer['buybox_landed'] . ',
                \'' . pSQL($offer['buybox_seller']) . '\',
                ' . (int) $offer['is_buybox_winner'] . ',
                ' . (float) $offer['lowest_price'] . ',
                ' . (float) $offer['lowest_shipping'] . ',
                ' . (float) $offer['lowest_landed'] . ',
                ' . (int) $offer['number_of_offers'] . ',
                ' . (float) $offer['our_price'] . ',
                ' . (float) $offer['our_shipping'] . ',
                \'' . pSQL($this->marketplaceId) . '\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )
            ON DUPLICATE KEY UPDATE
                `buybox_price` = VALUES(`buybox_price`),
                `buybox_shipping` = VALUES(`buybox_shipping`),
                `buybox_landed` = VALUES(`buybox_landed`),
                `buybox_seller` = VALUES(`buybox_seller`),
                `is_buybox_winner` = VALUES(`is_buybox_winner`),
                `lowest_price` = VALUES(`lowest_price`),
                `lowest_shipping` = VALUES(`lowest_shipping`),
                `lowest_landed` = VALUES(`lowest_landed`),
                `number_of_offers` = VALUES(`number_of_offers`),
                `our_price` = VALUES(`our_price`),
                `our_shipping` = VALUES(`our_shipping`),
                `date_upd` = VALUES(`date_upd`)';
        Db::getInstance()->execute($sql);
    }

    /* ─────────────────── Pricing Rules CRUD ─────────────────── */

    /**
     * The rules for all shops plus the current shop's own (only the shared
     * ones in "All shops"). Each row carries its id_shop.
     */
    public function listPricingRules()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
                WHERE ' . AmzproShop::sqlShared() . '
                ORDER BY `name` ASC';
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Add a rule for the scope being edited (a shop's own, or shared in "All
     * shops"), or change one. A shop may change only its own rules. Returns
     * false with getLastError() set when refused.
     */
    public function savePricingRule($data)
    {
        $this->lastError = null;
        $now = date('Y-m-d H:i:s');
        $id = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id > 0) {
            if (!$this->checkRuleChange($id)) {
                return false;
            }

            return Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule` SET
                    `name` = \'' . pSQL($data['name']) . '\',
                    `rule_type` = \'' . pSQL($data['rule_type']) . '\',
                    `price_adjustment` = ' . (float) $data['price_adjustment'] . ',
                    `adjustment_type` = \'' . pSQL($data['adjustment_type']) . '\',
                    `min_price` = ' . (float) $data['min_price'] . ',
                    `max_price` = ' . (float) $data['max_price'] . ',
                    `target_buybox` = ' . (int) $data['target_buybox'] . ',
                    `id_category` = ' . (int) $data['id_category'] . ',
                    `marketplace_id` = \'' . pSQL($data['marketplace_id']) . '\',
                    `active` = ' . (int) $data['active'] . ',
                    `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_amazonmarketplacepro_pricing_rule` = ' . $id
            );
        }

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             (`id_shop`, `name`, `rule_type`, `price_adjustment`, `adjustment_type`,
              `min_price`, `max_price`, `target_buybox`, `id_category`,
              `marketplace_id`, `active`, `date_add`, `date_upd`)
             VALUES (
                ' . (int) AmzproShop::sharedWriteId() . ',
                \'' . pSQL($data['name']) . '\',
                \'' . pSQL($data['rule_type']) . '\',
                ' . (float) $data['price_adjustment'] . ',
                \'' . pSQL($data['adjustment_type']) . '\',
                ' . (float) $data['min_price'] . ',
                ' . (float) $data['max_price'] . ',
                ' . (int) $data['target_buybox'] . ',
                ' . (int) $data['id_category'] . ',
                \'' . pSQL($data['marketplace_id']) . '\',
                ' . (int) $data['active'] . ',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
             )'
        );
    }

    /** Delete a rule, with the same rule as savePricingRule(). */
    public function deletePricingRule($id)
    {
        $this->lastError = null;
        if (!$this->checkRuleChange((int) $id)) {
            return false;
        }

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `id_amazonmarketplacepro_pricing_rule` = ' . (int) $id
        );
    }

    /**
     * True when the current context may change this rule: a shop its own
     * rules, "All shops" the shared ones (with multistore off, every rule).
     * Sets the error otherwise.
     */
    private function checkRuleChange($id)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `id_amazonmarketplacepro_pricing_rule` = ' . (int) $id . '
               AND ' . AmzproShop::sqlShared()
        );
        if (!$row) {
            $this->lastError = AmazonI18n::get()->l('This pricing rule was not found. Reload the page and try again.', 'amazonrepricingengine');

            return false;
        }
        if (AmzproShop::isMultistore() && (int) $row['id_shop'] !== (int) AmzproShop::sharedWriteId()) {
            $this->lastError = AmazonI18n::get()->l('This pricing rule is shared by all shops. Select "All shops" at the top of the page to change it.', 'amazonrepricingengine');

            return false;
        }

        return true;
    }

    /**
     * List competitive pricing data for admin display: the current shop's,
     * or every shop's in "All shops" (with shop_name).
     */
    public function listCompetitivePrices($limit = 100)
    {
        $sql = 'SELECT cp.*, p.`ps_name`, s.`name` AS shop_name
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
                LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
                    ON (p.`seller_sku` = cp.`seller_sku` AND p.`id_shop` = cp.`id_shop` AND p.`marketplace_id` = \'\')
                LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON (s.`id_shop` = cp.`id_shop`)
                WHERE cp.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                  AND ' . AmzproShop::sqlWhere('cp') . '
                ORDER BY cp.`is_buybox_winner` ASC, cp.`seller_sku` ASC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }
}
