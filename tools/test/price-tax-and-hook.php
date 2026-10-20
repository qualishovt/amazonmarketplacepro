<?php
/**
 * 1.6.4: "Prices sent to Amazon" tax included/excluded, and the real-time update that only patches live listings with the scan's own price.
 *
 *     php tools/test/price-tax-and-hook.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$before = Configuration::getGlobalValue('AMZPRO_PRICE_TAX');
echo 'main marketplace ' . $main . ', AMZPRO_PRICE_TAX before: ' . var_export($before, true) . "\n";
$db->execute('DROP TABLE IF EXISTS `' . $t . '_t164`');
$db->execute('CREATE TABLE `' . $t . '_t164` AS SELECT * FROM `' . $t . '`');
$client = testClient();

// 1. tax excluded = what earlier versions sent; tax included adds the rate
Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', 'excl');
$s = new AmazonProductSync($client, $main, 'SELLERTEST');
$s->syncPrestashopSide();
$excl = [];
foreach ($db->executeS('SELECT seller_sku, id_product, ps_price FROM `' . $t . '` WHERE marketplace_id = "" AND ps_exists = 1 AND id_product_attribute = 0') as $r) {
    $excl[$r['seller_sku']] = $r;
}
$prev = [];
foreach ($db->executeS('SELECT seller_sku, ps_price FROM `' . $t . '_t164` WHERE marketplace_id = "" AND ps_exists = 1') as $r) {
    $prev[$r['seller_sku']] = (float) $r['ps_price'];
}
$changed = 0;
foreach ($excl as $sku => $r) {
    if (isset($prev[$sku]) && abs($prev[$sku] - (float) $r['ps_price']) > 0.001) {
        ++$changed;
    }
}
check('tax excluded: same prices as before 1.6.4', $changed, 0);

Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', 'incl');
$s2 = new AmazonProductSync($client, $main, 'SELLERTEST');
$s2->syncPrestashopSide();
$overrides = AmazonProductOverride::all();
$bad = 0;
$shown = 0;
foreach ($db->executeS('SELECT seller_sku, id_product, ps_price FROM `' . $t . '` WHERE marketplace_id = "" AND ps_exists = 1 AND id_product_attribute = 0') as $r) {
    if (!isset($excl[$r['seller_sku']]) || (float) $excl[$r['seller_sku']]['ps_price'] <= 0) {
        continue;
    }
    $rate = (float) Tax::getProductTaxRate((int) $r['id_product'], null, AmzproShop::context());
    $fixed = isset($overrides[(int) $r['id_product']]) && (float) $overrides[(int) $r['id_product']]['override_price'] > 0;
    $want = $fixed ? (float) $excl[$r['seller_sku']]['ps_price']
        : AmazonListingSettings::applyRounding((float) $excl[$r['seller_sku']]['ps_price'] * (1 + $rate / 100));
    if (abs((float) $r['ps_price'] - $want) > 0.001) {
        ++$bad;
    }
    if ($shown < 2 && $rate > 0) {
        echo '   e.g. ' . $r['seller_sku'] . ': ' . $excl[$r['seller_sku']]['ps_price'] . ' + ' . $rate . '% = ' . $r['ps_price'] . "\n";
        ++$shown;
    }
}
check('tax included: every price = tax-excluded price x (1 + rate)', $bad, 0);

// 2. the real-time update
$s2->setMock(true);
$live = $db->getRow('SELECT seller_sku, id_product, ps_price, ps_quantity FROM `' . $t . '` WHERE marketplace_id = "" AND ps_exists = 1 AND id_product_attribute = 0 AND is_parent = 0 AND id_product > 0 AND ps_price > 0');
$idp = (int) $live['id_product'];
$db->execute('UPDATE `' . $t . '` SET amazon_exists = 0 WHERE marketplace_id = "" AND seller_sku = "' . pSQL($live['seller_sku']) . '"');
$sent = [];
check('not on Amazon: nothing is sent', $s2->pushOfferForProduct($idp, $sent), null);
$db->execute('UPDATE `' . $t . '` SET amazon_exists = 1 WHERE marketplace_id = "" AND seller_sku = "' . pSQL($live['seller_sku']) . '"');
$r1 = $s2->pushOfferForProduct($idp, $sent);
check('live on Amazon: an update is sent', is_array($r1), true);
check('it sends the same price as the scan', $r1 ? (float) $r1['price'] : null, (float) $live['ps_price']);
check('and the same quantity as the scan', $r1 ? (int) $r1['quantity'] : null, (int) $live['ps_quantity']);
check('a second hook in the same save sends nothing', $s2->pushOfferForProduct($idp, $sent), null);
AmazonListingSettings::saveProductSetting($idp, false, '');
$sent = [];
check('switched off: nothing is sent', $s2->pushOfferForProduct($idp, $sent), null);
$db->execute('DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` WHERE `id_product` = ' . $idp);

// 3. the update body: a partial PATCH of price and quantity only
$rm = new ReflectionMethod('AmazonProductSync', 'buildPatchBody');
$rm->setAccessible(true);
$b = $rm->invoke($s2, ['ps_price' => 10, 'ps_quantity' => 3, 'amazon_product_type' => 'WATCH'], 'price');
check('price patch replaces purchasable_offer only', $b['patches'][0]['path'], '/attributes/purchasable_offer');
check('it keeps the listing product type', $b['productType'], 'WATCH');

// 4. upgrade default for existing shops
Configuration::deleteByName('AMZPRO_PRICE_TAX');
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/upgrade/upgrade-1.6.4.php';
upgrade_module_1_6_4(Module::getInstanceByName('amazonmarketplacepro'));
check('existing shop after upgrade: tax excluded (no price change)', Configuration::getGlobalValue('AMZPRO_PRICE_TAX'), 'excl');
Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', 'incl');
upgrade_module_1_6_4(Module::getInstanceByName('amazonmarketplacepro'));
check('a choice already made is kept', Configuration::getGlobalValue('AMZPRO_PRICE_TAX'), 'incl');

// restore
if ($before === false || $before === null || $before === '') {
    Configuration::deleteByName('AMZPRO_PRICE_TAX');
} else {
    Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', $before);
}
$db->execute('DELETE FROM `' . $t . '`');
$db->execute('INSERT INTO `' . $t . '` SELECT * FROM `' . $t . '_t164`');
$db->execute('DROP TABLE `' . $t . '_t164`');
check('restored', (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '`') > 0, true);
done();
