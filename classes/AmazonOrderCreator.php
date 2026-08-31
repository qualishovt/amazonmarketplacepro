<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Creates real PrestaShop orders from staged Amazon order data.
 *
 * Takes rows from amazonmarketplacepro_order / amazonmarketplacepro_order_item (populated
 * by AmazonOrderImporter) and turns them into genuine PS Customer, Address,
 * Cart, Order, OrderDetail, OrderHistory, and OrderPayment records.
 *
 * Now uses real buyer address (when available via RDT), shipping costs, and tax.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonOrderCreator
{
    private $idCarrier;
    private $idOrderState;
    private $idLang;
    private $idShop;
    private $lastError = null;
    private $notices = array();

    /** 'amazon' = trust Amazon's tax amounts; 'ps_rules' = recompute from PS tax rules. */
    private $taxMode = 'amazon';

    /**
     * @param int $idCarrier      Default carrier id for imported orders
     * @param int $idOrderState   Initial order state (e.g. PS_OS_PAYMENT for "Payment accepted")
     * @param int $idLang         Language id
     * @param int $idShop         Shop id
     */
    public function __construct($idCarrier, $idOrderState, $idLang = 0, $idShop = 0)
    {
        $this->idCarrier = (int) $idCarrier;
        $this->idOrderState = (int) $idOrderState;
        $this->idLang = $idLang ? (int) $idLang : (int) Configuration::get('PS_LANG_DEFAULT');
        $this->idShop = $idShop ? (int) $idShop : (int) Context::getContext()->shop->id;
        if (!$this->idShop) {
            $this->idShop = 1;
        }

        $mode = (string) Configuration::get('AMZPRO_TAX_MODE');
        if ($mode === 'ps_rules') {
            $this->taxMode = 'ps_rules';
        }
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
     * Create real PS orders for all staged Amazon orders that haven't been
     * created yet (import_status = 'imported', id_order = 0). Only orders
     * with at least one matched item are processed.
     *
     * @return array Summary: created, skipped, failed, errors
     */
    public function createAllPending()
    {
        $this->lastError = null;
        $this->notices = array();

        $sql = 'SELECT o.*, GROUP_CONCAT(i.`match_status`) AS item_statuses
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` i
                    ON (i.`id_amazonmarketplacepro_order` = o.`id_amazonmarketplacepro_order`)
                WHERE o.`id_order` = 0 AND o.`import_status` = \'imported\'
                  AND o.`order_status` <> \'Pending\'
                GROUP BY o.`id_amazonmarketplacepro_order`
                ORDER BY o.`purchase_date` ASC';
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = array();
        }

        $summary = array(
            'total' => count($rows),
            'created' => 0,
            'skipped' => 0,
            'pending' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        foreach ($rows as $row) {
            $amazonId = $row['amazon_order_id'];

            // Skip orders with zero matched items
            if ((int) $row['items_matched'] === 0) {
                $summary['skipped']++;
                $this->notices[] = $amazonId . ': skipped (no matched products)';
                continue;
            }

            $result = $this->createOneOrder($row);
            if ($result === 'pending_stock') {
                $summary['pending']++;
                $this->notices[] = $amazonId . ': moved to Pending Orders (' . $this->lastError . ')';
            } elseif ($result === false) {
                $summary['failed']++;
                $summary['errors'][] = $amazonId . ': ' . $this->lastError;
            } else {
                $summary['created']++;
            }
        }

        return $summary;
    }

    /**
     * Create a single PS order from a staged Amazon order row.
     *
     * @param array $stagedOrder Row from amazonmarketplacepro_order
     * @param bool  $force       Skip the out-of-stock gate (Pending Orders "create anyway")
     * @return int|string|false PS order ID on success, 'pending_stock' when the
     *                          order was parked in Pending Orders, false on failure
     */
    public function createOneOrder($stagedOrder, $force = false)
    {
        $this->lastError = null;
        $amazonId = $stagedOrder['amazon_order_id'];
        $idStaged = (int) $stagedOrder['id_amazonmarketplacepro_order'];

        // Fetch matched items
        $items = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item`
             WHERE `id_amazonmarketplacepro_order` = ' . $idStaged . '
               AND `match_status` = \'matched\''
        );
        if (!is_array($items) || empty($items)) {
            $this->lastError = 'No matched items for order ' . $amazonId;
            return false;
        }

        // Out-of-stock gate: orders whose products lack stock (and cannot be
        // ordered out of stock) are parked in Pending Orders instead of
        // being created. FBA orders ship from Amazon stock — never parked.
        $channel = isset($stagedOrder['fulfillment_channel']) ? $stagedOrder['fulfillment_channel'] : 'MFN';
        if (!$force && $channel !== 'AFN' && Configuration::get('AMZPRO_SKIP_NO_STOCK')) {
            $shortages = $this->stockShortages($items);
            if (!empty($shortages)) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                     SET `import_status` = \'pending_stock\',
                         `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
                     WHERE `id_amazonmarketplacepro_order` = ' . $idStaged
                );
                $this->lastError = 'insufficient stock: ' . implode('; ', $shortages);
                return 'pending_stock';
            }
        }

        // Hand any Remote Cart hold back before creating the order: the order
        // creation below decrements the same stock through the normal flow.
        require_once dirname(__FILE__) . '/AmazonRemoteCart.php';
        AmazonRemoteCart::convert($amazonId);

        // 1. Find or create customer (optionally under an anonymized address)
        $buyerEmail = $stagedOrder['buyer_email'];
        if (Configuration::get('AMZPRO_FAKE_EMAIL') && trim((string) $buyerEmail) !== '') {
            // Amazon relay addresses expire; a stable synthetic address keeps
            // one customer account per buyer order without leaking PII.
            $buyerEmail = Tools::strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $amazonId))
                . '@marketplace.amazon';
        }
        $customer = $this->findOrCreateCustomer(
            $buyerEmail,
            $stagedOrder['buyer_name']
        );
        if (!$customer || !$customer->id) {
            $this->lastError = 'Could not create customer for ' . $amazonId;
            return false;
        }

        // 2. Create address using real data when available
        $address = $this->createAddress($customer, $stagedOrder);
        if (!$address || !$address->id) {
            $this->lastError = 'Could not create address for ' . $amazonId;
            return false;
        }

        // 3. Resolve currency
        $currencyCode = !empty($stagedOrder['currency']) ? $stagedOrder['currency'] : 'EUR';
        $idCurrency = (int) Currency::getIdByIsoCode($currencyCode);
        if (!$idCurrency) {
            $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        // 4. Create cart + add products
        $cart = new Cart();
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = (int) $address->id;
        $cart->id_address_invoice = (int) $address->id;
        $cart->id_currency = $idCurrency;
        $cart->id_lang = $this->idLang;
        $idCarrier = self::resolveCarrier($stagedOrder, $this->idCarrier);
        $cart->id_carrier = $idCarrier;
        $cart->id_shop = $this->idShop;
        $cart->secure_key = $customer->secure_key;
        $cart->add();

        if (!$cart->id) {
            $this->lastError = 'Could not create cart for ' . $amazonId;
            return false;
        }

        // Optional: recompute line taxes from the shop's own tax rules.
        // Amazon EU orders frequently report ItemTax = 0 with VAT-inclusive
        // prices; this mode restores a correct VAT breakdown for accounting.
        if ($this->taxMode === 'ps_rules') {
            $items = $this->applyPsTaxRules($items, $address, $stagedOrder);
        }

        $totalProducts = 0;
        $totalProductsTaxIncl = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($items as $it) {
            $idProduct = (int) $it['id_product'];
            $idPa = (int) $it['id_product_attribute'];
            $qty = max(1, (int) $it['quantity']);

            $cart->updateQty($qty, $idProduct, $idPa, false, 'up', $address->id);
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
        $idOrderState = self::resolveOrderState($stagedOrder, $this->idOrderState);

        $order = new Order();
        $order->id_customer = (int) $customer->id;
        $order->id_address_delivery = (int) $address->id;
        $order->id_address_invoice = (int) $address->id;
        $order->id_cart = (int) $cart->id;
        $order->id_currency = $idCurrency;
        $order->id_lang = $this->idLang;
        $order->id_shop = $this->idShop;
        $order->id_shop_group = (int) Context::getContext()->shop->id_shop_group;
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

        $order->valid = 1;

        if (!$order->add()) {
            $this->lastError = 'Could not save order for ' . $amazonId;
            return false;
        }

        // 7. Create OrderDetail for each matched item (with tax)
        foreach ($items as $it) {
            $this->createOrderDetail($order, $it);
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
             WHERE `id_amazonmarketplacepro_order` = ' . $idStaged
        );

        // Note fulfillment channel
        $channel = isset($stagedOrder['fulfillment_channel']) ? $stagedOrder['fulfillment_channel'] : 'MFN';
        if ($channel === 'AFN') {
            $this->notices[] = $amazonId . ': FBA order (fulfilled by Amazon) — PS order #' . $order->id;
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
     * @return int
     */
    public static function resolveOrderState($stagedOrder, $defaultState)
    {
        $isPrime = !empty($stagedOrder['is_prime']) ? 1 : 0;
        $isFba = (isset($stagedOrder['fulfillment_channel']) && $stagedOrder['fulfillment_channel'] === 'AFN') ? 1 : 0;
        $isBusiness = !empty($stagedOrder['is_business']) ? 1 : 0;

        $rules = json_decode((string) Configuration::get('AMZPRO_STATUS_RULES'), true);
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

        $fbaState = (int) Configuration::get('AMZPRO_FBA_ORDER_STATE');
        $shippedState = (int) Configuration::get('AMZPRO_ORDER_STATE_SHIPPED');
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
     * @return int
     */
    public static function resolveCarrier($stagedOrder, $defaultCarrier)
    {
        $level = isset($stagedOrder['ship_service_level']) ? trim((string) $stagedOrder['ship_service_level']) : '';
        if ($level !== '') {
            $map = json_decode((string) Configuration::get('AMZPRO_CARRIER_MAP_IN'), true);
            if (is_array($map)) {
                foreach ($map as $amazonLevel => $idCarrier) {
                    if ((int) $idCarrier > 0 && Tools::strtolower($amazonLevel) === Tools::strtolower($level)) {
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
     * @return Customer|false
     */
    private function findOrCreateCustomer($email, $fullName)
    {
        $email = trim((string) $email);
        if ($email === '') {
            $email = 'amazon-buyer-' . md5(uniqid((string) rand(), true)) . '@marketplace.local';
        }

        // Try to find existing customer. No LIMIT here: getValue() appends its
        // own, and a duplicated LIMIT makes the query fail silently.
        $idCustomer = (int) Db::getInstance()->getValue(
            'SELECT `id_customer` FROM `' . _DB_PREFIX_ . 'customer`
             WHERE `email` = \'' . pSQL($email) . '\'
             AND `id_shop` = ' . $this->idShop
        );

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
        $idGroup = (int) Configuration::get('AMZPRO_CUSTOMER_GROUP');
        if ($idGroup <= 0) {
            $idGroup = (int) Configuration::get('PS_CUSTOMER_GROUP');
        }

        $customer = new Customer();
        $customer->email = $email;
        $customer->firstname = $parts['firstname'];
        $customer->lastname = $parts['lastname'];
        $customer->passwd = md5(uniqid((string) rand(), true));
        $customer->id_default_group = $idGroup;
        $customer->id_lang = $this->idLang;
        $customer->id_shop = $this->idShop;
        $customer->active = 1;
        $customer->is_guest = 1;

        if ($customer->add()) {
            $customer->addGroups(array($idGroup));
            return $customer;
        }

        return false;
    }

    /**
     * Items that cannot be fulfilled from PrestaShop stock (and whose product
     * does not allow out-of-stock orders).
     *
     * @param array $items Matched staged order items
     * @return array Human-readable shortage descriptions, empty when fulfillable
     */
    private function stockShortages($items)
    {
        $shortages = array();
        foreach ($items as $it) {
            $idProduct = (int) $it['id_product'];
            if (!$idProduct) {
                continue;
            }
            $idPa = (int) $it['id_product_attribute'];
            $qty = max(1, (int) $it['quantity']);

            $available = (int) StockAvailable::getQuantityAvailableByProduct(
                $idProduct, $idPa ? $idPa : null, $this->idShop
            );
            if ($available >= $qty) {
                continue;
            }

            // Products configured to accept out-of-stock orders don't block.
            $oosBehaviour = (int) StockAvailable::outOfStock($idProduct, $this->idShop);
            $acceptsOos = ($oosBehaviour === 1)
                || ($oosBehaviour === 2 && Configuration::get('PS_ORDER_OUT_OF_STOCK'));
            if ($acceptsOos) {
                continue;
            }

            $shortages[] = $it['seller_sku'] . ' ordered ' . $qty . ', available ' . $available;
        }

        return $shortages;
    }

    /**
     * Create a delivery/invoice address for the customer.
     * Uses real Amazon shipping address when available, falls back to placeholder.
     *
     * @return Address|false
     */
    private function createAddress($customer, $stagedOrder)
    {
        $parts = $this->splitName($stagedOrder['buyer_name']);

        // Determine country from Amazon address or fallback
        $countryCode = !empty($stagedOrder['ship_country_code'])
            ? $stagedOrder['ship_country_code']
            : '';
        $idCountry = 0;
        if ($countryCode !== '') {
            $idCountry = (int) Country::getByIso($countryCode);
        }
        if (!$idCountry) {
            $idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT');
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
            : 'Amazon Marketplace Order';
        $address2 = !empty($stagedOrder['ship_address2'])
            ? $stagedOrder['ship_address2']
            : '';
        $city = !empty($stagedOrder['ship_city'])
            ? $stagedOrder['ship_city']
            : 'Amazon';
        $postalCode = !empty($stagedOrder['ship_postal_code'])
            ? $stagedOrder['ship_postal_code']
            : '00000';
        $phone = !empty($stagedOrder['ship_phone'])
            ? $stagedOrder['ship_phone']
            : '0000000000';

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
        $address->active = 1;

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
     * @param array   $items       Normalized staged items
     * @param Address $address     Delivery address (already saved)
     * @param array   $stagedOrder Staged order row — shipping fields updated in place
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
    private function createOrderDetail($order, $item)
    {
        $idProduct = (int) $item['id_product'];
        $idPa = (int) $item['id_product_attribute'];
        $qty = max(1, (int) $item['quantity']);

        $itemPrice = (float) $item['item_price'];
        $itemTax = (float) $item['item_tax'];
        $unitPriceTaxExcl = $qty > 0 ? ($itemPrice / $qty) : 0;
        $unitPriceTaxIncl = $qty > 0 ? (($itemPrice + $itemTax) / $qty) : 0;

        // Compute effective tax rate
        $taxRate = ($itemPrice > 0) ? ($itemTax / $itemPrice * 100) : 0;

        // Get product name from PS if available
        $productName = $item['title'];
        if ($idProduct) {
            $psName = Db::getInstance()->getValue(
                'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_lang`
                 WHERE `id_product` = ' . $idProduct . '
                   AND `id_lang` = ' . $this->idLang . '
                   AND `id_shop` = ' . $this->idShop
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
        $detail->id_shop = $this->idShop;
        $detail->id_warehouse = 0;

        $detail->add();
    }

    /**
     * Split a full name into firstname + lastname.
     */
    private function splitName($fullName)
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return array('firstname' => 'Amazon', 'lastname' => 'Buyer');
        }

        $parts = explode(' ', $fullName, 2);
        $firstname = isset($parts[0]) ? trim($parts[0]) : 'Amazon';
        $lastname = isset($parts[1]) ? trim($parts[1]) : 'Buyer';

        if ($firstname === '') {
            $firstname = 'Amazon';
        }
        if ($lastname === '') {
            $lastname = 'Buyer';
        }

        // PrestaShop validates name fields - strip invalid chars
        $firstname = preg_replace('/[^a-zA-Z0-9\s\-\.]/', '', $firstname);
        $lastname = preg_replace('/[^a-zA-Z0-9\s\-\.]/', '', $lastname);

        if ($firstname === '') {
            $firstname = 'Amazon';
        }
        if ($lastname === '') {
            $lastname = 'Buyer';
        }

        return array('firstname' => $firstname, 'lastname' => $lastname);
    }
}
