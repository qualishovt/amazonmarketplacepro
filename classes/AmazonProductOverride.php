<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Per-product Amazon settings, edited from the Amazon tab of the PrestaShop
 * product page.
 *
 * Everything here is an override: a value set on a product wins over the
 * profile, the category/manufacturer/supplier rules and the global settings.
 * Empty means "inherit", which is why numeric opt-outs use -1 rather than 0.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonProductOverride
{
    /** Columns the propagation tool can copy onto other products. */
    public static $propagatable = array(
        'sync', 'sync_price', 'sync_quantity', 'force_in_stock', 'force_out_of_stock',
        'is_fba', 'lead_time', 'gift_option', 'shipping_template', 'browse_node',
        'brand', 'condition_type', 'condition_note', 'gpsr_contact',
    );

    private static $cache = null;

    public static function ensureTable()
    {
        // The 1.1.0 table only carried sync + gpsr_contact; 1.2.0 grows it
        // into the full per-product override sheet.
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` (
                `id_amazonmarketplacepro_product_setting` INT(11) NOT NULL AUTO_INCREMENT,
                `id_product` INT(11) NOT NULL,
                `sync` TINYINT(1) NOT NULL DEFAULT 1,
                `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_product_setting`),
                UNIQUE KEY `id_product` (`id_product`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        $columns = array(
            'asin' => 'VARCHAR(32) NOT NULL DEFAULT \'\'',
            'override_price' => 'DECIMAL(20,6) NOT NULL DEFAULT 0',
            'override_sku' => 'VARCHAR(255) NOT NULL DEFAULT \'\'',
            'force_in_stock' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'force_out_of_stock' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'sync_price' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'sync_quantity' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'is_fba' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'lead_time' => 'INT(11) NOT NULL DEFAULT -1',
            'gift_option' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'transparency_code' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
            'shipping_template' => 'VARCHAR(128) NOT NULL DEFAULT \'\'',
            'browse_node' => 'VARCHAR(255) NOT NULL DEFAULT \'\'',
            'brand' => 'VARCHAR(128) NOT NULL DEFAULT \'\'',
            'bullet_points' => 'TEXT NULL',
            'condition_type' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
            'condition_note' => 'TEXT NULL',
        );

        $existing = array();
        $rows = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`'
        );
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $existing[$r['Field']] = true;
            }
        }
        foreach ($columns as $name => $definition) {
            if (!isset($existing[$name])) {
                Db::getInstance()->execute(
                    'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
                     ADD `' . bqSQL($name) . '` ' . $definition
                );
            }
        }
    }

    /** @return array Override row for a product, with sane defaults when unset */
    public static function get($idProduct)
    {
        self::ensureTable();
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
             WHERE `id_product` = ' . (int) $idProduct
        );
        if (!$row) {
            $row = array(
                'id_product' => (int) $idProduct,
                'sync' => 1, 'gpsr_contact' => '', 'asin' => '', 'override_price' => 0,
                'override_sku' => '', 'force_in_stock' => 0, 'force_out_of_stock' => 0,
                'sync_price' => 1, 'sync_quantity' => 1, 'is_fba' => 0, 'lead_time' => -1,
                'gift_option' => 0, 'transparency_code' => '', 'shipping_template' => '',
                'browse_node' => '', 'brand' => '', 'bullet_points' => '',
                'condition_type' => '', 'condition_note' => '',
            );
        }

        return $row;
    }

    /**
     * Write a product's overrides.
     *
     * @param array $data Any subset of the override columns
     */
    public static function save($idProduct, $data)
    {
        self::ensureTable();
        $idProduct = (int) $idProduct;
        if (!$idProduct) {
            return false;
        }
        $now = date('Y-m-d H:i:s');

        $intCols = array('sync', 'force_in_stock', 'force_out_of_stock', 'sync_price',
            'sync_quantity', 'is_fba', 'gift_option');
        $strCols = array('gpsr_contact', 'asin', 'override_sku', 'transparency_code',
            'shipping_template', 'browse_node', 'brand', 'bullet_points',
            'condition_type', 'condition_note');

        $sets = array();
        foreach ($intCols as $c) {
            if (array_key_exists($c, $data)) {
                $sets[] = '`' . $c . '` = ' . ((int) $data[$c] ? 1 : 0);
            }
        }
        foreach ($strCols as $c) {
            if (array_key_exists($c, $data)) {
                $sets[] = '`' . $c . '` = \'' . pSQL((string) $data[$c]) . '\'';
            }
        }
        if (array_key_exists('lead_time', $data)) {
            $lead = ($data['lead_time'] === '' || $data['lead_time'] === null) ? -1 : (int) $data['lead_time'];
            $sets[] = '`lead_time` = ' . $lead;
        }
        if (array_key_exists('override_price', $data)) {
            $sets[] = '`override_price` = ' . (float) $data['override_price'];
        }
        if (empty($sets)) {
            return true;
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT `id_amazonmarketplacepro_product_setting`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
             WHERE `id_product` = ' . $idProduct
        );

        self::$cache = null;

        if ($exists) {
            return Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
                 SET ' . implode(', ', $sets) . ', `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_product` = ' . $idProduct
            );
        }

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
             SET `id_product` = ' . $idProduct . ', ' . implode(', ', $sets) . ',
                 `date_add` = \'' . pSQL($now) . '\', `date_upd` = \'' . pSQL($now) . '\''
        );
    }

    /**
     * Copy selected override fields from one product onto a wider set.
     *
     * @param int    $idProduct Source product
     * @param string $scope     category | manufacturer | supplier | all
     * @param array  $fields    Column names to copy (subset of $propagatable)
     * @return int Number of products written
     */
    public static function propagate($idProduct, $scope, $fields)
    {
        $source = self::get($idProduct);
        $fields = array_intersect((array) $fields, self::$propagatable);
        if (empty($fields)) {
            return 0;
        }

        $targets = self::targetProducts($idProduct, $scope);
        $payload = array();
        foreach ($fields as $f) {
            $payload[$f] = $source[$f];
        }

        $count = 0;
        foreach ($targets as $idTarget) {
            if ((int) $idTarget === (int) $idProduct) {
                continue;
            }
            self::save((int) $idTarget, $payload);
            $count++;
        }

        return $count;
    }

    /** Product ids covered by a propagation scope. */
    private static function targetProducts($idProduct, $scope)
    {
        $idProduct = (int) $idProduct;
        if ($scope === 'all') {
            $sql = 'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product` WHERE `active` = 1';
        } elseif ($scope === 'manufacturer') {
            $sql = 'SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p
                    INNER JOIN `' . _DB_PREFIX_ . 'product` src
                        ON (src.`id_manufacturer` = p.`id_manufacturer` AND src.`id_product` = ' . $idProduct . ')
                    WHERE p.`id_manufacturer` > 0';
        } elseif ($scope === 'supplier') {
            $sql = 'SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p
                    INNER JOIN `' . _DB_PREFIX_ . 'product` src
                        ON (src.`id_supplier` = p.`id_supplier` AND src.`id_product` = ' . $idProduct . ')
                    WHERE p.`id_supplier` > 0';
        } else { // category
            $sql = 'SELECT cp.`id_product` FROM `' . _DB_PREFIX_ . 'category_product` cp
                    WHERE cp.`id_category` = (
                        SELECT `id_category_default` FROM `' . _DB_PREFIX_ . 'product`
                        WHERE `id_product` = ' . $idProduct . '
                    )';
        }

        $rows = Db::getInstance()->executeS($sql);
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[] = (int) $r['id_product'];
            }
        }

        return array_unique($out);
    }

    /**
     * Effective SKU for a product: the Amazon-side override when the merchant
     * already sells it under a different seller SKU, else the built one.
     */
    public static function effectiveSku($idProduct, $builtSku, $overrides)
    {
        if (isset($overrides[(int) $idProduct]['override_sku'])
            && trim((string) $overrides[(int) $idProduct]['override_sku']) !== '') {
            return trim($overrides[(int) $idProduct]['override_sku']);
        }

        return $builtSku;
    }

    /** All overrides keyed by product id (cached per request). */
    public static function all()
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        self::ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`'
        );
        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id_product']] = $r;
            }
        }
        self::$cache = $out;

        return $out;
    }
}
