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
 * Which shop the module is working for, and the scope of what it stores.
 *
 * Every read and write of module settings and module tables goes through here
 * so that the scope is always explicit. Letting PrestaShop pick it from the
 * context is what went wrong before: a value written by the front-office
 * Connect page landed in a different scope than the one the back office read.
 *
 * The rules, for PrestaShop 1.6 to 9:
 *
 * - id() is the shop a request acts for: the URL's shop in the front office
 *   and in cron, the selected shop in the back office, and 0 when the back
 *   office is set to "All shops" or a shop group. With multistore off it is
 *   always the one shop, so a single-shop install behaves exactly as before.
 * - Settings follow PrestaShop: a shop inherits the value saved for all shops
 *   unless it saves its own. See get() and set().
 * - The Amazon connection, the cron token and the scheduler state never
 *   inherit ($ownKeys): each shop connects its own seller account. The
 *   default shop falls back to the value stored for all shops, which is where
 *   installs from before multistore support keep theirs.
 * - Install-wide switches ($globalKeys) are the same for every shop.
 * - Data tables ($shopTables) hold rows of one shop. Rule tables
 *   ($sharedTables) hold rows for all shops (id_shop 0) that a shop can
 *   override with rows of its own.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';

class AmzproShop
{
    /** Settings that belong to the whole installation, never to one shop. */
    public static $globalKeys = [
        'AMZPRO_DEV_MODE',
        'AMZPRO_ENVIRONMENT',
        'AMZPRO_USE_MOCK',
        'AMZPRO_OAUTH_BETA',
        'AMZPRO_LWA_APP_ID',
        'AMZPRO_LWA_APP_ID_SANDBOX',
        'AMZPRO_RELAY_URL',
        'AMZPRO_SHOP_SCHEMA',
    ];

    /** Settings each shop holds for itself and never inherits. */
    public static $ownKeys = [
        'AMZPRO_REFRESH_TOKEN',
        'AMZPRO_REFRESH_TOKEN_SANDBOX',
        'AMZPRO_AUTH_MODE',
        'AMZPRO_CLIENT_ID',
        'AMZPRO_CLIENT_SECRET',
        'AMZPRO_SELLER_ID',
        'AMZPRO_SELLING_PARTNER_ID',
        'AMZPRO_OAUTH_NONCE',
        'AMZPRO_OAUTH_RETURN_URL',
        'AMZPRO_CRON_TOKEN',
        'AMZPRO_CRON_LOCK',
        'AMZPRO_RELAY_REGISTERED',
        'AMZPRO_RELAY_ERROR',
        'AMZPRO_RELAY_SINCE',
        'AMZPRO_RELAY_CRON_URL',
        'AMZPRO_CRON_MODE',
    ];

    /**
     * Tables whose rows each belong to one shop, with the unique keys that
     * must include the shop. Backfill says where existing rows come from.
     */
    public static $shopTables = [
        'amazonmarketplacepro_order' => [
            'unique' => [],
            'keys' => ['shop_status' => ['id_shop', 'import_status']],
            'backfill' => 'orders',
        ],
        // One comparison row per SKU and marketplace: '' is the shop's main
        // marketplace (Settings), an id is one added under Multi-Account.
        'amazonmarketplacepro_product' => [
            'columns' => ['marketplace_id' => 'VARCHAR(32) NOT NULL DEFAULT \'\''],
            'unique' => ['seller_sku' => ['marketplace_id', 'seller_sku']],
            'keys' => [],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_return' => [
            'unique' => [],
            'keys' => ['shop_status' => ['id_shop', 'return_status']],
            'backfill' => 'amazon_order',
        ],
        'amazonmarketplacepro_order_fee' => [
            'unique' => [],
            'keys' => ['shop_order' => ['id_shop', 'amazon_order_id']],
            'backfill' => 'amazon_order',
        ],
        'amazonmarketplacepro_fba_inventory' => [
            'unique' => ['sku_marketplace' => ['seller_sku', 'marketplace_id']],
            'keys' => [],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_competitive_price' => [
            'unique' => ['sku_marketplace' => ['seller_sku', 'marketplace_id']],
            'keys' => [],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_report' => [
            'unique' => [],
            'keys' => ['shop_status' => ['id_shop', 'status']],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_feed' => [
            'unique' => [],
            'keys' => ['shop_status' => ['id_shop', 'processing_status']],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_promotion' => [
            'unique' => [],
            'keys' => ['shop_status' => ['id_shop', 'status']],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_marketplace_config' => [
            'unique' => ['marketplace_id' => ['marketplace_id']],
            'keys' => [],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_queue' => [
            'unique' => ['id_product' => ['id_product']],
            'keys' => [],
            'backfill' => 'default',
        ],
        'amazonmarketplacepro_reservation' => [
            'unique' => [],
            'keys' => ['shop_status' => ['id_shop', 'status']],
            'backfill' => 'exists',
        ],
        // 0 marks a system line that belongs to no shop.
        'amazonmarketplacepro_log' => [
            'unique' => [],
            'keys' => ['shop_log' => ['id_shop', 'id_amazonmarketplacepro_log']],
            'backfill' => 'none',
        ],
    ];

