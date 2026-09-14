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
 *
 * Multistore: a product's row with id_shop 0 is shared by every shop, and a
 * shop can save a row of its own that replaces it whole for that shop. Saving
 * in "All shops" changes the shared row; saving in a shop changes the shop's.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonProductOverride
{
    /** Columns the propagation tool can copy onto other products. */
    public static $propagatable = [
        'sync', 'sync_price', 'sync_quantity', 'force_in_stock', 'force_out_of_stock',
        'is_fba', 'lead_time', 'gift_option', 'shipping_template', 'browse_node',
        'brand', 'condition_type', 'condition_note', 'gpsr_contact',
    ];

    /** Cache: id_shop => id_product => row */
    private static $cache = [];

    /** @var bool Table checked this request */
    private static $tableEnsured = false;

    private static $intCols = ['sync', 'force_in_stock', 'force_out_of_stock', 'sync_price',
        'sync_quantity', 'is_fba', 'gift_option'];

    private static $strCols = ['gpsr_contact', 'asin', 'override_sku', 'transparency_code',
        'shipping_template', 'browse_node', 'brand', 'bullet_points',
        'condition_type', 'condition_note'];

    public static function ensureTable()
    {
        if (self::$tableEnsured) {
            return;
        }
        self::$tableEnsured = true;

        // The 1.1.0 table only carried sync + gpsr_contact; 1.2.0 grows it
        // into the full per-product override sheet.
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` (
                `id_amazonmarketplacepro_product_setting` INT(11) NOT NULL AUTO_INCREMENT,
                `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                `id_product` INT(11) NOT NULL,
                `sync` TINYINT(1) NOT NULL DEFAULT 1,
                `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_product_setting`),
                UNIQUE KEY `id_product` (`id_shop`, `id_product`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );
        // A table created before multistore support gets its shop column.
        AmzproShop::ensureTableShop('amazonmarketplacepro_product_setting');

        $columns = [
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
        ];

        $existing = [];
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

    /** The values of a product without any saved override. */
    private static function defaults($idProduct)
    {
        return [
            'id_product' => (int) $idProduct,
            'sync' => 1, 'gpsr_contact' => '', 'asin' => '', 'override_price' => 0,
            'override_sku' => '', 'force_in_stock' => 0, 'force_out_of_stock' => 0,
            'sync_price' => 1, 'sync_quantity' => 1, 'is_fba' => 0, 'lead_time' => -1,
            'gift_option' => 0, 'transparency_code' => '', 'shipping_template' => '',
            'browse_node' => '', 'brand' => '', 'bullet_points' => '',
            'condition_type' => '', 'condition_note' => '',
        ];
    }

    /**
     * Override row for a product as the current shop sees it: its own row,
     * else the shared one, else defaults. In "All shops" the shared row.
     *
     * Besides the columns it carries id_shop (of the row, 0 when none) and
     * scope: 'shop' (this shop's own values), 'shared' (the values for all
     * shops) or 'none' (nothing saved).
     *
     * @return array
     */
    public static function get($idProduct)
    {
        self::ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
             WHERE `id_product` = ' . (int) $idProduct . '
               AND ' . AmzproShop::sqlShared() . '
             ORDER BY `id_shop` DESC'
        );
        if (is_array($rows) && $rows) {
            $row = $rows[0];
            $row['scope'] = ((int) $row['id_shop'] > 0 && AmzproShop::isMultistore()) ? 'shop' : 'shared';
        } else {
            $row = self::defaults($idProduct);
            $row['id_shop'] = 0;
            $row['scope'] = 'none';
        }

        return $row;
    }

    /**
     * Write a product's overrides for the scope being edited.
     *
     * In a shop the first save creates the shop's own row, starting from the
     * shared values so the fields not being saved keep them. A shop saving
     * exactly what it already inherits gets no row, so it keeps following
     * the shared values. With multistore off the existing row is updated.
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

        // Column => normalised value, and column => SQL literal.
        $values = [];
        $assign = [];
        foreach (self::$intCols as $c) {
            if (array_key_exists($c, $data)) {
                $values[$c] = (int) $data[$c] ? 1 : 0;
                $assign[$c] = (string) $values[$c];
            }
        }
        foreach (self::$strCols as $c) {
            if (array_key_exists($c, $data)) {
                $values[$c] = (string) $data[$c];
                $assign[$c] = '\'' . pSQL($values[$c]) . '\'';
            }
        }
        if (array_key_exists('lead_time', $data)) {
            $values['lead_time'] = ($data['lead_time'] === '' || $data['lead_time'] === null) ? -1 : (int) $data['lead_time'];
            $assign['lead_time'] = (string) $values['lead_time'];
        }
        if (array_key_exists('override_price', $data)) {
            $values['override_price'] = (float) $data['override_price'];
            $assign['override_price'] = (string) $values['override_price'];
        }
        if (empty($assign)) {
            return true;
        }

        self::$cache = [];
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'amazonmarketplacepro_product_setting';

        $byShop = [];
        $existing = $db->executeS(
            'SELECT * FROM `' . $table . '`
             WHERE `id_product` = ' . $idProduct . ' AND ' . AmzproShop::sqlShared()
        );
        foreach ((is_array($existing) ? $existing : []) as $r) {
            $byShop[(int) $r['id_shop']] = $r;
        }

        if (AmzproShop::isMultistore()) {
            $idShop = (int) AmzproShop::sharedWriteId();
            if ($idShop > 0 && !isset($byShop[$idShop])) {
                $inherited = isset($byShop[0]) ? $byShop[0] : self::defaults($idProduct);
                if (self::matches($inherited, $values)) {
                    return true;
                }
                if (isset($byShop[0])) {
                    foreach (array_keys(self::defaults($idProduct)) as $c) {
                        if ($c === 'id_product' || isset($assign[$c]) || !array_key_exists($c, $byShop[0])) {
                            continue;
                        }
                        $assign[$c] = ($byShop[0][$c] === null) ? 'NULL' : '\'' . pSQL((string) $byShop[0][$c], true) . '\'';
                    }
                }
            }
        } else {
            // One shop: update the row it already has, whichever scope it was saved in.
            $idShop = $byShop ? max(array_keys($byShop)) : (int) AmzproShop::sharedWriteId();
        }

        $sets = [];
        foreach ($assign as $c => $sql) {
            $sets[] = '`' . bqSQL($c) . '` = ' . $sql;
        }

        if (isset($byShop[$idShop])) {
            return $db->execute(
                'UPDATE `' . $table . '`
                 SET ' . implode(', ', $sets) . ', `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_product` = ' . $idProduct . ' AND `id_shop` = ' . (int) $idShop
            );
        }

        return $db->execute(
            'INSERT INTO `' . $table . '`
             SET `id_shop` = ' . (int) $idShop . ', `id_product` = ' . $idProduct . ', ' . implode(', ', $sets) . ',
                 `date_add` = \'' . pSQL($now) . '\', `date_upd` = \'' . pSQL($now) . '\''
        );
    }

    /** True when every saved value equals the row's. */
    private static function matches($row, $values)
    {
        foreach ($values as $c => $v) {
            $current = isset($row[$c]) ? $row[$c] : null;
            if ($c === 'override_price') {
                if (abs((float) $current - (float) $v) > 0.000001) {
                    return false;
                }
            } elseif (is_int($v)) {
                if ((int) $current !== $v) {
                    return false;
                }
            } elseif ((string) $current !== (string) $v) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a product's overrides from the scope being edited. In a shop only
     * the shop's own row goes, and the product follows the shared values
     * again; in "All shops" the shared row goes and shop rows stay.
     *
     * @return bool
     */
    public static function delete($idProduct)
    {
        self::ensureTable();
        self::$cache = [];
        $id = (int) AmzproShop::sharedWriteId();
        $shops = AmzproShop::isMultistore() ? [$id] : array_values(array_unique([0, $id]));

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
             WHERE `id_product` = ' . (int) $idProduct . '
               AND `id_shop` IN (' . implode(', ', $shops) . ')'
        );
    }

    /**
     * Copy selected override fields from one product onto a wider set of the
     * current shop's products (every product in "All shops").
     *
     * @param int $idProduct Source product
     * @param string $scope category | manufacturer | supplier | all
     * @param array $fields Column names to copy (subset of $propagatable)
     *
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
        $payload = [];
        foreach ($fields as $f) {
            $payload[$f] = $source[$f];
        }

        $count = 0;
        foreach ($targets as $idTarget) {
            if ((int) $idTarget === (int) $idProduct) {
                continue;
            }
            self::save((int) $idTarget, $payload);
            ++$count;
        }

        return $count;
    }

    /** Product ids covered by a propagation scope, limited to the current shop. */
    private static function targetProducts($idProduct, $scope)
    {
        $idProduct = (int) $idProduct;
        $idShop = (int) AmzproShop::id();
        $p = _DB_PREFIX_;
        // In a shop only its products, with the shop's active flag and default category.
        $inShop = $idShop
            ? ' INNER JOIN `' . $p . 'product_shop` ps ON (ps.`id_product` = x.`id_product` AND ps.`id_shop` = ' . $idShop . ')'
            : '';
        if ($scope === 'all') {
            $sql = $idShop
                ? 'SELECT x.`id_product` FROM `' . $p . 'product_shop` x WHERE x.`id_shop` = ' . $idShop . ' AND x.`active` = 1'
                : 'SELECT `id_product` FROM `' . $p . 'product` WHERE `active` = 1';
        } elseif ($scope === 'manufacturer') {
            $sql = 'SELECT x.`id_product` FROM `' . $p . 'product` x' . $inShop . '
                    INNER JOIN `' . $p . 'product` src
                        ON (src.`id_manufacturer` = x.`id_manufacturer` AND src.`id_product` = ' . $idProduct . ')
                    WHERE x.`id_manufacturer` > 0';
        } elseif ($scope === 'supplier') {
            $sql = 'SELECT x.`id_product` FROM `' . $p . 'product` x' . $inShop . '
                    INNER JOIN `' . $p . 'product` src
                        ON (src.`id_supplier` = x.`id_supplier` AND src.`id_product` = ' . $idProduct . ')
                    WHERE x.`id_supplier` > 0';
        } else { // category
            $defaultCategory = $idShop
                ? 'IFNULL(
                        (SELECT `id_category_default` FROM `' . $p . 'product_shop`
                         WHERE `id_product` = ' . $idProduct . ' AND `id_shop` = ' . $idShop . '),
                        (SELECT `id_category_default` FROM `' . $p . 'product` WHERE `id_product` = ' . $idProduct . ')
                    )'
                : '(SELECT `id_category_default` FROM `' . $p . 'product` WHERE `id_product` = ' . $idProduct . ')';
            $sql = 'SELECT x.`id_product` FROM `' . $p . 'category_product` x' . $inShop . '
                    WHERE x.`id_category` = ' . $defaultCategory;
        }

        $rows = Db::getInstance()->executeS($sql);
        $out = [];
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

    /** All overrides the current shop uses, keyed by product id (cached per request and shop). */
    public static function all()
    {
        $idShop = (int) AmzproShop::id();
        if (isset(self::$cache[$idShop])) {
            return self::$cache[$idShop];
        }
        self::ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
             WHERE ' . AmzproShop::sqlShared()
        );
        $out = [];
        if (is_array($rows)) {
            foreach (AmzproShop::preferShopRows($rows, ['id_product']) as $r) {
                $out[(int) $r['id_product']] = $r;
            }
        }
        self::$cache[$idShop] = $out;

        return $out;
    }
}
