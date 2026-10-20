<?php
/**
 * 1.6.5: the PrestaShop IDs SKU source (123 / 123_45), its prefix, order matching and the real-time update.
 *
 *     php tools/test/sku-ids.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$keep = ['AMZPRO_SKU_SOURCE' => Configuration::get('AMZPRO_SKU_SOURCE'), 'AMZPRO_SKU_PREFIX' => Configuration::get('AMZPRO_SKU_PREFIX')];
$db->execute('DROP TABLE IF EXISTS `' . $t . '_t165`');
$db->execute('CREATE TABLE `' . $t . '_t165` AS SELECT * FROM `' . $t . '`');
$client = testClient();

// 1. the scan builds 123 / 123_45
Configuration::updateValue('AMZPRO_SKU_SOURCE', 'id');
Configuration::updateValue('AMZPRO_SKU_PREFIX', '');
$s = new AmazonProductSync($client, $main, 'SELLERTEST');
$s->resetStaged();
$s->syncPrestashopSide();
$rows = $db->executeS('SELECT seller_sku, id_product, id_product_attribute, parent_sku FROM `' . $t . '` WHERE marketplace_id = "" AND ps_exists = 1');
$bad = 0;
$combos = 0;
$example = null;
foreach ($rows as $r) {
    $want = (int) $r['id_product'] . ((int) $r['id_product_attribute'] > 0 ? '_' . (int) $r['id_product_attribute'] : '');
    if ($r['seller_sku'] !== $want) {
        ++$bad;
    }
    if ((int) $r['id_product_attribute'] > 0) {
        ++$combos;
        $example = $r;
    }
}
echo '   ' . count($rows) . ' rows, ' . $combos . " combinations\n";
check('every SKU is id_product or id_product_attribute', $bad, 0);
check('combinations staged with id SKUs', $combos > 0, true);
if ($example) {
    echo '   e.g. combination ' . $example['seller_sku'] . ', parent ' . var_export($example['parent_sku'], true) . "\n";
    check('a combination\'s parent is its product id', $example['parent_sku'] === '' || $example['parent_sku'] === (string) (int) $example['id_product'], true);
}

// 2. prefix still applies
Configuration::updateValue('AMZPRO_SKU_PREFIX', 'PS');
check('with prefix PS: PS-123', AmazonListingSettings::buildSku(['id_product' => 123, 'id_product_attribute' => 0]), 'PS-123');
check('with prefix PS: PS-123_45', AmazonListingSettings::buildSku(['id_product' => 123, 'id_product_attribute' => 45]), 'PS-123_45');
Configuration::updateValue('AMZPRO_SKU_PREFIX', '');
check('no product id: no SKU', AmazonListingSettings::buildSku(['reference' => 'X']), '');

// 3. orders with these SKUs find their product (order matching left on reference)
$im = new AmazonOrderImporter($client, $main, (int) AmzproShop::actingId());
$rm = new ReflectionMethod('AmazonOrderImporter', 'resolveProduct');
$rm->setAccessible(true);
if ($example) {
    $res = $rm->invoke($im, $example['seller_sku'], '');
    check('order SKU 123_45 -> that product and combination', [(int) $res['id_product'], (int) $res['id_product_attribute']], [(int) $example['id_product'], (int) $example['id_product_attribute']]);
    Configuration::updateValue('AMZPRO_SKU_PREFIX', 'PS');
    $res = $rm->invoke($im, 'PS-' . $example['seller_sku'], '');
    check('order SKU PS-123_45 with prefix PS -> same', [(int) $res['id_product'], (int) $res['id_product_attribute']], [(int) $example['id_product'], (int) $example['id_product_attribute']]);
    Configuration::updateValue('AMZPRO_SKU_PREFIX', '');
}

// 4. the real-time update finds the listing by its id SKU
$s->setMock(true);
$p = $db->getRow('SELECT seller_sku, id_product FROM `' . $t . '` WHERE marketplace_id = "" AND ps_exists = 1 AND id_product_attribute = 0 AND is_parent = 0 AND id_product > 0 AND ps_price > 0');
if ($p) {
    $db->execute('UPDATE `' . $t . '` SET amazon_exists = 1 WHERE marketplace_id = "" AND seller_sku = "' . pSQL($p['seller_sku']) . '"');
    $sent = [];
    $r = $s->pushOfferForProduct((int) $p['id_product'], $sent);
    check('real-time update uses SKU ' . $p['seller_sku'], $r ? $r['sku'] : null, $p['seller_sku']);
}

// restore
foreach ($keep as $k => $v) {
    Configuration::updateValue($k, ($v === false) ? '' : $v);
}
$db->execute('DELETE FROM `' . $t . '`');
$db->execute('INSERT INTO `' . $t . '` SELECT * FROM `' . $t . '_t165`');
$db->execute('DROP TABLE `' . $t . '_t165`');
check('settings restored', (string) Configuration::get('AMZPRO_SKU_SOURCE'), (string) $keep['AMZPRO_SKU_SOURCE']);
done();
