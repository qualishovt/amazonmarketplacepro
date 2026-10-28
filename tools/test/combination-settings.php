<?php
/**
 * 1.6.17: combinations listed as separate products. With the switch on, the
 * product itself is not listed, its combinations are not a variation family,
 * and each combination can have its own settings (price, SKU, ASIN, GTIN
 * exemption, stock forcing...). A setting left empty follows the product.
 * Switched back to options, the saved values are kept but not used.
 *
 *     php tools/test/combination-settings.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$t = $p . 'amazonmarketplacepro_product';
$ovT = $p . 'amazonmarketplacepro_product_setting';
$idShop = (int) AmzproShop::actingId();
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
AmazonProductOverride::ensureTable();

// A product of this shop with at least two combinations and a reference.
$idProduct = (int) $db->getValue('SELECT pa.`id_product` FROM `' . $p . 'product_attribute` pa
    INNER JOIN `' . $p . 'product_shop` ps ON (ps.`id_product` = pa.`id_product` AND ps.`id_shop` = ' . $idShop . ' AND ps.`active` = 1)
    INNER JOIN `' . $p . 'product` pr ON (pr.`id_product` = pa.`id_product` AND pr.`reference` <> \'\')
    GROUP BY pa.`id_product` HAVING COUNT(*) >= 2 ORDER BY pa.`id_product` ASC');
$combos = array_map('intval', array_column($db->executeS('SELECT `id_product_attribute` FROM `' . $p . 'product_attribute`
    WHERE `id_product` = ' . $idProduct . ' ORDER BY `id_product_attribute`'), 'id_product_attribute'));
echo 'product #' . $idProduct . ', ' . count($combos) . " combinations\n";
list($a, $b) = $combos;

$db->execute('DROP TABLE IF EXISTS `' . $ovT . '_snap`');
$db->execute('CREATE TABLE `' . $ovT . '_snap` AS SELECT * FROM `' . $ovT . '`');
$keepPost = $_POST;
$refs = $db->executeS('SELECT `id_product_attribute`, `reference`, `ean13` FROM `' . $p . 'product_attribute` WHERE `id_product` = ' . $idProduct);
$db->execute('UPDATE `' . $p . 'product_attribute` SET `reference` = CONCAT(\'ZZC-\', `id_product_attribute`), `ean13` = \'4006381333931\' WHERE `id_product` = ' . $idProduct);

/** Run a private method. */
function invokePrivate($object, $method, array $args = [])
{
    $m = new ReflectionMethod($object, $method);
    $m->setAccessible(true);

    return $m->invokeArgs($object, $args);
}

/** The product page posts this; $mode 1 = separate products. */
function postTab($mode, $own)
{
    $_POST = ['amzpro_tab_present' => 1, 'amzpro_id_shop' => AmzproShop::id(), 'amzpro_sync' => 1,
        'amzpro_sync_price' => 1, 'amzpro_sync_quantity' => 1, 'amzpro_asin' => 'B0PRODUCT1',
        'amzpro_combos_present' => 1, 'amzpro_combo_send' => $GLOBALS['combos'],
        'amzpro_separate_combinations' => $mode, 'amzpro_combo' => $own, ];
}

/** Staged rows of the product: id_product_attribute => row. */
function staged()
{
    $out = [];
    foreach (Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
        WHERE `id_product` = ' . (int) $GLOBALS['idProduct'] . ' AND `id_shop` = ' . (int) AmzproShop::actingId() . '
          AND `marketplace_id` = \'\' AND `ps_exists` = 1') as $r) {
        $out[(int) $r['id_product_attribute']] = $r;
    }

    return $out;
}

