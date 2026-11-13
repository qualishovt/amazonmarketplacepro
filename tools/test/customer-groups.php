<?php
/**
 * 1.6.26: Amazon buyers filed into customer groups by kind (private /
 * Amazon Business) and destination (shop country / abroad), and the order's
 * carrier line (order_carrier), which was never written before (Schwietz
 * order 6002: "Versanddienst" empty on the order page).
 *
 *     php tools/test/customer-groups.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$ctx = Context::getContext();
$idShop = (int) AmzproShop::actingId();
$env = AmazonOrderCreator::shopEnvironment($idShop);
$keys = ['AMZPRO_CUSTOMER_GROUP', 'AMZPRO_GROUP_BUSINESS', 'AMZPRO_GROUP_ABROAD', 'AMZPRO_GROUP_BUSINESS_ABROAD'];
$keep = $db->executeS('SELECT * FROM `' . $p . 'configuration` WHERE `name` IN (\'' . implode('\',\'', $keys) . '\')');
$homeIso = Country::getIsoById((int) $env['id_country']);
$abroadIso = $homeIso === 'FR' ? 'DE' : 'FR';
$groups = array_map('intval', array_column($db->executeS('SELECT `id_group` FROM `' . $p . 'group` ORDER BY `id_group` LIMIT 4'), 'id_group'));
$made = ['orders' => 0, 'detail' => 0, 'groups' => []];
// A test shop may have fewer than four groups: add temporary ones.
while (count($groups) < 4) {
    $db->insert('group', ['reduction' => 0, 'price_display_method' => 0, 'show_prices' => 1,
        'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);
    $newGroup = (int) $db->Insert_ID();
    $groups[] = $newGroup;
    $made['groups'][] = $newGroup;
}

$set = function (array $values) use ($keys) {
    foreach ($keys as $i => $k) {
        AmzproShop::set($k, isset($values[$i]) ? $values[$i] : 0);
    }
};
$groupFor = function ($business, $iso) use ($env) {
    return AmazonOrderCreator::customerGroupFor(['is_business' => $business ? 1 : 0, 'ship_country_code' => $iso], $env);
};

$db->execute("SET SESSION sql_mode = ''");
try {
    check('four groups to test with', count($groups), 4);
    list($gPriv, $gBiz, $gAbroad, $gBizAbroad) = $groups;

    // Nothing set: the shop's default customer group for everyone.
    $set([0, 0, 0, 0]);
    $default = (int) Configuration::get('PS_CUSTOMER_GROUP');
    check('none set: private home -> shop default', $groupFor(false, $homeIso), $default);
    check('none set: business abroad -> shop default', $groupFor(true, $abroadIso), $default);

    // All four set: each buyer to its own group.
    $set([$gPriv, $gBiz, $gAbroad, $gBizAbroad]);
    check('private, home country', $groupFor(false, $homeIso), $gPriv);
    check('business, home country', $groupFor(true, $homeIso), $gBiz);
    check('private, abroad', $groupFor(false, $abroadIso), $gAbroad);
    check('business, abroad', $groupFor(true, $abroadIso), $gBizAbroad);
    check('no country known counts as home', $groupFor(false, ''), $gPriv);
    check('country code in lower case', $groupFor(false, Tools::strtolower($abroadIso)), $gAbroad);

    // Fallbacks.
    $set([$gPriv, $gBiz, $gAbroad, 0]);
    check('business abroad not set -> business group', $groupFor(true, $abroadIso), $gBiz);
    $set([$gPriv, 0, $gAbroad, 0]);
    check('then -> group for abroad', $groupFor(true, $abroadIso), $gAbroad);
    $set([$gPriv, 0, 0, 0]);
    check('then -> private group', $groupFor(true, $abroadIso), $gPriv);
    check('business home, not set -> private group', $groupFor(true, $homeIso), $gPriv);
    $set([$gPriv, 999999, 0, 0]);
    check('a deleted group is skipped', $groupFor(true, $homeIso), $gPriv);

    // order_carrier: none -> one with the order's carrier, weight and cost.
    $idCarrier = (int) $db->getValue('SELECT `id_carrier` FROM `' . $p . 'carrier` WHERE `deleted` = 0 ORDER BY `id_carrier`');
    $idProduct = (int) $db->getValue('SELECT `id_product` FROM `' . $p . 'product` ORDER BY `id_product`');
    $now = date('Y-m-d H:i:s');
    $db->insert('orders', ['reference' => 'ZZCARRIER', 'id_shop_group' => (int) $ctx->shop->id_shop_group,
        'id_shop' => $idShop, 'id_carrier' => $idCarrier, 'id_lang' => (int) $env['id_lang'], 'id_customer' => 0,
        'id_cart' => 0, 'id_currency' => (int) $env['id_currency'], 'id_address_delivery' => 0, 'id_address_invoice' => 0,
        'current_state' => 0, 'secure_key' => md5('zz'), 'payment' => 'Amazon Marketplace', 'module' => 'amazonmarketplacepro',
        'conversion_rate' => 1, 'total_paid' => 24.90, 'total_paid_tax_incl' => 24.90, 'total_paid_tax_excl' => 20.92,
        'total_products' => 16.76, 'total_products_wt' => 19.95, 'total_shipping' => 4.95,
        'total_shipping_tax_incl' => 4.95, 'total_shipping_tax_excl' => 4.16, 'valid' => 1, 'date_add' => $now, 'date_upd' => $now]);
    $idOrder = $made['orders'] = (int) $db->Insert_ID();
    $db->insert('order_detail', ['id_order' => $idOrder, 'id_shop' => $idShop, 'product_id' => $idProduct,
        'product_attribute_id' => 0, 'product_name' => 'ZZ carrier test', 'product_quantity' => 2, 'product_weight' => 0.25,
        'unit_price_tax_excl' => 8.38, 'unit_price_tax_incl' => 9.975, 'total_price_tax_excl' => 16.76, 'total_price_tax_incl' => 19.95]);
    $made['detail'] = (int) $db->Insert_ID();

    check('missing carrier line is added', AmazonOrderCreator::ensureOrderCarrier($idOrder), true);
    $oc = $db->getRow('SELECT * FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . $idOrder);
    check('with the order\'s carrier', $oc ? (int) $oc['id_carrier'] : null, AmazonOrderCreator::currentCarrier($idCarrier));
    check('with the weight of the items', $oc ? round((float) $oc['weight'], 3) : null, 0.5);
    check('with the shipping cost', $oc ? [round((float) $oc['shipping_cost_tax_excl'], 2), round((float) $oc['shipping_cost_tax_incl'], 2)] : null, [4.16, 4.95]);
    check('second run adds nothing', AmazonOrderCreator::ensureOrderCarrier($idOrder), false);
    check('still one carrier line', (int) $db->getValue('SELECT COUNT(*) FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . $idOrder), 1);

    // A line some other module wrote without a carrier is completed, not doubled.
    $db->execute('UPDATE `' . $p . 'order_carrier` SET `id_carrier` = 0, `weight` = 0, `shipping_cost_tax_excl` = 0,
        `shipping_cost_tax_incl` = 0 WHERE `id_order` = ' . $idOrder);
    check('empty carrier line is completed', AmazonOrderCreator::ensureOrderCarrier($idOrder), true);
    $oc = $db->getRow('SELECT * FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . $idOrder);
    check('now with the carrier and cost', [(int) $oc['id_carrier'], round((float) $oc['shipping_cost_tax_incl'], 2)],
        [AmazonOrderCreator::currentCarrier($idCarrier), 4.95]);
    check('and still one line', (int) $db->getValue('SELECT COUNT(*) FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . $idOrder), 1);

    // Settings page and save.
    $tpl = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/views/templates/admin/configure.tpl');
    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php');
    foreach (['mkpro_group_business' => 'AMZPRO_GROUP_BUSINESS', 'mkpro_group_abroad' => 'AMZPRO_GROUP_ABROAD',
        'mkpro_group_business_abroad' => 'AMZPRO_GROUP_BUSINESS_ABROAD'] as $field => $key) {
        check("$field on the page and saved", [strpos($tpl, 'name="' . $field . '"') !== false,
            strpos($src, "'$key' => '$field'") !== false], [true, true]);
    }
} finally {
    foreach ($made['groups'] as $g) {
        $db->execute('DELETE FROM `' . $p . 'group` WHERE `id_group` = ' . (int) $g);
    }
    if ($made['orders']) {
        $db->execute('DELETE FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . (int) $made['orders']);
        $db->execute('DELETE FROM `' . $p . 'order_detail` WHERE `id_order` = ' . (int) $made['orders']);
        $db->execute('DELETE FROM `' . $p . 'orders` WHERE `id_order` = ' . (int) $made['orders']);
    }
    $db->execute('DELETE FROM `' . $p . 'configuration` WHERE `name` IN (\'' . implode('\',\'', $keys) . '\')');
    foreach ($keep as $r) {
        $db->insert('configuration', array_map(function ($v) {
            return $v === null ? null : pSQL($v, true);
        }, $r), true);
    }
    Configuration::loadConfiguration();
}
done();
