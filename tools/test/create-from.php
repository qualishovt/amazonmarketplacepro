<?php
/**
 * 1.6.23: "Create orders placed from". A shop switching from another Amazon
 * module enters the moment that module stopped importing; orders placed
 * before it (which that module already created) are not created again.
 * Empty or unreadable = no limit.
 *
 *     php tools/test/create-from.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonOrderCreator.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idShop = (int) AmzproShop::actingId();
AmzproShop::ensureSchema();

$keys = ['AMZPRO_ORDER_CREATE_FROM', 'AMZPRO_ORDER_UNSHIPPED_ONLY'];
$keepConfig = $db->executeS('SELECT * FROM `' . $p . 'configuration` WHERE `name` IN (\'' . implode('\',\'', $keys) . '\')');
$dates = ['2026-11-12 21:59:59', '2026-11-12 22:00:00', '2026-11-13 01:30:00'];

// The staged orders createAllPending() would pick, among the test rows.
$picked = function () use ($db, $p, $idShop) {
    $rows = $db->executeS('SELECT o.`amazon_order_id` FROM `' . $p . 'amazonmarketplacepro_order` o
        WHERE o.`amazon_order_id` LIKE \'ZZF-%\' AND o.`id_order` = 0 AND o.`import_status` = \'imported\'
          AND o.`order_status` <> \'Pending\'' . AmazonOrderCreator::unshippedOnlySql($idShop)
          . AmazonOrderCreator::createFromSql($idShop) . '
          AND ' . AmzproShop::sqlWhere('o', $idShop) . '
        ORDER BY o.`amazon_order_id`');
    $out = [];
    foreach ((array) $rows as $r) {
        $out[] = $r['amazon_order_id'];
    }

    return $out;
};

try {
    foreach ($dates as $i => $d) {
        $db->execute('INSERT INTO `' . $p . 'amazonmarketplacepro_order`
            (`amazon_order_id`, `marketplace_id`, `order_status`, `fulfillment_channel`, `purchase_date`, `id_order`, `id_shop`, `import_status`, `date_add`, `date_upd`)
            VALUES (\'ZZF-' . $i . '\', \'A1PA6795UKMFR9\', \'Unshipped\', \'MFN\', \'' . $d . '\', 0, ' . $idShop . ', \'imported\', NOW(), NOW())');
    }
    AmzproShop::set('AMZPRO_ORDER_UNSHIPPED_ONLY', 1);
    $all = ['ZZF-0', 'ZZF-1', 'ZZF-2'];

    $db->execute('DELETE FROM `' . $p . 'configuration` WHERE `name` = \'AMZPRO_ORDER_CREATE_FROM\'');
    Configuration::loadConfiguration();
    check('unset: no limit', $picked(), $all);

    AmzproShop::set('AMZPRO_ORDER_CREATE_FROM', '2026-11-12T22:00');
    check('form value (datetime-local): from that minute on', $picked(), ['ZZF-1', 'ZZF-2']);

    AmzproShop::set('AMZPRO_ORDER_CREATE_FROM', '2026-11-13 00:00:00');
    check('later moment', $picked(), ['ZZF-2']);

    AmzproShop::set('AMZPRO_ORDER_CREATE_FROM', '');
    check('cleared: no limit', $picked(), $all);

    AmzproShop::set('AMZPRO_ORDER_CREATE_FROM', "2026-11-12' OR '1'='1");
    check('unreadable value: ignored, no SQL', [AmazonOrderCreator::createFromSql($idShop), $picked()], ['', $all]);

    $tpl = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/views/templates/admin/configure.tpl');
    check('settings field present', strpos($tpl, 'name="mkpro_order_create_from"') !== false, true);
    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php');
    check('saved with the settings', strpos($src, "'AMZPRO_ORDER_CREATE_FROM' => 'mkpro_order_create_from'") !== false, true);
    $creator = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonOrderCreator.php');
    check('used when creating orders', strpos($creator, '. self::createFromSql($this->idShop)') !== false, true);
} finally {
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order` WHERE `amazon_order_id` LIKE \'ZZF-%\'');
    $db->execute('DELETE FROM `' . $p . 'configuration` WHERE `name` IN (\'' . implode('\',\'', $keys) . '\')');
    foreach ($keepConfig as $r) {
        $db->insert('configuration', array_map(function ($v) {
            return $v === null ? null : pSQL($v, true);
        }, $r), true);
    }
    Configuration::loadConfiguration();
}
done();