try {
    $module = Module::getInstanceByName('amazonmarketplacepro');
    $save = new ReflectionMethod($module, 'saveProductOverrides');
    $save->setAccessible(true);

    // 1. what is stored: only the fields a combination sets itself
    check('empty fields are not stored', AmazonProductOverride::normalizeCombinationValues([
        'sync_price' => '', 'stock_force' => '', 'override_price' => '', 'override_sku' => ' ', 'lead_time' => '',
    ]), []);
    check('set fields are stored', AmazonProductOverride::normalizeCombinationValues([
        'sync_price' => '0', 'stock_force' => 'out', 'override_price' => '12.34', 'override_sku' => ' ZZ-OWN ', 'lead_time' => '3',
    ]), ['force_in_stock' => 0, 'force_out_of_stock' => 1, 'sync_price' => 0, 'override_price' => 12.34, 'lead_time' => 3, 'override_sku' => 'ZZ-OWN']);

    // 2. the product page saves the mode and each combination's settings
    $db->execute('DELETE FROM `' . $ovT . '` WHERE `id_product` = ' . $idProduct);
    $own = [
        $a => ['override_price' => '12.34', 'override_sku' => 'ZZ-OWN-SKU', 'asin' => 'B0COMBO001', 'gtin_exempt' => '1',
            'stock_force' => 'out', 'lead_time' => '', 'condition_type' => 'used_good', 'gpsr_contact' => 'safety@example.com', ],
        $b => ['override_price' => '', 'stock_force' => '', 'gtin_exempt' => ''],
    ];
    postTab(1, $own);
    $save->invoke($module, $idProduct);
    $ov = AmazonProductOverride::get($idProduct);
    check('separate products saved', (int) $ov['separate_combinations'], 1);
    check('a combination with nothing of its own stores nothing', array_keys(json_decode($ov['combination_settings'], true)), [$a]);
    check('its own values', AmazonProductOverride::combinationSettings($ov, $a)['override_sku'], 'ZZ-OWN-SKU');

    $html = $module->hookDisplayAdminProductsExtra(['id_product' => $idProduct]);
    check('the tab shows the mode', strpos($html, '<option value="1" selected="selected">') !== false, true);
    check('the tab shows the combination\'s SKU', strpos($html, 'name="amzpro_combo[' . $a . '][override_sku]" value="ZZ-OWN-SKU"') !== false, true);
    check('a field it does not set is empty', strpos($html, 'name="amzpro_combo[' . $b . '][override_sku]" value=""') !== false, true);

    // 3. the merge
    $merged = AmazonProductOverride::forCombination($ov, $a);
    check('own value wins', [(float) $merged['override_price'], $merged['asin'], (int) $merged['force_out_of_stock']], [12.34, 'B0COMBO001', 1]);
    $other = AmazonProductOverride::forCombination($ov, $b);
    check('the product\'s ASIN is never a combination\'s', $other['asin'], '');
    check('the rest follows the product', [(int) $other['sync_price'], (float) $other['override_price']], [1, 0.0]);

    // 4. the scan
    withStagedSnapshot(function () use ($db, $a, $b, $idProduct) {
        AmzproShop::get('AMZPRO_MARKETPLACE_ID');
        $sync = new AmazonProductSync(testClient(), (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID'), 'SELLERTEST');
        $sync->syncPrestashopSide();
        $rows = staged();
        check('the product itself is not listed', isset($rows[0]), false);
        check('combination A under its own SKU', isset($rows[$a]) ? $rows[$a]['seller_sku'] : null, 'ZZ-OWN-SKU');
        check('combination A: own price, out of stock, no EAN', isset($rows[$a])
            ? [(float) $rows[$a]['ps_price'], (int) $rows[$a]['ps_quantity'], $rows[$a]['ps_ean13']] : null, [12.34, 0, '']);
        check('combination B under its usual SKU', isset($rows[$b]) ? $rows[$b]['seller_sku'] : null, 'ZZC-' . $b);
        check('combination B keeps its EAN', isset($rows[$b]) ? $rows[$b]['ps_ean13'] : null, '4006381333931');
        check('no variation family', isset($rows[$b]) ? [$rows[$b]['parent_sku'], (int) $rows[$b]['is_parent']] : null, ['', 0]);

        // 5. the listing sent for A carries its own settings
        $pushRows = invokePrivate($sync, 'withCombinationSettings', [[
            ['id_product' => $idProduct, 'id_product_attribute' => $a, 'seller_sku' => 'ZZ-OWN-SKU',
                'ov_asin' => 'B0PRODUCT1', 'ov_condition_type' => '', 'ov_gtin_exempt' => 0, ],
            ['id_product' => $idProduct, 'id_product_attribute' => $b, 'seller_sku' => 'ZZC-' . $b,
                'ov_asin' => 'B0PRODUCT1', 'ov_condition_type' => '', 'ov_gtin_exempt' => 0, ],
        ]]);
        check('A: own ASIN, condition, GTIN exemption, GPSR contact', [$pushRows[0]['ov_asin'], $pushRows[0]['ov_condition_type'],
            (int) $pushRows[0]['ov_gtin_exempt'], $pushRows[0]['ov_gpsr_contact'], ], ['B0COMBO001', 'used_good', 1, 'safety@example.com']);
        check('B: not the product\'s ASIN', $pushRows[1]['ov_asin'], '');
    });

    // 6. an Amazon order for the combination's own SKU
    $im = new AmazonOrderImporter(testClient(), $main, $idShop);
    $found = invokePrivate($im, 'resolveProduct', ['ZZ-OWN-SKU']);
    check('an order for its SKU is the combination', [$found['id_product'], $found['id_product_attribute']], [$idProduct, $a]);

    // 7. back to options: values kept, not used
    postTab(0, $own);
    $save->invoke($module, $idProduct);
    $ov = AmazonProductOverride::get($idProduct);
    check('switching back keeps the values', AmazonProductOverride::combinationSettings($ov, $a)['override_sku'], 'ZZ-OWN-SKU');
    check('but they are not used', [AmazonProductOverride::forCombination($ov, $a)['override_sku'], (float) AmazonProductOverride::forCombination($ov, $a)['override_price']], ['', 0.0]);
    withStagedSnapshot(function () use ($a) {
        $sync = new AmazonProductSync(testClient(), (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID'), 'SELLERTEST');
        $sync->syncPrestashopSide();
        $rows = staged();
        check('as options: the usual SKU', isset($rows[$a]) ? $rows[$a]['seller_sku'] : null, 'ZZC-' . $a);
        check('as options: the product is listed again', isset($rows[0]), true);
    });
} finally {
    $_POST = $keepPost;
    foreach ($refs as $r) {
        $db->execute('UPDATE `' . $p . 'product_attribute` SET `reference` = \'' . pSQL($r['reference']) . '\', `ean13` = \'' . pSQL($r['ean13']) . '\'
            WHERE `id_product_attribute` = ' . (int) $r['id_product_attribute']);
    }
    $db->execute('DELETE FROM `' . $ovT . '`');
    $db->execute('INSERT INTO `' . $ovT . '` SELECT * FROM `' . $ovT . '_snap`');
    $db->execute('DROP TABLE `' . $ovT . '_snap`');
}
done();
