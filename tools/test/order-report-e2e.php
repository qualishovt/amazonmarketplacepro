<?php
/**
 * End-to-end check of the order-report address fallback against a real shop.
 *
 * Stages its own orders (ids 999-0000001-000000N), gives some of them a
 * PrestaShop order, feeds a report through the real parser and applier,
 * checks every outcome, and deletes everything it created - before starting,
 * at the end, and from a shutdown handler if a fatal error cuts the run short.
 *
 * HOOKS. A dev shop has other modules listening on customer, order, payment
 * and status events - exporters that upload every new customer, marketplace
 * modules that push status changes. A test must not set those off. So:
 *   - customers and order rows are written with plain SQL (no hooks);
 *   - addresses go through AmazonOrderCreator::createAddress(), the code under
 *     test, which fires only the Address hooks - and those are audited first:
 *     the run stops before touching anything if a module outside the reviewed
 *     allowlist is attached to them.
 *
 *     php tools/test/order-report-e2e.php <shop root>            run the test
 *     php tools/test/order-report-e2e.php <shop root> cleanup    only remove leftovers
 */

// It writes to the shop's database: command line only, never over HTTP.
if (PHP_SAPI !== 'cli') {
    exit;
}

$root = rtrim(str_replace('\\', '/', isset($argv[1]) ? $argv[1] : ''), '/');
$mode = isset($argv[2]) ? $argv[2] : '';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';
require $root . '/config/config.inc.php';

// The module copy inside the shop being tested, not this checkout.
require_once $root . '/modules/amazonmarketplacepro/classes/AmazonOrderCreator.php';
require_once $root . '/modules/amazonmarketplacepro/classes/AmazonOrderReportImporter.php';
require_once $root . '/modules/amazonmarketplacepro/classes/AmazonPiiPurger.php';

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;
$GLOBALS['cleaned'] = false;

/**
 * Modules reviewed and allowed to see this test's Address add/update.
 *
 * itcelinvoicefields (1.6 shop): on add/update it calls
 * ItcFEHelper::manageInvoiceData(), which saves one row per address in its
 * own itcfefields table - local only, no network or mail. deleteAddresses()
 * removes those rows with the addresses.
 */
$GLOBALS['hookAllowlist'] = array('itcelinvoicefields');

$ids = array(
    'A' => '999-0000001-0000001', // staged only, no PrestaShop order yet
    'B' => '999-0000001-0000002', // PrestaShop order with placeholder address
    'C' => '999-0000001-0000003', // PrestaShop order whose address the merchant edited
    'D' => '999-0000001-0000004', // buyer data already purged
    'E' => '999-0000001-0000005', // in the report, never imported
);

function check($ok, $label, $got = null)
{
    if ($ok) {
        $GLOBALS['pass']++;
        echo "  ok    $label\n";
    } else {
        $GLOBALS['fail']++;
        echo "  FAIL  $label" . ($got !== null ? '  (got: ' . var_export($got, true) . ')' : '') . "\n";
    }
}

function testEmail($amazonId)
{
    return 'amzpro-e2e-' . str_replace('-', '', $amazonId) . '@marketplace.invalid';
}

/** Every module attached to a hook this test fires, outside the allowlist. */
function unexpectedListeners()
{
    $hooks = array(
        'actionObjectAddressAddBefore', 'actionObjectAddressAddAfter',
        'actionObjectAddressUpdateBefore', 'actionObjectAddressUpdateAfter',
        'actionObjectAddBefore', 'actionObjectAddAfter',
        'actionObjectUpdateBefore', 'actionObjectUpdateAfter',
    );
    $p = _DB_PREFIX_;
    $rows = Db::getInstance()->executeS(
        "SELECT DISTINCT h.`name` AS hook, m.`name` AS module
         FROM `{$p}hook_module` hm
         JOIN `{$p}hook` h ON h.`id_hook` = hm.`id_hook`
         JOIN `{$p}module` m ON m.`id_module` = hm.`id_module`
         WHERE m.`active` = 1 AND h.`name` IN ('" . implode("','", $hooks) . "')"
    );
    $out = array();
    foreach ((array) $rows as $r) {
        if (!in_array($r['module'], $GLOBALS['hookAllowlist'], true)) {
            $out[] = $r['module'] . ' on ' . $r['hook'];
        }
    }

    return $out;
}

