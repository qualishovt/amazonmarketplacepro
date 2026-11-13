<?php
/**
 * 1.6.24: an order staged while still Pending (payment not yet taken) is
 * refreshed on the next import. Before, it stayed Pending for ever and was
 * never created in PrestaShop (Schwietz test order, 12 Nov 2026).
 *
 * Runs importNewOrders() against a fake Amazon client.
 *
 *     php tools/test/pending-refresh.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

/** Answers getOrders / getOrderItems from fixed data; no network. */
class PendingRefreshStubClient extends AmazonSpApiClient
{
    public $orders = [];

    public function __construct()
    {
    }

    public function request($method, $path, $query = [], $body = null)
    {
        if ($path === '/orders/v0/orders') {
            return ['status' => 200, 'body' => ['payload' => ['Orders' => $this->orders]]];
        }
        if (preg_match('#^/orders/v0/orders/([^/]+)/orderItems$#', $path, $m)) {
            return ['status' => 200, 'body' => ['payload' => ['OrderItems' => [[
                'OrderItemId' => 'IT-' . $m[1],
                'SellerSKU' => 'ZZP-NO-SUCH-SKU',
                'ASIN' => 'B000000000',
                'Title' => 'Test item',
                'QuantityOrdered' => 1,
                'ItemPrice' => ['Amount' => '19.95', 'CurrencyCode' => 'EUR'],
            ]]]]];
        }

        return ['status' => 404, 'body' => []];
    }

    public function getRestrictedDataToken($restrictedResources)
    {
        return false;
    }

    public function getLastError()
    {
        return null;
    }
}

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idShop = (int) AmzproShop::actingId();
AmzproShop::ensureSchema();

$order = function ($id, $status) {
    return [
        'AmazonOrderId' => $id,
        'PurchaseDate' => '2026-11-12T21:23:35Z',
        'OrderStatus' => $status,
        'OrderTotal' => ['Amount' => '24.90', 'CurrencyCode' => 'EUR'],
        'MarketplaceId' => 'A1PA6795UKMFR9',
        'FulfillmentChannel' => 'MFN',
    ];
};
$staged = function ($id) use ($db, $p) {
    $r = $db->getRow('SELECT o.`id_amazonmarketplacepro_order` AS id, o.`order_status`, o.`id_order`, o.`order_total`,
            (SELECT COUNT(*) FROM `' . $p . 'amazonmarketplacepro_order_item` i
             WHERE i.`id_amazonmarketplacepro_order` = o.`id_amazonmarketplacepro_order`) AS items
        FROM `' . $p . 'amazonmarketplacepro_order` o WHERE o.`amazon_order_id` = \'' . pSQL($id) . '\'');

    return $r ? $r : null;
};
$cleanup = function () use ($db, $p) {
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order_item` WHERE `amazon_order_id` LIKE \'ZZP-%\'');
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order` WHERE `amazon_order_id` LIKE \'ZZP-%\'');
};

$client = new PendingRefreshStubClient();
$importer = new AmazonOrderImporter($client, 'A1PA6795UKMFR9', $idShop);
$cleanup();
try {
    // 1. First seen while Pending: staged as Pending.
    $client->orders = [$order('ZZP-1', 'Pending'), $order('ZZP-2', 'Pending'), $order('ZZP-3', 'Unshipped')];
    $s = $importer->importNewOrders('2026-11-01T00:00:00Z');
    check('first import stages all three', $s['imported_new'], 3);
    check('ZZP-1 staged as Pending', $staged('ZZP-1')['order_status'], 'Pending');
    $firstId = (int) $staged('ZZP-1')['id'];

    // A run with nothing changed changes nothing.
    $s = $importer->importNewOrders('2026-11-01T00:00:00Z');
    check('unchanged: all already staged', [$s['already'], $s['imported_new']], [3, 0]);
    check('unchanged: nothing refreshed', [isset($s['refreshed']), isset($s['restaged'])], [false, false]);

    // 2. Paid: ZZP-1 leaves Pending and is staged again from scratch.
    //    ZZP-2 was cancelled before payment: status only.
    //    ZZP-3 shipped elsewhere: status only.
    $client->orders = [$order('ZZP-1', 'Unshipped'), $order('ZZP-2', 'Canceled'), $order('ZZP-3', 'Shipped')];
    $s = $importer->importNewOrders('2026-11-01T00:00:00Z');
    check('paid Pending order staged again', [$s['restaged'], $s['imported_new']], [1, 1]);
    check('status changes refreshed', $s['refreshed'], 2);
    check('ZZP-1 now Unshipped', $staged('ZZP-1')['order_status'], 'Unshipped');
    check('ZZP-1 is a new row, old one gone', (int) $staged('ZZP-1')['id'] !== $firstId, true);
    check('ZZP-1 keeps exactly one item', (int) $staged('ZZP-1')['items'], 1);
    check('ZZP-2 now Canceled, same row', $staged('ZZP-2')['order_status'], 'Canceled');
    check('ZZP-3 now Shipped', $staged('ZZP-3')['order_status'], 'Shipped');
    check('only one staged row per order', (int) $db->getValue('SELECT COUNT(*) FROM `' . $p . 'amazonmarketplacepro_order` WHERE `amazon_order_id` LIKE \'ZZP-%\''), 3);

    // 3. The paid order is now picked by "Create PrestaShop orders" (unless
    //    its items are unmatched, which this stub's SKU is): the Pending
    //    filter no longer excludes it.
    $picked = $db->getValue('SELECT COUNT(*) FROM `' . $p . 'amazonmarketplacepro_order` o
        WHERE o.`amazon_order_id` = \'ZZP-1\' AND o.`id_order` = 0 AND o.`import_status` = \'imported\'
          AND o.`order_status` <> \'Pending\'');
    check('paid order passes the Pending filter', (int) $picked, 1);

    // 4. An order already created in PrestaShop is never touched.
    $db->execute('UPDATE `' . $p . 'amazonmarketplacepro_order` SET `id_order` = 999999, `order_status` = \'Pending\'
        WHERE `amazon_order_id` = \'ZZP-3\'');
    $s = $importer->importNewOrders('2026-11-01T00:00:00Z');
    check('created order left alone', [$staged('ZZP-3')['order_status'], (int) $staged('ZZP-3')['id_order']], ['Pending', 999999]);
} finally {
    $cleanup();
}
done();
