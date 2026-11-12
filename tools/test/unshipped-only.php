<?php
/**
 * 1.6.21: "Orders created in PrestaShop: only paid orders not yet shipped".
 * A shop switching from another Amazon module must not get the orders that
 * module already shipped (or that were cancelled) a second time. Unpaid
 * (Pending) orders are never created, with or without the option.
 *
 *     php tools/test/unshipped-only.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonOrderCreator.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idShop = (int) AmzproShop::actingId();
AmzproShop::ensureSchema();

$keepConfig = $db->executeS('SELECT * FROM `' . $p . 'configuration` WHERE `name` = \'AMZPRO_ORDER_UNSHIPPED_ONLY\'');
$statuses = ['Pending', 'Unshipped', 'PartiallyShipped', 'Shipped', 'Canceled'];

// The staged orders createAllPending() would pick, among the test rows.
$picked = function () use ($db, $p, $idShop) {
    $rows = $db->executeS('SELECT o.`order_status` FROM `' . $p . 'amazonmarketplacepro_order` o
        WHERE o.`amazon_order_id` LIKE \'ZZU-%\' AND o.`id_order` = 0 AND o.`import_status` = \'imported\'
          AND o.`order_status` <> \'Pending\'' . AmazonOrderCreator::unshippedOnlySql($idShop) . '
          AND ' . AmzproShop::sqlWhere('o', $idShop) . '
        ORDER BY o.`amazon_order_id`');
    $out = [];
    foreach ((array) $rows as $r) {
        $out[] = $r['order_status'];
    }

    return $out;
};

try {
    foreach ($statuses as $i => $status) {
        $db->execute('INSERT INTO `' . $p . 'amazonmarketplacepro_order`
            (`amazon_order_id`, `marketplace_id`, `order_status`, `fulfillment_channel`, `id_order`, `id_shop`, `import_status`, `date_add`, `date_upd`)
            VALUES (\'ZZU-' . $i . '\', \'A1PA6795UKMFR9\', \'' . $status . '\', \'MFN\', 0, ' . $idShop . ', \'imported\', NOW(), NOW())');
    }

    // An existing shop has no value: everything paid is created, as before.
    $db->execute('DELETE FROM `' . $p . 'configuration` WHERE `name` = \'AMZPRO_ORDER_UNSHIPPED_ONLY\'');
    Configuration::loadConfiguration();
    check('option unset: every paid order', $picked(), ['Unshipped', 'PartiallyShipped', 'Shipped', 'Canceled']);

    AmzproShop::set('AMZPRO_ORDER_UNSHIPPED_ONLY', 1);
    check('option on: only orders not yet shipped', $picked(), ['Unshipped', 'PartiallyShipped']);

    AmzproShop::set('AMZPRO_ORDER_UNSHIPPED_ONLY', 0);
    check('option off: every paid order', $picked(), ['Unshipped', 'PartiallyShipped', 'Shipped', 'Canceled']);

    // The settings page offers it and saves it.
    $module = Module::getInstanceByName('amazonmarketplacepro');
    $tpl = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/views/templates/admin/configure.tpl');
    check('settings field present', strpos($tpl, 'name="mkpro_order_unshipped_only"') !== false, true);
    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php');
    check('saved with the settings', strpos($src, "'AMZPRO_ORDER_UNSHIPPED_ONLY' => 'mkpro_order_unshipped_only'") !== false, true);
    check('new installs start with it on', strpos($src, "updateValue('AMZPRO_ORDER_UNSHIPPED_ONLY', '1')") !== false, true);
} finally {
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order` WHERE `amazon_order_id` LIKE \'ZZU-%\'');
    $db->execute('DELETE FROM `' . $p . 'configuration` WHERE `name` = \'AMZPRO_ORDER_UNSHIPPED_ONLY\'');
    foreach ($keepConfig as $r) {
        $db->insert('configuration', array_map(function ($v) {
            return $v === null ? null : pSQL($v, true);
        }, $r), true);
    }
    Configuration::loadConfiguration();
}
done();