    /**
     * Tables whose rows are shared by every shop (id_shop 0) unless a shop
     * has its own. Existing rows stay shared.
     */
    public static $sharedTables = [
        'amazonmarketplacepro_category_map' => [
            'unique' => ['cat_marketplace' => ['id_category', 'marketplace_id']],
        ],
        'amazonmarketplacepro_entity_setting' => [
            'unique' => ['entity' => ['entity_type', 'id_entity']],
        ],
        'amazonmarketplacepro_product_setting' => [
            'unique' => ['id_product' => ['id_product']],
        ],
        'amazonmarketplacepro_shipping_template' => ['unique' => []],
        'amazonmarketplacepro_pricing_rule' => ['unique' => []],
        'amazonmarketplacepro_profile' => ['unique' => []],
        'amazonmarketplacepro_profile_category' => [
            'unique' => ['cat_marketplace' => ['id_category', 'marketplace_id']],
        ],
    ];

    /** The schema version ensureSchema() brings the tables to. */
    public static $SCHEMA = '1.6.3';

    /** @var Context|null The module's context, handed over by its constructor */
    private static $context;

    /* ─────────────────── Context ─────────────────── */

    /**
     * Called by the module constructor, which every entry point (hooks,
     * front controllers, cron, upgrade scripts) goes through.
     *
     * @param Context $context
     */
    public static function setContext($context)
    {
        self::$context = $context;
    }

    /**
     * The module's context: the shop, link and Smarty the request runs with.
     *
     * @return Context
     */
    public static function context()
    {
        if (self::$context === null) {
            Module::getInstanceByName('amazonmarketplacepro');
        }

        return self::$context;
    }

    /* ─────────────────── Which shop ─────────────────── */

    /** True when PrestaShop runs more than one shop. */
    public static function isMultistore()
    {
        return (bool) Shop::isFeatureActive();
    }

    /**
     * The shop this request acts for, or 0 for "All shops" or a shop group in
     * the back office.
     *
     * @return int
     */
    public static function id()
    {
        if (!self::isMultistore()) {
            $shop = self::context()->shop;

            return ($shop && $shop->id) ? (int) $shop->id : self::defaultShopId();
        }
        if (self::isBackOffice()) {
            return Shop::getContext() == Shop::CONTEXT_SHOP ? (int) Shop::getContextShopID() : 0;
        }
        $shop = self::context()->shop;

        return ($shop && $shop->id) ? (int) $shop->id : self::defaultShopId();
    }

    /**
     * The shop to write a data row for. Actions that write data are refused
     * in "All shops" (see requireShop()); should one run anyway, it acts for
     * the default shop, as the module did before it knew about shops.
     *
     * @return int
     */
    public static function actingId()
    {
        $id = self::id();

        return $id ? $id : self::defaultShopId();
    }

    /** The back office is set to "All shops" or a shop group. */
    public static function isAllShops()
    {
        return self::id() === 0;
    }

    /** @return int */
    public static function defaultShopId()
    {
        return (int) Configuration::get('PS_SHOP_DEFAULT');
    }

    /** @return int */
    public static function groupId($idShop)
    {
        return (int) Shop::getGroupFromShop((int) $idShop);
    }

    /**
     * Every shop id, active or not.
     *
     * @return int[]
     */
    public static function shopIds()
    {
        if (!self::isMultistore()) {
            return [self::actingId()];
        }

        return array_map('intval', array_values(Shop::getShops(false, null, true)));
    }

