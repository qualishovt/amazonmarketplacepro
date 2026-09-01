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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonListingSettings
{
    const ENTITY_CATEGORY = 'category';
    const ENTITY_MANUFACTURER = 'manufacturer';
    const ENTITY_SUPPLIER = 'supplier';

    /** Cache: entity_type => array(id_entity => row) */
    private static $entityCache = array();

    /** Cache: id_product => product_setting row */
    private static $productCache = null;

    /** @var bool Tables checked this request */
    private static $tablesEnsured = false;

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

        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting` (
            `id_amazonmarketplacepro_entity_setting` INT(11) NOT NULL AUTO_INCREMENT,
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
            UNIQUE KEY `entity` (`entity_type`, `id_entity`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` (
            `id_amazonmarketplacepro_product_setting` INT(11) NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) NOT NULL,
            `sync` TINYINT(1) NOT NULL DEFAULT 1,
            `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_product_setting`),
            UNIQUE KEY `id_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` (
            `id_amazonmarketplacepro_queue` INT(11) NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) NOT NULL,
            `reason` VARCHAR(128) NOT NULL DEFAULT \'\',
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_queue`),
            UNIQUE KEY `id_product` (`id_product`),
            KEY `active` (`active`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template` (
            `id_amazonmarketplacepro_shipping_template` INT(11) NOT NULL AUTO_INCREMENT,
            `basis` VARCHAR(16) NOT NULL DEFAULT \'price\',
            `min_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `max_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `template_name` VARCHAR(128) NOT NULL DEFAULT \'\',
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_shipping_template`),
            KEY `basis` (`basis`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }
    }

    /* ─────────────────── Entity settings (category / manufacturer / supplier) ─────────────────── */

    /**
     * @param string $type One of the ENTITY_* constants
     * @return array id_entity => setting row
     */
    public static function getEntitySettings($type)
    {
        self::ensureTables();
        if (isset(self::$entityCache[$type])) {
            return self::$entityCache[$type];
        }
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting`
             WHERE `entity_type` = \'' . pSQL($type) . '\''
        );
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id_entity']] = $r;
            }
        }
        self::$entityCache[$type] = $out;

        return $out;
    }

    public static function saveEntitySetting($type, $idEntity, $markup, $delay, $gpsr, $coo, $sync)
    {
        self::ensureTables();
        $now = date('Y-m-d H:i:s');
        $ok = Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting`
                (`entity_type`, `id_entity`, `price_markup`, `shipping_delay`, `gpsr_contact`,
                 `country_of_origin`, `sync`, `date_add`, `date_upd`)
             VALUES (\'' . pSQL($type) . '\', ' . (int) $idEntity . ', \'' . pSQL(trim((string) $markup)) . '\',
                 ' . (int) $delay . ', \'' . pSQL(trim((string) $gpsr)) . '\',
                 \'' . pSQL(Tools::strtoupper(trim((string) $coo))) . '\', ' . ($sync ? 1 : 0) . ',
                 \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')
             ON DUPLICATE KEY UPDATE
                `price_markup` = VALUES(`price_markup`),
                `shipping_delay` = VALUES(`shipping_delay`),
                `gpsr_contact` = VALUES(`gpsr_contact`),
                `country_of_origin` = VALUES(`country_of_origin`),
                `sync` = VALUES(`sync`),
                `date_upd` = VALUES(`date_upd`)'
        );
        unset(self::$entityCache[$type]);

        return $ok;
    }

    /* ─────────────────── Per-product settings ─────────────────── */

    /** @return array id_product => row */
    public static function getProductSettings()
    {
        self::ensureTables();
        if (self::$productCache !== null) {
            return self::$productCache;
        }
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`'
        );
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id_product']] = $r;
            }
        }
        self::$productCache = $out;

        return $out;
    }

    public static function saveProductSetting($idProduct, $sync, $gpsr)
    {
        self::ensureTables();
        $now = date('Y-m-d H:i:s');
        $ok = Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
                (`id_product`, `sync`, `gpsr_contact`, `date_add`, `date_upd`)
             VALUES (' . (int) $idProduct . ', ' . ($sync ? 1 : 0) . ',
                 \'' . pSQL(trim((string) $gpsr)) . '\', \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')
             ON DUPLICATE KEY UPDATE
                `sync` = VALUES(`sync`),
                `gpsr_contact` = VALUES(`gpsr_contact`),
                `date_upd` = VALUES(`date_upd`)'
        );
        self::$productCache = null;

        return $ok;
    }

    /* ─────────────────── Product context lookup ─────────────────── */

    /**
     * The ids the cascades need: default category, manufacturer, default supplier.
     *
     * @return array array('id_category' => int, 'id_manufacturer' => int, 'id_supplier' => int)
     */
    public static function productContext($idProduct)
    {
        static $cache = array();
        $idProduct = (int) $idProduct;
        if (isset($cache[$idProduct])) {
            return $cache[$idProduct];
        }
        $row = Db::getInstance()->getRow(
            'SELECT `id_category_default`, `id_manufacturer`, `id_supplier`
             FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . $idProduct
        );
        $cache[$idProduct] = array(
            'id_category' => $row ? (int) $row['id_category_default'] : 0,
            'id_manufacturer' => $row ? (int) $row['id_manufacturer'] : 0,
            'id_supplier' => $row ? (int) $row['id_supplier'] : 0,
        );

        return $cache[$idProduct];
    }

    /** Enabled cascade sources for a config key (AMZPRO_MARKUP_SOURCES / AMZPRO_DELAY_SOURCES). */
    private static function enabledSources($configKey)
    {
        $raw = (string) Configuration::get($configKey);
        $list = array_filter(array_map('trim', explode(',', $raw)));

        return empty($list) ? array() : $list;
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
        $map = array(
            self::ENTITY_CATEGORY => $ctx['id_category'],
            self::ENTITY_MANUFACTURER => $ctx['id_manufacturer'],
            self::ENTITY_SUPPLIER => $ctx['id_supplier'],
        );
        foreach (array(self::ENTITY_CATEGORY, self::ENTITY_MANUFACTURER, self::ENTITY_SUPPLIER) as $type) {
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
            $markup = trim((string) Configuration::get('AMZPRO_DEFAULT_MARKUP'));
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
        switch ((string) Configuration::get('AMZPRO_ROUNDING')) {
            case 'smart':
                $rounded = floor($price) + 0.99;
                if ($rounded < $price) {
                    $rounded += 1;
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
        if (!Configuration::get('AMZPRO_SEND_SALE_PRICE')) {
            return false;
        }

        $idShop = (int) Context::getContext()->shop->id;
        $row = Db::getInstance()->getRow(
            'SELECT `price`, `reduction`, `reduction_type`, `from`, `to`
             FROM `' . _DB_PREFIX_ . 'specific_price`
             WHERE `id_product` = ' . (int) $idProduct . '
               AND (`id_product_attribute` = 0 OR `id_product_attribute` = ' . (int) $idProductAttribute . ')
               AND (`id_shop` = 0 OR `id_shop` = ' . $idShop . ')
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

        return array(
            'price' => $sale,
            'from' => date('Y-m-d', strtotime($row['from'])),
            'to' => date('Y-m-d', strtotime($row['to'])),
        );
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
        $flat = (float) Configuration::get('AMZPRO_B2B_DISCOUNT');
        $idGroup = (int) Configuration::get('AMZPRO_BUSINESS_GROUP');
        if ($flat <= 0 && $idGroup <= 0) {
            return false;
        }

        $price = ($flat > 0 && $flat < 100)
            ? self::applyRounding($basePrice * (1 - $flat / 100))
            : self::applyRounding($basePrice);

        $levels = array();
        $discountType = 'PERCENT_OFF';
        if ($idGroup > 0) {
            $idShop = (int) Context::getContext()->shop->id;
            $rows = Db::getInstance()->executeS(
                'SELECT `from_quantity`, `reduction`, `reduction_type`, `price`
                 FROM `' . _DB_PREFIX_ . 'specific_price`
                 WHERE `id_product` = ' . (int) $idProduct . '
                   AND `id_group` = ' . $idGroup . '
                   AND `from_quantity` > 1
                   AND (`id_product_attribute` = 0 OR `id_product_attribute` = ' . (int) $idProductAttribute . ')
                   AND (`id_shop` = 0 OR `id_shop` = ' . $idShop . ')
                 ORDER BY `from_quantity` ASC'
            );
            if (is_array($rows)) {
                $seen = array();
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
                    $levels[] = array('lower_bound' => $qty, 'value' => $value);
                }
            }
        }

        // Amazon accepts at most five tiers.
        $levels = array_slice($levels, 0, 5);

        return array('price' => $price, 'levels' => $levels, 'discount_type' => $discountType);
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

        $offer = array(
            'audience' => 'B2B',
            'currency' => AmazonSpApiClient::currencyForMarketplace($marketplaceId),
            'marketplace_id' => $marketplaceId,
            'our_price' => array(array(
                'schedule' => array(array('value_with_tax' => $b2b['price'])),
            )),
        );
        if (!empty($b2b['levels'])) {
            $offer['quantity_discount_plan'] = array(array(
                'schedule' => array(array(
                    'discount_type' => $b2b['discount_type'],
                    'levels' => $b2b['levels'],
                )),
            ));
        }

        $attributes['purchasable_offer'][] = $offer;
    }

    /** Amazon condition for a PrestaShop condition (new / used / refurbished). */
    public static function mapCondition($psCondition)
    {
        $map = json_decode((string) Configuration::get('AMZPRO_CONDITION_MAP'), true);
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
            $delay = (int) Configuration::get('AMZPRO_DEFAULT_DELAY');
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
        $order = (Configuration::get('AMZPRO_GPSR_PRIORITY') === 'supplier')
            ? array(self::ENTITY_SUPPLIER => $ctx['id_supplier'], self::ENTITY_MANUFACTURER => $ctx['id_manufacturer'])
            : array(self::ENTITY_MANUFACTURER => $ctx['id_manufacturer'], self::ENTITY_SUPPLIER => $ctx['id_supplier']);

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
        $settings = self::getEntitySettings(self::ENTITY_MANUFACTURER);
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
        $map = array(
            self::ENTITY_CATEGORY => $ctx['id_category'],
            self::ENTITY_MANUFACTURER => $ctx['id_manufacturer'],
            self::ENTITY_SUPPLIER => $ctx['id_supplier'],
        );
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
     * @return string '' when the source field is empty for this product
     */
    public static function buildSku($row)
    {
        $source = (string) Configuration::get('AMZPRO_SKU_SOURCE');
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
        $prefix = trim((string) Configuration::get('AMZPRO_SKU_PREFIX'));

        return ($prefix !== '') ? $prefix . '-' . $value : $value;
    }

    /* ─────────────────── Specific prices ─────────────────── */

    /**
     * Base price adjusted by a matching PrestaShop specific price for the
     * configured customer group (only when the feature is enabled).
     */
    public static function applySpecificPrice($price, $idProduct, $idProductAttribute)
    {
        if (!Configuration::get('AMZPRO_USE_SPECIFIC_PRICES')) {
            return (float) $price;
        }
        $idGroup = (int) Configuration::get('AMZPRO_SPECIFIC_PRICE_GROUP');
        $idShop = (int) Context::getContext()->shop->id;
        $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT');

        $sp = SpecificPrice::getSpecificPrice(
            (int) $idProduct, $idShop, $idCurrency, $idCountry, $idGroup,
            1, (int) $idProductAttribute, 0, 0, 1
        );
        if (!is_array($sp) || empty($sp)) {
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

    /* ─────────────────── Shipping template ranges ─────────────────── */

    public static function getShippingTemplates()
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`
             ORDER BY `min_value` ASC'
        );

        return is_array($rows) ? $rows : array();
    }

    /**
     * Template name for a price/weight, per the configured basis. Ranges are
     * min-inclusive / max-exclusive (max 0 = open-ended). Falls back to the
     * static AMZPRO_SHIPPING_TEMPLATE, then ''.
     */
    public static function resolveShippingTemplate($priceTaxIncl, $weight)
    {
        if (Configuration::get('AMZPRO_SHIP_TPL_ENABLED')) {
            $basis = (Configuration::get('AMZPRO_SHIP_TPL_BASIS') === 'weight') ? 'weight' : 'price';
            $value = ($basis === 'weight') ? (float) $weight : (float) $priceTaxIncl;
            foreach (self::getShippingTemplates() as $tpl) {
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

        return trim((string) Configuration::get('AMZPRO_SHIPPING_TEMPLATE'));
    }

    public static function saveShippingTemplate($basis, $min, $max, $name, $id = 0)
    {
        $now = date('Y-m-d H:i:s');
        $basis = ($basis === 'weight') ? 'weight' : 'price';
        if ((int) $id) {
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
                (`basis`, `min_value`, `max_value`, `template_name`, `date_add`, `date_upd`)
             VALUES (\'' . pSQL($basis) . '\', ' . (float) $min . ', ' . (float) $max . ',
                 \'' . pSQL($name) . '\', \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')'
        );
    }

    public static function deleteShippingTemplate($id)
    {
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`
             WHERE `id_amazonmarketplacepro_shipping_template` = ' . (int) $id
        );
    }

    /* ─────────────────── Change queue (delta export) ─────────────────── */

    public static function enqueueProduct($idProduct, $reason)
    {
        self::ensureTables();
        $now = date('Y-m-d H:i:s');

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
                (`id_product`, `reason`, `active`, `date_add`, `date_upd`)
             VALUES (' . (int) $idProduct . ', \'' . pSQL(Tools::substr($reason, 0, 128)) . '\', 1,
                 \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')
             ON DUPLICATE KEY UPDATE
                `reason` = VALUES(`reason`), `active` = 1, `date_upd` = VALUES(`date_upd`)'
        );
    }

    /** Queue every product belonging to an entity whose module rules changed. */
    public static function enqueueEntityProducts($type, $idEntity, $reason)
    {
        $idEntity = (int) $idEntity;
        if ($type === self::ENTITY_CATEGORY) {
            $sql = 'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'category_product`
                    WHERE `id_category` = ' . $idEntity;
        } elseif ($type === self::ENTITY_MANUFACTURER) {
            $sql = 'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
                    WHERE `id_manufacturer` = ' . $idEntity;
        } elseif ($type === self::ENTITY_SUPPLIER) {
            $sql = 'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
                    WHERE `id_supplier` = ' . $idEntity;
        } else {
            return 0;
        }

        $rows = Db::getInstance()->executeS($sql);
        $count = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                self::enqueueProduct((int) $r['id_product'], $reason);
                $count++;
            }
        }

        return $count;
    }

    public static function getQueue($limit = 500)
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT q.*, p.`reference`, pl.`name`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` q
             LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = q.`id_product`)
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = q.`id_product`
                     AND pl.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT') . ')
             GROUP BY q.`id_amazonmarketplacepro_queue`
             ORDER BY q.`date_upd` DESC
             LIMIT ' . (int) $limit
        );

        return is_array($rows) ? $rows : array();
    }

    /** Active queued product ids (for delta export). */
    public static function getQueuedProductIds()
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` WHERE `active` = 1'
        );
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id_product']] = true;
            }
        }

        return $out;
    }

    /** After a sync, queued entries are disabled (AmazonSync behaviour). */
    public static function deactivateQueued($idProducts)
    {
        if (empty($idProducts)) {
            return true;
        }
        $ids = array_map('intval', $idProducts);

        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
             SET `active` = 0, `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `id_product` IN (' . implode(',', $ids) . ')'
        );
    }

    /** @param string $action enable | disable | clear */
    public static function queueAction($action)
    {
        if ($action === 'clear') {
            return Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`');
        }
        $active = ($action === 'enable') ? 1 : 0;

        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
             SET `active` = ' . $active . ', `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\''
        );
    }

    /** Drop entries older than the configured retention. */
    public static function purgeQueue()
    {
        $days = max(1, (int) Configuration::get('AMZPRO_QUEUE_TTL_DAYS'));
        // PHP-computed cutoff: date_upd is written with PHP's clock, which
        // may not share MySQL's timezone.
        $cutoff = date('Y-m-d H:i:s', time() - $days * 86400);

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`
             WHERE `date_upd` < \'' . pSQL($cutoff) . '\''
        );
    }

    /* ─────────────────── Orphans ─────────────────── */

    /**
     * Amazon listings that no longer map to a sellable PrestaShop product:
     * either the SKU has no PS product at all, or the PS product is inactive.
     * Reads the staged product table, so run an Amazon-side sync first.
     */
    public static function getOrphanedProducts($limit = 500)
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT ap.`seller_sku`, ap.`amazon_asin`, ap.`amazon_title`, ap.`id_product`,
                    ap.`id_product_attribute`, ap.`ps_exists`, p.`active`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` ap
             LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = ap.`id_product`)
             WHERE ap.`amazon_exists` = 1
               AND (ap.`ps_exists` = 0 OR p.`id_product` IS NULL OR p.`active` = 0)
             ORDER BY ap.`seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $reason = 'No PrestaShop product with this SKU';
                if ((int) $r['id_product'] > 0 && $r['active'] !== null && !(int) $r['active']) {
                    $reason = 'PrestaShop product is inactive';
                } elseif ((int) $r['id_product'] > 0 && $r['active'] === null) {
                    $reason = 'PrestaShop product was deleted';
                }
                $r['reason'] = $reason;
                $out[] = $r;
            }
        }

        return $out;
    }
}
