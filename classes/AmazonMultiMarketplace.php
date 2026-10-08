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
 * Amazon Multi-Marketplace Manager.
 *
 * Handles multiple simultaneous Amazon marketplace configurations.
 * Each marketplace can have its own credentials (or share the primary set),
 * carrier, order state, and sync toggles.
 *
 * Enables running syncs across all active marketplaces in a single cron run
 * or triggering per-marketplace operations from the admin UI.
 *
 * Multistore: marketplace configurations belong to one shop each (a shop
 * connects its own seller account). Lists in the back office's "All shops"
 * view show every shop's rows; everything else works on one shop.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonMultiMarketplace
{
    /** Amazon marketplace directory (id => label). */
    private static $marketplaceNames = [
        'ATVPDKIKX0DER' => 'Amazon.com (US)',
        'A2EUQ1WTGCTBG2' => 'Amazon.ca (Canada)',
        'A1AM78C64UM0Y8' => 'Amazon.com.mx (Mexico)',
        'A2Q3Y263D00KMC' => 'Amazon.com.br (Brazil)',
        'A1F83G8C2ARO7P' => 'Amazon.co.uk (UK)',
        'A1PA6795UKMFR9' => 'Amazon.de (Germany)',
        'A13V1IB3VIYZZH' => 'Amazon.fr (France)',
        'APJ6JRA9NG5V4' => 'Amazon.it (Italy)',
        'A1RKKUPIHCS9HS' => 'Amazon.es (Spain)',
        'A1805IZSGTT6HS' => 'Amazon.nl (Netherlands)',
        'A1C3SOZRARQ6R3' => 'Amazon.pl (Poland)',
        'A2NODRKZP88ZB9' => 'Amazon.se (Sweden)',
        'AMEN7PMS3EDWL' => 'Amazon.com.be (Belgium)',
        'A28R8C7NBKEWEA' => 'Amazon.ie (Ireland)',
        'ARBP9OOSHTCHU' => 'Amazon.eg (Egypt)',
        'AE08WJ6YKNBMC' => 'Amazon.co.za (South Africa)',
        'A33AVAJ2PDY3EV' => 'Amazon.com.tr (Turkey)',
        'A21TJRUUN4KGV' => 'Amazon.in (India)',
        'A2VIGQ35RCS4UG' => 'Amazon.ae (UAE)',
        'A17E79C6D8DWNP' => 'Amazon.sa (Saudi Arabia)',
        'A19VAU5U5O7RUS' => 'Amazon.sg (Singapore)',
        'A39IBJ37TRP1C6' => 'Amazon.com.au (Australia)',
        'A1VC38T7YXB528' => 'Amazon.co.jp (Japan)',
    ];

    /** Marketplace to SP-API region mapping. */
    private static $regionMap = [
        'ATVPDKIKX0DER' => 'NA', 'A2EUQ1WTGCTBG2' => 'NA', 'A1AM78C64UM0Y8' => 'NA',
        'A2Q3Y263D00KMC' => 'NA',
        'A1F83G8C2ARO7P' => 'EU', 'A1PA6795UKMFR9' => 'EU', 'A13V1IB3VIYZZH' => 'EU',
        'APJ6JRA9NG5V4' => 'EU', 'A1RKKUPIHCS9HS' => 'EU', 'A1805IZSGTT6HS' => 'EU',
        'A1C3SOZRARQ6R3' => 'EU', 'A2NODRKZP88ZB9' => 'EU', 'AMEN7PMS3EDWL' => 'EU',
        'A33AVAJ2PDY3EV' => 'EU', 'A21TJRUUN4KGV' => 'EU', 'A2VIGQ35RCS4UG' => 'EU',
        'A17E79C6D8DWNP' => 'EU', 'A28R8C7NBKEWEA' => 'EU', 'ARBP9OOSHTCHU' => 'EU',
        'AE08WJ6YKNBMC' => 'EU', 'A19VAU5U5O7RUS' => 'FE',
        'A39IBJ37TRP1C6' => 'FE', 'A1VC38T7YXB528' => 'FE',
    ];

    private $lastError;
    private $notices = [];

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Get all configured marketplace configs (each row carries its id_shop).
     *
     * @param bool $activeOnly Only return active marketplaces
     * @param int|null $idShop Null = the current shop, or every shop in the
     *                         back office's "All shops" view
     *
     * @return array
     */
    public function getMarketplaceConfigs($activeOnly = false, $idShop = null)
    {
        $where = 'WHERE ' . AmzproShop::sqlWhere('', $idShop) . ($activeOnly ? ' AND `active` = 1' : '');
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
                ' . $where . '
                ORDER BY `id_shop` ASC, `marketplace_name` ASC';
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Get a single marketplace config of one shop.
     *
     * @param string $marketplaceId
     * @param int|null $idShop Null = the shop the request acts for (the
     *                         default shop in "All shops")
     *
     * @return array|false
     */
    public function getMarketplaceConfig($marketplaceId, $idShop = null)
    {
        $idShop = $idShop ? (int) $idShop : AmzproShop::actingId();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
             WHERE ' . AmzproShop::sqlWhere('', $idShop) . '
               AND `marketplace_id` = \'' . pSQL($marketplaceId) . '\''
        );
    }

    /**
     * Save or update a marketplace configuration of the current shop.
     *
     * Refused in the back office's "All shops" view: a configuration belongs
     * to one shop's seller account.
     *
     * @param array $data Marketplace config fields
     *
     * @return bool
     */
    public function saveMarketplaceConfig($data)
    {
        $shopError = AmzproShop::requireShop();
        if ($shopError !== null) {
            $this->lastError = $shopError;

            return false;
        }
        $idShop = AmzproShop::actingId();

        $marketplaceId = isset($data['marketplace_id']) ? $data['marketplace_id'] : '';
        if ($marketplaceId === '') {
            $this->lastError = AmazonI18n::get()->l('Marketplace ID is required.', 'amazonmultimarketplace');

            return false;
        }

        $active = (int) (isset($data['active']) ? $data['active'] : 1);
        if ($active) {
            $sellerId = isset($data['seller_id']) ? trim((string) $data['seller_id']) : '';
            if ($sellerId === '') {
                $sellerId = (string) AmzproShop::get('AMZPRO_SELLER_ID', $idShop);
            }
            $otherShop = $this->shopUsingSellerOnMarketplace($sellerId, $marketplaceId, $idShop);
            if ($otherShop) {
                $this->lastError = sprintf(
                    AmazonI18n::get()->l('This Amazon seller account already sells on this marketplace from the shop "%s". Each seller account and marketplace can be used by one shop only.', 'amazonmultimarketplace'),
                    AmzproShop::name($otherShop)
                );

                return false;
            }
        }

        $name = isset(self::$marketplaceNames[$marketplaceId])
            ? self::$marketplaceNames[$marketplaceId]
            : $marketplaceId;

        $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
            (`id_shop`, `marketplace_id`, `marketplace_name`, `client_id`, `client_secret`,
             `refresh_token`, `seller_id`, `active`, `default_carrier`,
             `default_order_state`, `sync_orders`, `sync_products`, `sync_stock`,
             `date_add`, `date_upd`)
            VALUES (
                ' . (int) $idShop . ',
                \'' . pSQL($marketplaceId) . '\',
                \'' . pSQL($name) . '\',
                \'' . pSQL(isset($data['client_id']) ? $data['client_id'] : '') . '\',
                \'' . pSQL(isset($data['client_secret']) ? $data['client_secret'] : '') . '\',
                \'' . pSQL(isset($data['refresh_token']) ? $data['refresh_token'] : '') . '\',
                \'' . pSQL(isset($data['seller_id']) ? $data['seller_id'] : '') . '\',
                ' . $active . ',
                ' . (int) (isset($data['default_carrier']) ? $data['default_carrier'] : 0) . ',
                ' . (int) (isset($data['default_order_state']) ? $data['default_order_state'] : 0) . ',
                ' . (int) (isset($data['sync_orders']) ? $data['sync_orders'] : 1) . ',
                ' . (int) (isset($data['sync_products']) ? $data['sync_products'] : 1) . ',
                ' . (int) (isset($data['sync_stock']) ? $data['sync_stock'] : 1) . ',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )
            ON DUPLICATE KEY UPDATE
                `marketplace_name` = VALUES(`marketplace_name`),
                `client_id` = VALUES(`client_id`),
                `client_secret` = VALUES(`client_secret`),
                `refresh_token` = VALUES(`refresh_token`),
                `seller_id` = VALUES(`seller_id`),
                `active` = VALUES(`active`),
                `default_carrier` = VALUES(`default_carrier`),
                `default_order_state` = VALUES(`default_order_state`),
                `sync_orders` = VALUES(`sync_orders`),
                `sync_products` = VALUES(`sync_products`),
                `sync_stock` = VALUES(`sync_stock`),
                `date_upd` = VALUES(`date_upd`)';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Delete a marketplace configuration of the current shop.
     *
     * Refused in the back office's "All shops" view (see getLastError()), so
     * a click there cannot remove another shop's configuration.
     *
     * @param string $marketplaceId
     *
     * @return bool
     */
    public function deleteMarketplaceConfig($marketplaceId)
    {
        $shopError = AmzproShop::requireShop();
        if ($shopError !== null) {
            $this->lastError = $shopError;

            return false;
        }

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
             WHERE ' . AmzproShop::sqlWhere('', AmzproShop::actingId()) . '
               AND `marketplace_id` = \'' . pSQL($marketplaceId) . '\''
        );
    }

    /**
     * The shop, other than $exceptShop, that already uses this seller account
     * on this marketplace: as its main connection, or through an active
     * marketplace configuration. 0 when none.
     *
     * @param string $sellerId
     * @param string $marketplaceId
     * @param int $exceptShop
     *
     * @return int
     */
    public function shopUsingSellerOnMarketplace($sellerId, $marketplaceId, $exceptShop)
    {
        $sellerId = (string) $sellerId;
        if ($sellerId === '' || !AmzproShop::isMultistore()) {
            return 0;
        }

        $idShop = AmzproShop::shopUsingSeller($sellerId, $marketplaceId, $exceptShop);
        if ($idShop) {
            return $idShop;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_shop`, `seller_id` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
             WHERE `id_shop` <> ' . (int) $exceptShop . '
               AND `active` = 1
               AND `marketplace_id` = \'' . pSQL($marketplaceId) . '\'
             ORDER BY `id_shop` ASC'
        );
        foreach ((is_array($rows) ? $rows : []) as $row) {
            // A configuration without its own seller id uses its shop's.
            $rowSeller = (string) $row['seller_id'] !== ''
                ? (string) $row['seller_id']
                : (string) AmzproShop::get('AMZPRO_SELLER_ID', (int) $row['id_shop']);
            if ($rowSeller === $sellerId) {
                return (int) $row['id_shop'];
            }
        }

        return 0;
    }

    /**
     * Build an SP-API client for a specific marketplace of one shop.
     *
     * Uses per-marketplace credentials if configured, otherwise falls back
     * to the shop's primary credentials.
     *
     * @param string $marketplaceId
     * @param int|null $idShop Null = the shop the request acts for
     *
     * @return AmazonSpApiClient
     */
    public function buildClientForMarketplace($marketplaceId, $idShop = null)
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';

        $idShop = $idShop ? (int) $idShop : AmzproShop::actingId();
        $config = $this->getMarketplaceConfig($marketplaceId, $idShop);

        $usingPrimary = !($config && !empty($config['client_id']));
        if (!$usingPrimary) {
            // Use per-marketplace credentials
            $clientId = $config['client_id'];
            $clientSecret = $config['client_secret'];
            $refreshToken = $config['refresh_token'];
        } else {
            // Fall back to the shop's primary credentials
            $clientId = AmzproShop::get('AMZPRO_CLIENT_ID', $idShop);
            $clientSecret = AmzproShop::get('AMZPRO_CLIENT_SECRET', $idShop);
            $refreshToken = AmazonSpApiClient::storedRefreshToken(null, $idShop);
        }

        $endpoint = $this->resolveEndpoint($marketplaceId);

        $client = new AmazonSpApiClient($clientId, $clientSecret, $refreshToken, $endpoint, null, $idShop);

        // Primary credentials in "Connect with Amazon" mode use the token relay.
        if ($usingPrimary && AmazonSpApiClient::authMode($idShop) !== 'manual') {
            $client->setTokenRelay(AmazonSpApiClient::relayUrl());
        }

        return $client;
    }

    /**
     * Resolve the SP-API endpoint for a marketplace.
     *
     * @param string $marketplaceId
     *
     * @return string
     */
    public function resolveEndpoint($marketplaceId)
    {
        $env = AmazonSpApiClient::environment();

        if ($env !== 'production') {
            return AmazonSpApiClient::$ENDPOINT_NA_SANDBOX;
        }

        $region = isset(self::$regionMap[$marketplaceId]) ? self::$regionMap[$marketplaceId] : 'EU';

        switch ($region) {
            case 'NA':
                return AmazonSpApiClient::$ENDPOINT_NA;
            case 'FE':
                return AmazonSpApiClient::$ENDPOINT_FE;
            default:
                return AmazonSpApiClient::$ENDPOINT_EU;
        }
    }

    /**
     * Get the seller ID for a marketplace of one shop.
     *
     * @param string $marketplaceId
     * @param int|null $idShop Null = the shop the request acts for
     *
     * @return string
     */
    public function getSellerIdForMarketplace($marketplaceId, $idShop = null)
    {
        $idShop = $idShop ? (int) $idShop : AmzproShop::actingId();
        $config = $this->getMarketplaceConfig($marketplaceId, $idShop);
        if ($config && !empty($config['seller_id'])) {
            return $config['seller_id'];
        }

        return (string) AmzproShop::get('AMZPRO_SELLER_ID', $idShop);
    }

    /**
     * Get carrier for a marketplace of one shop.
     *
     * @param string $marketplaceId
     * @param int|null $idShop Null = the shop the request acts for
     *
     * @return int
     */
    public function getCarrierForMarketplace($marketplaceId, $idShop = null)
    {
        $idShop = $idShop ? (int) $idShop : AmzproShop::actingId();
        $config = $this->getMarketplaceConfig($marketplaceId, $idShop);
        if ($config && (int) $config['default_carrier'] > 0) {
            return (int) $config['default_carrier'];
        }

        return (int) AmzproShop::get('AMZPRO_DEFAULT_CARRIER', $idShop);
    }

    /**
     * Get order state for a marketplace of one shop.
     *
     * @param string $marketplaceId
     * @param int|null $idShop Null = the shop the request acts for
     *
     * @return int
     */
    public function getOrderStateForMarketplace($marketplaceId, $idShop = null)
    {
        $idShop = $idShop ? (int) $idShop : AmzproShop::actingId();
        $config = $this->getMarketplaceConfig($marketplaceId, $idShop);
        if ($config && (int) $config['default_order_state'] > 0) {
            return (int) $config['default_order_state'];
        }
        $state = (int) AmzproShop::get('AMZPRO_DEFAULT_ORDER_STATE', $idShop);

        return $state ? $state : (int) Configuration::get('PS_OS_PAYMENT', null, AmzproShop::groupId($idShop), $idShop);
    }

    /**
     * Run a job over the marketplaces of one concrete shop.
     *
     * Cron and the scheduler always run for one shop, which is then simply
     * the current one. Should the back office start a job in "All shops"
     * anyway, it works for the default shop, as the module did before it knew
     * about shops, and inside that shop's context so the importers it calls
     * read and write that shop's rows as well.
     *
     * @param callable $job receives the shop id
     *
     * @return mixed what $job returns
     */
    private function forOneShop($job)
    {
        $idShop = AmzproShop::actingId();
        if (AmzproShop::isAllShops()) {
            return AmzproShop::runInShop($idShop, $job);
        }

        return call_user_func($job, $idShop);
    }

    /**
     * Run order import across the current shop's active marketplaces.
     *
     * @return array Per-marketplace results
     */
    public function importOrdersAllMarketplaces()
    {
        return $this->forOneShop(function ($idShop) {
            return $this->importOrdersForShop($idShop);
        });
    }

    private function importOrdersForShop($idShop)
    {
        $this->notices = [];
        $results = [];

        $configs = $this->getMarketplaceConfigs(true, $idShop);
        if (empty($configs)) {
            $this->notices[] = 'No active marketplace configurations found.';

            return $results;
        }

        require_once dirname(__FILE__) . '/AmazonOrderImporter.php';

        $env = AmazonSpApiClient::environment();

        foreach ($configs as $config) {
            $mpId = $config['marketplace_id'];
            $mpName = $config['marketplace_name'];

            if (!(int) $config['sync_orders']) {
                $this->notices[] = $mpName . ': order sync disabled';
                continue;
            }

            $client = $this->buildClientForMarketplace($mpId, $idShop);
            $importer = new AmazonOrderImporter($client, $mpId);

            $createdAfter = ($env === 'production')
                ? gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days'))
                : 'TEST_CASE_200';

            $summary = $importer->importNewOrders($createdAfter);

            $results[] = [
                'marketplace_id' => $mpId,
                'marketplace_name' => $mpName,
                'success' => ($summary !== false),
                'summary' => $summary,
                'error' => $importer->getLastError(),
            ];
        }

        return $results;
    }

    /**
     * Run product sync across the current shop's active marketplaces.
     *
     * @return array Per-marketplace results
     */
    public function syncProductsAllMarketplaces()
    {
        return $this->forOneShop(function ($idShop) {
            return $this->syncProductsForShop($idShop);
        });
    }

    private function syncProductsForShop($idShop)
    {
        $this->notices = [];
        $results = [];

        $configs = $this->getMarketplaceConfigs(true, $idShop);
        if (empty($configs)) {
            return $results;
        }

        require_once dirname(__FILE__) . '/AmazonProductSync.php';

        foreach ($configs as $config) {
            $mpId = $config['marketplace_id'];
            $mpName = $config['marketplace_name'];

            if (!(int) $config['sync_products']) {
                continue;
            }

            $client = $this->buildClientForMarketplace($mpId, $idShop);
            $sellerId = $this->getSellerIdForMarketplace($mpId, $idShop);
            $sync = new AmazonProductSync($client, $mpId, $sellerId);

            $env = AmazonSpApiClient::environment();
            $useMock = AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production';
            $sync->setMock($useMock);

            $psSummary = $sync->syncPrestashopSide();
            $notices = $sync->getNotices();
            $azSummary = $sync->syncAmazonSide();
            $sent = $this->sendPending($sync, $client, $mpId, $sellerId, $useMock);
            foreach (array_merge($notices, $sync->getNotices()) as $notice) {
                $this->notices[] = $mpName . ': ' . $notice;
            }

            $results[] = [
                'marketplace_id' => $mpId,
                'marketplace_name' => $mpName,
                'ps_summary' => $psSummary,
                'amazon_summary' => $azSummary,
                'sent' => $sent,
            ];
        }

        return $results;
    }

    /**
     * Send what the comparison marks as pending on one marketplace, the way
     * the main Send button does: a small batch SKU by SKU, a larger one as a
     * single feed that Amazon processes in the background.
     *
     * @return array method ('none', 'listings' or 'feed'), pending, sent (what
     *               actually left: pushed SKUs, or the feed's messages once
     *               Amazon took the feed), and the push summary or feed id
     */
    private function sendPending(AmazonProductSync $sync, $client, $mpId, $sellerId, $useMock)
    {
        $pending = $sync->countPending();
        if ($pending === 0) {
            return ['method' => 'none', 'pending' => 0, 'sent' => 0];
        }

        $feedAbove = class_exists('Amazonmarketplacepro') ? (int) Amazonmarketplacepro::$SEND_AS_FEED_ABOVE : 25;
        if ($pending <= $feedAbove) {
            $summary = $sync->pushToAmazon($feedAbove);

            return ['method' => 'listings', 'pending' => $pending, 'sent' => (int) $summary['pushed'], 'summary' => $summary];
        }

        require_once dirname(__FILE__) . '/AmazonFeedManager.php';
        $collected = $sync->collectFeedMessages(500);
        $feeds = new AmazonFeedManager($client, $mpId, $sellerId);
        $feeds->setMock($useMock);
        $feedId = empty($collected['messages']) ? false : $feeds->submitListingsFeed($collected['messages']);
        if ($feedId === false && $feeds->getLastError()) {
            $this->notices[] = $feeds->getLastError();
        }

        return [
            'method' => 'feed',
            'pending' => $pending,
            'sent' => ($feedId === false) ? 0 : count($collected['messages']),
            'feed_id' => $feedId,
            'messages' => count($collected['messages']),
        ];
    }

    /**
     * Run stock sync across the current shop's active marketplaces.
     *
     * @return array Per-marketplace results
     */
    public function syncStockAllMarketplaces()
    {
        return $this->forOneShop(function ($idShop) {
            return $this->syncStockForShop($idShop);
        });
    }

    private function syncStockForShop($idShop)
    {
        $this->notices = [];
        $results = [];

        $configs = $this->getMarketplaceConfigs(true, $idShop);
        if (empty($configs)) {
            return $results;
        }

        require_once dirname(__FILE__) . '/AmazonProductSync.php';

        foreach ($configs as $config) {
            $mpId = $config['marketplace_id'];
            $mpName = $config['marketplace_name'];

            if (!(int) $config['sync_stock']) {
                continue;
            }

            $client = $this->buildClientForMarketplace($mpId, $idShop);
            $sellerId = $this->getSellerIdForMarketplace($mpId, $idShop);
            $sync = new AmazonProductSync($client, $mpId, $sellerId);

            $env = AmazonSpApiClient::environment();
            $useMock = AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production';
            $sync->setMock($useMock);

            $sync->syncPrestashopSide();
            $pushResult = $sync->pushToAmazon(100);

            $results[] = [
                'marketplace_id' => $mpId,
                'marketplace_name' => $mpName,
                'summary' => $pushResult,
            ];
        }

        return $results;
    }

    /**
     * Get available marketplaces for dropdown selection.
     *
     * @return array
     */
    public static function getAvailableMarketplaces()
    {
        return self::$marketplaceNames;
    }

    /**
     * Get the region for a marketplace.
     *
     * @param string $marketplaceId
     *
     * @return string NA, EU, or FE
     */
    public static function getRegion($marketplaceId)
    {
        return isset(self::$regionMap[$marketplaceId]) ? self::$regionMap[$marketplaceId] : 'EU';
    }
}