    /** @return string */
    public static function name($idShop)
    {
        $shop = new Shop((int) $idShop);

        return Validate::isLoadedObject($shop) ? (string) $shop->name : '#' . (int) $idShop;
    }

    /**
     * The error an action returns when it needs one shop and the back office
     * shows all of them, or null when a shop is selected.
     *
     * @return string|null
     */
    public static function requireShop()
    {
        if (!self::isAllShops()) {
            return null;
        }

        return AmazonI18n::get()->l('Select a shop at the top of the page first: this works on one shop at a time.', 'amzproshop');
    }

    protected static function isBackOffice()
    {
        return defined('_PS_ADMIN_DIR_');
    }

    /* ─────────────────── Settings ─────────────────── */

    /**
     * A module setting for a shop (default: the current one).
     *
     * @return string|false
     */
    public static function get($key, $idShop = null)
    {
        if (in_array($key, self::$globalKeys, true)) {
            return Configuration::getGlobalValue($key);
        }
        if (in_array($key, self::$ownKeys, true)) {
            return self::getOwn($key, $idShop);
        }
        if (!self::isMultistore()) {
            return Configuration::get($key);
        }
        $idShop = ($idShop === null) ? self::id() : (int) $idShop;
        if ($idShop === 0) {
            // All shops or a group: the value being edited for that scope.
            return Configuration::get($key);
        }

        return Configuration::get($key, null, self::groupId($idShop), $idShop);
    }

    /**
     * Save a module setting for a shop (default: the current one). In "All
     * shops" or a group it becomes the default for every shop that has not
     * saved its own. A value equal to the inherited one is not copied into
     * the shop, which keeps the shop following the default.
     *
     * @return bool
     */
    public static function set($key, $value, $idShop = null, $html = false)
    {
        if (in_array($key, self::$globalKeys, true)) {
            return Configuration::updateGlobalValue($key, $value, $html);
        }
        if (in_array($key, self::$ownKeys, true)) {
            return self::setOwn($key, $value, $idShop, $html);
        }
        if (!self::isMultistore()) {
            return Configuration::updateValue($key, $value, $html);
        }
        $idShop = ($idShop === null) ? self::id() : (int) $idShop;
        if ($idShop === 0) {
            return Configuration::updateValue($key, $value, $html);
        }
        $idGroup = self::groupId($idShop);
        // The settings form posts every field, so without this one change
        // saved in a shop would pin all the others to that shop.
        if (!is_array($value)
            && !Configuration::hasKey($key, null, null, $idShop)
            && (string) Configuration::get($key, null, $idGroup, 0) === (string) $value) {
            return true;
        }

        return Configuration::updateValue($key, $value, $html, $idGroup, $idShop);
    }

    /**
     * The shops that saved their own value for a setting, for the notice the
     * "All shops" page shows next to it.
     *
     * @return int[]
     */
    public static function overridingShops($key)
    {
        if (!self::isMultistore()) {
            return [];
        }
        $ids = [];
        foreach (self::shopIds() as $idShop) {
            if (Configuration::hasKey($key, null, null, $idShop)) {
                $ids[] = $idShop;
            }
        }

        return $ids;
    }

    /** A connection or scheduler value: the shop's own, never inherited. */
    protected static function getOwn($key, $idShop)
    {
        if (!self::isMultistore()) {
            return Configuration::getGlobalValue($key);
        }
        $idShop = ($idShop === null) ? self::actingId() : (int) $idShop;
        if (Configuration::hasKey($key, null, null, $idShop)) {
            return Configuration::get($key, null, self::groupId($idShop), $idShop);
        }
        // Installs from before multistore support keep theirs for all shops.
        if ($idShop === self::defaultShopId()) {
            return Configuration::getGlobalValue($key);
        }

        return false;
    }

