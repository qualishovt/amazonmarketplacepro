<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Listing profiles: the bridge between a PrestaShop category and the shape
 * Amazon expects for a given product type.
 *
 * A profile pins one Amazon product type and records, for every attribute of
 * that type's schema, where its value comes from:
 *
 *   ps      — a PrestaShop field ("all my shirts have their own colour")
 *   allowed — one of Amazon's constrained values ("all of these are Women's")
 *   fixed   — a literal typed by the merchant
 *
 * Many categories may share a profile; a category has at most one, so a
 * product's profile is unambiguous.
 *
 * Multistore: profiles with id_shop 0 are shared by every shop and a shop can
 * add profiles of its own. A category's profile binding is shared too, and a
 * shop's own binding for the same category and marketplace replaces it in
 * that shop. A shop changes only its own profiles; shared ones are changed in
 * "All shops".
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonProfile
{
    /** @var string|null Why the last save or delete was refused */
    private static $lastError = null;

    /**
     * PrestaShop fields offerable as an attribute source. The labels here are
     * the English originals; use getPsFields() to show them to the merchant.
     */
    public static $psFields = array(
        'name' => 'Product name',
        'description' => 'Description',
        'description_short' => 'Short description',
        'manufacturer' => 'Brand / Manufacturer',
        'supplier' => 'Supplier',
        'reference' => 'Reference (SKU)',
        'ean13' => 'EAN / barcode',
        'upc' => 'UPC',
        'price' => 'Price',
        'quantity' => 'Quantity',
        'weight' => 'Weight',
        'width' => 'Width',
        'height' => 'Height',
        'depth' => 'Depth',
        'category' => 'Default category name',
    );

    /**
     * The same keys as $psFields, with labels translated for display.
     *
     * @return array field key => label
     */
    public static function getPsFields()
    {
        return array(
            'name' => AmazonI18n::get()->l('Product name', 'amazonprofile'),
            'description' => AmazonI18n::get()->l('Description', 'amazonprofile'),
            'description_short' => AmazonI18n::get()->l('Short description', 'amazonprofile'),
            'manufacturer' => AmazonI18n::get()->l('Brand / Manufacturer', 'amazonprofile'),
            'supplier' => AmazonI18n::get()->l('Supplier', 'amazonprofile'),
            'reference' => AmazonI18n::get()->l('Reference (SKU)', 'amazonprofile'),
            'ean13' => AmazonI18n::get()->l('EAN / barcode', 'amazonprofile'),
            'upc' => AmazonI18n::get()->l('UPC', 'amazonprofile'),
            'price' => AmazonI18n::get()->l('Price', 'amazonprofile'),
            'quantity' => AmazonI18n::get()->l('Quantity', 'amazonprofile'),
            'weight' => AmazonI18n::get()->l('Weight', 'amazonprofile'),
            'width' => AmazonI18n::get()->l('Width', 'amazonprofile'),
            'height' => AmazonI18n::get()->l('Height', 'amazonprofile'),
            'depth' => AmazonI18n::get()->l('Depth', 'amazonprofile'),
            'category' => AmazonI18n::get()->l('Default category name', 'amazonprofile'),
        );
    }

    /* ─────────────────── Schema ─────────────────── */

    public static function ensureTables()
    {
        $sql = array();
        $sql['amazonmarketplacepro_profile'] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` (
            `id_amazonmarketplacepro_profile` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `name` VARCHAR(128) NOT NULL DEFAULT \'\',
            `product_type` VARCHAR(128) NOT NULL DEFAULT \'\',
            `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
            `browse_nodes` VARCHAR(255) NOT NULL DEFAULT \'\',
            `is_variation` TINYINT(1) NOT NULL DEFAULT 0,
            `variation_attributes` VARCHAR(255) NOT NULL DEFAULT \'\',
            `attributes_json` LONGTEXT NULL,
            `raw_attributes_json` TEXT NULL,
            `latency` INT(11) NOT NULL DEFAULT -1,
            `shipping_template` VARCHAR(128) NOT NULL DEFAULT \'\',
            `price_markup` VARCHAR(16) NOT NULL DEFAULT \'\',
            `gtin_exemption` TINYINT(1) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_profile`),
            KEY `marketplace_id` (`marketplace_id`),
            KEY `id_shop` (`id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        $sql['amazonmarketplacepro_profile_category'] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` (
            `id_amazonmarketplacepro_profile_category` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `id_profile` INT(11) NOT NULL,
            `id_category` INT(11) NOT NULL,
            `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id_amazonmarketplacepro_profile_category`),
            UNIQUE KEY `cat_marketplace` (`id_shop`, `id_category`, `marketplace_id`),
            KEY `id_profile` (`id_profile`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $table => $q) {
            Db::getInstance()->execute($q);
            // A table created before multistore support gets its shop column.
            AmzproShop::ensureTableShop($table);
        }
    }

    /** Why the last save() or delete() was refused, or null. */
    public static function getLastError()
    {
        return self::$lastError;
    }

    /** @return int[] the id_shop values a change made in this context may touch */
    private static function writableShops()
    {
        $id = (int) AmzproShop::sharedWriteId();
        if (!AmzproShop::isMultistore()) {
            // One shop: every row it sees is its own, as before shops were known.
            return array_values(array_unique(array(0, $id)));
        }

        return array($id);
    }

    /**
     * The category bindings (alias pc) the current shop uses: its own binding
     * for a category and marketplace over the shared one.
     */
    private static function sqlEffectiveBinding()
    {
        return AmzproShop::sqlShared('pc') . '
            AND NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` pc2
                WHERE pc2.`id_category` = pc.`id_category`
                  AND pc2.`marketplace_id` = pc.`marketplace_id`
                  AND ' . AmzproShop::sqlShared('pc2') . '
                  AND pc2.`id_shop` > pc.`id_shop`)';
    }

    /* ─────────────────── CRUD ─────────────────── */

    /**
     * The profiles for all shops plus the current shop's own (only the shared
     * ones in "All shops"). Rows carry id_shop; category_count counts the
     * categories that use the profile in the current shop.
     */
    public static function getAll($marketplaceId)
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` pc
                      WHERE pc.`id_profile` = p.`id_amazonmarketplacepro_profile`
                        AND ' . self::sqlEffectiveBinding() . ') AS category_count
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` p
             WHERE p.`marketplace_id` = \'' . pSQL($marketplaceId) . '\'
               AND ' . AmzproShop::sqlShared('p') . '
             ORDER BY p.`name` ASC'
        );

        return is_array($rows) ? $rows : array();
    }

    /** A profile the current shop can see, or false. */
    public static function get($idProfile)
    {
        self::ensureTables();
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile`
             WHERE `id_amazonmarketplacepro_profile` = ' . (int) $idProfile . '
               AND ' . AmzproShop::sqlShared()
        );
        if (!$row) {
            return false;
        }
        $row['categories'] = self::getCategoryIds((int) $idProfile);
        $row['attributes'] = json_decode((string) $row['attributes_json'], true);
        if (!is_array($row['attributes'])) {
            $row['attributes'] = array();
        }

        return $row;
    }

    /** Categories that use this profile in the current shop. */
    public static function getCategoryIds($idProfile)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT pc.`id_category` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` pc
             WHERE pc.`id_profile` = ' . (int) $idProfile . '
               AND ' . self::sqlEffectiveBinding()
        );
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[] = (int) $r['id_category'];
            }
        }

        return $out;
    }

    /**
     * Create or update a profile.
     *
     * @param array $data Keys: id, name, product_type, marketplace_id, browse_nodes,
     *                    is_variation, variation_attributes, attributes (array),
     *                    raw_attributes_json, latency, shipping_template,
     *                    price_markup, gtin_exemption, categories (array of ids)
     * In a shop a new profile belongs to that shop, in "All shops" to every
     * shop. Returns false with getLastError() set when the profile cannot be
     * changed here.
     *
     * @return int|false Profile id
     */
    public static function save($data)
    {
        self::ensureTables();
        self::$lastError = null;
        $now = date('Y-m-d H:i:s');
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        if ($id && !self::checkChange($id)) {
            return false;
        }

        $attributes = isset($data['attributes']) && is_array($data['attributes'])
            ? $data['attributes'] : array();
        $latency = (isset($data['latency']) && $data['latency'] !== '') ? (int) $data['latency'] : -1;

        $fields = '`name` = \'' . pSQL(Tools::substr((string) $data['name'], 0, 128)) . '\',
            `product_type` = \'' . pSQL((string) $data['product_type']) . '\',
            `marketplace_id` = \'' . pSQL((string) $data['marketplace_id']) . '\',
            `browse_nodes` = \'' . pSQL((string) $data['browse_nodes']) . '\',
            `is_variation` = ' . (!empty($data['is_variation']) ? 1 : 0) . ',
            `variation_attributes` = \'' . pSQL((string) $data['variation_attributes']) . '\',
            `attributes_json` = \'' . pSQL(json_encode($attributes)) . '\',
            `raw_attributes_json` = \'' . pSQL((string) $data['raw_attributes_json']) . '\',
            `latency` = ' . $latency . ',
            `shipping_template` = \'' . pSQL((string) $data['shipping_template']) . '\',
            `price_markup` = \'' . pSQL((string) $data['price_markup']) . '\',
            `gtin_exemption` = ' . (!empty($data['gtin_exemption']) ? 1 : 0) . ',
            `date_upd` = \'' . pSQL($now) . '\'';

        if ($id) {
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` SET ' . $fields . '
                 WHERE `id_amazonmarketplacepro_profile` = ' . $id
            );
        } else {
            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile`
                 SET ' . $fields . ', `id_shop` = ' . (int) AmzproShop::sharedWriteId() . ',
                     `date_add` = \'' . pSQL($now) . '\''
            );
            $id = (int) Db::getInstance()->Insert_ID();
        }
        if (!$id) {
            return false;
        }

        // Category bindings: a category belongs to at most one profile, so
        // claiming it here detaches it from whichever profile held it before.
        // Bindings are saved for the scope being edited: a shop's binding
        // replaces the shared one in that shop only.
        $marketplaceId = pSQL((string) $data['marketplace_id']);
        $shops = implode(', ', self::writableShops());
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
             WHERE `id_profile` = ' . $id . ' AND `id_shop` IN (' . $shops . ')'
        );
        if (!empty($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $idCategory) {
                $idCategory = (int) $idCategory;
                if (!$idCategory) {
                    continue;
                }
                Db::getInstance()->execute(
                    'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
                     WHERE `id_category` = ' . $idCategory . '
                       AND `marketplace_id` = \'' . $marketplaceId . '\'
                       AND `id_shop` IN (' . $shops . ')'
                );
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
                        (`id_shop`, `id_profile`, `id_category`, `marketplace_id`)
                     VALUES (' . (int) AmzproShop::sharedWriteId() . ', ' . $id . ', ' . $idCategory . ', \'' . $marketplaceId . '\')'
                );
            }
        }

        return $id;
    }

    /**
     * True when the current context may change this profile: a shop its own
     * profiles, "All shops" the shared ones. Sets the error otherwise.
     */
    private static function checkChange($idProfile)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile`
             WHERE `id_amazonmarketplacepro_profile` = ' . (int) $idProfile . '
               AND ' . AmzproShop::sqlShared()
        );
        if (!$row) {
            self::$lastError = AmazonI18n::get()->l('This profile was not found. Reload the page and try again.', 'amazonprofile');

            return false;
        }
        if (!in_array((int) $row['id_shop'], self::writableShops(), true)) {
            self::$lastError = AmazonI18n::get()->l('This profile is shared by all shops. Select "All shops" at the top of the page to change it, or create a profile for this shop.', 'amazonprofile');

            return false;
        }

        return true;
    }

    /** Delete a profile, with the same rule as save(). */
    public static function delete($idProfile)
    {
        self::ensureTables();
        self::$lastError = null;
        $idProfile = (int) $idProfile;
        if (!self::checkChange($idProfile)) {
            return false;
        }
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
             WHERE `id_profile` = ' . $idProfile
        );

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile`
             WHERE `id_amazonmarketplacepro_profile` = ' . $idProfile
        );
    }

    /* ─────────────────── Value resolution ─────────────────── */

    /**
     * Resolve a profile's attribute map into Amazon listing attributes.
     *
     * @param array  $profileAttributes Stored map: name => array('src' => ps|allowed|fixed, 'value' => ...)
     * @param array  $row               Staged product row (ps_* columns, id_product)
     * @param string $marketplaceId
     * @param callable|null $featureResolver function($idProduct, $featureName) for feature: sources
     * @return array Amazon-shaped attributes
     */
    public static function resolveAttributes($profileAttributes, $row, $marketplaceId, $featureResolver = null)
    {
        $out = array();
        if (!is_array($profileAttributes)) {
            return $out;
        }

        foreach ($profileAttributes as $name => $spec) {
            if (!is_array($spec) || !isset($spec['src'])) {
                continue;
            }
            $value = '';

            if ($spec['src'] === 'ps') {
                $value = self::resolvePsField(
                    isset($spec['value']) ? (string) $spec['value'] : '',
                    $row,
                    $featureResolver
                );
            } elseif ($spec['src'] === 'allowed' || $spec['src'] === 'fixed') {
                $value = isset($spec['value']) ? trim((string) $spec['value']) : '';
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $entry = array('value' => $value, 'marketplace_id' => $marketplaceId);
            if (!empty($spec['unit'])) {
                $entry['unit'] = (string) $spec['unit'];
            }
            $out[$name] = array($entry);
        }

        return $out;
    }

    /**
     * Value of a PrestaShop source for a staged row.
     *
     * Sources are either a known field key, "feature:Name" (a PrestaShop
     * product feature) or "attribute:Group" (a combination attribute).
     */
    private static function resolvePsField($source, $row, $featureResolver)
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }

        if (strpos($source, 'feature:') === 0 && is_callable($featureResolver)) {
            return (string) call_user_func(
                $featureResolver,
                isset($row['id_product']) ? (int) $row['id_product'] : 0,
                trim(Tools::substr($source, 8))
            );
        }

        if (strpos($source, 'attribute:') === 0) {
            $group = Tools::strtolower(trim(Tools::substr($source, 10)));
            $vars = array();
            if (!empty($row['variation_attributes'])) {
                $decoded = json_decode($row['variation_attributes'], true);
                if (is_array($decoded)) {
                    $vars = $decoded;
                }
            }
            foreach ($vars as $k => $v) {
                if (Tools::strtolower($k) === $group) {
                    return (string) $v;
                }
            }
            return '';
        }

        // Plain PrestaShop fields map onto the staged row's ps_* columns.
        $map = array(
            'name' => 'ps_name',
            'description' => 'ps_description',
            'description_short' => 'ps_description_short',
            'manufacturer' => 'ps_manufacturer',
            'reference' => 'seller_sku',
            'ean13' => 'ps_ean13',
            'upc' => 'ps_ean13',
            'price' => 'ps_price',
            'quantity' => 'ps_quantity',
        );
        if (isset($map[$source]) && isset($row[$map[$source]])) {
            return trim(strip_tags((string) $row[$map[$source]]));
        }

        // Dimensions, supplier and category need a lookup on the product.
        $idProduct = isset($row['id_product']) ? (int) $row['id_product'] : 0;
        if (!$idProduct) {
            return '';
        }
        if (in_array($source, array('weight', 'width', 'height', 'depth'))) {
            return (string) (float) Db::getInstance()->getValue(
                'SELECT `' . bqSQL($source) . '` FROM `' . _DB_PREFIX_ . 'product`
                 WHERE `id_product` = ' . $idProduct
            );
        }
        if ($source === 'supplier') {
            return (string) Db::getInstance()->getValue(
                'SELECT s.`name` FROM `' . _DB_PREFIX_ . 'product` p
                 LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON (s.`id_supplier` = p.`id_supplier`)
                 WHERE p.`id_product` = ' . $idProduct
            );
        }
        if ($source === 'category') {
            // The staged row's shop: its default category and category name.
            $idShop = !empty($row['id_shop']) ? (int) $row['id_shop'] : (int) AmzproShop::actingId();
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT', null, AmzproShop::groupId($idShop), $idShop);

            return (string) Db::getInstance()->getValue(
                'SELECT cl.`name` FROM `' . _DB_PREFIX_ . 'product` p
                 LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                     ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ')
                 LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                     ON (cl.`id_category` = IFNULL(ps.`id_category_default`, p.`id_category_default`)
                         AND cl.`id_shop` = ' . $idShop . '
                         AND cl.`id_lang` = ' . $idLang . ')
                 WHERE p.`id_product` = ' . $idProduct
            );
        }

        return '';
    }
}
