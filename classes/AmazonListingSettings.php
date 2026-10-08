<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Listing rules resolved per product: price markups, handling delays,
 * GPSR compliance contacts, country of origin, per-entity/per-product
 * sync switches, SKU building, shipping template ranges and the change
 * queue used for delta exports.
 *
 * Markups and delays cascade: category -> manufacturer -> supplier ->
 * module default. Each source can be switched off in the settings.
 *
 * Multistore: entity rules, product rules and shipping templates are shared
 * by every shop (id_shop 0) and a shop can override them with rows of its
 * own; the change queue holds one row per shop and product. See AmzproShop.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';
require_once dirname(__FILE__) . '/AmazonProductOverride.php';

class AmazonListingSettings
{
    public static $ENTITY_CATEGORY = 'category';
    public static $ENTITY_MANUFACTURER = 'manufacturer';
    public static $ENTITY_SUPPLIER = 'supplier';

    /** Cache: id_shop => entity_type => array(id_entity => row) */
    private static $entityCache = [];

    /** @var bool Tables checked this request */
    private static $tablesEnsured = false;

    /** @var string|null Why the last shipping template change was refused */
    private static $lastError;

    /**
     * Create the rules/queue tables when they don't exist yet (e.g. module
     * updated by file copy without the 1.1.0 upgrade script having run).
     */
    public static function ensureTables()
    {
        if (self::$tablesEnsured) {
            return;
        }
        self::$tablesEnsured = true;

        $sql = [];
        $sql['amazonmarketplacepro_entity_setting'] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting` (
            `id_amazonmarketplacepro_entity_setting` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `entity_type` VARCHAR(16) NOT NULL,
            `id_entity` INT(11) NOT NULL,
            `price_markup` VARCHAR(16) NOT NULL DEFAULT \'\',
            `shipping_delay` INT(11) NOT NULL DEFAULT -1,
            `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
            `country_of_origin` VARCHAR(4) NOT NULL DEFAULT \'\',
            `sync` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_entity_setting`),
            UNIQUE KEY `entity` (`id_shop`, `entity_type`, `id_entity`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $sql['amazonmarketplacepro_product_setting'] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` (
            `id_amazonmarketplacepro_product_setting` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `id_product` INT(11) NOT NULL,
            `sync` TINYINT(1) NOT NULL DEFAULT 1,
            `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_product_setting`),
            UNIQUE KEY `id_product` (`id_shop`, `id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $sql['amazonmarketplacepro_queue'] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` (
            `id_amazonmarketplacepro_queue` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `id_product` INT(11) NOT NULL,
            `reason` VARCHAR(128) NOT NULL DEFAULT \'\',
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_queue`),
            UNIQUE KEY `id_product` (`id_shop`, `id_product`),
            KEY `active` (`active`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $sql['amazonmarketplacepro_shipping_template'] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template` (
            `id_amazonmarketplacepro_shipping_template` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `basis` VARCHAR(16) NOT NULL DEFAULT \'price\',
            `min_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `max_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `template_name` VARCHAR(128) NOT NULL DEFAULT \'\',
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_shipping_template`),
            KEY `basis` (`basis`),
            KEY `id_shop` (`id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $table => $query) {
            Db::getInstance()->execute($query);
            // A table created before multistore support gets its shop column.
            AmzproShop::ensureTableShop($table);
        }
    }

    /** Why the last shipping template save or delete was refused, or null. */
    public static function getLastError()
    {
        return self::$lastError;
    }

    /**
     * A shop changes its own rows of a shared table and "All shops" changes
     * the shared ones. With multistore off every row the shop sees is its own,
     * as it was before the module knew about shops.
     */
    private static function canChange($rowShop)
    {
        return in_array((int) $rowShop, self::writableShops(), true);
    }

    /** @return int[] the id_shop values a change made in this context may touch */
    private static function writableShops()
    {
        $id = (int) AmzproShop::sharedWriteId();
        if (!AmzproShop::isMultistore()) {
            return array_values(array_unique([0, $id]));
        }

        return [$id];
    }

    /* ─────────────────── Entity settings (category / manufacturer / supplier) ─────────────────── */

    /**
     * The rules the current shop uses: its own row where it has one, else the
     * row shared by all shops. In "All shops" only the shared rows.
     *
     * @param string $type One of the ENTITY_* constants
     *
     * @return array id_entity => setting row (includes id_shop)
     */
    public static function getEntitySettings($type)
    {
        self::ensureTables();
        $idShop = AmzproShop::id();
        if (isset(self::$entityCache[$idShop][$type])) {
            return self::$entityCache[$idShop][$type];
        }
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting`
             WHERE `entity_type` = \'' . pSQL($type) . '\'
               AND ' . AmzproShop::sqlShared()
        );
        $out = [];
        if (is_array($rows)) {
            foreach (AmzproShop::preferShopRows($rows, ['id_entity']) as $r) {
                $out[(int) $r['id_entity']] = $r;
            }
        }
        self::$entityCache[$idShop][$type] = $out;

        return $out;
    }

    /**
     * Save an entity rule for the scope being edited: in "All shops" the rule
     * every shop inherits, in a shop that shop's own rule. A shop saving the
     * values it already inherits gets no row of its own, so it keeps
     * following the shared rule.
     */
    public static function saveEntitySetting($type, $idEntity, $markup, $delay, $gpsr, $coo, $sync)
    {
        self::ensureTables();
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting';
        $values = [
            'price_markup' => trim((string) $markup),
            'shipping_delay' => (int) $delay,
            'gpsr_contact' => trim((string) $gpsr),
            'country_of_origin' => Tools::strtoupper(trim((string) $coo)),
            'sync' => $sync ? 1 : 0,
        ];
        $key = '`entity_type` = \'' . pSQL($type) . '\' AND `id_entity` = ' . (int) $idEntity;
        self::$entityCache = [];

        $existing = $db->executeS('SELECT * FROM `' . bqSQL($table) . '` WHERE ' . $key . ' AND ' . AmzproShop::sqlShared());
        $byShop = [];
        foreach ((is_array($existing) ? $existing : []) as $r) {
            $byShop[(int) $r['id_shop']] = $r;
        }

        if (AmzproShop::isMultistore()) {
            $idShop = (int) AmzproShop::sharedWriteId();
            if ($idShop > 0 && !isset($byShop[$idShop])) {
                $inherited = isset($byShop[0]) ? $byShop[0] : [
                    'price_markup' => '', 'shipping_delay' => -1, 'gpsr_contact' => '',
                    'country_of_origin' => '', 'sync' => 1,
                ];
                if ((string) $inherited['price_markup'] === $values['price_markup']
                    && (int) $inherited['shipping_delay'] === $values['shipping_delay']
                    && (string) $inherited['gpsr_contact'] === $values['gpsr_contact']
                    && (string) $inherited['country_of_origin'] === $values['country_of_origin']
                    && (int) $inherited['sync'] === $values['sync']) {
                    return true;
                }
            }
        } else {
            // One shop: update the row it already has, whichever scope it was saved in.
            $idShop = $byShop ? max(array_keys($byShop)) : (int) AmzproShop::sharedWriteId();
        }

        $now = date('Y-m-d H:i:s');

        return $db->execute(
            'INSERT INTO `' . $table . '`
                (`id_shop`, `entity_type`, `id_entity`, `price_markup`, `shipping_delay`, `gpsr_contact`,
                 `country_of_origin`, `sync`, `date_add`, `date_upd`)
             VALUES (' . (int) $idShop . ', \'' . pSQL($type) . '\', ' . (int) $idEntity . ', \'' . pSQL($values['price_markup']) . '\',
                 ' . $values['shipping_delay'] . ', \'' . pSQL($values['gpsr_contact']) . '\',
                 \'' . pSQL($values['country_of_origin']) . '\', ' . $values['sync'] . ',
                 \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')
             ON DUPLICATE KEY UPDATE
                `price_markup` = VALUES(`price_markup`),
                `shipping_delay` = VALUES(`shipping_delay`),
                `gpsr_contact` = VALUES(`gpsr_contact`),
                `country_of_origin` = VALUES(`country_of_origin`),
                `sync` = VALUES(`sync`),
                `date_upd` = VALUES(`date_upd`)'
        );
    }

    /**
     * Remove an entity rule from the scope being edited. In a shop only the
     * shop's own rule goes, and the shop follows the shared rule again; in
     * "All shops" the shared rule goes and shop rules stay.
     */
    public static function deleteEntitySetting($type, $idEntity)
    {
        self::ensureTables();
        self::$entityCache = [];

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting`
             WHERE `entity_type` = \'' . pSQL($type) . '\' AND `id_entity` = ' . (int) $idEntity . '
               AND `id_shop` IN (' . implode(', ', self::writableShops()) . ')'
        );
    }

    /* ─────────────────── Per-product settings ─────────────────── */

    /**
     * The product rules the current shop uses (its own row over the shared
     * one). Same rows as AmazonProductOverride::all().
     *
     * @return array id_product => row
     */
    public static function getProductSettings()
    {
        self::ensureTables();

        return AmazonProductOverride::all();
    }

    /** Save a product's sync switch and GPSR contact for the scope being edited. */
    public static function saveProductSetting($idProduct, $sync, $gpsr)
    {
        self::ensureTables();

        return AmazonProductOverride::save((int) $idProduct, [
            'sync' => $sync ? 1 : 0,
            'gpsr_contact' => trim((string) $gpsr),
        ]);
    }

    /* ─────────────────── Product context lookup ─────────────────── */

    /**
     * The ids the cascades need: default category (the shop's own, which
     * PrestaShop lets differ per shop), manufacturer, default supplier.
     *
     * @return array array('id_category' => int, 'id_manufacturer' => int, 'id_supplier' => int)
     */
    public static function productContext($idProduct)
    {
        static $cache = [];
        $idProduct = (int) $idProduct;
        $idShop = AmzproShop::actingId();
        $cacheKey = $idShop . '-' . $idProduct;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }
        $row = Db::getInstance()->getRow(
            'SELECT IFNULL(ps.`id_category_default`, p.`id_category_default`) AS id_category_default,
                    p.`id_manufacturer`, p.`id_supplier`
             FROM `' . _DB_PREFIX_ . 'product` p
             LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                 ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . (int) $idShop . ')
             WHERE p.`id_product` = ' . $idProduct
        );
        $cache[$cacheKey] = [
            'id_category' => $row ? (int) $row['id_category_default'] : 0,
            'id_manufacturer' => $row ? (int) $row['id_manufacturer'] : 0,
            'id_supplier' => $row ? (int) $row['id_supplier'] : 0,
        ];

        return $cache[$cacheKey];
    }

