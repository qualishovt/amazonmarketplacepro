<?php
/**
 * 1.6.27: the Amazon box on the order page and refunds, plus the carrier
 * line's weight (Schwietz order 6002 showed 0.000 kg).
 *
 * Builds a staged Amazon order and its PrestaShop order with SQL, then checks
 * what the box shows, what a refund may be, the order adjustment feed it
 * sends, and the weight repair. Nothing is sent to Amazon.
 *
 *     php tools/test/order-panel.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$ctx = Context::getContext();
$idShop = (int) AmzproShop::actingId();
$amazonId = 'ZZR-1234567-7654321';
$made = ['orders' => 0, 'staged' => 0, 'weight' => null, 'idProduct' => 0];
$db->execute("SET SESSION sql_mode = ''");
AmazonRefundManager::ensureTable();

$cleanup = function () use ($db, $p, $amazonId, &$made) {
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_refund` WHERE `amazon_order_id` = \'' . $amazonId . '\'');
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order_item` WHERE `amazon_order_id` = \'' . $amazonId . '\'');
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_order` WHERE `amazon_order_id` = \'' . $amazonId . '\'');
    if ($made['orders']) {
        $db->execute('DELETE FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . (int) $made['orders']);
        $db->execute('DELETE FROM `' . $p . 'order_detail` WHERE `id_order` = ' . (int) $made['orders']);
        $db->execute('DELETE FROM `' . $p . 'orders` WHERE `id_order` = ' . (int) $made['orders']);
    }
    if ($made['weight'] !== null) {
        $db->execute('UPDATE `' . $p . 'product` SET `weight` = ' . (float) $made['weight'] . ' WHERE `id_product` = ' . (int) $made['idProduct']);
    }
};
$cleanup();

try {
    $idProduct = $made['idProduct'] = (int) $db->getValue('SELECT `id_product` FROM `' . $p . 'product` ORDER BY `id_product`');
    $made['weight'] = (float) $db->getValue('SELECT `weight` FROM `' . $p . 'product` WHERE `id_product` = ' . $idProduct);
    $db->execute('UPDATE `' . $p . 'product` SET `weight` = 0.04 WHERE `id_product` = ' . $idProduct);
    $idCarrier = (int) $db->getValue('SELECT `id_carrier` FROM `' . $p . 'carrier` WHERE `deleted` = 0 ORDER BY `id_carrier`');
    $now = date('Y-m-d H:i:s');

    $db->insert('orders', ['reference' => 'ZZREFUND', 'id_shop_group' => (int) $ctx->shop->id_shop_group, 'id_shop' => $idShop,
        'id_carrier' => $idCarrier, 'id_lang' => (int) Configuration::get('PS_LANG_DEFAULT'), 'id_customer' => 0, 'id_cart' => 0,
        'id_currency' => (int) Configuration::get('PS_CURRENCY_DEFAULT'), 'id_address_delivery' => 0, 'id_address_invoice' => 0,
        'current_state' => 0, 'secure_key' => md5('zz'), 'payment' => 'Amazon Marketplace', 'module' => 'amazonmarketplacepro',
        'conversion_rate' => 1, 'total_paid' => 24.90, 'total_paid_tax_incl' => 24.90, 'total_paid_tax_excl' => 20.92,
        'total_products' => 16.76, 'total_products_wt' => 19.95, 'total_shipping' => 4.95, 'total_shipping_tax_incl' => 4.95,
        'total_shipping_tax_excl' => 4.16, 'valid' => 1, 'date_add' => $now, 'date_upd' => $now]);
    $idOrder = $made['orders'] = (int) $db->Insert_ID();
    // A line as 1.6.26 wrote it: no weight.
    $db->insert('order_detail', ['id_order' => $idOrder, 'id_shop' => $idShop, 'product_id' => $idProduct,
        'product_attribute_id' => 0, 'product_name' => 'ZZ refund test', 'product_quantity' => 1, 'product_weight' => 0,
        'unit_price_tax_excl' => 16.76, 'unit_price_tax_incl' => 19.95, 'total_price_tax_excl' => 16.76, 'total_price_tax_incl' => 19.95]);
    $db->insert('order_carrier', ['id_order' => $idOrder, 'id_carrier' => $idCarrier, 'weight' => 0,
        'shipping_cost_tax_excl' => 4.16, 'shipping_cost_tax_incl' => 4.95, 'tracking_number' => '', 'date_add' => $now]);

    $raw = json_encode(['AmazonOrderId' => $amazonId, 'SalesChannel' => 'Amazon.de', 'ShipServiceLevel' => 'Std DE Dom_6',
        'EarliestShipDate' => '2026-11-12T23:00:00Z', 'LatestShipDate' => '2026-11-13T22:59:59Z']);
    $db->insert('amazonmarketplacepro_order', ['amazon_order_id' => $amazonId, 'purchase_date' => $now, 'order_status' => 'Shipped',
        'order_total' => 24.90, 'currency' => 'EUR', 'marketplace_id' => 'A1PA6795UKMFR9', 'fulfillment_channel' => 'MFN',
        'shipping_total' => 4.95, 'raw_json' => pSQL($raw, true), 'id_order' => $idOrder, 'import_status' => 'created',
        'id_shop' => $idShop, 'date_add' => $now, 'date_upd' => $now]);
    $staged = (int) $db->Insert_ID();
    $db->insert('amazonmarketplacepro_order_item', ['id_amazonmarketplacepro_order' => $staged, 'amazon_order_id' => $amazonId,
        'order_item_id' => '70695585306122', 'seller_sku' => '000013', 'title' => 'Microlautsprecher US-3750 B', 'quantity' => 1,
        'item_price' => 16.76, 'item_tax' => 3.19, 'currency' => 'EUR', 'id_product' => $idProduct, 'match_status' => 'matched']);

    // Weight: the line and the carrier line get the product's weight.
    check('weight repair reports a change', AmazonOrderCreator::ensureOrderCarrier($idOrder), true);
    check('line weight from the product', (float) $db->getValue('SELECT `product_weight` FROM `' . $p . 'order_detail` WHERE `id_order` = ' . $idOrder), 0.04);
    check('carrier line weight 0.040 kg', round((float) $db->getValue('SELECT `weight` FROM `' . $p . 'order_carrier` WHERE `id_order` = ' . $idOrder), 3), 0.04);
    check('weight set once, then left alone', AmazonOrderCreator::ensureOrderCarrier($idOrder), false);
    check('productWeight adds nothing for no combination', AmazonOrderCreator::productWeight($idProduct, 0), 0.04);

    // What the box shows.
    Cache::clean('objectmodel_*');
    $data = AmazonRefundManager::orderData($idOrder);
    check('box: Amazon order number', $data['amazon_order_id'], $amazonId);
    check('box: sales channel', $data['sales_channel'], 'Amazon.de');
    check('box: shipping service', $data['ship_service_level'], 'Std DE Dom_6');
    check('box: latest ship date in shop time', $data['latest_ship'], date('Y-m-d H:i:s', strtotime('2026-11-13T22:59:59Z')));
    check('box: item paid incl. VAT', $data['items'][0]['paid'], 19.95);
    check('box: shipping paid', $data['shipping_paid'], 4.95);
    check('a non-Amazon order shows no box', AmazonRefundManager::orderData(999999999), null);

    // Refund checks.
    $code = '70695585306122';
    list($err) = AmazonRefundManager::validate($data, ['items' => [$code => '19,95'], 'reason' => '']);
    check('no reason: refused', $err !== null, true);
    list($err) = AmazonRefundManager::validate($data, ['items' => [$code => '20.00'], 'reason' => 'CustomerReturn']);
    check('more than paid: refused', $err !== null, true);
    list($err) = AmazonRefundManager::validate($data, ['shipping' => '5.00', 'reason' => 'CustomerReturn']);
    check('more shipping than paid: refused', $err !== null, true);
    list($err) = AmazonRefundManager::validate($data, ['reason' => 'CustomerReturn']);
    check('nothing to refund: refused', $err !== null, true);
    list($err, $req) = AmazonRefundManager::validate($data, ['items' => [$code => '19,95'], 'shipping' => '4.95', 'extra' => '1', 'reason' => 'CustomerReturn']);
    check('full refund accepted', [$err, $req['total']], [null, 25.90]);

    // The feed.
    $xml = AmazonRefundManager::buildXml('AJAB7RSIP6LCN', $amazonId, $data, $req);
    $doc = simplexml_load_string($xml);
    check('feed parses', $doc !== false, true);
    check('feed: message type', (string) $doc->MessageType, 'OrderAdjustment');
    $item = $doc->Message->OrderAdjustment->AdjustedItem;
    check('feed: order and item', [(string) $doc->Message->OrderAdjustment->AmazonOrderID, (string) $item->AmazonOrderItemCode], [$amazonId, $code]);
    check('feed: reason', (string) $item->AdjustmentReason, 'CustomerReturn');
    $components = [];
    foreach ($item->ItemPriceAdjustments->Component as $c) {
        $components[(string) $c->Type] = (string) $c->Amount . ' ' . (string) $c->Amount['currency'];
    }
    check('feed: amounts', $components, ['Principal' => '19.95 EUR', 'Shipping' => '4.95 EUR', 'Goodwill' => '1.00 EUR']);

    // Shipping only still goes on an item line.
    list($err, $req2) = AmazonRefundManager::validate($data, ['shipping' => '4.95', 'reason' => 'GeneralAdjustment']);
    $doc2 = simplexml_load_string(AmazonRefundManager::buildXml('X', $amazonId, $data, $req2));
    check('shipping-only refund rides on the first item', [(string) $doc2->Message->OrderAdjustment->AdjustedItem->AmazonOrderItemCode,
        count($doc2->Message->OrderAdjustment->AdjustedItem->ItemPriceAdjustments->Component)], [$code, 2]);

    // What was refunded is subtracted next time.
    $db->insert('amazonmarketplacepro_refund', ['id_shop' => $idShop, 'id_order' => $idOrder, 'amazon_order_id' => $amazonId,
        'reason' => 'CustomerReturn', 'amount' => 10, 'currency' => 'EUR',
        'lines_json' => pSQL(json_encode(['items' => [$code => 10.0], 'shipping' => 0, 'extra' => 0])), 'feed_id' => 'F1', 'date_add' => $now]);
    $data = AmazonRefundManager::orderData($idOrder);
    check('after a 10.00 refund: 9.95 left', [$data['items'][0]['left'], $data['refunded_total']], [9.95, 10.0]);
    list($err) = AmazonRefundManager::validate($data, ['items' => [$code => '10'], 'reason' => 'CustomerReturn']);
    check('refunding twice beyond the price: refused', $err !== null, true);

    // Template and hooks.
    $tpl = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/views/templates/admin/order_panel.tpl');
    foreach (array_keys(AmazonRefundManager::reasons()) as $r) {
        if (strpos($tpl, 'value="' . $r . '"') === false) {
            check("reason $r offered in the box", false, true);
        }
    }
    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php');
    check('order page and list hooks registered', strpos($src, "'displayAdminOrderMain', 'actionOrderGridDefinitionModifier'") !== false, true);

    // PS 1.6 list hook adds the column and its filter.
    $module = Module::getInstanceByName('amazonmarketplacepro');
    // AdminOrdersController passes references, as here.
    $select = 'a.id_order';
    $join = '';
    $fields = [];
    $module->hookActionAdminOrdersListingFieldsModifier(['select' => &$select, 'join' => &$join, 'fields' => &$fields]);
    check('legacy list: column + join', [isset($fields['amazon_order_id']), strpos($join, 'amazonmarketplacepro_order') !== false], [true, true]);
} finally {
    $cleanup();
}
done();
