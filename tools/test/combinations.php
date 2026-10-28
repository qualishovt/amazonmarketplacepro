<?php
/**
 * 1.6.16: choosing which combinations of a product go to Amazon. The product
 * tab lists them, ticked by default; an unticked one is not collected by the
 * scan, so it is never sent, and if it was live it becomes a deletion
 * candidate.
 *
 *     php tools/test/combinations.php <shop root>
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

// A product of this shop with at least three combinations.
$idProduct = (int) $db->getValue('SELECT pa.`id_product` FROM `' . $p . 'product_attribute` pa
    INNER JOIN `' . $p . 'product_shop` ps ON (ps.`id_product` = pa.`id_product` AND ps.`id_shop` = ' . $idShop . ' AND ps.`active` = 1)
    GROUP BY pa.`id_product` HAVING COUNT(*) >= 3 ORDER BY pa.`id_product` ASC');
$combos = array_map('intval', array_column($db->executeS('SELECT `id_product_attribute` FROM `' . $p . 'product_attribute`
    WHERE `id_product` = ' . $idProduct . ' ORDER BY `id_product_attribute`'), 'id_product_attribute'));
echo 'product #' . $idProduct . ', ' . count($combos) . " combinations\n";
$keep = $combos[1];

$db->execute('DROP TABLE IF EXISTS `' . $ovT . '_snap`');
$db->execute('CREATE TABLE `' . $ovT . '_snap` AS SELECT * FROM `' . $ovT . '`');
$keepPost = $_POST;
// Combinations need a reference to get a SKU; give them one for the test.
$refs = $db->executeS('SELECT `id_product_attribute`, `reference` FROM `' . $p . 'product_attribute` WHERE `id_product` = ' . $idProduct);
$db->execute('UPDATE `' . $p . 'product_attribute` SET `reference` = CONCAT(\'ZZC-\', `id_product_attribute`) WHERE `id_product` = ' . $idProduct);

try {
    $module = Module::getInstanceByName('amazonmarketplacepro');

    // 1. the tab lists every combination, all ticked
    $db->execute('DELETE FROM `' . $ovT . '` WHERE `id_product` = ' . $idProduct);
    $html = $module->hookDisplayAdminProductsExtra(['id_product' => $idProduct]);
    check('the tab lists every combination', substr_count($html, 'name="amzpro_combo_send[]"'), count($combos));
    check('all ticked by default', preg_match_all('/name="amzpro_combo_send\[\]" value="\d+" checked/', $html), count($combos));

    // 2. saving with only one ticked keeps the others off Amazon
    $_POST = ['amzpro_tab_present' => 1, 'amzpro_id_shop' => AmzproShop::id(), 'amzpro_sync' => 1,
        'amzpro_sync_price' => 1, 'amzpro_sync_quantity' => 1, 'amzpro_combos_present' => 1, 'amzpro_combo_send' => [$keep], ];
    $save = new ReflectionMethod($module, 'saveProductOverrides');
    $save->setAccessible(true);
    $save->invoke($module, $idProduct);
    $ov = AmazonProductOverride::get($idProduct);
    $skipped = array_map('intval', explode(',', $ov['skip_combinations']));
    sort($skipped);
    check('the unticked combinations are recorded', $skipped, array_values(array_diff($combos, [$keep])));
    check('the ticked one is sent', AmazonProductOverride::skipsCombination($ov, $keep), false);
    $html = $module->hookDisplayAdminProductsExtra(['id_product' => $idProduct]);
    check('the tab shows one ticked', preg_match_all('/name="amzpro_combo_send\[\]" value="\d+" checked/', $html), 1);

    // 3. the scan collects only the ticked combination
    withStagedSnapshot(function () use ($db, $t, $idProduct, $idShop, $main, $keep) {
        $sync = new AmazonProductSync(testClient(), $main, 'SELLERTEST');
        $sync->syncPrestashopSide();
        $rows = $db->executeS('SELECT `id_product_attribute` FROM `' . $t . '` WHERE `id_product` = ' . $idProduct . '
            AND `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'\' AND `ps_exists` = 1 AND `id_product_attribute` > 0');
        check('only the ticked combination is staged', array_map('intval', array_column($rows, 'id_product_attribute')), [$keep]);
    });

    // 4. no combinations listed: nothing is recorded (an unrelated save keeps the choice)
    $_POST = ['amzpro_tab_present' => 1, 'amzpro_id_shop' => AmzproShop::id(), 'amzpro_sync' => 1,
        'amzpro_sync_price' => 1, 'amzpro_sync_quantity' => 1, ];
    $save->invoke($module, $idProduct);
    check('a save without the list keeps the choice', AmazonProductOverride::get($idProduct)['skip_combinations'], $ov['skip_combinations']);
} finally {
    $_POST = $keepPost;
    foreach ($refs as $r) {
        $db->execute('UPDATE `' . $p . 'product_attribute` SET `reference` = \'' . pSQL($r['reference']) . '\' WHERE `id_product_attribute` = ' . (int) $r['id_product_attribute']);
    }
    $db->execute('DELETE FROM `' . $ovT . '`');
    $db->execute('INSERT INTO `' . $ovT . '` SELECT * FROM `' . $ovT . '_snap`');
    $db->execute('DROP TABLE `' . $ovT . '_snap`');
}
done();
