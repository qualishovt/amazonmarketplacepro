<?php
/**
 * 1.6.20: the shipment confirmation Amazon gets for a merchant-fulfilled
 * order. Amazon requires the package reference, carrier code, tracking
 * number, ship date and the order items; before 1.6.20 only the carrier
 * code and tracking number were sent, so Amazon refused every confirmation.
 * The carrier's shipping method (e.g. DHL Paket) and, for "Other", the
 * carrier's own name go with it. An order shipped without a tracking number
 * is confirmed when it gets one.
 *
 *     php tools/test/shipment-confirmation.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/upgrade/upgrade-1.6.20.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idShop = (int) AmzproShop::actingId();
AmzproShop::ensureSchema();

$row = $db->getRow('SELECT o.`id_order`, oc.`id_order_carrier`, oc.`id_carrier`, oc.`tracking_number`, c.`name`
    FROM `' . $p . 'orders` o
    INNER JOIN `' . $p . 'order_carrier` oc ON (oc.`id_order` = o.`id_order`)
    INNER JOIN `' . $p . 'carrier` c ON (c.`id_carrier` = oc.`id_carrier`)
    WHERE o.`id_shop` = ' . $idShop . ' ORDER BY o.`id_order` ASC');
$order = new Order((int) $row['id_order']);
$idCarrier = (int) $row['id_carrier'];
echo 'order #' . $order->id . ', carrier ' . $row['name'] . "\n";

// The configuration rows as they are, so the test leaves no shop-level
// value behind that would shadow the shared one.
$keepConfig = $db->executeS('SELECT * FROM `' . $p . 'configuration` WHERE `name` IN (\'AMZPRO_CARRIER_MAP\', \'AMZPRO_CARRIER_METHOD\')');
$amazonId = 'ZZT-302-0000001-0000001';

try {
    $db->execute('INSERT INTO `' . $p . 'amazonmarketplacepro_order`
        (`amazon_order_id`, `marketplace_id`, `fulfillment_channel`, `id_order`, `id_shop`, `import_status`, `date_add`, `date_upd`)
        VALUES (\'' . $amazonId . '\', \'A1PA6795UKMFR9\', \'MFN\', ' . (int) $order->id . ', ' . $idShop . ', \'imported\', NOW(), NOW())');
    $idStaged = (int) $db->Insert_ID();
    $db->execute('INSERT INTO `' . $p . 'amazonmarketplacepro_order_item`
        (`id_amazonmarketplacepro_order`, `amazon_order_id`, `order_item_id`, `seller_sku`, `quantity`)
        VALUES (' . $idStaged . ', \'' . $amazonId . '\', \'11111\', \'A\', 2), (' . $idStaged . ', \'' . $amazonId . '\', \'22222\', \'B\', 1)');
    $module = Module::getInstanceByName('amazonmarketplacepro');

    // 1. no tracking number yet
    $db->execute('UPDATE `' . $p . 'order_carrier` SET `tracking_number` = \'\' WHERE `id_order_carrier` = ' . (int) $row['id_order_carrier']);
    $built = $module->shipmentConfirmation($order);
    check('without a tracking number nothing is sent yet', isset($built['skip']) && $built['skip'] !== '', true);

    // 2. everything Amazon requires
    $db->execute('UPDATE `' . $p . 'order_carrier` SET `tracking_number` = \'00340434161234567890\' WHERE `id_order_carrier` = ' . (int) $row['id_order_carrier']);
    AmzproShop::set('AMZPRO_CARRIER_MAP', json_encode([$idCarrier => 'DHL']));
    AmzproShop::set('AMZPRO_CARRIER_METHOD', json_encode([$idCarrier => 'DHL Paket']));
    $built = $module->shipmentConfirmation($order);
    $pkg = isset($built['body']['packageDetail']) ? $built['body']['packageDetail'] : [];
    check('the order\'s own marketplace', [$built['marketplace_id'], $built['body']['marketplaceId']], ['A1PA6795UKMFR9', 'A1PA6795UKMFR9']);
    check('carrier, shipping method, tracking number', [$pkg['carrierCode'], $pkg['shippingMethod'], $pkg['trackingNumber']], ['DHL', 'DHL Paket', '00340434161234567890']);
    check('package reference and ship date', [$pkg['packageReferenceId'], (bool) preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/', $pkg['shipDate'])], ['1', true]);
    check('every order item with its quantity', $pkg['orderItems'], [['orderItemId' => '11111', 'quantity' => 2], ['orderItemId' => '22222', 'quantity' => 1]]);
    check('no carrier name for a known code', isset($pkg['carrierName']), false);

    // 3. "Other" carries the carrier's own name
    AmzproShop::set('AMZPRO_CARRIER_MAP', json_encode([$idCarrier => 'Other']));
    $pkg = $module->shipmentConfirmation($order)['body']['packageDetail'];
    check('"Other" sends the carrier name', [$pkg['carrierCode'], $pkg['carrierName']], ['Other', (string) $row['name']]);

    // 4. nothing to confirm
    $db->execute('UPDATE `' . $p . 'amazonmarketplacepro_order` SET `shipment_confirmed` = 1 WHERE `id_amazonmarketplacepro_order` = ' . $idStaged);
    check('already confirmed: not sent twice', $module->shipmentConfirmation($order), ['skip' => '']);
    $db->execute('UPDATE `' . $p . 'amazonmarketplacepro_order` SET `shipment_confirmed` = 0, `fulfillment_channel` = \'AFN\' WHERE `id_amazonmarketplacepro_order` = ' . $idStaged);
    check('Amazon ships it (FBA): nothing to confirm', $module->shipmentConfirmation($order), ['skip' => '']);

    // 5. a tracking number entered later confirms it
    check('upgrade registers the tracking number hook', (bool) upgrade_module_1_6_20($module), true);
    check('hooked', (bool) $module->isRegisteredInHook('actionAdminOrdersTrackingNumberUpdate'), true);
} finally {
    $db->execute('UPDATE `' . $p . 'order_carrier` SET `tracking_number` = \'' . pSQL($row['tracking_number']) . '\' WHERE `id_order_carrier` = ' . (int) $row['id_order_carrier']);
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order_item` WHERE `amazon_order_id` = \'' . $amazonId . '\'');
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order` WHERE `amazon_order_id` = \'' . $amazonId . '\'');
    $db->execute('DELETE FROM `' . $p . 'configuration` WHERE `name` IN (\'AMZPRO_CARRIER_MAP\', \'AMZPRO_CARRIER_METHOD\')');
    foreach ($keepConfig as $r) {
        $db->insert('configuration', array_map(function ($v) {
            return $v === null ? null : pSQL($v, true);
        }, $r), true);
    }
    Configuration::loadConfiguration();
}
done();
