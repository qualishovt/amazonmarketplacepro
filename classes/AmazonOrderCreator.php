<?php

/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/*
 * Creates real PrestaShop orders from staged Amazon order data.
 *
 * Takes rows from amazonmarketplacepro_order / amazonmarketplacepro_order_item (populated
 * by AmazonOrderImporter) and turns them into genuine PS Customer, Address,
 * Cart, Order, OrderDetail, OrderHistory, and OrderPayment records.
 *
 * Now uses real buyer address (when available via RDT), shipping costs, and tax.
 *
 * Multistore: a creator works for one shop and only takes that shop's staged
 * orders. Each order is built inside that shop (AmzproShop::runInShop) with the
 * shop's own group, language, currency, country, carrier, order states and
 * module settings, so nothing is borrowed from the back office's context.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonOrderCreator
{
    /**
     * Stand-ins for what Amazon withholds without the personal-data role.
     *
     * Public because AmazonOrderReportImporter recognises an unfilled address
     * by exactly these values; change them here and it follows.
     */
    const PLACEHOLDER_FIRSTNAME = 'Amazon';
    const PLACEHOLDER_LASTNAME = 'Buyer';
    const PLACEHOLDER_ADDRESS1 = 'Amazon Marketplace Order';
    const PLACEHOLDER_CITY = 'Amazon';
    const PLACEHOLDER_POSTCODE = '00000';
    const PLACEHOLDER_PHONE = '0000000000';

    private $idCarrier;
    private $idOrderState;
    /** Language the caller asked for; 0 = the default language of the order's shop. */
    private $idLang;
    private $idShop;
    private $lastError;
    private $notices = [];

    /**
     * @param int $idCarrier Default carrier id for imported orders
     * @param int $idOrderState Initial order state (e.g. PS_OS_PAYMENT for "Payment accepted")
     * @param int $idLang Language id (0 = the shop's default language)
     * @param int $idShop Shop id (0 = the shop the request acts for)
     */
    public function __construct($idCarrier, $idOrderState, $idLang = 0, $idShop = 0)
    {
        $this->idCarrier = (int) $idCarrier;
        $this->idOrderState = (int) $idOrderState;
        $this->idLang = (int) $idLang;
        $this->idShop = $idShop ? (int) $idShop : AmzproShop::actingId();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Create real PS orders for all staged Amazon orders of the creator's
     * shop that haven't been created yet (import_status = 'imported',
     * id_order = 0). Only orders with at least one matched item are processed.
     *
     * @return array Summary: created, skipped, failed, errors
     */
    public function createAllPending()
    {
        $this->lastError = null;
        $this->notices = [];

        $sql = 'SELECT o.*, GROUP_CONCAT(i.`match_status`) AS item_statuses
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` i
                    ON (i.`id_amazonmarketplacepro_order` = o.`id_amazonmarketplacepro_order`)
                WHERE o.`id_order` = 0 AND o.`import_status` = \'imported\'
                  AND o.`order_status` <> \'Pending\'
                  AND ' . AmzproShop::sqlWhere('o', $this->idShop) . '
                GROUP BY o.`id_amazonmarketplacepro_order`
                ORDER BY o.`purchase_date` ASC';
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }

        $summary = [
            'total' => count($rows),
            'created' => 0,
            'skipped' => 0,
            'pending' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($rows as $row) {
            $amazonId = $row['amazon_order_id'];

            // Skip orders with zero matched items
            if ((int) $row['items_matched'] === 0) {
                ++$summary['skipped'];
                $this->notices[] = sprintf(
                    AmazonI18n::get()->l('%s: skipped (no matched products)', 'amazonordercreator'),
                    $amazonId
                );
                continue;
            }

            $result = $this->createOneOrder($row);
            if ($result === 'pending_stock') {
                ++$summary['pending'];
                $this->notices[] = sprintf(
                    AmazonI18n::get()->l('%1$s: moved to Pending Orders (%2$s)', 'amazonordercreator'),
                    $amazonId,
                    $this->lastError
                );
            } elseif ($result === false) {
                ++$summary['failed'];
                $summary['errors'][] = $amazonId . ': ' . $this->lastError;
            } else {
                ++$summary['created'];
            }
        }

        return $summary;
    }

    /**
     * Create a single PS order from a staged Amazon order row.
     *
     * The order is created in the shop the staged order belongs to, which has
     * to be the creator's shop: an order of another shop is refused.
     *
     * @param array $stagedOrder Row from amazonmarketplacepro_order
     * @param bool $force Skip the out-of-stock gate (Pending Orders "create anyway")
     *
     * @return int|string|false PS order ID on success, 'pending_stock' when the
     *                          order was parked in Pending Orders, false on failure
     */
    public function createOneOrder($stagedOrder, $force = false)
    {
        $this->lastError = null;
        $idShop = $this->stagedShopId($stagedOrder);
        if ($idShop !== $this->idShop) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Order %1$s belongs to the shop "%2$s". Select that shop at the top of the page, then create it there.', 'amazonordercreator'),
                $stagedOrder['amazon_order_id'],
                AmzproShop::name($idShop)
            );

            return false;
        }

        return AmzproShop::runInShop($idShop, function ($idShop) use ($stagedOrder, $force) {
            return $this->createInShop($stagedOrder, $force, $idShop);
        });
    }

    /**
     * The shop a staged order belongs to.
     *
     * @return int
     */
    private function stagedShopId($stagedOrder)
    {
        $idShop = isset($stagedOrder['id_shop']) ? (int) $stagedOrder['id_shop'] : 0;
        if (!$idShop && !empty($stagedOrder['id_amazonmarketplacepro_order'])) {
            // A row selected without its shop column: read the owner by id.
            $idShop = (int) Db::getInstance()->getValue(
                'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 WHERE `id_amazonmarketplacepro_order` = ' . (int) $stagedOrder['id_amazonmarketplacepro_order']
            );
        }

        return $idShop ? $idShop : $this->idShop;
    }

    /**
     * What an order of this shop is built with, read for the shop itself
     * rather than for the request's context.
     *
     * @param int $idShop
     *
     * @return array id_shop, id_shop_group, share_customer (bool), id_lang,
     *               id_currency, id_country, id_carrier (the shop's default
     *               carrier, or its first carrier; 0 when it has none)
     */
    public static function shopEnvironment($idShop)
    {
        $idShop = (int) $idShop;
        $group = self::shopGroupInfo($idShop);
        $idGroup = $group['id_shop_group'];

        $idCarrier = self::carrierForShop(
            (int) Configuration::get('PS_CARRIER_DEFAULT', null, $idGroup, $idShop),
            $idShop
        );
        if (!$idCarrier) {
            // "Best price" / "Best grade", or a default the shop does not use.
            $idCarrier = (int) Db::getInstance()->getValue(
                'SELECT c.`id_carrier`
                 FROM `' . _DB_PREFIX_ . 'carrier` c
                 INNER JOIN `' . _DB_PREFIX_ . 'carrier_shop` cs
                     ON (cs.`id_carrier` = c.`id_carrier` AND cs.`id_shop` = ' . $idShop . ')
                 WHERE c.`deleted` = 0 AND c.`active` = 1
                 ORDER BY c.`position` ASC, c.`id_carrier` ASC'
            );
        }

        return [
            'id_shop' => $idShop,
            'id_shop_group' => $idGroup,
            'share_customer' => $group['share_customer'],
            'id_lang' => (int) Configuration::get('PS_LANG_DEFAULT', null, $idGroup, $idShop),
            'id_currency' => (int) Configuration::get('PS_CURRENCY_DEFAULT', null, $idGroup, $idShop),
            'id_country' => (int) Configuration::get('PS_COUNTRY_DEFAULT', null, $idGroup, $idShop),
            'id_carrier' => $idCarrier,
        ];
    }

    /**
     * The customer an order of this shop is filed under: looked up across the
     * shop group when the group shares its customers, else in the shop only.
     * Deleted customers are never reused.
     *
     * @param string $email
     * @param int $idShop
     *
     * @return int Customer id, or 0
     */
    public static function findCustomerId($email, $idShop)
    {
        $email = trim((string) $email);
        if ($email === '') {
            return 0;
        }
        $group = self::shopGroupInfo($idShop);
        $scope = $group['share_customer']
            ? '`id_shop_group` = ' . (int) $group['id_shop_group']
            : '`id_shop` = ' . (int) $idShop;

        // No LIMIT here: getValue() appends its own, and a duplicated LIMIT
        // makes the query fail silently.
        return (int) Db::getInstance()->getValue(
            'SELECT `id_customer` FROM `' . _DB_PREFIX_ . 'customer`
             WHERE `email` = \'' . pSQL($email) . '\'
               AND `deleted` = 0
               AND ' . $scope
        );
    }

    /**
     * The shop's group and whether that group shares customers. Read from the
     * tables rather than Shop's cache, which depends on the employee.
     *
     * @return array id_shop_group (int), share_customer (bool)
     */
    private static function shopGroupInfo($idShop)
    {
        $row = Db::getInstance()->getRow(
            'SELECT s.`id_shop_group`, g.`share_customer`
             FROM `' . _DB_PREFIX_ . 'shop` s
             INNER JOIN `' . _DB_PREFIX_ . 'shop_group` g ON (g.`id_shop_group` = s.`id_shop_group`)
             WHERE s.`id_shop` = ' . (int) $idShop
        );
        if (!$row) {
            return ['id_shop_group' => AmzproShop::groupId($idShop), 'share_customer' => false];
        }

        return [
            'id_shop_group' => (int) $row['id_shop_group'],
            'share_customer' => (bool) $row['share_customer'],
        ];
    }

    /**
     * The carrier when the shop offers it (carrier_shop), else 0.
     *
     * @return int
     */
    private static function carrierForShop($idCarrier, $idShop)
    {
        if ((int) $idCarrier <= 0) {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'carrier_shop`
             WHERE `id_carrier` = ' . (int) $idCarrier . '
               AND `id_shop` = ' . (int) $idShop
        );
    }

    /**
     * createOneOrder() for a staged order of $idShop, running inside that shop.
     *
     * @return int|string|false
     */
    private function createInShop($stagedOrder, $force, $idShop)
    {
        $amazonId = $stagedOrder['amazon_order_id'];
        $idStaged = (int) $stagedOrder['id_amazonmarketplacepro_order'];
        $env = self::shopEnvironment($idShop);
        $idLang = $this->idLang ? $this->idLang : $env['id_lang'];

        // Fetch matched items
        $items = Db::getInstance()->executeS(
            'SELECT i.* FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` i
             INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`id_amazonmarketplacepro_order` = i.`id_amazonmarketplacepro_order`)
             WHERE i.`id_amazonmarketplacepro_order` = ' . $idStaged . '
               AND o.`id_shop` = ' . (int) $idShop . '
               AND i.`match_status` = \'matched\''
        );
        if (!is_array($items) || empty($items)) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('No matched items for order %s', 'amazonordercreator'),
                $amazonId
            );

            return false;
        }

        // Out-of-stock gate: orders whose products lack stock (and cannot be
        // ordered out of stock) are parked in Pending Orders instead of
        // being created. FBA orders ship from Amazon stock — never parked.
        $channel = isset($stagedOrder['fulfillment_channel']) ? $stagedOrder['fulfillment_channel'] : 'MFN';
        if (!$force && $channel !== 'AFN' && AmzproShop::get('AMZPRO_SKIP_NO_STOCK', $idShop)) {
            $shortages = $this->stockShortages($items, $env);
            if (!empty($shortages)) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                     SET `import_status` = \'pending_stock\',
                         `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
                     WHERE `id_amazonmarketplacepro_order` = ' . $idStaged . '
                       AND `id_shop` = ' . (int) $idShop
                );
                $this->lastError = sprintf(
                    AmazonI18n::get()->l('insufficient stock: %s', 'amazonordercreator'),
                    implode('; ', $shortages)
                );

                return 'pending_stock';
            }
        }

        // Hand any Remote Cart hold back before creating the order: the order
        // creation below decrements the same stock through the normal flow.
        require_once dirname(__FILE__) . '/AmazonRemoteCart.php';
        AmazonRemoteCart::convert($amazonId, $idShop);

        // 1. Find or create customer (optionally under an anonymized address)
        $buyerEmail = $stagedOrder['buyer_email'];
        if (AmzproShop::get('AMZPRO_FAKE_EMAIL', $idShop) && trim((string) $buyerEmail) !== '') {
            // Amazon relay addresses expire; a stable synthetic address keeps
            // one customer account per buyer order without leaking PII.
            $buyerEmail = Tools::strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $amazonId))
                . '@marketplace.amazon';
        }
        $customer = $this->findOrCreateCustomer(
            $buyerEmail,
            $stagedOrder['buyer_name'],
            $env,
            $idLang
        );
        if (!$customer || !$customer->id) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not create customer for %s', 'amazonordercreator'),
                $amazonId
            );

            return false;
        }

        // 2. Create address using real data when available
        $address = $this->createAddress($customer, $stagedOrder, $env);
        if (!$address || !$address->id) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not create address for %s', 'amazonordercreator'),
                $amazonId
            );

            return false;
        }

        // 3. Resolve currency: one the shop accepts, else the shop's default.
        $currencyCode = !empty($stagedOrder['currency']) ? $stagedOrder['currency'] : 'EUR';
        $idCurrency = (int) Currency::getIdByIsoCode($currencyCode, $idShop);
        if (!$idCurrency) {
            $idCurrency = $env['id_currency'];
        }

        // Carrier: the one mapped to the shipping speed or the configured
        // default, as long as this shop offers it; else the shop's default.
        $idCarrier = self::resolveCarrier($stagedOrder, $this->idCarrier, $idShop);
        if ($idCarrier !== 0 && !self::carrierForShop($idCarrier, $idShop)) {
            $idCarrier = $env['id_carrier'];
        }

        // 4. Create cart + add products
        $cart = new Cart();
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = (int) $address->id;
        $cart->id_address_invoice = (int) $address->id;
        $cart->id_currency = $idCurrency;
        $cart->id_lang = $idLang;
        $cart->id_carrier = $idCarrier;
        $cart->id_shop = $idShop;
        $cart->id_shop_group = $env['id_shop_group'];
        $cart->secure_key = $customer->secure_key;
        $cart->add();

        if (!$cart->id) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not create cart for %s', 'amazonordercreator'),
                $amazonId
            );

            return false;
        }

        // Optional: recompute line taxes from the shop's own tax rules.
        // Amazon EU orders frequently report ItemTax = 0 with VAT-inclusive
        // prices; this mode restores a correct VAT breakdown for accounting.
        // 'amazon' = trust Amazon's tax amounts; 'ps_rules' = recompute.
        if ((string) AmzproShop::get('AMZPRO_TAX_MODE', $idShop) === 'ps_rules') {
            $items = $this->applyPsTaxRules($items, $address, $stagedOrder);
        }

        $totalProducts = 0;
        $totalProductsTaxIncl = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        $shop = new Shop($idShop);

        foreach ($items as $it) {
            $idProduct = (int) $it['id_product'];
            $idPa = (int) $it['id_product_attribute'];
            $qty = max(1, (int) $it['quantity']);

            $cart->updateQty($qty, $idProduct, $idPa, false, 'up', $address->id, $shop);
            $totalProducts += (float) $it['item_price'];
            $totalProductsTaxIncl += (float) $it['item_price'] + (float) $it['item_tax'];
            $totalTax += (float) $it['item_tax'] + (float) $it['shipping_tax'];
            $totalDiscount += (float) $it['promotion_discount'];
        }

        // 5. Shipping totals from order level
        $shippingTotal = (float) $stagedOrder['shipping_total'];
        $shippingTax = (float) $stagedOrder['shipping_tax'];
        $shippingTotalTaxIncl = $shippingTotal + $shippingTax;

        // 6. Create Order
        $orderTotal = (float) $stagedOrder['order_total'];
        if ($orderTotal <= 0) {
            $orderTotal = $totalProductsTaxIncl + $shippingTotalTaxIncl - $totalDiscount;
        }

        $totalPaidTaxExcl = $totalProducts + $shippingTotal - $totalDiscount;
        $totalPaidTaxIncl = $orderTotal;

        // Order state: an advanced rule matching this order's flags wins;
        // otherwise the FBA / already-shipped states, else the default.
        $idOrderState = self::resolveOrderState($stagedOrder, $this->idOrderState, $idShop);

        $order = new Order();
        $order->id_customer = (int) $customer->id;
        $order->id_address_delivery = (int) $address->id;
        $order->id_address_invoice = (int) $address->id;
        $order->id_cart = (int) $cart->id;
        $order->id_currency = $idCurrency;
        $order->id_lang = $idLang;
        $order->id_shop = $idShop;
        $order->id_shop_group = $env['id_shop_group'];
        $order->id_carrier = $idCarrier;
        $order->current_state = $idOrderState;
        $order->payment = 'Amazon Marketplace';
        $order->module = 'amazonmarketplacepro';
        $order->total_paid = $totalPaidTaxIncl;
        $order->total_paid_tax_incl = $totalPaidTaxIncl;
        $order->total_paid_tax_excl = $totalPaidTaxExcl;
        $order->total_paid_real = $totalPaidTaxIncl;
        $order->total_products = $totalProducts;
        $order->total_products_wt = $totalProductsTaxIncl;
        $order->total_shipping = $shippingTotalTaxIncl;
        $order->total_shipping_tax_incl = $shippingTotalTaxIncl;
        $order->total_shipping_tax_excl = $shippingTotal;
        $order->total_wrapping = 0;
        $order->total_wrapping_tax_incl = 0;
        $order->total_wrapping_tax_excl = 0;
        $order->total_discounts = $totalDiscount;
        $order->total_discounts_tax_incl = $totalDiscount;
        $order->total_discounts_tax_excl = $totalDiscount;
        $order->conversion_rate = 1;
        $order->secure_key = $customer->secure_key;
        $order->reference = Order::generateReference();

        // Preserve Amazon purchase date
        if (!empty($stagedOrder['purchase_date'])) {
            $order->date_add = $stagedOrder['purchase_date'];
        }
        $order->date_upd = date('Y-m-d H:i:s');

        $order->valid = true;

        if (!$order->add()) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not save order for %s', 'amazonordercreator'),
                $amazonId
            );

            return false;
        }

        // 7. Create OrderDetail for each matched item (with tax)
        foreach ($items as $it) {
            $this->createOrderDetail($order, $it, $idLang, $idShop);
        }

        // 8. Create OrderHistory
        $history = new OrderHistory();
        $history->id_order = (int) $order->id;
        $history->id_employee = 0;
        $history->changeIdOrderState($idOrderState, (int) $order->id);
        $history->add();

        // 9. Create OrderPayment
        $payment = new OrderPayment();
        $payment->order_reference = $order->reference;
        $payment->id_currency = $idCurrency;
        $payment->amount = $totalPaidTaxIncl;
        $payment->payment_method = 'Amazon Marketplace';
        $payment->transaction_id = $amazonId;
        $payment->date_add = !empty($stagedOrder['purchase_date'])
            ? $stagedOrder['purchase_date']
            : date('Y-m-d H:i:s');
        $payment->add();

        // 10. Update staged order with PS order reference
        $now = date('Y-m-d H:i:s');
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` SET
                `id_order` = ' . (int) $order->id . ',
                `import_status` = \'created\',
                `date_upd` = \'' . pSQL($now) . '\'
             WHERE `id_amazonmarketplacepro_order` = ' . $idStaged . '
               AND `id_shop` = ' . (int) $idShop
        );

        // Note fulfillment channel
        $channel = isset($stagedOrder['fulfillment_channel']) ? $stagedOrder['fulfillment_channel'] : 'MFN';
        if ($channel === 'AFN') {
            $this->notices[] = sprintf(
                AmazonI18n::get()->l('%1$s: FBA order (fulfilled by Amazon) — PrestaShop order #%2$d', 'amazonordercreator'),
                $amazonId,
                (int) $order->id
            );
        }

        return (int) $order->id;
    }

    /**
     * PrestaShop state for an imported order.
     *
     * Advanced status rules are evaluated in order; each condition is 1 (must
     * be set), 0 (must not be set) or -1 (don't care) over the Prime, FBA and
     * Amazon Business flags. The first match wins, which lets a merchant route
     * e.g. "Prime + Business" somewhere of its own.
     *
     * @param array $stagedOrder
     * @param int $defaultState
     * @param int|null $idShop the shop whose rules apply (default: the current one)
     *
     * @return int
     */
    public static function resolveOrderState($stagedOrder, $defaultState, $idShop = null)
    {
        $isPrime = !empty($stagedOrder['is_prime']) ? 1 : 0;
        $isFba = (isset($stagedOrder['fulfillment_channel']) && $stagedOrder['fulfillment_channel'] === 'AFN') ? 1 : 0;
        $isBusiness = !empty($stagedOrder['is_business']) ? 1 : 0;

        $rules = json_decode((string) AmzproShop::get('AMZPRO_STATUS_RULES', $idShop), true);
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if (empty($rule['state'])) {
                    continue;
                }
                $matches = self::flagMatches($rule, 'prime', $isPrime)
                    && self::flagMatches($rule, 'fba', $isFba)
                    && self::flagMatches($rule, 'business', $isBusiness);
                if ($matches) {
                    return (int) $rule['state'];
                }
            }
        }

        $fbaState = (int) AmzproShop::get('AMZPRO_FBA_ORDER_STATE', $idShop);
        $shippedState = (int) AmzproShop::get('AMZPRO_ORDER_STATE_SHIPPED', $idShop);
        if ($isFba && $fbaState > 0) {
            return $fbaState;
        }
        if (isset($stagedOrder['order_status']) && $stagedOrder['order_status'] === 'Shipped'
            && $shippedState > 0) {
            return $shippedState;
        }

        return (int) $defaultState;
    }

    /** A rule condition of -1 (or missing) means "don't care". */
    private static function flagMatches($rule, $key, $actual)
    {
        $want = isset($rule[$key]) ? (int) $rule[$key] : -1;

        return ($want < 0) || ($want === (int) $actual);
    }

    /**
     * Carrier for an imported order: mapped from the shipping speed Amazon
     * promised (Standard, Expedited, NextDay...), else the configured default.
     *
     * With a shop, the map is that shop's and a mapped carrier the shop does
     * not offer is passed over for the default.
     *
     * @param array $stagedOrder
     * @param int $defaultCarrier
     * @param int|null $idShop (default: the current shop, carrier not checked)
     *
     * @return int
     */
    public static function resolveCarrier($stagedOrder, $defaultCarrier, $idShop = null)
    {
        $level = isset($stagedOrder['ship_service_level']) ? trim((string) $stagedOrder['ship_service_level']) : '';
        if ($level !== '') {
            $map = json_decode((string) AmzproShop::get('AMZPRO_CARRIER_MAP_IN', $idShop), true);
            if (is_array($map)) {
                foreach ($map as $amazonLevel => $idCarrier) {
                    if ((int) $idCarrier > 0 && Tools::strtolower($amazonLevel) === Tools::strtolower($level)
                        && ($idShop === null || self::carrierForShop($idCarrier, $idShop))
                    ) {
                        return (int) $idCarrier;
                    }
                }
            }
        }

        return (int) $defaultCarrier;
    }

    /**
     * Find an existing customer by email, or create a new one.
     *
     * @param string $email
     * @param string $fullName
     * @param array $env shopEnvironment() of the order's shop
     * @param int $idLang
     *
     * @return Customer|false
     */
    private function findOrCreateCustomer($email, $fullName, array $env, $idLang)
    {
        $email = trim((string) $email);
        if ($email === '') {
            $email = 'amazon-buyer-' . md5(uniqid((string) rand(), true)) . '@marketplace.local';
        }
        $idShop = $env['id_shop'];

        $idCustomer = self::findCustomerId($email, $idShop);
        if ($idCustomer) {
            $customer = new Customer($idCustomer);
            if (Validate::isLoadedObject($customer)) {
                return $customer;
            }
        }

        // Parse name
        $parts = $this->splitName($fullName);

        // Amazon buyers can be filed under a dedicated customer group (own
        // pricing/tax rules, easy filtering). Falls back to the shop default.
        $idGroup = (int) AmzproShop::get('AMZPRO_CUSTOMER_GROUP', $idShop);
        if ($idGroup <= 0) {
            $idGroup = (int) Configuration::get('PS_CUSTOMER_GROUP', null, $env['id_shop_group'], $idShop);
        }

        $customer = new Customer();
        $customer->email = $email;
        $customer->firstname = $parts['firstname'];
        $customer->lastname = $parts['lastname'];
        $customer->passwd = md5(uniqid((string) rand(), true));
        $customer->id_default_group = $idGroup;
        $customer->id_lang = (int) $idLang;
        $customer->id_shop = $idShop;
        $customer->id_shop_group = $env['id_shop_group'];
        $customer->active = true;
        $customer->is_guest = true;

        if ($customer->add()) {
            $customer->addGroups([$idGroup]);

            return $customer;
        }

        return false;
    }

    /**
     * Items that cannot be fulfilled from PrestaShop stock (and whose product
     * does not allow out-of-stock orders).
     *
     * @param array $items Matched staged order items
     * @param array $env shopEnvironment() of the order's shop
     *
     * @return array Human-readable shortage descriptions, empty when fulfillable
     */
    private function stockShortages($items, array $env)
    {
        $idShop = $env['id_shop'];
        $shortages = [];
        foreach ($items as $it) {
            $idProduct = (int) $it['id_product'];
            if (!$idProduct) {
                continue;
            }
            $idPa = (int) $it['id_product_attribute'];
            $qty = max(1, (int) $it['quantity']);

            $available = (int) StockAvailable::getQuantityAvailableByProduct(
                $idProduct, $idPa ? $idPa : null, $idShop
            );
            if ($available >= $qty) {
                continue;
            }

            // Products configured to accept out-of-stock orders don't block.
            $oosBehaviour = (int) StockAvailable::outOfStock($idProduct, $idShop);
            $acceptsOos = ($oosBehaviour === 1)
                || ($oosBehaviour === 2
                    && Configuration::get('PS_ORDER_OUT_OF_STOCK', null, $env['id_shop_group'], $idShop));
            if ($acceptsOos) {
                continue;
            }

            $shortages[] = sprintf(
                AmazonI18n::get()->l('%1$s ordered %2$d, available %3$d', 'amazonordercreator'),
                $it['seller_sku'],
                $qty,
                $available
            );
        }

        return $shortages;
    }

    /**
     * Create a delivery/invoice address for the customer.
     * Uses real Amazon shipping address when available, falls back to placeholder.
     *
     * @param Customer $customer
     * @param array $stagedOrder
     * @param array $env shopEnvironment() of the order's shop
     *
     * @return Address|false
     */
    private function createAddress($customer, $stagedOrder, array $env)
    {
        // The recipient from an uploaded order report, when there is one: the
        // parcel goes to them, and for a gift that is not the buyer.
        $parts = $this->splitName(
            !empty($stagedOrder['ship_name']) ? $stagedOrder['ship_name'] : $stagedOrder['buyer_name']
        );

        // Determine country from Amazon address or fallback
        $countryCode = !empty($stagedOrder['ship_country_code'])
            ? $stagedOrder['ship_country_code']
            : '';
        $idCountry = 0;
        if ($countryCode !== '') {
            $idCountry = (int) Country::getByIso($countryCode);
        }
        if (!$idCountry) {
            $idCountry = $env['id_country'];
        }

        // Resolve state if provided
        $idState = 0;
        $stateCode = !empty($stagedOrder['ship_state']) ? $stagedOrder['ship_state'] : '';
        if ($stateCode !== '' && $idCountry) {
            $idState = (int) Db::getInstance()->getValue(
                'SELECT `id_state` FROM `' . _DB_PREFIX_ . 'state`
                 WHERE `id_country` = ' . $idCountry . '
                   AND (`iso_code` = \'' . pSQL($stateCode) . '\'
                        OR `name` = \'' . pSQL($stateCode) . '\')'
            );
        }

        // Use real address data when available, placeholder when not
        $address1 = !empty($stagedOrder['ship_address1'])
            ? $stagedOrder['ship_address1']
            : self::PLACEHOLDER_ADDRESS1;
        $address2 = !empty($stagedOrder['ship_address2'])
            ? $stagedOrder['ship_address2']
            : '';
        $city = !empty($stagedOrder['ship_city'])
            ? $stagedOrder['ship_city']
            : self::PLACEHOLDER_CITY;
        $postalCode = !empty($stagedOrder['ship_postal_code'])
            ? $stagedOrder['ship_postal_code']
            : self::PLACEHOLDER_POSTCODE;
        $phone = !empty($stagedOrder['ship_phone'])
            ? $stagedOrder['ship_phone']
            : self::PLACEHOLDER_PHONE;

        $address = new Address();
        $address->id_customer = (int) $customer->id;
        $address->firstname = $parts['firstname'];
        $address->lastname = $parts['lastname'];
        $address->alias = 'Amazon ' . $stagedOrder['amazon_order_id'];
        $address->address1 = $address1;
        $address->address2 = $address2;
        $address->city = $city;
        $address->postcode = $postalCode;
        $address->id_country = $idCountry;
        $address->id_state = $idState;
        $address->phone = $phone;

        if ($address->add()) {
            return $address;
        }

        return false;
    }

    /**
     * Recompute item and shipping taxes from PrestaShop tax rules.
     *
     * Treats the Amazon amounts as tax-INCLUSIVE (price + reported tax) and
     * splits them using each product's PS tax rate for the delivery address.
     * Shipping follows the highest goods rate on the order (common EU rule).
     *
     * @param array $items Normalized staged items
     * @param Address $address Delivery address (already saved)
     * @param array $stagedOrder Staged order row — shipping fields updated in place
     *
     * @return array Items with item_price (excl) / item_tax rewritten
     */
    private function applyPsTaxRules($items, $address, &$stagedOrder)
    {
        $maxRate = 0;

        foreach ($items as $idx => $it) {
            $idProduct = (int) $it['id_product'];
            if (!$idProduct) {
                continue; // unmatched line: keep Amazon's numbers
            }

            $rate = (float) Tax::getProductTaxRate($idProduct, (int) $address->id);
            if ($rate > $maxRate) {
                $maxRate = $rate;
            }

            $incl = (float) $it['item_price'] + (float) $it['item_tax'];
            $excl = ($rate > 0) ? $incl / (1 + $rate / 100) : $incl;

            $items[$idx]['item_price'] = Tools::ps_round($excl, 6);
            $items[$idx]['item_tax'] = Tools::ps_round($incl - $excl, 6);
        }

        $shippingIncl = (float) $stagedOrder['shipping_total'] + (float) $stagedOrder['shipping_tax'];
        $shippingExcl = ($maxRate > 0) ? $shippingIncl / (1 + $maxRate / 100) : $shippingIncl;
        $stagedOrder['shipping_total'] = Tools::ps_round($shippingExcl, 6);
        $stagedOrder['shipping_tax'] = Tools::ps_round($shippingIncl - $shippingExcl, 6);

        foreach ($items as $idx => $it) {
            // shipping_tax per item is folded into the order-level figure above
            $items[$idx]['shipping_tax'] = 0;
        }

        return $items;
    }

    /**
     * Create an OrderDetail record for one order item.
     * Now includes tax data from Amazon.
     */
    private function createOrderDetail($order, $item, $idLang, $idShop)
    {
        $idLang = (int) $idLang;
        $idShop = (int) $idShop;
        $idProduct = (int) $item['id_product'];
        $idPa = (int) $item['id_product_attribute'];
        $qty = max(1, (int) $item['quantity']);

        $itemPrice = (float) $item['item_price'];
        $itemTax = (float) $item['item_tax'];
        $unitPriceTaxExcl = $itemPrice / $qty;
        $unitPriceTaxIncl = ($itemPrice + $itemTax) / $qty;

        // Compute effective tax rate
        $taxRate = ($itemPrice > 0) ? ($itemTax / $itemPrice * 100) : 0;

        // Get product name from PS if available
        $productName = $item['title'];
        if ($idProduct) {
            $psName = Db::getInstance()->getValue(
                'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_lang`
                 WHERE `id_product` = ' . $idProduct . '
                   AND `id_lang` = ' . $idLang . '
                   AND `id_shop` = ' . $idShop
            );
            if ($psName) {
                $productName = $psName;
            }
        }

        // Get product reference
        $reference = '';
        if ($idPa) {
            $reference = (string) Db::getInstance()->getValue(
                'SELECT `reference` FROM `' . _DB_PREFIX_ . 'product_attribute`
                 WHERE `id_product_attribute` = ' . $idPa
            );
        }
        if ($reference === '' && $idProduct) {
            $reference = (string) Db::getInstance()->getValue(
                'SELECT `reference` FROM `' . _DB_PREFIX_ . 'product`
                 WHERE `id_product` = ' . $idProduct
            );
        }

        // Get EAN13
        $ean13 = '';
        if ($idPa) {
            $ean13 = (string) Db::getInstance()->getValue(
                'SELECT `ean13` FROM `' . _DB_PREFIX_ . 'product_attribute`
                 WHERE `id_product_attribute` = ' . $idPa
            );
        }
        if ($ean13 === '' && $idProduct) {
            $ean13 = (string) Db::getInstance()->getValue(
                'SELECT `ean13` FROM `' . _DB_PREFIX_ . 'product`
                 WHERE `id_product` = ' . $idProduct
            );
        }

        $detail = new OrderDetail();
        $detail->id_order = (int) $order->id;
        $detail->product_id = $idProduct;
        $detail->product_attribute_id = $idPa;
        $detail->product_name = $productName;
        $detail->product_quantity = $qty;
        $detail->product_quantity_in_stock = $qty;
        $detail->product_price = $unitPriceTaxExcl;
        $detail->unit_price_tax_incl = $unitPriceTaxIncl;
        $detail->unit_price_tax_excl = $unitPriceTaxExcl;
        $detail->total_price_tax_incl = $itemPrice + $itemTax;
        $detail->total_price_tax_excl = $itemPrice;
        $detail->product_reference = $reference;
        $detail->product_ean13 = $ean13;
        $detail->product_upc = '';
        $detail->tax_rate = round($taxRate, 2);
        $detail->id_shop = $idShop;
        $detail->id_warehouse = 0;

        $detail->add();
    }

    /**
     * Split a full name into firstname + lastname.
     */
    private function splitName($fullName)
    {
        return self::splitFullName($fullName);
    }

    /**
     * Split a full name into a firstname and lastname PrestaShop will accept.
     *
     * The first word is the first name and the rest the last name; either
     * one missing falls back to the placeholder. Public because the order
     * report import has to split names the same way.
     *
     * @param string $fullName
     *
     * @return array 'firstname', 'lastname'
     */
    public static function splitFullName($fullName)
    {
        $fullName = trim((string) $fullName);
        $parts = $fullName === '' ? [] : explode(' ', $fullName, 2);

        $firstname = self::cleanNamePart(isset($parts[0]) ? $parts[0] : '', 'firstname');
        $lastname = self::cleanNamePart(isset($parts[1]) ? $parts[1] : '', 'lastname');

        return [
            'firstname' => $firstname !== '' ? $firstname : self::PLACEHOLDER_FIRSTNAME,
            'lastname' => $lastname !== '' ? $lastname : self::PLACEHOLDER_LASTNAME,
        ];
    }

    /**
     * Remove what Validate::isName rejects and fit the shorter of the
     * customer and address columns.
     *
     * The filter used to keep ASCII letters only, which turned "Jürgen Müller"
     * into "Jrgen Mller" - on marketplaces where most names carry accents.
     * The columns are 32 characters in PrestaShop 1.6 and 255 in 8 and 9, and
     * a longer value fails validation when the address is saved.
     *
     * @param string $part
     * @param string $field 'firstname' or 'lastname'
     *
     * @return string
     */
    private static function cleanNamePart($part, $field)
    {
        $clean = preg_replace('/[0-9!<>,;?=+()@#"°{}_$%:¤|]/u', '', (string) $part);
        if ($clean === null) {
            // Not valid UTF-8: keep the ASCII letters rather than lose the name.
            $clean = preg_replace('/[^a-zA-Z\s\-\.]/', '', (string) $part);
        }
        $clean = preg_replace('/\s+/u', ' ', (string) $clean);
        $clean = trim((string) $clean);

        $size = 0;
        foreach (['Customer', 'Address'] as $class) {
            $def = $class::$definition;
            if (isset($def['fields'][$field]['size'])) {
                $limit = (int) $def['fields'][$field]['size'];
                $size = $size > 0 ? min($size, $limit) : $limit;
            }
        }
        if ($size > 0 && Tools::strlen($clean) > $size) {
            $clean = trim(Tools::substr($clean, 0, $size));
        }

        return $clean;
    }
}