/** Delete addresses, and the row itcelinvoicefields writes for each one it sees. */
function deleteAddresses(array $idAddresses)
{
    $ids = array_values(array_filter(array_unique(array_map('intval', $idAddresses))));
    if (!$ids) {
        return;
    }
    $db = Db::getInstance();
    if (is_file(_PS_MODULE_DIR_ . 'itcelinvoicefields/classes/ItcFE.php')) {
        require_once _PS_MODULE_DIR_ . 'itcelinvoicefields/classes/ItcFE.php';
        if (class_exists('ItcFE') && isset(ItcFE::$definition['table'])) {
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . bqSQL(ItcFE::$definition['table'])
                . '` WHERE `id_address` IN (' . implode(',', $ids) . ')');
        }
    }
    $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` IN (' . implode(',', $ids) . ')');
}

function cleanup(array $ids)
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $in = "'" . implode("','", array_map('pSQL', $ids)) . "'";

    $emails = array();
    foreach ($ids as $id) {
        $emails[] = testEmail($id);
        $emails[] = Tools::strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $id)) . '@marketplace.amazon';
    }
    $emailIn = "'" . implode("','", array_map('pSQL', $emails)) . "'";

    $rows = $db->executeS("SELECT `id_order` FROM `{$p}amazonmarketplacepro_order`
                           WHERE `amazon_order_id` IN ($in) AND `id_order` > 0");
    foreach ((array) $rows as $r) {
        $idOrder = (int) $r['id_order'];
        $o = $db->getRow("SELECT `id_cart`, `id_address_delivery`, `id_address_invoice`
                          FROM `{$p}orders` WHERE `id_order` = $idOrder");
        if (!$o) {
            continue;
        }
        if ((int) $o['id_cart'] > 0) {
            $db->execute("DELETE FROM `{$p}cart_product` WHERE `id_cart` = " . (int) $o['id_cart']);
            $db->execute("DELETE FROM `{$p}cart` WHERE `id_cart` = " . (int) $o['id_cart']);
        }
        $db->execute("DELETE FROM `{$p}order_detail` WHERE `id_order` = $idOrder");
        $db->execute("DELETE FROM `{$p}order_history` WHERE `id_order` = $idOrder");
        $db->execute("DELETE FROM `{$p}orders` WHERE `id_order` = $idOrder");
        deleteAddresses(array((int) $o['id_address_delivery'], (int) $o['id_address_invoice']));
    }

    // Only customers this test created, matched by their e-mail addresses.
    $customers = $db->executeS("SELECT `id_customer` FROM `{$p}customer` WHERE `email` IN ($emailIn)");
    foreach ((array) $customers as $c) {
        $idc = (int) $c['id_customer'];
        if ((int) $db->getValue("SELECT COUNT(*) FROM `{$p}orders` WHERE `id_customer` = $idc")) {
            echo "  note: customer #$idc still has an order, left in place\n";
            continue;
        }
        $addr = $db->executeS("SELECT `id_address` FROM `{$p}address` WHERE `id_customer` = $idc");
        deleteAddresses(array_map(function ($row) {
            return (int) $row['id_address'];
        }, (array) $addr));
        $db->execute("DELETE FROM `{$p}customer_group` WHERE `id_customer` = $idc");
        $db->execute("DELETE FROM `{$p}customer` WHERE `id_customer` = $idc");
    }

    $db->execute("DELETE FROM `{$p}amazonmarketplacepro_order_item` WHERE `amazon_order_id` IN ($in)");
    $db->execute("DELETE FROM `{$p}amazonmarketplacepro_order` WHERE `amazon_order_id` IN ($in)");
}

/** A placeholder customer, written with SQL so no add hook fires. */
function precreateCustomer($amazonId)
{
    $db = Db::getInstance();
    $ctx = Context::getContext();
    $now = date('Y-m-d H:i:s');
    $idGroup = (int) Configuration::get('PS_CUSTOMER_GROUP');
    $db->insert('customer', array(
        'id_shop_group' => (int) $ctx->shop->id_shop_group,
        'id_shop' => (int) $ctx->shop->id,
        'id_gender' => 0,
        'id_default_group' => $idGroup,
        'id_lang' => (int) $ctx->language->id,
        'firstname' => pSQL(AmazonOrderCreator::$PLACEHOLDER_FIRSTNAME),
        'lastname' => pSQL(AmazonOrderCreator::$PLACEHOLDER_LASTNAME),
        'email' => pSQL(testEmail($amazonId)),
        'passwd' => md5(uniqid('', true)),
        'secure_key' => md5(uniqid('', true)),
        'active' => 1,
        'is_guest' => 1,
        'deleted' => 0,
        'date_add' => $now,
        'date_upd' => $now,
    ));
    $id = (int) $db->Insert_ID();
    if ($id) {
        $db->insert('customer_group', array('id_customer' => $id, 'id_group' => $idGroup));
    }

    return $id;
}

function stage($amazonId, array $over, $idProduct)
{
    $db = Db::getInstance();
    $now = date('Y-m-d H:i:s');
    $row = array_merge(array(
        'id_shop' => (int) Context::getContext()->shop->id,
        'amazon_order_id' => $amazonId,
        'purchase_date' => $now,
        'order_status' => 'Unshipped',
        'order_total' => 19.99,
        'currency' => 'EUR',
        'buyer_email' => testEmail($amazonId),
        'buyer_name' => '',
        'marketplace_id' => 'A1PA6795UKMFR9',
        'fulfillment_channel' => 'MFN',
        'ship_address1' => '',
        'ship_city' => 'Berlin',
        'ship_postal_code' => '10115',
        'ship_country_code' => 'DE',
        'ship_phone' => '',
        'items_matched' => 1,
        'import_status' => 'imported',
        'date_add' => $now,
        'date_upd' => $now,
    ), $over);
    $db->insert('amazonmarketplacepro_order', $row);
    $idStaged = (int) $db->Insert_ID();
    $db->insert('amazonmarketplacepro_order_item', array(
        'id_amazonmarketplacepro_order' => $idStaged,
        'amazon_order_id' => $amazonId,
        'order_item_id' => '1',
        'seller_sku' => 'E2E-SKU',
        'title' => 'E2E test item',
        'quantity' => 1,
        'item_price' => 16.80,
        'item_tax' => 3.19,
        'currency' => 'EUR',
        'id_product' => (int) $idProduct,
        'id_product_attribute' => 0,
        'match_status' => 'matched',
    ));

    return $idStaged;
}

function staged($amazonId)
{
    return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
        WHERE `amazon_order_id` = \'' . pSQL($amazonId) . '\'');
}

/**
 * Give a staged order a PrestaShop order.
 *
 * The address is built by the real AmazonOrderCreator::createAddress() - that
 * is the code under test. The order row itself is plain SQL: creating it the
 * normal way also fires the order, payment and status hooks.
 */
function createPsOrder($amazonId, $idCarrier)
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $ctx = Context::getContext();

    $idCustomer = (int) $db->getValue("SELECT `id_customer` FROM `{$p}customer`
        WHERE `email` = '" . pSQL(testEmail($amazonId)) . "' AND `id_shop` = " . (int) $ctx->shop->id);
    if (!$idCustomer) {
        echo "  (no pre-created customer for $amazonId)\n";

        return 0;
    }

    $creator = new AmazonOrderCreator($idCarrier, (int) Configuration::get('PS_OS_BANKWIRE'));
    $method = new ReflectionMethod('AmazonOrderCreator', 'createAddress');
    $method->setAccessible(true);
    $address = $method->invoke($creator, new Customer($idCustomer), staged($amazonId),
        AmazonOrderCreator::shopEnvironment((int) $ctx->shop->id));
    if (!$address || !$address->id) {
        echo "  (createAddress failed for $amazonId)\n";

        return 0;
    }

    $db->execute("SET SESSION sql_mode = ''");
    $now = date('Y-m-d H:i:s');
    $db->insert('orders', array(
        'reference' => pSQL(Tools::strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'))),
        'id_shop_group' => (int) $ctx->shop->id_shop_group,
        'id_shop' => (int) $ctx->shop->id,
        'id_carrier' => (int) $idCarrier,
        'id_lang' => (int) $ctx->language->id,
        'id_customer' => $idCustomer,
        'id_cart' => 0,
        'id_currency' => (int) Configuration::get('PS_CURRENCY_DEFAULT'),
        'id_address_delivery' => (int) $address->id,
        'id_address_invoice' => (int) $address->id,
        'current_state' => 0,
        'secure_key' => md5(uniqid('', true)),
        'payment' => 'Amazon Marketplace',
        'module' => 'amazonmarketplacepro',
        'conversion_rate' => 1,
        'total_paid' => 19.99,
        'total_paid_tax_incl' => 19.99,
        'total_paid_tax_excl' => 16.80,
        'total_paid_real' => 19.99,
        'total_products' => 16.80,
        'total_products_wt' => 19.99,
        'invoice_date' => '0000-00-00 00:00:00',
        'delivery_date' => '0000-00-00 00:00:00',
        'valid' => 0,
        'date_add' => $now,
        'date_upd' => $now,
    ));
    $idOrder = (int) $db->Insert_ID();
    if ($idOrder) {
        $db->execute("UPDATE `{$p}amazonmarketplacepro_order` SET `id_order` = $idOrder, `import_status` = 'created'
                      WHERE `amazon_order_id` = '" . pSQL($amazonId) . "'");
    }

    return $idOrder;
}

register_shutdown_function(function () use ($ids) {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) {
        echo 'FATAL: ' . $e['message'] . "\n  at " . $e['file'] . ':' . $e['line'] . "\n";
    }
    if (!$GLOBALS['cleaned']) {
        cleanup(array_values($ids));
        echo "cleaned up after an interrupted run\n";
    }
});

$db = Db::getInstance();
$p = _DB_PREFIX_;
cleanup(array_values($ids));
if ($mode === 'cleanup') {
    $GLOBALS['cleaned'] = true;
    echo "leftovers removed\n";
    exit(0);
}

try {
    echo 'PrestaShop ' . _PS_VERSION_ . ' / PHP ' . PHP_VERSION . "\n";

    // ------------------------------------------------------------ hook audit
    $unexpected = unexpectedListeners();
    check(!$unexpected, 'only reviewed modules listen on the Address hooks this test fires', $unexpected);
    if ($unexpected) {
        throw new Exception('refusing to run: review these listeners first');
    }

    // ---------------------------------------------------------------- schema
    echo "schema\n";
    $importer = new AmazonOrderReportImporter();
    $importer->ensureSchema();
    $purger = new AmazonPiiPurger();
    $purger->ensureSchema();
    $col = $db->executeS("SHOW COLUMNS FROM `{$p}amazonmarketplacepro_order` LIKE 'ship_name'");
    check(!empty($col), 'ship_name column exists');
    $prop = new ReflectionProperty('AmazonPiiPurger', 'piiColumns');
    $prop->setAccessible(true);
    check(in_array('ship_name', $prop->getValue(), true), 'the purger clears ship_name with the other PII columns');

    // --------------------------------------------------------- splitFullName
    echo "splitFullName\n";
    $s = AmazonOrderCreator::splitFullName('Jürgen Müller');
    check($s === array('firstname' => 'Jürgen', 'lastname' => 'Müller'), 'keeps accented letters', $s);
    $s = AmazonOrderCreator::splitFullName('');
    check($s === array('firstname' => 'Amazon', 'lastname' => 'Buyer'), 'empty name -> placeholders', $s);
    $s = AmazonOrderCreator::splitFullName('Madonna');
    check($s === array('firstname' => 'Madonna', 'lastname' => 'Buyer'), 'one word -> placeholder last name', $s);
    $s = AmazonOrderCreator::splitFullName('Anne-Marie 3rd');
    check($s === array('firstname' => 'Anne-Marie', 'lastname' => 'rd'), 'digits removed, as Validate::isName requires', $s);
    $s = AmazonOrderCreator::splitFullName("O'Brien Seán");
    check($s === array('firstname' => "O'Brien", 'lastname' => 'Seán'), 'apostrophe kept', $s);
    $limit = min(Customer::$definition['fields']['lastname']['size'], Address::$definition['fields']['lastname']['size']);
    $s = AmazonOrderCreator::splitFullName('A ' . str_repeat('x', 300));
    check(Tools::strlen($s['lastname']) === (int) $limit, "long last name cut to the $limit-character column", Tools::strlen($s['lastname']));
    check(Validate::isName($s['firstname']) && Validate::isName($s['lastname']), 'result passes Validate::isName');

    // ------------------------------------------------------------------- parse
    echo "parse\n";
    $header = array('order-id', 'order-item-id', 'purchase-date', 'buyer-email', 'buyer-name', 'buyer-phone-number',
        'sku', 'product-name', 'quantity-purchased', 'recipient-name', 'ship-address-1', 'ship-address-2',
        'ship-address-3', 'ship-city', 'ship-state', 'ship-postal-code', 'ship-country', 'ship-phone-number');
    $line = function (array $v) use ($header) {
        $row = array();
        foreach ($header as $h) {
            $row[] = isset($v[$h]) ? $v[$h] : '';
        }

        return implode("\t", $row);
    };
    $lines = array(
        implode("\t", $header),
        // A, two item lines: the phone only on the second one.
        $line(array('order-id' => $ids['A'], 'sku' => 'S1', 'buyer-name' => 'Anna Schmidt', 'recipient-name' => 'Anna Schmidt',
            'ship-address-1' => 'Lindenstraße 12', 'ship-address-2' => 'Hinterhaus', 'ship-address-3' => '3. OG',
            'ship-city' => 'Berlin', 'ship-postal-code' => '10115', 'ship-country' => 'DE')),
        $line(array('order-id' => $ids['A'], 'sku' => 'S2', 'ship-phone-number' => '+49 30 1234567')),
        // B: a gift - buyer and recipient differ; characters PrestaShop rejects; an emoji.
        $line(array('order-id' => $ids['B'], 'sku' => 'S3', 'buyer-name' => 'Jürgen Müller',
            'recipient-name' => "Zoë \xF0\x9F\x98\x80 Ångström-Łukasiewicz", 'ship-address-1' => 'Hauptstr. 1 @ Hinterhof!',
            'ship-city' => 'Berlin', 'ship-postal-code' => '80331 ', 'ship-country' => 'DE',
            'ship-phone-number' => "+49 (89) 123/456 \xF0\x9F\x98\x80")),
        $line(array('order-id' => $ids['C'], 'sku' => 'S4', 'buyer-name' => 'Carl Test', 'recipient-name' => 'Carl Test',
            'ship-address-1' => 'Report Road 9', 'ship-city' => 'Berlin', 'ship-postal-code' => '10115', 'ship-country' => 'DE')),
        $line(array('order-id' => $ids['D'], 'sku' => 'S5', 'buyer-name' => 'Dora Purged', 'recipient-name' => 'Dora Purged',
            'ship-address-1' => 'Gone Lane 1', 'ship-city' => 'Berlin', 'ship-postal-code' => '10115', 'ship-country' => 'DE')),
        $line(array('order-id' => $ids['E'], 'sku' => 'S6', 'buyer-name' => 'Eve Later', 'recipient-name' => 'Eve Later',
            'ship-address-1' => 'Future Way 2', 'ship-city' => 'Berlin', 'ship-postal-code' => '10115', 'ship-country' => 'DE')),
        "not-an-order\tx",
        '',
    );
    $tsv = implode("\n", $lines);

    $parsed = $importer->parse($tsv);
    check($parsed['error'] === null, 'tab-separated report parses');
    check(count($parsed['orders']) === 5, 'five orders, the two lines of A merged', count($parsed['orders']));
    check($parsed['invalid'] === 1, 'the malformed order id is counted and skipped', $parsed['invalid']);
    check($parsed['orders'][$ids['A']]['phone'] === '+49 30 1234567', 'a field present only on a later line is picked up');
    check($parsed['orders'][$ids['A']]['address2'] === 'Hinterhaus, 3. OG', 'ship-address-3 folded into the second line');

    $again = $importer->parse("\xEF\xBB\xBF" . str_replace("\n", "\r\n", $tsv));
    check($again['orders'] == $parsed['orders'], 'UTF-8 with BOM and CRLF gives the same result');
    $again = $importer->parse("\xFF\xFE" . mb_convert_encoding($tsv, 'UTF-16LE', 'UTF-8'));
    check($again['orders'] == $parsed['orders'], 'UTF-16LE with BOM gives the same result');

    $cp = $line(array('order-id' => $ids['A'], 'buyer-name' => 'Jürgen Müller', 'ship-address-1' => 'Straße 1'));
    $again = $importer->parse(mb_convert_encoding(implode("\t", $header) . "\n" . $cp, 'Windows-1252', 'UTF-8'));
    check($again['orders'][$ids['A']]['buyer_name'] === 'Jürgen Müller', 'Windows-1252 is converted to UTF-8',
        $again['orders'][$ids['A']]['buyer_name']);

    $csv = "order-id;recipient-name;ship-address-1;ship-city\n" . $ids['A'] . ';"Schmidt; Anna";"Linden ""12""";Berlin';
    $again = $importer->parse($csv);
    check($again['error'] === null && $again['orders'][$ids['A']]['ship_name'] === 'Schmidt; Anna'
        && $again['orders'][$ids['A']]['address1'] === 'Linden "12"', 'a spreadsheet-saved semicolon file with quotes');

    $r = $importer->parse('');
    check($r['error'] === 'no_order_id', 'empty file -> no_order_id');
    $r = $importer->parse("sku\tquantity\nX\t1");
    check($r['error'] === 'no_order_id', 'another report entirely -> no_order_id');
    $r = $importer->parse("order-id\tsku\n" . $ids['A'] . "\tX");
    check($r['error'] === 'no_address', 'an order report without addresses -> no_address');

    // ----------------------------------------------------------------- set up
    echo "set up\n";
    $idProduct = (int) $db->getValue("SELECT `id_product` FROM `{$p}product` WHERE `active` = 1 ORDER BY `id_product`");
    $idCarrier = (int) $db->getValue("SELECT `id_carrier` FROM `{$p}carrier` WHERE `active` = 1 AND `deleted` = 0 ORDER BY `id_carrier`");
    check($idProduct > 0 && $idCarrier > 0, "product #$idProduct, carrier #$idCarrier");

    foreach (array('A', 'B', 'C') as $k) {
        check(precreateCustomer($ids[$k]) > 0, "customer for $k written directly, without Customer::add()");
    }

    stage($ids['A'], array(), $idProduct);
    stage($ids['B'], array(), $idProduct);
    stage($ids['C'], array(), $idProduct);
    stage($ids['D'], array('order_status' => 'Shipped', 'ship_city' => '', 'ship_postal_code' => '',
        'buyer_email' => '', 'pii_purged_at' => date('Y-m-d H:i:s')), $idProduct);

    $idOrderB = createPsOrder($ids['B'], $idCarrier);
    $idOrderC = createPsOrder($ids['C'], $idCarrier);
    check($idOrderB > 0 && $idOrderC > 0, "PrestaShop orders #$idOrderB (B) and #$idOrderC (C) created");
    if (!$idOrderB || !$idOrderC) {
        throw new Exception('cannot continue without the PrestaShop orders');
    }

    $oB = new Order($idOrderB);
    $aB = new Address((int) $oB->id_address_delivery);
    check($aB->address1 === AmazonOrderCreator::$PLACEHOLDER_ADDRESS1 && $aB->firstname === 'Amazon'
        && $aB->phone === AmazonOrderCreator::$PLACEHOLDER_PHONE, 'B starts with the placeholder address');

    // The merchant corrects C by hand before the report arrives.
    $oC = new Order($idOrderC);
    $aC = new Address((int) $oC->id_address_delivery);
    $aC->address1 = 'Merchant Street 5';
    $aC->update();

    // ------------------------------------------------------------ first apply
    echo "apply\n";
    $s = $importer->apply($parsed['orders']);
    $want = array('in_report' => 5, 'updated' => 3, 'addresses' => 1, 'customers' => 2,
        'already' => 0, 'kept' => 1, 'not_imported' => 1, 'purged' => 1, 'other_shop' => 0);
    check($s === $want, 'summary: A, B and C filled; C kept; D purged; E not imported', $s);

    $sA = staged($ids['A']);
    check($sA['ship_name'] === 'Anna Schmidt' && $sA['ship_address1'] === 'Lindenstraße 12'
        && $sA['ship_address2'] === 'Hinterhaus, 3. OG' && $sA['ship_phone'] === '+49 30 1234567',
        'A staged: recipient, street, second line and phone filled');

    $sB = staged($ids['B']);
    check($sB['ship_address1'] === 'Hauptstr. 1 Hinterhof', 'B staged: @ and ! stripped from the street', $sB['ship_address1']);
    check($sB['ship_name'] === 'Zoë Ångström-Łukasiewicz', 'B staged: emoji dropped, accents kept', $sB['ship_name']);
    check($sB['ship_phone'] === '+49 (89) 123456', 'B staged: phone cut to what PrestaShop 1.6 accepts', $sB['ship_phone']);
    check($sB['ship_postal_code'] === '10115', 'B staged: the postcode the API gave is not overwritten', $sB['ship_postal_code']);
    check($sB['buyer_name'] === 'Jürgen Müller', 'B staged: buyer name filled separately from the recipient');

    $aB = new Address((int) $oB->id_address_delivery);
    check($aB->address1 === 'Hauptstr. 1 Hinterhof' && $aB->firstname === 'Zoë' && $aB->lastname === 'Ångström-Łukasiewicz',
        'B address: street and recipient name filled', array($aB->address1, $aB->firstname, $aB->lastname));
    check($aB->phone === '+49 (89) 123456' && $aB->city === 'Berlin' && $aB->postcode === '10115',
        'B address: placeholder phone replaced, real city and postcode left');
    $rowB = $db->getRow("SELECT `firstname`, `lastname` FROM `{$p}customer` WHERE `id_customer` = " . (int) $oB->id_customer);
    check($rowB['firstname'] === 'Jürgen' && $rowB['lastname'] === 'Müller', 'B customer row: the buyer, not the recipient', $rowB);
    $cB = new Customer((int) $oB->id_customer);
    check($cB->firstname === 'Jürgen', 'B customer object: no stale copy left in the object cache', $cB->firstname);

    $aC = new Address((int) $oC->id_address_delivery);
    check($aC->address1 === 'Merchant Street 5' && $aC->firstname === 'Amazon', 'C address: the edited address is left alone entirely');
    $sC = staged($ids['C']);
    check($sC['ship_address1'] === 'Report Road 9', 'C staged: filled all the same');

    $sD = staged($ids['D']);
    check($sD['ship_address1'] === '' && $sD['ship_name'] === '', 'D: purged data is not brought back');
    check(staged($ids['E']) === false, 'E: nothing is stored for an order that is not imported');

    // ----------------------------------------------------------- second apply
    $s = $importer->apply($parsed['orders']);
    $want = array('in_report' => 5, 'updated' => 0, 'addresses' => 0, 'customers' => 0,
        'already' => 3, 'kept' => 0, 'not_imported' => 1, 'purged' => 1, 'other_shop' => 0);
    check($s === $want, 'uploading the same report again changes nothing', $s);

    // ------------------------------------------- order created after upload
    $idOrderA = createPsOrder($ids['A'], $idCarrier);
    check($idOrderA > 0, "PrestaShop order #$idOrderA (A) created after the upload");
    $oA = new Order($idOrderA);
    $aA = new Address((int) $oA->id_address_delivery);
    check($aA->firstname === 'Anna' && $aA->lastname === 'Schmidt' && $aA->address1 === 'Lindenstraße 12'
        && $aA->address2 === 'Hinterhaus, 3. OG' && $aA->phone === '+49 30 1234567',
        'A: createAddress uses the recipient and full address from the upload',
        array($aA->firstname, $aA->lastname, $aA->address1, $aA->phone));
} catch (Throwable $e) {
    $GLOBALS['fail']++;
    echo '  EXCEPTION ' . get_class($e) . ': ' . $e->getMessage() . "\n    at " . $e->getFile() . ':' . $e->getLine() . "\n";
}

cleanup(array_values($ids));
$GLOBALS['cleaned'] = true;
$left = (int) $db->getValue("SELECT COUNT(*) FROM `{$p}amazonmarketplacepro_order` WHERE `amazon_order_id` LIKE '999-0000001-%'")
    + (int) $db->getValue("SELECT COUNT(*) FROM `{$p}customer` WHERE `email` LIKE 'amzpro-e2e-%'");
echo $left === 0 ? "cleaned up\n" : "WARNING: $left test row(s) left behind\n";

printf("%d passed, %d failed\n", $GLOBALS['pass'], $GLOBALS['fail']);
exit($GLOBALS['fail'] ? 1 : 0);
