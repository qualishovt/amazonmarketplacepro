<?php
/**
 * 1.6.2: the scan says when the catalogue is bigger than one pass and the
 * comparison reports its real size; rows the scan no longer collects stop
 * being pending; a product switched off is not pending; reset empties.
 *
 *     php tools/test/sync-fixes.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$table = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$settings = _DB_PREFIX_ . 'amazonmarketplacepro_product_setting';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$client = testClient();

withStagedSnapshot(function () use ($db, $table, $settings, $main, $client) {
    $sync = new AmazonProductSync($client, $main, 'SELLERTEST');

    $sync->resetStaged();
    check('countStaged after reset', $sync->countStaged(), 0);

    $summary = $sync->syncPrestashopSide();
    check('scan staged something', $sync->countStaged() > 0, true);
    check('nothing dropped on a first scan', (int) $summary['ps_dropped'], 0);

    // A row the scan no longer collects stops being pending.
    $db->execute(
        'INSERT INTO `' . $table . '` (`id_shop`, `marketplace_id`, `seller_sku`, `id_product`, `id_product_attribute`,
            `ps_exists`, `ps_name`, `ps_price`, `ps_quantity`, `amazon_exists`, `sync_direction`, `date_add`, `date_upd`)
         VALUES (' . (int) AmzproShop::actingId() . ', \'\', \'ZZ-GONE-TEST\', 999999, 0, 1, \'Gone\', 9.99, 3, 0, \'ps_only\',
            \'2000-01-01 00:00:00\', \'2000-01-01 00:00:00\')'
    );
    $pendingWithGhost = $sync->countPending();
    $summary = $sync->syncPrestashopSide();
    check('ghost row reported as dropped', (int) $summary['ps_dropped'] >= 1, true);
    check('ghost row deleted (neither side has it)',
        (int) $db->getValue('SELECT COUNT(*) FROM `' . $table . '` WHERE `seller_sku` = "ZZ-GONE-TEST"'), 0);
    check('pending fell by the ghost row', $sync->countPending() < $pendingWithGhost, true);

    // A product switched off is not pending.
    $row = $db->getRow('SELECT `id_product`, COUNT(*) AS n FROM `' . $table . '`
        WHERE `id_shop` = ' . (int) AmzproShop::actingId() . ' AND `marketplace_id` = "" AND `ps_exists` = 1
          AND `sync_direction` IN ("ps_only", "conflict") AND `id_product` > 0
        GROUP BY `id_product` ORDER BY n DESC');
    $idProduct = (int) $row['id_product'];
    $pendingBefore = $sync->countPending();
    AmazonListingSettings::saveProductSetting($idProduct, false, '');
    check('switching a product off removes its rows from pending', $pendingBefore - $sync->countPending(), (int) $row['n']);
    $summary = $sync->syncPrestashopSide();
    check('the scan drops the switched-off product too', (int) $summary['ps_dropped'] >= 1, true);
    $db->execute('DELETE FROM `' . $settings . '` WHERE `id_product` = ' . $idProduct);

    // The scan says when the catalogue is bigger than one pass.
    $sync->resetStaged();
    $max = AmazonProductSync::$MAX_PRODUCTS;
    AmazonProductSync::$MAX_PRODUCTS = 2;
    $capped = new AmazonProductSync($client, $main, 'SELLERTEST');
    $capped->syncPrestashopSide();
    AmazonProductSync::$MAX_PRODUCTS = $max;
    check('capped scan warns with the real total',
        strpos(implode(' ', $capped->getNotices()), 'were read into the comparison') !== false, true);
});
done();
