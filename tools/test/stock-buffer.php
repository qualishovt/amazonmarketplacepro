<?php
/**
 * 1.6.19:
 * - A product (or a combination listed as a separate product) can keep its
 *   own number of units back from Amazon; empty follows the global buffer.
 * - The brand set on a product's Amazon tab is the brand staged, so the sync
 *   summary shows the brand that is sent.
 *
 *     php tools/test/stock-buffer.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$t = $p . 'amazonmarketplacepro_product';
$ovT = $p . 'amazonmarketplacepro_product_setting';
$idShop = (int) AmzproShop::actingId();
AmazonProductOverride::ensureTable();
$keepBuffer = AmzproShop::get('AMZPRO_STOCK_BUFFER', $idShop);
$keepZero = AmzproShop::get('AMZPRO_FORCE_ZERO_QTY', $idShop);

$idProduct = (int) $db->getValue('SELECT p.`id_product` FROM `' . $p . 'product` p
    INNER JOIN `' . $p . 'product_shop` ps ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ' AND ps.`active` = 1)
    WHERE p.`reference` <> \'\' ORDER BY p.`id_product` ASC');
echo 'product #' . $idProduct . "\n";

$db->execute('DROP TABLE IF EXISTS `' . $ovT . '_snap`');
$db->execute('CREATE TABLE `' . $ovT . '_snap` AS SELECT * FROM `' . $ovT . '`');

/** Run a private method. */
function invokePrivate($object, $method, array $args = [])
{
    $m = new ReflectionMethod($object, $method);
    $m->setAccessible(true);

    return $m->invokeArgs($object, $args);
}

try {
    AmzproShop::set('AMZPRO_FORCE_ZERO_QTY', '0');
    AmzproShop::set('AMZPRO_STOCK_BUFFER', '3');

    // 1. the rule
    check('global buffer: 10 in stock, 7 sent', AmazonSpApiClient::effectiveQuantity(10), 7);
    check('own buffer 2 replaces it: 8 sent', AmazonSpApiClient::effectiveQuantity(10, null, 2), 8);
    check('own buffer 0: everything sent', AmazonSpApiClient::effectiveQuantity(10, null, 0), 10);
    check('never below 0', AmazonSpApiClient::effectiveQuantity(1, null, 2), 0);

    // 2. saved on the product
    $db->execute('DELETE FROM `' . $ovT . '` WHERE `id_product` = ' . $idProduct);
    AmazonProductOverride::save($idProduct, ['stock_buffer' => '2', 'brand' => 'ZZ Own Brand']);
    check('saved', (int) AmazonProductOverride::get($idProduct)['stock_buffer'], 2);
    AmazonProductOverride::save($idProduct, ['stock_buffer' => '']);
    check('emptied: follows the global buffer again', (int) AmazonProductOverride::get($idProduct)['stock_buffer'], -1);
    AmazonProductOverride::save($idProduct, ['stock_buffer' => '2']);

    // 3. the quantity sent to Amazon
    $sync = new AmazonProductSync(testClient(), (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID'), 'SELLERTEST');
    $body = invokePrivate($sync, 'buildPatchBody', [['ps_price' => 10, 'ps_quantity' => 10, 'amazon_product_type' => 'PRODUCT', 'ov_stock_buffer' => '2'], 'quantity']);
    check('the product\'s buffer is used', $body['patches'][0]['value'][0]['quantity'], 8);
    $body = invokePrivate($sync, 'buildPatchBody', [['ps_price' => 10, 'ps_quantity' => 10, 'amazon_product_type' => 'PRODUCT', 'ov_stock_buffer' => '-1'], 'quantity']);
    check('without one, the global buffer', $body['patches'][0]['value'][0]['quantity'], 7);

    // 4. the brand staged is the brand sent
    withStagedSnapshot(function () use ($db, $t, $idShop, $idProduct) {
        $sync = new AmazonProductSync(testClient(), (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID'), 'SELLERTEST');
        $sync->syncPrestashopSide();
        check('the summary shows the brand of the Amazon tab', $db->getValue('SELECT `ps_manufacturer` FROM `' . $t . '`
            WHERE `id_product` = ' . $idProduct . ' AND `id_product_attribute` = 0 AND `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'\''), 'ZZ Own Brand');
    });

    // 5. a combination listed as a separate product has its own
    $idPa = (int) $db->getValue('SELECT `id_product_attribute` FROM `' . $p . 'product_attribute` ORDER BY `id_product_attribute` ASC');
    $idComboProduct = (int) $db->getValue('SELECT `id_product` FROM `' . $p . 'product_attribute` WHERE `id_product_attribute` = ' . $idPa);
    AmazonProductOverride::save($idComboProduct, ['separate_combinations' => 1,
        'combination_settings' => json_encode([$idPa => AmazonProductOverride::normalizeCombinationValues(['stock_buffer' => '1'])]), ]);
    $rows = invokePrivate($sync, 'withCombinationSettings', [[
        ['id_product' => $idComboProduct, 'id_product_attribute' => $idPa, 'seller_sku' => 'X', 'ov_stock_buffer' => '-1'],
    ]]);
    check('the combination\'s own buffer', (int) $rows[0]['ov_stock_buffer'], 1);
} finally {
    AmzproShop::set('AMZPRO_STOCK_BUFFER', (string) $keepBuffer);
    AmzproShop::set('AMZPRO_FORCE_ZERO_QTY', (string) $keepZero);
    $db->execute('DELETE FROM `' . $ovT . '`');
    $db->execute('INSERT INTO `' . $ovT . '` SELECT * FROM `' . $ovT . '_snap`');
    $db->execute('DROP TABLE `' . $ovT . '_snap`');
}
done();