    /** Enabled cascade sources for a config key (AMZPRO_MARKUP_SOURCES / AMZPRO_DELAY_SOURCES). */
    private static function enabledSources($configKey)
    {
        $raw = (string) AmzproShop::get($configKey);
        $list = array_filter(array_map('trim', explode(',', $raw)));

        return empty($list) ? [] : $list;
    }

    /**
     * Walk the category -> manufacturer -> supplier cascade and return the
     * first entity setting where $picker returns a usable value.
     *
     * @param callable $picker function($settingRow) -> mixed|null
     */
    private static function resolveFromCascade($idProduct, $sources, $picker)
    {
        $ctx = self::productContext($idProduct);
        $map = [
            self::$ENTITY_CATEGORY => $ctx['id_category'],
            self::$ENTITY_MANUFACTURER => $ctx['id_manufacturer'],
            self::$ENTITY_SUPPLIER => $ctx['id_supplier'],
        ];
        foreach ([self::$ENTITY_CATEGORY, self::$ENTITY_MANUFACTURER, self::$ENTITY_SUPPLIER] as $type) {
            if (!in_array($type, $sources) || !$map[$type]) {
                continue;
            }
            $settings = self::getEntitySettings($type);
            if (!isset($settings[$map[$type]])) {
                continue;
            }
            $value = call_user_func($picker, $settings[$map[$type]]);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /* ─────────────────── Price markup ─────────────────── */

    /**
     * Apply the resolved markup to a price. Markup strings are either a
     * fixed amount ("5.99") or a percentage ("10%" / "10.5%"). Empty = none.
     */
    public static function applyMarkup($price, $idProduct)
    {
        $markup = self::resolveFromCascade(
            $idProduct,
            self::enabledSources('AMZPRO_MARKUP_SOURCES'),
            function ($row) {
                $m = trim((string) $row['price_markup']);

                return ($m !== '') ? $m : null;
            }
        );
        if ($markup === null) {
            $markup = trim((string) AmzproShop::get('AMZPRO_DEFAULT_MARKUP'));
        }

        return self::applyMarkupString($price, $markup);
    }

    /**
     * Round an exported price per the configured policy.
     *
     * none    — leave it alone (still normalised to 2 decimals)
     * cents   — nearest cent
     * smart   — up to the nearest .99 (15.93 becomes 15.99)
     * integer — nearest whole unit
     */
    public static function applyRounding($price)
    {
        $price = (float) $price;
        switch ((string) AmzproShop::get('AMZPRO_ROUNDING')) {
            case 'smart':
                $rounded = floor($price) + 0.99;
                if ($rounded < $price) {
                    ++$rounded;
                }

                return round($rounded, 2);
            case 'integer':
                return (float) round($price, 0);
            case 'cents':
                return round($price, 2);
            default:
                return round($price, 2);
        }
    }

    /**
     * A dated PrestaShop specific price becomes an Amazon sale price: Amazon
     * stores the window and shows the discount only while it is open, so
     * future promotions can be pushed ahead of time.
     *
     * @return array|false array('price' => float, 'from' => 'Y-m-d', 'to' => 'Y-m-d')
     */
    public static function resolveSaleSchedule($basePrice, $idProduct, $idProductAttribute)
    {
        if (!AmzproShop::get('AMZPRO_SEND_SALE_PRICE')) {
            return false;
        }

        $row = Db::getInstance()->getRow(
            'SELECT `price`, `reduction`, `reduction_type`, `from`, `to`
             FROM `' . _DB_PREFIX_ . 'specific_price`
             WHERE `id_product` = ' . (int) $idProduct . '
               AND (`id_product_attribute` = 0 OR `id_product_attribute` = ' . (int) $idProductAttribute . ')
               AND ' . self::sqlSpecificPriceScope() . '
               AND `from` <> \'0000-00-00 00:00:00\' AND `to` <> \'0000-00-00 00:00:00\'
               AND `to` >= \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             ORDER BY `id_product_attribute` DESC, `from` ASC'
        );
        if (!$row) {
            return false;
        }

        $sale = (isset($row['price']) && (float) $row['price'] > 0)
            ? (float) $row['price'] : (float) $basePrice;
        if ((float) $row['reduction'] > 0) {
            $sale = ($row['reduction_type'] === 'percentage')
                ? $sale * (1 - (float) $row['reduction'])
                : $sale - (float) $row['reduction'];
        }
        $sale = self::applyRounding(max(0, $sale));
        if ($sale <= 0 || $sale >= (float) $basePrice) {
            return false;
        }

        return [
            'price' => $sale,
            'from' => date('Y-m-d', strtotime($row['from'])),
            'to' => date('Y-m-d', strtotime($row['to'])),
        ];
    }

    /**
     * Amazon Business pricing for a product.
     *
     * The B2B price itself comes from the flat discount setting, and the
     * quantity ladder from PrestaShop specific prices addressed to the
     * configured business customer group with a from_quantity above 1 — which
     * is exactly how a merchant already expresses "buy 10, save 5%" in
     * PrestaShop. Nothing new to maintain in two places.
     *
     * @return array|false array('price' => float, 'levels' => array, 'discount_type' => string)
     */
    public static function resolveBusinessPricing($basePrice, $idProduct, $idProductAttribute)
    {
        $flat = (float) AmzproShop::get('AMZPRO_B2B_DISCOUNT');
        $idGroup = (int) AmzproShop::get('AMZPRO_BUSINESS_GROUP');
        if ($flat <= 0 && $idGroup <= 0) {
            return false;
        }

        $price = ($flat > 0 && $flat < 100)
            ? self::applyRounding($basePrice * (1 - $flat / 100))
            : self::applyRounding($basePrice);

        $levels = [];
        $discountType = 'PERCENT_OFF';
        if ($idGroup > 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT `from_quantity`, `reduction`, `reduction_type`, `price`
                 FROM `' . _DB_PREFIX_ . 'specific_price`
                 WHERE `id_product` = ' . (int) $idProduct . '
                   AND `id_group` = ' . $idGroup . '
                   AND `from_quantity` > 1
                   AND (`id_product_attribute` = 0 OR `id_product_attribute` = ' . (int) $idProductAttribute . ')
                   AND ' . self::sqlSpecificPriceScope() . '
                 ORDER BY `from_quantity` ASC'
            );
            if (is_array($rows)) {
                $seen = [];
                foreach ($rows as $r) {
                    $qty = (int) $r['from_quantity'];
                    if ($qty < 2 || isset($seen[$qty])) {
                        continue;
                    }
                    $value = 0;
                    if ((float) $r['reduction'] > 0) {
                        if ($r['reduction_type'] === 'percentage') {
                            $discountType = 'PERCENT_OFF';
                            $value = round((float) $r['reduction'] * 100, 2);
                        } else {
                            $discountType = 'FIXED_AMOUNT';
                            $value = round((float) $r['reduction'], 2);
                        }
                    } elseif ((float) $r['price'] > 0 && (float) $r['price'] < $price) {
                        // A flat tier price expressed as an amount off.
                        $discountType = 'FIXED_AMOUNT';
                        $value = round($price - (float) $r['price'], 2);
                    }
                    if ($value <= 0) {
                        continue;
                    }
                    $seen[$qty] = true;
                    $levels[] = ['lower_bound' => $qty, 'value' => $value];
                }
            }
        }

        // Amazon accepts at most five tiers.
        $levels = array_slice($levels, 0, 5);

        return ['price' => $price, 'levels' => $levels, 'discount_type' => $discountType];
    }

    /**
     * Attach the Amazon Business offer (price + quantity ladder) to a listing
     * that already carries a B2C purchasable_offer.
     *
     * @param array $attributes by reference
     */
    public static function applyBusinessPricing(&$attributes, $marketplaceId, $basePrice, $idProduct, $idProductAttribute)
    {
        if (!isset($attributes['purchasable_offer'][0]) || $basePrice <= 0) {
            return;
        }
        $b2b = self::resolveBusinessPricing($basePrice, $idProduct, $idProductAttribute);
        if ($b2b === false || $b2b['price'] <= 0) {
            return;
        }

        $offer = [
            'audience' => 'B2B',
            'currency' => AmazonSpApiClient::currencyForMarketplace($marketplaceId),
            'marketplace_id' => $marketplaceId,
            'our_price' => [[
                'schedule' => [['value_with_tax' => $b2b['price']]],
            ]],
        ];
        if (!empty($b2b['levels'])) {
            $offer['quantity_discount_plan'] = [[
                'schedule' => [[
                    'discount_type' => $b2b['discount_type'],
                    'levels' => $b2b['levels'],
                ]],
            ]];
        }

        $attributes['purchasable_offer'][] = $offer;
    }

    /** Amazon condition for a PrestaShop condition (new / used / refurbished). */
    public static function mapCondition($psCondition)
    {
        $map = json_decode((string) AmzproShop::get('AMZPRO_CONDITION_MAP'), true);
        $psCondition = trim((string) $psCondition);
        if (is_array($map) && $psCondition !== '' && !empty($map[$psCondition])) {
            return (string) $map[$psCondition];
        }

        return '';
    }

    public static function applyMarkupString($price, $markup)
    {
        $price = (float) $price;
        $markup = trim((string) $markup);
        if ($markup === '') {
            return $price;
        }
        if (Tools::substr($markup, -1) === '%') {
            $pct = (float) str_replace(',', '.', rtrim($markup, '%'));

            return round($price * (1 + $pct / 100), 2);
        }

        return round($price + (float) str_replace(',', '.', $markup), 2);
    }

    /* ─────────────────── Handling / shipping delay ─────────────────── */

    /**
     * @return int Days of handling time to send with the offer; 0 = do not send
     */
    public static function resolveDelay($idProduct)
    {
        $delay = self::resolveFromCascade(
            $idProduct,
            self::enabledSources('AMZPRO_DELAY_SOURCES'),
            function ($row) {
                $d = (int) $row['shipping_delay'];

                return ($d >= 0) ? $d : null;
            }
        );
        if ($delay === null) {
            $delay = (int) AmzproShop::get('AMZPRO_DEFAULT_DELAY');
        }

        return max(0, (int) $delay);
    }

    /* ─────────────────── GPSR contact & country of origin ─────────────────── */

    /**
     * GPSR responsible-person contact (e-mail address or URL) for a product.
     * A product-level contact always wins; below that, manufacturer/supplier
     * in the configured priority order.
     *
     * @return string '' when none configured
     */
    public static function resolveGpsrContact($idProduct)
    {
        // The per-product contact (Amazon tab of the product page) wins.
        $products = self::getProductSettings();
        if (isset($products[(int) $idProduct])
            && trim((string) $products[(int) $idProduct]['gpsr_contact']) !== '') {
            return trim($products[(int) $idProduct]['gpsr_contact']);
        }

        $ctx = self::productContext($idProduct);
        $order = (AmzproShop::get('AMZPRO_GPSR_PRIORITY') === 'supplier')
            ? [self::$ENTITY_SUPPLIER => $ctx['id_supplier'], self::$ENTITY_MANUFACTURER => $ctx['id_manufacturer']]
            : [self::$ENTITY_MANUFACTURER => $ctx['id_manufacturer'], self::$ENTITY_SUPPLIER => $ctx['id_supplier']];

        foreach ($order as $type => $idEntity) {
            if (!$idEntity) {
                continue;
            }
            $settings = self::getEntitySettings($type);
            if (isset($settings[$idEntity]) && trim((string) $settings[$idEntity]['gpsr_contact']) !== '') {
                return trim($settings[$idEntity]['gpsr_contact']);
            }
        }

        return '';
    }

    /** @return string Two-letter country code from the manufacturer setting, or '' */
    public static function resolveCountryOfOrigin($idProduct)
    {
        $ctx = self::productContext($idProduct);
        if (!$ctx['id_manufacturer']) {
            return '';
        }
        $settings = self::getEntitySettings(self::$ENTITY_MANUFACTURER);
        if (isset($settings[$ctx['id_manufacturer']])) {
            return trim((string) $settings[$ctx['id_manufacturer']]['country_of_origin']);
        }

        return '';
    }

    /* ─────────────────── Sync switches ─────────────────── */

    /**
     * A product syncs unless it — or its category, manufacturer or supplier —
     * has been switched off.
     */
    public static function isSyncEnabled($idProduct)
    {
        $products = self::getProductSettings();
        if (isset($products[(int) $idProduct]) && !(int) $products[(int) $idProduct]['sync']) {
            return false;
        }

        $ctx = self::productContext($idProduct);
        $map = [
            self::$ENTITY_CATEGORY => $ctx['id_category'],
            self::$ENTITY_MANUFACTURER => $ctx['id_manufacturer'],
            self::$ENTITY_SUPPLIER => $ctx['id_supplier'],
        ];
        foreach ($map as $type => $idEntity) {
            if (!$idEntity) {
                continue;
            }
            $settings = self::getEntitySettings($type);
            if (isset($settings[$idEntity]) && !(int) $settings[$idEntity]['sync']) {
                return false;
            }
        }

        return true;
    }

    /* ─────────────────── SKU building ─────────────────── */

    /**
     * Build the Amazon SKU for a product row according to the configured
     * source field and optional prefix ("PREFIX-VALUE").
     *
     * @param array $row Needs keys: reference, ean13, supplier_reference
     *
     * @return string '' when the source field is empty for this product
     */
    public static function buildSku($row)
    {
        $source = (string) AmzproShop::get('AMZPRO_SKU_SOURCE');
        $value = '';
        if ($source === 'ean13') {
            $value = isset($row['ean13']) ? trim((string) $row['ean13']) : '';
        } elseif ($source === 'supplier_reference') {
            $value = isset($row['supplier_reference']) ? trim((string) $row['supplier_reference']) : '';
        } else {
            $value = isset($row['reference']) ? trim((string) $row['reference']) : '';
        }
        if ($value === '') {
            return '';
        }
        $prefix = trim((string) AmzproShop::get('AMZPRO_SKU_PREFIX'));

        return ($prefix !== '') ? $prefix . '-' . $value : $value;
    }

    /* ─────────────────── Specific prices ─────────────────── */

    /**
     * Base price adjusted by a matching PrestaShop specific price for the
     * configured customer group (only when the feature is enabled).
     */
    public static function applySpecificPrice($price, $idProduct, $idProductAttribute)
    {
        if (!AmzproShop::get('AMZPRO_USE_SPECIFIC_PRICES')) {
            return (float) $price;
        }
        $idGroup = (int) AmzproShop::get('AMZPRO_SPECIFIC_PRICE_GROUP');
        $scope = self::specificPriceScope();

        $sp = SpecificPrice::getSpecificPrice(
            (int) $idProduct, $scope['id_shop'], $scope['id_currency'], $scope['id_country'], $idGroup,
            1, (int) $idProductAttribute, 0, 0, 1
        );
        if (!is_array($sp) || empty($sp)) {
            return (float) $price;
        }
        // PrestaShop does not filter on the shop group; a price saved for
        // another group of shops does not apply here.
        if (!empty($sp['id_shop_group']) && (int) $sp['id_shop_group'] !== $scope['id_shop_group']) {
            return (float) $price;
        }

        // A fixed specific price replaces the base price entirely.
        if (isset($sp['price']) && (float) $sp['price'] > 0) {
            $price = (float) $sp['price'];
        }
        if (isset($sp['reduction']) && (float) $sp['reduction'] > 0) {
            if ($sp['reduction_type'] === 'percentage') {
                $price = $price * (1 - (float) $sp['reduction']);
            } else {
                $price = $price - (float) $sp['reduction'];
            }
        }

        return max(0, round((float) $price, 6));
    }

    /**
     * The shop, shop group, currency and country specific prices are looked
     * up for: the shop being worked for and its own defaults.
     *
     * @return array
     */
    private static function specificPriceScope()
    {
        $idShop = (int) AmzproShop::actingId();
        $idGroup = (int) AmzproShop::groupId($idShop);

        return [
            'id_shop' => $idShop,
            'id_shop_group' => $idGroup,
            'id_currency' => (int) Configuration::get('PS_CURRENCY_DEFAULT', null, $idGroup, $idShop),
            'id_country' => (int) Configuration::get('PS_COUNTRY_DEFAULT', null, $idGroup, $idShop),
        ];
    }

    /** SQL condition on ps_specific_price (no alias) for specificPriceScope(). */
    private static function sqlSpecificPriceScope()
    {
        $scope = self::specificPriceScope();

        return '`id_shop` IN (0, ' . $scope['id_shop'] . ')
               AND `id_shop_group` IN (0, ' . $scope['id_shop_group'] . ')
               AND `id_currency` IN (0, ' . $scope['id_currency'] . ')
               AND `id_country` IN (0, ' . $scope['id_country'] . ')';
    }

    /* ─────────────────── Shipping template ranges ─────────────────── */

    /**
     * The templates for all shops plus the current shop's own (only the
     * shared ones in "All shops"). Each row carries its id_shop.
     */
    public static function getShippingTemplates()
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`
             WHERE ' . AmzproShop::sqlShared() . '
             ORDER BY `min_value` ASC, `id_shop` ASC'
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * Template name for a price/weight, per the configured basis. Ranges are
     * min-inclusive / max-exclusive (max 0 = open-ended). Falls back to the
     * static AMZPRO_SHIPPING_TEMPLATE, then ''. A shop's own ranges are tried
     * before the ones shared by all shops.
     */
    public static function resolveShippingTemplate($priceTaxIncl, $weight)
    {
        if (AmzproShop::get('AMZPRO_SHIP_TPL_ENABLED')) {
            $basis = (AmzproShop::get('AMZPRO_SHIP_TPL_BASIS') === 'weight') ? 'weight' : 'price';
            $value = ($basis === 'weight') ? (float) $weight : (float) $priceTaxIncl;
            $templates = self::getShippingTemplates();
            if (AmzproShop::isMultistore()) {
                $own = [];
                $shared = [];
                foreach ($templates as $tpl) {
                    if ((int) $tpl['id_shop'] > 0) {
                        $own[] = $tpl;
                    } else {
                        $shared[] = $tpl;
                    }
                }
                $templates = array_merge($own, $shared);
            }
            foreach ($templates as $tpl) {
                if ($tpl['basis'] !== $basis) {
                    continue;
                }
                $min = (float) $tpl['min_value'];
                $max = (float) $tpl['max_value'];
                if ($value >= $min && ($max <= 0 || $value < $max)) {
                    return trim((string) $tpl['template_name']);
                }
            }
        }

        return trim((string) AmzproShop::get('AMZPRO_SHIPPING_TEMPLATE'));
    }

    /**
     * Add a template range for the scope being edited, or change one. A shop
     * may change only its own templates; shared ones are changed in "All
     * shops". Returns false with getLastError() set when refused.
     */
    public static function saveShippingTemplate($basis, $min, $max, $name, $id = 0)
    {
        self::ensureTables();
        self::$lastError = null;
        $now = date('Y-m-d H:i:s');
        $basis = ($basis === 'weight') ? 'weight' : 'price';
        if ((int) $id) {
            if (!self::checkTemplateChange((int) $id)) {
                return false;
            }

            return Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template` SET
                    `basis` = \'' . pSQL($basis) . '\', `min_value` = ' . (float) $min . ',
                    `max_value` = ' . (float) $max . ', `template_name` = \'' . pSQL($name) . '\',
                    `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_amazonmarketplacepro_shipping_template` = ' . (int) $id
            );
        }

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`
                (`id_shop`, `basis`, `min_value`, `max_value`, `template_name`, `date_add`, `date_upd`)
             VALUES (' . (int) AmzproShop::sharedWriteId() . ', \'' . pSQL($basis) . '\', ' . (float) $min . ', ' . (float) $max . ',
                 \'' . pSQL($name) . '\', \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')'
        );
    }

    /** Delete a template range, with the same rule as saveShippingTemplate(). */
    public static function deleteShippingTemplate($id)
    {
        self::ensureTables();
        self::$lastError = null;
        if (!self::checkTemplateChange((int) $id)) {
            return false;
        }

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`
             WHERE `id_amazonmarketplacepro_shipping_template` = ' . (int) $id
        );
    }

    /** True when the current context may change this template; else sets the error. */
    private static function checkTemplateChange($id)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`
             WHERE `id_amazonmarketplacepro_shipping_template` = ' . (int) $id . '
               AND ' . AmzproShop::sqlShared()
        );
        if (!$row) {
            self::$lastError = AmazonI18n::get()->l('This shipping template was not found. Reload the page and try again.', 'amazonlistingsettings');

            return false;
        }
        if (!self::canChange($row['id_shop'])) {
            self::$lastError = AmazonI18n::get()->l('This shipping template is shared by all shops. Select "All shops" at the top of the page to change it.', 'amazonlistingsettings');

            return false;
        }

        return true;
    }

    /* ─────────────────── Change queue (delta export) ─────────────────── */

    /**
     * Queue a product for the current shop's next delta export. In "All
     * shops" it is queued for every shop the product belongs to.
     */
    public static function enqueueProduct($idProduct, $reason)
    {
        self::ensureTables();
        $idProduct = (int) $idProduct;
        $idShop = AmzproShop::id();
        $shops = [];
        if ($idShop) {
            $shops[] = $idShop;
        } else {
            $rows = Db::getInstance()->executeS(
                'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_product` = ' . $idProduct
            );
            foreach ((is_array($rows) ? $rows : []) as $r) {
                $shops[] = (int) $r['id_shop'];
            }
        }
        if (!$shops) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $values = [];
        foreach ($shops as $s) {
            $values[] = '(' . (int) $s . ', ' . $idProduct . ', \'' . pSQL(Tools::substr($reason, 0, 128)) . '\', 1,
                 \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')';
        }

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
                (`id_shop`, `id_product`, `reason`, `active`, `date_add`, `date_upd`)
             VALUES ' . implode(', ', $values) . '
             ON DUPLICATE KEY UPDATE
                `reason` = VALUES(`reason`), `active` = 1, `date_upd` = VALUES(`date_upd`)'
        );
    }

    /**
     * Queue every product belonging to an entity whose module rules changed:
     * the current shop's products, or in "All shops" every product (each
     * queued for the shops it is in).
     */
    public static function enqueueEntityProducts($type, $idEntity, $reason)
    {
        $idEntity = (int) $idEntity;
        $idShop = AmzproShop::id();
        $inShop = $idShop
            ? ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                    ON (ps.`id_product` = x.`id_product` AND ps.`id_shop` = ' . (int) $idShop . ')'
            : '';
        if ($type === self::$ENTITY_CATEGORY) {
            $sql = 'SELECT x.`id_product` FROM `' . _DB_PREFIX_ . 'category_product` x' . $inShop . '
                    WHERE x.`id_category` = ' . $idEntity;
        } elseif ($type === self::$ENTITY_MANUFACTURER) {
            $sql = 'SELECT x.`id_product` FROM `' . _DB_PREFIX_ . 'product` x' . $inShop . '
                    WHERE x.`id_manufacturer` = ' . $idEntity;
        } elseif ($type === self::$ENTITY_SUPPLIER) {
            $sql = 'SELECT x.`id_product` FROM `' . _DB_PREFIX_ . 'product` x' . $inShop . '
                    WHERE x.`id_supplier` = ' . $idEntity;
        } else {
            return 0;
        }

        $rows = Db::getInstance()->executeS($sql);
        $count = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                self::enqueueProduct((int) $r['id_product'], $reason);
                ++$count;
            }
        }

        return $count;
    }

    /** The current shop's queue, or every shop's in "All shops" (with shop_name). */
    public static function getQueue($limit = 500)
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT q.*, p.`reference`, pl.`name`, s.`name` AS shop_name
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` q
             LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = q.`id_product`)
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = q.`id_product`
                     AND pl.`id_shop` = q.`id_shop`
                     AND pl.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT') . ')
             LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON (s.`id_shop` = q.`id_shop`)
             WHERE ' . AmzproShop::sqlWhere('q') . '
             GROUP BY q.`id_amazonmarketplacepro_queue`
             ORDER BY q.`date_upd` DESC
             LIMIT ' . (int) $limit
        );

        return is_array($rows) ? $rows : [];
    }

    /** Active queued product ids of the current shop (for delta export). */
    public static function getQueuedProductIds()
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
             WHERE `active` = 1 AND ' . AmzproShop::sqlWhere()
        );
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id_product']] = true;
            }
        }

        return $out;
    }

    /** After a sync, the current shop's queued entries are disabled (AmazonSync behaviour). */
    public static function deactivateQueued($idProducts)
    {
        if (empty($idProducts)) {
            return true;
        }
        $ids = array_map('intval', $idProducts);

        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
             SET `active` = 0, `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `id_product` IN (' . implode(',', $ids) . ')
               AND ' . AmzproShop::sqlWhere()
        );
    }

    /**
     * Acts on the current shop's queue, or on every shop's in "All shops".
     *
     * @param string $action enable | disable | clear
     */
    public static function queueAction($action)
    {
        if ($action === 'clear') {
            return Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` WHERE ' . AmzproShop::sqlWhere()
            );
        }
        $active = ($action === 'enable') ? 1 : 0;

        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
             SET `active` = ' . $active . ', `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE ' . AmzproShop::sqlWhere()
        );
    }

    /**
     * Drop entries older than the configured retention: the current shop's,
     * or in "All shops" each shop's with its own retention.
     */
    public static function purgeQueue()
    {
        $idShop = AmzproShop::id();
        $shops = $idShop ? [$idShop] : AmzproShop::shopIds();
        $ok = true;
        foreach ($shops as $s) {
            $days = max(1, (int) AmzproShop::get('AMZPRO_QUEUE_TTL_DAYS', $s));
            // PHP-computed cutoff: date_upd is written with PHP's clock, which
            // may not share MySQL's timezone.
            $cutoff = date('Y-m-d H:i:s', time() - $days * 86400);

            $ok = Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
                 WHERE `date_upd` < \'' . pSQL($cutoff) . '\'
                   AND ' . AmzproShop::sqlWhere('', $s)
            ) && $ok;
        }

        return $ok;
    }

    /* ─────────────────── Orphans ─────────────────── */

    /**
     * Amazon listings that no longer map to a sellable PrestaShop product:
     * either the SKU has no PS product at all, or the PS product is inactive
     * or missing in the listing's shop. Reads the staged product table, so run
     * an Amazon-side sync first. The current shop's listings, or every shop's
     * in "All shops" (with shop_name).
     */
    public static function getOrphanedProducts($limit = 500)
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT ap.`seller_sku`, ap.`amazon_asin`, ap.`amazon_title`, ap.`id_product`,
                    ap.`id_product_attribute`, ap.`ps_exists`, ap.`id_shop`, ps.`active`,
                    IF(p.`id_product` IS NULL, 0, 1) AS product_found, s.`name` AS shop_name
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` ap
             LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = ap.`id_product`)
             LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                 ON (ps.`id_product` = ap.`id_product` AND ps.`id_shop` = ap.`id_shop`)
             LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON (s.`id_shop` = ap.`id_shop`)
             WHERE ap.`amazon_exists` = 1 AND ap.`marketplace_id` = \'\'
               AND ' . AmzproShop::sqlWhere('ap') . '
               AND (ap.`ps_exists` = 0 OR p.`id_product` IS NULL OR ps.`id_product` IS NULL OR ps.`active` = 0)
             ORDER BY ap.`seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $reason = AmazonI18n::get()->l('No PrestaShop product with this SKU', 'amazonlistingsettings');
                if ((int) $r['id_product'] > 0 && !(int) $r['product_found']) {
                    $reason = AmazonI18n::get()->l('PrestaShop product was deleted', 'amazonlistingsettings');
                } elseif ((int) $r['id_product'] > 0 && $r['active'] === null) {
                    $reason = AmazonI18n::get()->l('The PrestaShop product is not in this shop', 'amazonlistingsettings');
                } elseif ((int) $r['id_product'] > 0 && !(int) $r['active']) {
                    $reason = AmazonI18n::get()->l('PrestaShop product is inactive', 'amazonlistingsettings');
                }
                $r['reason'] = $reason;
                $out[] = $r;
            }
        }

        return $out;
    }
}
