<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Amazon Multi-Marketplace Manager.
 *
 * Handles multiple simultaneous Amazon marketplace configurations.
 * Each marketplace can have its own credentials (or share the primary set),
 * carrier, order state, and sync toggles.
 *
 * Enables running syncs across all active marketplaces in a single cron run
 * or triggering per-marketplace operations from the admin UI.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonMultiMarketplace
{
    /** Amazon marketplace directory (id => label). */
    private static $marketplaceNames = array(
        'ATVPDKIKX0DER'  => 'Amazon.com (US)',
        'A2EUQ1WTGCTBG2' => 'Amazon.ca (Canada)',
        'A1AM78C64UM0Y8'  => 'Amazon.com.mx (Mexico)',
        'A2Q3Y263D00KMC'  => 'Amazon.com.br (Brazil)',
        'A1F83G8C2ARO7P'  => 'Amazon.co.uk (UK)',
        'A1PA6795UKMFR9'  => 'Amazon.de (Germany)',
        'A13V1IB3VIYZZH'  => 'Amazon.fr (France)',
        'APJ6JRA9NG5V4'   => 'Amazon.it (Italy)',
        'A1RKKUPIHCS9HS'  => 'Amazon.es (Spain)',
        'A1805IZSGTT6HS'  => 'Amazon.nl (Netherlands)',
        'A1C3SOZRARQ6R3'  => 'Amazon.pl (Poland)',
        'A2NODRKZP88ZB9'  => 'Amazon.se (Sweden)',
        'AMEN7PMS3EDWL'   => 'Amazon.com.be (Belgium)',
        'A28R8C7NBKEWEA'  => 'Amazon.ie (Ireland)',
        'ARBP9OOSHTCHU'   => 'Amazon.eg (Egypt)',
        'AE08WJ6YKNBMC'   => 'Amazon.co.za (South Africa)',
        'A33AVAJ2PDY3EV'  => 'Amazon.com.tr (Turkey)',
        'A21TJRUUN4KGV'   => 'Amazon.in (India)',
        'A2VIGQ35RCS4UG'  => 'Amazon.ae (UAE)',
        'A17E79C6D8DWNP'  => 'Amazon.sa (Saudi Arabia)',
        'A19VAU5U5O7RUS'  => 'Amazon.sg (Singapore)',
        'A39IBJ37TRP1C6'  => 'Amazon.com.au (Australia)',
        'A1VC38T7YXB528'  => 'Amazon.co.jp (Japan)',
    );

    /** Marketplace to SP-API region mapping. */
    private static $regionMap = array(
        'ATVPDKIKX0DER'  => 'NA', 'A2EUQ1WTGCTBG2' => 'NA', 'A1AM78C64UM0Y8' => 'NA',
        'A2Q3Y263D00KMC' => 'NA',
        'A1F83G8C2ARO7P' => 'EU', 'A1PA6795UKMFR9' => 'EU', 'A13V1IB3VIYZZH' => 'EU',
        'APJ6JRA9NG5V4'  => 'EU', 'A1RKKUPIHCS9HS' => 'EU', 'A1805IZSGTT6HS' => 'EU',
        'A1C3SOZRARQ6R3' => 'EU', 'A2NODRKZP88ZB9' => 'EU', 'AMEN7PMS3EDWL'  => 'EU',
        'A33AVAJ2PDY3EV' => 'EU', 'A21TJRUUN4KGV'  => 'EU', 'A2VIGQ35RCS4UG' => 'EU',
        'A17E79C6D8DWNP' => 'EU', 'A28R8C7NBKEWEA' => 'EU', 'ARBP9OOSHTCHU'  => 'EU',
        'AE08WJ6YKNBMC'  => 'EU', 'A19VAU5U5O7RUS' => 'FE',
        'A39IBJ37TRP1C6' => 'FE', 'A1VC38T7YXB528' => 'FE',
    );

    private $lastError = null;
    private $notices = array();

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Get all configured marketplace configs.
     *
     * @param bool $activeOnly Only return active marketplaces
     * @return array
     */
    public function getMarketplaceConfigs($activeOnly = false)
    {
        $where = $activeOnly ? 'WHERE `active` = 1' : '';
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
                ' . $where . '
                ORDER BY `marketplace_name` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Get a single marketplace config.
     *
     * @param string $marketplaceId
     * @return array|false
     */
    public function getMarketplaceConfig($marketplaceId)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
             WHERE `marketplace_id` = \'' . pSQL($marketplaceId) . '\''
        );
    }

    /**
     * Save or update a marketplace configuration.
     *
     * @param array $data Marketplace config fields
     * @return bool
     */
    public function saveMarketplaceConfig($data)
    {
        $marketplaceId = isset($data['marketplace_id']) ? $data['marketplace_id'] : '';
        if ($marketplaceId === '') {
            $this->lastError = 'Marketplace ID is required.';
            return false;
        }

        $name = isset(self::$marketplaceNames[$marketplaceId])
            ? self::$marketplaceNames[$marketplaceId]
            : $marketplaceId;

        $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
            (`marketplace_id`, `marketplace_name`, `client_id`, `client_secret`,
             `refresh_token`, `seller_id`, `active`, `default_carrier`,
             `default_order_state`, `sync_orders`, `sync_products`, `sync_stock`,
             `date_add`, `date_upd`)
            VALUES (
                \'' . pSQL($marketplaceId) . '\',
                \'' . pSQL($name) . '\',
                \'' . pSQL(isset($data['client_id']) ? $data['client_id'] : '') . '\',
                \'' . pSQL(isset($data['client_secret']) ? $data['client_secret'] : '') . '\',
                \'' . pSQL(isset($data['refresh_token']) ? $data['refresh_token'] : '') . '\',
                \'' . pSQL(isset($data['seller_id']) ? $data['seller_id'] : '') . '\',
                ' . (int) (isset($data['active']) ? $data['active'] : 1) . ',
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
     * Delete a marketplace configuration.
     *
     * @param string $marketplaceId
     * @return bool
     */
    public function deleteMarketplaceConfig($marketplaceId)
    {
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
             WHERE `marketplace_id` = \'' . pSQL($marketplaceId) . '\''
        );
    }

    /**
     * Build an SP-API client for a specific marketplace.
     *
     * Uses per-marketplace credentials if configured, otherwise falls back
     * to the primary (global) credentials.
     *
     * @param string $marketplaceId
     * @return AmazonSpApiClient
     */
    public function buildClientForMarketplace($marketplaceId)
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';

        $config = $this->getMarketplaceConfig($marketplaceId);

        $usingPrimary = !($config && !empty($config['client_id']));
        if (!$usingPrimary) {
            // Use per-marketplace credentials
            $clientId = $config['client_id'];
            $clientSecret = $config['client_secret'];
            $refreshToken = $config['refresh_token'];
        } else {
            // Fall back to primary credentials
            $clientId = Configuration::get('AMZPRO_CLIENT_ID');
            $clientSecret = Configuration::get('AMZPRO_CLIENT_SECRET');
            $refreshToken = AmazonSpApiClient::storedRefreshToken();
        }

        $endpoint = $this->resolveEndpoint($marketplaceId);

        $client = new AmazonSpApiClient($clientId, $clientSecret, $refreshToken, $endpoint);

        // Primary credentials in "Connect with Amazon" mode use the token relay.
        if ($usingPrimary && Configuration::get('AMZPRO_AUTH_MODE') !== 'manual') {
            $client->setTokenRelay(AmazonSpApiClient::relayUrl());
        }

        return $client;
    }

    /**
     * Resolve the SP-API endpoint for a marketplace.
     *
     * @param string $marketplaceId
     * @return string
     */
    public function resolveEndpoint($marketplaceId)
    {
        $env = Configuration::get('AMZPRO_ENVIRONMENT');

        if ($env !== 'production') {
            return AmazonSpApiClient::ENDPOINT_NA_SANDBOX;
        }

        $region = isset(self::$regionMap[$marketplaceId]) ? self::$regionMap[$marketplaceId] : 'EU';

        switch ($region) {
            case 'NA':
                return AmazonSpApiClient::ENDPOINT_NA;
            case 'FE':
                return AmazonSpApiClient::ENDPOINT_FE;
            default:
                return AmazonSpApiClient::ENDPOINT_EU;
        }
    }

    /**
     * Get the seller ID for a marketplace.
     *
     * @param string $marketplaceId
     * @return string
     */
    public function getSellerIdForMarketplace($marketplaceId)
    {
        $config = $this->getMarketplaceConfig($marketplaceId);
        if ($config && !empty($config['seller_id'])) {
            return $config['seller_id'];
        }
        return (string) Configuration::get('AMZPRO_SELLER_ID');
    }

    /**
     * Get carrier for a marketplace.
     *
     * @param string $marketplaceId
     * @return int
     */
    public function getCarrierForMarketplace($marketplaceId)
    {
        $config = $this->getMarketplaceConfig($marketplaceId);
        if ($config && (int) $config['default_carrier'] > 0) {
            return (int) $config['default_carrier'];
        }
        return (int) Configuration::get('AMZPRO_DEFAULT_CARRIER');
    }

    /**
     * Get order state for a marketplace.
     *
     * @param string $marketplaceId
     * @return int
     */
    public function getOrderStateForMarketplace($marketplaceId)
    {
        $config = $this->getMarketplaceConfig($marketplaceId);
        if ($config && (int) $config['default_order_state'] > 0) {
            return (int) $config['default_order_state'];
        }
        $state = (int) Configuration::get('AMZPRO_DEFAULT_ORDER_STATE');
        return $state ? $state : (int) Configuration::get('PS_OS_PAYMENT');
    }

    /**
     * Run order import across all active marketplaces.
     *
     * @return array Per-marketplace results
     */
    public function importOrdersAllMarketplaces()
    {
        $this->notices = array();
        $results = array();

        $configs = $this->getMarketplaceConfigs(true);
        if (empty($configs)) {
            $this->notices[] = 'No active marketplace configurations found.';
            return $results;
        }

        require_once dirname(__FILE__) . '/AmazonOrderImporter.php';

        $env = Configuration::get('AMZPRO_ENVIRONMENT');

        foreach ($configs as $config) {
            $mpId = $config['marketplace_id'];
            $mpName = $config['marketplace_name'];

            if (!(int) $config['sync_orders']) {
                $this->notices[] = $mpName . ': order sync disabled';
                continue;
            }

            $client = $this->buildClientForMarketplace($mpId);
            $importer = new AmazonOrderImporter($client, $mpId);

            $createdAfter = ($env === 'production')
                ? gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days'))
                : 'TEST_CASE_200';

            $summary = $importer->importNewOrders($createdAfter);

            $results[] = array(
                'marketplace_id' => $mpId,
                'marketplace_name' => $mpName,
                'success' => ($summary !== false),
                'summary' => $summary,
                'error' => $importer->getLastError(),
            );
        }

        return $results;
    }

    /**
     * Run product sync across all active marketplaces.
     *
     * @return array Per-marketplace results
     */
    public function syncProductsAllMarketplaces()
    {
        $this->notices = array();
        $results = array();

        $configs = $this->getMarketplaceConfigs(true);
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

            $client = $this->buildClientForMarketplace($mpId);
            $sellerId = $this->getSellerIdForMarketplace($mpId);
            $sync = new AmazonProductSync($client, $mpId, $sellerId);

            $env = Configuration::get('AMZPRO_ENVIRONMENT');
            $useMock = Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production';
            $sync->setMock($useMock);

            $psSummary = $sync->syncPrestashopSide();
            $azSummary = $sync->syncAmazonSide();

            $results[] = array(
                'marketplace_id' => $mpId,
                'marketplace_name' => $mpName,
                'ps_summary' => $psSummary,
                'amazon_summary' => $azSummary,
            );
        }

        return $results;
    }

    /**
     * Run stock sync across all active marketplaces.
     *
     * @return array Per-marketplace results
     */
    public function syncStockAllMarketplaces()
    {
        $this->notices = array();
        $results = array();

        $configs = $this->getMarketplaceConfigs(true);
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

            $client = $this->buildClientForMarketplace($mpId);
            $sellerId = $this->getSellerIdForMarketplace($mpId);
            $sync = new AmazonProductSync($client, $mpId, $sellerId);

            $env = Configuration::get('AMZPRO_ENVIRONMENT');
            $useMock = Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production';
            $sync->setMock($useMock);

            $sync->syncPrestashopSide();
            $pushResult = $sync->pushToAmazon(100);

            $results[] = array(
                'marketplace_id' => $mpId,
                'marketplace_name' => $mpName,
                'summary' => $pushResult,
            );
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
     * @return string NA, EU, or FE
     */
    public static function getRegion($marketplaceId)
    {
        return isset(self::$regionMap[$marketplaceId]) ? self::$regionMap[$marketplaceId] : 'EU';
    }
}
