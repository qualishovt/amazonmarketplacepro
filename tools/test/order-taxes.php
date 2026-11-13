<?php
/**
 * 1.6.25: invoices of Amazon orders show the VAT rate per line and the tax
 * breakdown (products and shipping). Before, the order had the right totals
 * but no order_detail_tax rows and no carrier_tax_rate, so the invoice said
 * "tax free" (Schwietz order 6002, 13 Nov 2026).
 *
 * Builds such an order with SQL, completes it with repairOrderTaxes() (what
 * the upgrade and the actionSetInvoice hook run), and reads the breakdown the
 * way PrestaShop's invoice does.
 *
 *     php tools/test/order-taxes.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$ctx = Context::getContext();
if (!$ctx->currency) {
    $ctx->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
}
$idShop = (int) AmzproShop::actingId();
$idLang = (int) Configuration::get('PS_LANG_DEFAULT');
$idCountry = (int) Country::getByIso('DE');
$idProduct = (int) $db->getValue('SELECT `id_product` FROM `' . $p . 'product` ORDER BY `id_product`');
$made = ['orders' => 0, 'detail' => 0, 'invoice' => 0, 'address' => 0, 'tax' => 0];

$db->execute("SET SESSION sql_mode = ''");
try {
    // A 19 % tax the shop may or may not have already.
    $tax19 = $db->getValue('SELECT `id_tax` FROM `' . $p . 'tax` WHERE `deleted` = 0 AND `rate` = 19');
    if (!$tax19) {
        $db->insert('tax', ['rate' => 19, 'active' => 1, 'deleted' => 0]);
        $tax19 = $made['tax'] = (int) $db->Insert_ID();
    }
    $tax19 = (int) $tax19;

    check('snapRate: 19.03 is the shop\'s 19 %', AmazonOrderCreator::snapRate(19.03), 19.0);
    check('snapRate: 0 stays 0', AmazonOrderCreator::snapRate(0), 0.0);

    $now = date('Y-m-d H:i:s');
    $db->insert('address', ['id_country' => $idCountry, 'id_customer' => 0, 'alias' => 'ZZT', 'lastname' => 'Test',
        'firstname' => 'Tax', 'address1' => 'Teststr. 1', 'postcode' => '35713', 'city' => 'Eschenburg',
        'date_add' => $now, 'date_upd' => $now, 'active' => 1, 'deleted' => 0]);
    $idAddress = $made['address'] = (int) $db->Insert_ID();

    // The order as 1.6.24 wrote it: totals right, no tax rows, no carrier rate.
    $db->insert('orders', ['reference' => 'ZZTAXTEST', 'id_shop_group' => (int) $ctx->shop->id_shop_group,
        'id_shop' => $idShop, 'id_carrier' => 0, 'id_lang' => $idLang, 'id_customer' => 0, 'id_cart' => 0,
        'id_currency' => (int) Configuration::get('PS_CURRENCY_DEFAULT'), 'id_address_delivery' => $idAddress,
        'id_address_invoice' => $idAddress, 'current_state' => 0, 'secure_key' => md5('zz'),
        'payment' => 'Amazon Marketplace', 'module' => 'amazonmarketplacepro', 'conversion_rate' => 1,
        'total_paid' => 24.90, 'total_paid_tax_incl' => 24.90, 'total_paid_tax_excl' => 20.92, 'total_paid_real' => 24.90,
        'total_products' => 16.76, 'total_products_wt' => 19.95,
        'total_shipping' => 4.95, 'total_shipping_tax_incl' => 4.95, 'total_shipping_tax_excl' => 4.16,
        'carrier_tax_rate' => 0, 'round_mode' => 2, 'round_type' => 2, 'valid' => 1,
        'date_add' => $now, 'date_upd' => $now]);
    $idOrder = $made['orders'] = (int) $db->Insert_ID();
    $db->insert('order_detail', ['id_order' => $idOrder, 'id_shop' => $idShop, 'product_id' => $idProduct,
        'product_attribute_id' => 0, 'product_name' => 'ZZ tax test', 'product_quantity' => 1,
        'product_price' => 16.76, 'unit_price_tax_excl' => 16.76, 'unit_price_tax_incl' => 19.95,
        'total_price_tax_excl' => 16.76, 'total_price_tax_incl' => 19.95, 'tax_rate' => 0, 'id_tax_rules_group' => 0]);
    $idDetail = $made['detail'] = (int) $db->Insert_ID();
    $db->insert('order_invoice', ['id_order' => $idOrder, 'number' => 0, 'delivery_number' => 0,
        'total_products' => 16.76, 'total_products_wt' => 19.95, 'total_paid_tax_excl' => 20.92, 'total_paid_tax_incl' => 24.90,
        'total_shipping_tax_excl' => 4.16, 'total_shipping_tax_incl' => 4.95, 'date_add' => $now]);
    $idInvoice = $made['invoice'] = (int) $db->Insert_ID();
    $db->execute('UPDATE `' . $p . 'order_detail` SET `id_order_invoice` = ' . $idInvoice . ' WHERE `id_order_detail` = ' . $idDetail);

    check('repair adds what is missing', AmazonOrderCreator::repairOrderTaxes($idOrder), true);

    $odt = $db->getRow('SELECT `id_tax`, `total_amount` FROM `' . $p . 'order_detail_tax` WHERE `id_order_detail` = ' . $idDetail);
    check('order line has a tax row of 3.19', $odt ? round((float) $odt['total_amount'], 2) : null, 3.19);
    check('the row is a 19 % tax', $odt ? (float) $db->getValue('SELECT `rate` FROM `' . $p . 'tax` WHERE `id_tax` = ' . (int) $odt['id_tax']) : null, 19.0);
    check('order line tax_rate 19', (float) $db->getValue('SELECT `tax_rate` FROM `' . $p . 'order_detail` WHERE `id_order_detail` = ' . $idDetail), 19.0);
    check('shipping tax rate 19', (float) $db->getValue('SELECT `carrier_tax_rate` FROM `' . $p . 'orders` WHERE `id_order` = ' . $idOrder), 19.0);
    check('invoice shipping tax row 0.79', round((float) $db->getValue('SELECT `amount` FROM `' . $p . 'order_invoice_tax`
        WHERE `type` = \'shipping\' AND `id_order_invoice` = ' . $idInvoice), 2), 0.79);

    // What the invoice PDF prints (PS 1.6 caches objects within a run).
    Cache::clean('objectmodel_*');
    $order = new Order($idOrder);
    $invoice = new OrderInvoice($idInvoice);
    $products = $invoice->getProductTaxesBreakdown($order);
    $first = $products ? reset($products) : null;
    check('invoice: product VAT 19 %', $first ? (float) $first['rate'] : null, 19.0);
    check('invoice: product VAT amount 3.19', $first ? round((float) $first['total_amount'], 2) : null, 3.19);
    $ship = $invoice->getShippingTaxesBreakdown($order);
    $s = $ship ? reset($ship) : null;
    check('invoice: shipping VAT 19 %', $s ? (float) $s['rate'] : null, 19.0);
    check('invoice: shipping VAT amount 0.79', $s ? round((float) $s['total_amount'], 2) : null, 0.79);
    $labels = OrderDetail::getTaxListStatic($idDetail);
    check('invoice: VAT column has the line\'s tax', count($labels), 1);

    check('second run changes nothing', AmazonOrderCreator::repairOrderTaxes($idOrder), false);
    check('still one tax row', (int) $db->getValue('SELECT COUNT(*) FROM `' . $p . 'order_detail_tax` WHERE `id_order_detail` = ' . $idDetail), 1);

    // A tax-free line gets no row.
    Cache::clean('objectmodel_*');
    $db->execute('DELETE FROM `' . $p . 'order_detail_tax` WHERE `id_order_detail` = ' . $idDetail);
    $db->execute('UPDATE `' . $p . 'order_detail` SET `total_price_tax_incl` = 16.76 WHERE `id_order_detail` = ' . $idDetail);
    Cache::clean('objectmodel_*');
    check('tax-free line: no row', AmazonOrderCreator::writeDetailTaxes(new OrderDetail($idDetail), new Address($idAddress)), 0);

    // New orders: the hook is registered and the creator sets the rate.
    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonOrderCreator.php');
    check('creator sets carrier_tax_rate', strpos($src, '$order->carrier_tax_rate = self::snapRate(') !== false, true);
    check('creator writes line taxes', strpos($src, 'self::writeDetailTaxes($detail, new Address(') !== false, true);
    $main = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php');
    check('install registers actionSetInvoice', strpos($main, "registerHook('actionSetInvoice')") !== false, true);
} finally {
    if ($made['detail']) {
        $db->execute('DELETE FROM `' . $p . 'order_detail_tax` WHERE `id_order_detail` = ' . (int) $made['detail']);
        $db->execute('DELETE FROM `' . $p . 'order_detail` WHERE `id_order_detail` = ' . (int) $made['detail']);
    }
    if ($made['invoice']) {
        $db->execute('DELETE FROM `' . $p . 'order_invoice_tax` WHERE `id_order_invoice` = ' . (int) $made['invoice']);
        $db->execute('DELETE FROM `' . $p . 'order_invoice` WHERE `id_order_invoice` = ' . (int) $made['invoice']);
    }
    if ($made['orders']) {
        $db->execute('DELETE FROM `' . $p . 'orders` WHERE `id_order` = ' . (int) $made['orders']);
    }
    if ($made['address']) {
        $db->execute('DELETE FROM `' . $p . 'address` WHERE `id_address` = ' . (int) $made['address']);
    }
    if ($made['tax']) {
        $db->execute('DELETE FROM `' . $p . 'tax` WHERE `id_tax` = ' . (int) $made['tax']);
    }
}
done();