    /**
     * Store a shop's own value. The row is written even when it equals the
     * value stored for all shops: Configuration::updateValue() would skip it
     * and leave the shop reading someone else's connection.
     */
    protected static function setOwn($key, $value, $idShop, $html = false)
    {
        if (!self::isMultistore()) {
            return Configuration::updateGlobalValue($key, $value, $html);
        }
        $idShop = ($idShop === null) ? self::actingId() : (int) $idShop;
        $idGroup = self::groupId($idShop);
        $ok = true;
        if (!Configuration::hasKey($key, null, null, $idShop)) {
            $now = date('Y-m-d H:i:s');
            $ok = Db::getInstance()->insert('configuration', [
                'name' => pSQL($key),
                'value' => pSQL((string) $value, $html),
                'id_shop_group' => $idGroup,
                'id_shop' => $idShop,
                'date_add' => $now,
                'date_upd' => $now,
            ]);
            Configuration::set($key, (string) $value, $idGroup, $idShop);
        } else {
            $ok = Configuration::updateValue($key, $value, $html, $idGroup, $idShop);
        }
        // Keep the value for all shops in step with the default shop, so the
        // connection survives switching multistore off again.
        if ($idShop === self::defaultShopId()) {
            $ok = Configuration::updateGlobalValue($key, $value, $html) && $ok;
        }

        return (bool) $ok;
    }

    /**
     * The shop, other than $exceptShop, already connected to this seller on
     * this marketplace, or 0.
     *
     * @return int
     */
    public static function shopUsingSeller($sellerId, $marketplaceId, $exceptShop)
    {
        $sellerId = (string) $sellerId;
        if ($sellerId === '' || !self::isMultistore()) {
            return 0;
        }
        foreach (self::shopIds() as $idShop) {
            if ($idShop === (int) $exceptShop) {
                continue;
            }
            $connected = (string) self::get('AMZPRO_REFRESH_TOKEN', $idShop) !== ''
                || (string) self::get('AMZPRO_REFRESH_TOKEN_SANDBOX', $idShop) !== '';
            if ($connected
                && (string) self::get('AMZPRO_SELLER_ID', $idShop) === $sellerId
                && (string) self::get('AMZPRO_MARKETPLACE_ID', $idShop) === (string) $marketplaceId) {
                return $idShop;
            }
        }

        return 0;
    }

    /* ─────────────────── Running for another shop ─────────────────── */

