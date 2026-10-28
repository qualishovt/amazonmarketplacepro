<?php
/**
 * 1.6.15: switching Orders / Products / Stock / Active on a marketplace
 * already added under Multi-Account changes that one setting and keeps the
 * rest (own seller ID, credentials, carrier). Orders off is why an Amazon.nl
 * sale never reached PrestaShop: that marketplace's orders are not imported.
 *
 *     php tools/test/marketplace-switch.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonMultiMarketplace.php';

$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config';
$mm = new AmazonMultiMarketplace();
$nl = 'A1805IZSGTT6HS';
$db->execute('DROP TABLE IF EXISTS `' . $t . '_snap`');
$db->execute('CREATE TABLE `' . $t . '_snap` AS SELECT * FROM `' . $t . '`');

try {
    $db->execute('DELETE FROM `' . $t . '` WHERE `marketplace_id` = \'' . $nl . '\'');
    check('added with Orders off', $mm->saveMarketplaceConfig(['marketplace_id' => $nl, 'seller_id' => 'SELLERNL', 'default_carrier' => 7,
        'sync_orders' => 0, 'sync_products' => 1, 'sync_stock' => 1, ]), true);
    $row = function () use ($db, $t, $nl) {
        return $db->getRow('SELECT `seller_id`, `default_carrier`, `sync_orders`, `sync_products`, `sync_stock`, `active` FROM `' . $t . '`
            WHERE `marketplace_id` = \'' . $nl . '\' AND ' . AmzproShop::sqlWhere('', AmzproShop::actingId()));
    };

    $r = new ReflectionMethod($mm, 'importOrdersForShop');
    $r->setAccessible(true);
    $r->invoke($mm, (int) AmzproShop::actingId());
    check('with Orders off its orders are skipped', in_array('Amazon.nl (Netherlands): order sync disabled', $mm->getNotices(), true)
        || (bool) array_filter($mm->getNotices(), function ($n) { return strpos($n, 'order sync disabled') !== false; }), true);

    check('switch Orders on', $mm->setMarketplaceFlag($nl, 'sync_orders', 1), true);
    $after = $row();
    check('only Orders changed', array_map('intval', [$after['sync_orders'], $after['sync_products'], $after['sync_stock'], $after['active']]), [1, 1, 1, 1]);
    check('seller ID and carrier kept', [$after['seller_id'], (int) $after['default_carrier']], ['SELLERNL', 7]);

    check('switch Stock off', $mm->setMarketplaceFlag($nl, 'sync_stock', 0) && (int) $row()['sync_stock'] === 0, true);
    check('an unknown setting is refused', $mm->setMarketplaceFlag($nl, 'seller_id', 1), false);
    check('the column of an unknown setting is untouched', $row()['seller_id'], 'SELLERNL');
} finally {
    $db->execute('DELETE FROM `' . $t . '`');
    $db->execute('INSERT INTO `' . $t . '` SELECT * FROM `' . $t . '_snap`');
    $db->execute('DROP TABLE `' . $t . '_snap`');
}
done();
