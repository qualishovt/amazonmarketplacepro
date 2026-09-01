<?php
/**
 * 2007-2026 PrestaShop
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonProfile
{
    /** PrestaShop fields offerable as an attribute source. */
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

    /* ─────────────────── Schema ─────────────────── */

    public static function ensureTables()
    {
        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` (
            `id_amazonmarketplacepro_profile` INT(11) NOT NULL AUTO_INCREMENT,
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
            KEY `marketplace_id` (`marketplace_id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` (
            `id_amazonmarketplacepro_profile_category` INT(11) NOT NULL AUTO_INCREMENT,
            `id_profile` INT(11) NOT NULL,
            `id_category` INT(11) NOT NULL,
            `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`id_amazonmarketplacepro_profile_category`),
            UNIQUE KEY `cat_marketplace` (`id_category`, `marketplace_id`),
            KEY `id_profile` (`id_profile`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $q) {
            Db::getInstance()->execute($q);
        }
    }

    /* ─────────────────── CRUD ─────────────────── */

    public static function getAll($marketplaceId)
    {
        self::ensureTables();
        $rows = Db::getInstance()->executeS(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` pc
                      WHERE pc.`id_profile` = p.`id_amazonmarketplacepro_profile`) AS category_count
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` p
             WHERE p.`marketplace_id` = \'' . pSQL($marketplaceId) . '\'
             ORDER BY p.`name` ASC'
        );

        return is_array($rows) ? $rows : array();
    }

    public static function get($idProfile)
    {
        self::ensureTables();
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile`
             WHERE `id_amazonmarketplacepro_profile` = ' . (int) $idProfile
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

    public static function getCategoryIds($idProfile)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_category` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
             WHERE `id_profile` = ' . (int) $idProfile
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
     * @return int|false Profile id
     */
    public static function save($data)
    {
        self::ensureTables();
        $now = date('Y-m-d H:i:s');
        $id = isset($data['id']) ? (int) $data['id'] : 0;

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
                 SET ' . $fields . ', `date_add` = \'' . pSQL($now) . '\''
            );
            $id = (int) Db::getInstance()->Insert_ID();
        }
        if (!$id) {
            return false;
        }

        // Category bindings: a category belongs to at most one profile, so
        // claiming it here detaches it from whichever profile held it before.
        $marketplaceId = pSQL((string) $data['marketplace_id']);
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
             WHERE `id_profile` = ' . $id
        );
        if (!empty($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $idCategory) {
                $idCategory = (int) $idCategory;
                if (!$idCategory) {
                    continue;
                }
                Db::getInstance()->execute(
                    'REPLACE INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`
                        (`id_profile`, `id_category`, `marketplace_id`)
                     VALUES (' . $id . ', ' . $idCategory . ', \'' . $marketplaceId . '\')'
                );
            }
        }

        return $id;
    }

    public static function delete($idProfile)
    {
        $idProfile = (int) $idProfile;
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
            return (string) Db::getInstance()->getValue(
                'SELECT cl.`name` FROM `' . _DB_PREFIX_ . 'product` p
                 LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                     ON (cl.`id_category` = p.`id_category_default`
                         AND cl.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT') . ')
                 WHERE p.`id_product` = ' . $idProduct
            );
        }

        return '';
    }
}