    /**
     * Run $callable as if the request belonged to $idShop, and put the
     * context back afterwards. The language is left alone: it is the
     * language messages are written in, not a property of the shop.
     *
     * @param int $idShop
     * @param callable $callable receives the shop id
     *
     * @return mixed what $callable returns
     */
    public static function runInShop($idShop, $callable)
    {
        $idShop = (int) $idShop;
        $context = self::context();
        $saved = [
            'type' => Shop::getContext(),
            'shop_id' => Shop::getContextShopID(),
            'group_id' => Shop::getContextShopGroupID(),
            'shop' => $context->shop,
            'currency' => $context->currency,
            'country' => $context->country,
            'link' => $context->link,
        ];
        $switch = !$context->shop || (int) $context->shop->id !== $idShop || Shop::getContext() != Shop::CONTEXT_SHOP;

        try {
            if ($switch) {
                $shop = new Shop($idShop);
                Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
                $context->shop = $shop;
                $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, self::groupId($idShop), $idShop);
                if ($idCurrency) {
                    $context->currency = new Currency($idCurrency);
                }
                $idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT', null, self::groupId($idShop), $idShop);
                if ($idCountry) {
                    $context->country = new Country($idCountry);
                }
                $context->link = new Link();
            }

            return call_user_func($callable, $idShop);
        } finally {
            if ($switch) {
                if ($saved['type'] == Shop::CONTEXT_SHOP) {
                    Shop::setContext(Shop::CONTEXT_SHOP, (int) $saved['shop_id']);
                } elseif ($saved['type'] == Shop::CONTEXT_GROUP) {
                    Shop::setContext(Shop::CONTEXT_GROUP, (int) $saved['group_id']);
                } else {
                    Shop::setContext(Shop::CONTEXT_ALL);
                }
                $context->shop = $saved['shop'];
                $context->currency = $saved['currency'];
                $context->country = $saved['country'];
                $context->link = $saved['link'];
            }
        }
    }

    /* ─────────────────── SQL ─────────────────── */

    /**
     * Condition for a data table: the shop's rows, or every row in "All
     * shops". Never append LIMIT through getValue()/getRow() with it.
     *
     * @param string $alias table alias, or '' for none
     * @param int|null $idShop default: the current shop
     *
     * @return string
     */
    public static function sqlWhere($alias = '', $idShop = null)
    {
        $idShop = ($idShop === null) ? self::id() : (int) $idShop;
        if ($idShop === 0) {
            return '1';
        }

        return self::column($alias) . ' = ' . $idShop;
    }

    /**
     * Condition for a shared table: the rows for all shops plus the shop's
     * own. In "All shops" only the rows for all shops. Combine with
     * preferShopRows() where a shop row replaces a shared one.
     *
     * @return string
     */
    public static function sqlShared($alias = '', $idShop = null)
    {
        $idShop = ($idShop === null) ? self::id() : (int) $idShop;
        if ($idShop === 0) {
            return self::column($alias) . ' = 0';
        }

        return self::column($alias) . ' IN (0, ' . $idShop . ')';
    }

    /**
     * The id_shop a new row of a shared table gets: 0 in "All shops", else
     * the shop's own. With multistore off it is 0 too, so the rules a
     * single shop saves stay shared if multistore is switched on later.
     *
     * @return int
     */
    public static function sharedWriteId()
    {
        return self::isMultistore() ? self::id() : 0;
    }

    /**
     * Keep one row per key, the shop's own over the shared one.
     *
     * @param array $rows rows that include id_shop
     * @param string[] $keyFields the columns that identify a row
     *
     * @return array
     */
    public static function preferShopRows(array $rows, array $keyFields)
    {
        $out = [];
        foreach ($rows as $row) {
            $parts = [];
            foreach ($keyFields as $f) {
                $parts[] = isset($row[$f]) ? (string) $row[$f] : '';
            }
            $k = implode("\x1f", $parts);
            if (!isset($out[$k]) || (int) $row['id_shop'] > (int) $out[$k]['id_shop']) {
                $out[$k] = $row;
            }
        }

        return array_values($out);
    }

    protected static function column($alias)
    {
        return ($alias !== '' ? '`' . bqSQL($alias) . '`.' : '') . '`id_shop`';
    }

    /* ─────────────────── Schema ─────────────────── */

    /**
     * Give every module table its shop column and shop-aware unique keys.
     *
     * Runs from the 1.6.0 upgrade and from install, and is also called by the
     * classes that create tables, because a cron request can reach new code
     * before anyone opens the back office, which is when upgrades run. The
     * flag makes the later calls free. Each table is only touched while it
     * lacks the column, so a failure halfway is picked up on the next call.
     *
     * @return bool
     */
    public static function ensureSchema()
    {
        static $done = false;
        if ($done || Configuration::getGlobalValue('AMZPRO_SHOP_SCHEMA') === self::$SCHEMA) {
            $done = true;

            return true;
        }

        $db = Db::getInstance();
        $default = self::defaultShopId();
        $ok = true;

        // Orders first: returns and fees take their shop from them.
        foreach (self::$shopTables as $table => $spec) {
            $ok = self::addShopColumn($table, $spec['unique'], $spec['keys'], self::extraColumns($spec)) && $ok;
        }
        foreach (self::$sharedTables as $table => $spec) {
            $ok = self::addShopColumn($table, $spec['unique'], []) && $ok;
        }
        if (!$ok) {
            return false;
        }

        foreach (self::$shopTables as $table => $spec) {
            if (!self::tableExists($table)) {
                continue;
            }
            if ($spec['backfill'] === 'orders') {
                $db->execute('UPDATE `' . _DB_PREFIX_ . bqSQL($table) . '` t
                    INNER JOIN `' . _DB_PREFIX_ . 'orders` o ON o.`id_order` = t.`id_order`
                    SET t.`id_shop` = o.`id_shop`
                    WHERE t.`id_shop` = 0 AND t.`id_order` > 0');
            } elseif ($spec['backfill'] === 'amazon_order') {
                $db->execute('UPDATE `' . _DB_PREFIX_ . bqSQL($table) . '` t
                    INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o ON o.`amazon_order_id` = t.`amazon_order_id`
                    SET t.`id_shop` = o.`id_shop`
                    WHERE t.`id_shop` = 0');
            }
            if (in_array($spec['backfill'], ['orders', 'amazon_order', 'default'], true)) {
                $db->execute('UPDATE `' . _DB_PREFIX_ . bqSQL($table) . '` SET `id_shop` = ' . (int) $default . ' WHERE `id_shop` = 0');
            }
        }

        Configuration::updateGlobalValue('AMZPRO_SHOP_SCHEMA', self::$SCHEMA);
        $done = true;

        return true;
    }

    /**
     * Give one table its shop column right after a class has created it.
     *
     * ensureSchema() stops looking once the install is marked up to date, so
     * a table a class creates later would otherwise never get the column.
     * Checked once per request per table.
     *
     * @param string $table name without prefix
     *
     * @return bool
     */
    public static function ensureTableShop($table)
    {
        static $checked = [];
        if (isset($checked[$table])) {
            return $checked[$table];
        }
        if (isset(self::$shopTables[$table])) {
            $spec = self::$shopTables[$table];
            $checked[$table] = self::addShopColumn($table, $spec['unique'], $spec['keys'], self::extraColumns($spec));
        } elseif (isset(self::$sharedTables[$table])) {
            $checked[$table] = self::addShopColumn($table, self::$sharedTables[$table]['unique'], []);
        } else {
            $checked[$table] = true;
        }

        return $checked[$table];
    }

    /** Columns a table spec adds besides id_shop (see $shopTables). */
    protected static function extraColumns(array $spec)
    {
        return isset($spec['columns']) ? $spec['columns'] : [];
    }

    protected static function tableExists($table)
    {
        return (bool) Db::getInstance()->executeS('SHOW TABLES LIKE \'' . pSQL(_DB_PREFIX_ . $table) . '\'');
    }

    /**
     * Add id_shop to one table and rebuild its unique keys around it. A table
     * that does not exist yet is left for the class that creates it.
     */
    protected static function addShopColumn($table, array $unique, array $keys, array $columns = [])
    {
        if (!self::tableExists($table)) {
            return true;
        }
        $db = Db::getInstance();
        $name = '`' . _DB_PREFIX_ . bqSQL($table) . '`';

        $hasColumn = false;
        foreach ((array) $db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($table) . '`') as $col) {
            if ($col['Field'] === 'id_shop') {
                $hasColumn = true;
            }
        }
        if (!$hasColumn) {
            if (!$db->execute('ALTER TABLE `' . _DB_PREFIX_ . bqSQL($table) . '` ADD `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0')) {
                return false;
            }
        }
        // Columns the unique keys below need, added before a key is rebuilt.
        if ($columns) {
            $present = [];
            foreach ((array) $db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($table) . '`') as $col) {
                $present[$col['Field']] = true;
            }
            foreach ($columns as $column => $definition) {
                if (!isset($present[$column])
                    && !$db->execute('ALTER TABLE `' . _DB_PREFIX_ . bqSQL($table) . '` ADD `' . bqSQL($column) . '` ' . $definition)) {
                    return false;
                }
            }
        }

        $indexes = [];
        foreach ((array) $db->executeS('SHOW INDEX FROM `' . _DB_PREFIX_ . bqSQL($table) . '`') as $ix) {
            $indexes[$ix['Key_name']][(int) $ix['Seq_in_index']] = $ix['Column_name'];
        }

        foreach ($unique as $keyName => $cols) {
            $want = array_merge(['id_shop'], $cols);
            if (isset($indexes[$keyName]) && array_values($indexes[$keyName]) === $want) {
                continue;
            }
            $drop = isset($indexes[$keyName]) ? 'DROP INDEX `' . bqSQL($keyName) . '`, ' : '';
            if (!$db->execute('ALTER TABLE ' . $name . ' ' . $drop . 'ADD UNIQUE KEY `' . bqSQL($keyName) . '` (`'
                . implode('`, `', array_map('bqSQL', $want)) . '`)')) {
                return false;
            }
        }
        if (!$unique && !$keys && !isset($indexes['id_shop'])) {
            $keys = ['id_shop' => ['id_shop']];
        }
        foreach ($keys as $keyName => $cols) {
            if (isset($indexes[$keyName])) {
                continue;
            }
            if (!$db->execute('ALTER TABLE ' . $name . ' ADD KEY `' . bqSQL($keyName) . '` (`'
                . implode('`, `', array_map('bqSQL', $cols)) . '`)')) {
                return false;
            }
        }

        return true;
    }
}
