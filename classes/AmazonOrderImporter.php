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
 * Fetches Amazon SP-API orders and stages them in module tables for review.
 *
 * "Staging" import: orders are stored in amazonmarketplacepro_order /
 * amazonmarketplacepro_order_item with their SKU->product match status. No real
 * PrestaShop Order/Customer/Cart records are created here — that's a later step.
 *
 * Captures shipping costs, tax, buyer address (via RDT), and FBA channel.
 *
 * PHP 5.6+ compatible (no scalar type hints, no ?? operator, no enums).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonOrderImporter
{
    /** Safety cap on NextToken pagination (100 orders per page). */
    const MAX_ORDER_PAGES = 30;

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError = null;

    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
    }

    /**
     * @return string|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Create the staging tables if they don't exist yet (idempotent).
     */
    public function ensureTables()
    {
        $engine = defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB';

        $this->ensureOrderColumns();

        $sqls = array();
        $sqls[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` (
            `id_amazonmarketplacepro_order` INT(11) NOT NULL AUTO_INCREMENT,
            `amazon_order_id` VARCHAR(64) NOT NULL,
            `purchase_date` DATETIME NULL,
            `order_status` VARCHAR(64) NOT NULL DEFAULT \'\',
            `order_total` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
            `buyer_email` VARCHAR(255) NOT NULL DEFAULT \'\',
            `buyer_name` VARCHAR(255) NOT NULL DEFAULT \'\',
            `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
            `fulfillment_channel` VARCHAR(16) NOT NULL DEFAULT \'MFN\',
            `shipping_total` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `shipping_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `order_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `amazon_fees` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `ship_address1` VARCHAR(255) NOT NULL DEFAULT \'\',
            `ship_address2` VARCHAR(255) NOT NULL DEFAULT \'\',
            `ship_city` VARCHAR(128) NOT NULL DEFAULT \'\',
            `ship_state` VARCHAR(64) NOT NULL DEFAULT \'\',
            `ship_postal_code` VARCHAR(20) NOT NULL DEFAULT \'\',
            `ship_country_code` VARCHAR(8) NOT NULL DEFAULT \'\',
            `ship_phone` VARCHAR(32) NOT NULL DEFAULT \'\',
            `items_matched` INT(11) NOT NULL DEFAULT 0,
            `items_unmatched` INT(11) NOT NULL DEFAULT 0,
            `raw_json` LONGTEXT NULL,
            `id_order` INT(11) NOT NULL DEFAULT 0,
            `import_status` VARCHAR(32) NOT NULL DEFAULT \'imported\',
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_order`),
            UNIQUE KEY `amazon_order_id` (`amazon_order_id`)
        ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8;';

        $sqls[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` (
            `id_amazonmarketplacepro_order_item` INT(11) NOT NULL AUTO_INCREMENT,
            `id_amazonmarketplacepro_order` INT(11) NOT NULL,
            `amazon_order_id` VARCHAR(64) NOT NULL,
            `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
            `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
            `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
            `title` VARCHAR(512) NOT NULL DEFAULT \'\',
            `quantity` INT(11) NOT NULL DEFAULT 0,
            `item_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `item_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `shipping_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `shipping_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `promotion_discount` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
            `id_product` INT(11) NOT NULL DEFAULT 0,
            `id_product_attribute` INT(11) NOT NULL DEFAULT 0,
            `match_status` VARCHAR(16) NOT NULL DEFAULT \'unmatched\',
            PRIMARY KEY (`id_amazonmarketplacepro_order_item`),
            KEY `id_amazonmarketplacepro_order` (`id_amazonmarketplacepro_order`)
        ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8;';

        foreach ($sqls as $q) {
            Db::getInstance()->execute($q);
        }

        // Older installs: add columns introduced after the table shipped.
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` LIKE \'is_prime\''
        );
        if (empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 ADD COLUMN `is_prime` TINYINT(1) NOT NULL DEFAULT 0'
            );
        }
    }

    /**
     * Fetch orders from Amazon and stage any that aren't already imported.
     *
     * @param string $createdAfter ISO8601 date, or a sandbox magic value like TEST_CASE_200
     *
     * @return array|false Summary counts, or false on API error (see getLastError())
     */
    /**
     * Columns added after 1.0 so status rules and carrier mapping have
     * something to route on.
     */
    private function ensureOrderColumns()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $rows = Db::getInstance()->executeS(
            'SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'amazonmarketplacepro_order\''
        );
        if (empty($rows)) {
            return; // fresh install: the CREATE below already has them
        }

        $existing = array();
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`'
        );
        if (is_array($cols)) {
            foreach ($cols as $c) {
                $existing[$c['Field']] = true;
            }
        }
        $add = array(
            'ship_service_level' => 'VARCHAR(64) NOT NULL DEFAULT \'\'',
            'is_business' => 'TINYINT(1) NOT NULL DEFAULT 0',
        );
        foreach ($add as $name => $definition) {
            if (!isset($existing[$name])) {
                Db::getInstance()->execute(
                    'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                     ADD `' . bqSQL($name) . '` ' . $definition
                );
            }
        }
    }

    public function importNewOrders($createdAfter)
    {
        $this->ensureTables();

        $summary = array(
            'fetched' => 0,
            'imported_new' => 0,
            'already' => 0,
            'items_matched' => 0,
            'items_unmatched' => 0,
            'pages' => 0,
            'truncated' => false,
        );

        // Follow NextToken pagination until exhausted (or the safety cap).
        $orders = array();
        $nextToken = null;
        do {
            $query = array('MarketplaceIds' => $this->marketplaceId);
            if ($nextToken !== null) {
                // When NextToken is set, Amazon ignores the other filter criteria.
                $query['NextToken'] = $nextToken;
            } else {
                $query['CreatedAfter'] = $createdAfter;
            }

            $resp = $this->client->request('GET', '/orders/v0/orders', $query);

            if ($resp === false) {
                if ($summary['pages'] > 0) {
                    $summary['truncated'] = true;
                    break; // keep what we already fetched
                }
                $this->lastError = $this->client->getLastError();
                return false;
            }
            if ($resp['status'] === 429 && $summary['pages'] > 0) {
                // Throttled mid-pagination: import what we have, flag as partial.
                $summary['truncated'] = true;
                break;
            }
            if ($resp['status'] >= 400) {
                $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
                $this->lastError = 'getOrders HTTP ' . $resp['status'] . ': ' . $body;
                return false;
            }

            $payload = (is_array($resp['body']) && isset($resp['body']['payload']))
                ? $resp['body']['payload']
                : array();

            if (isset($payload['Orders']) && is_array($payload['Orders'])) {
                foreach ($payload['Orders'] as $o) {
                    $orders[] = $o;
                }
            }

            $nextToken = (isset($payload['NextToken']) && $payload['NextToken'] !== '')
                ? $payload['NextToken']
                : null;
            $summary['pages']++;

            if ($nextToken !== null && $summary['pages'] >= self::MAX_ORDER_PAGES) {
                $summary['truncated'] = true;
                break;
            }
        } while ($nextToken !== null);

        $summary['fetched'] = count($orders);
        $importFba = (Configuration::get('AMZPRO_IMPORT_FBA_ORDERS') === false)
            ? true : (bool) Configuration::get('AMZPRO_IMPORT_FBA_ORDERS');
        $skippedFba = 0;

        foreach ($orders as $order) {
            $amazonId = isset($order['AmazonOrderId']) ? $order['AmazonOrderId'] : null;
            if ($amazonId === null || $amazonId === '') {
                continue;
            }
            // FBA (AFN) orders are shipped by Amazon; importing them is optional.
            if (!$importFba && isset($order['FulfillmentChannel'])
                && $order['FulfillmentChannel'] === 'AFN') {
                $skippedFba++;
                continue;
            }
            if ($this->orderExists($amazonId)) {
                $summary['already']++;
                continue;
            }

            // Fetch line items
            $items = $this->fetchItems($amazonId);
            $matched = 0;
            $unmatched = 0;
            $shippingTotal = 0;
            $shippingTax = 0;
            $orderTax = 0;

            foreach ($items as $idx => $it) {
                $res = $this->resolveProduct($it['seller_sku'], isset($it['asin']) ? $it['asin'] : '');
                $items[$idx]['id_product'] = $res['id_product'];
                $items[$idx]['id_product_attribute'] = $res['id_product_attribute'];
                if ($res['id_product']) {
                    $items[$idx]['match_status'] = 'matched';
                    $matched++;
                } else {
                    $items[$idx]['match_status'] = 'unmatched';
                    $unmatched++;
                }

                // Accumulate shipping and tax totals from items
                $shippingTotal += (float) $it['shipping_price'];
                $shippingTax += (float) $it['shipping_tax'];
                $orderTax += (float) $it['item_tax'];
            }

            // Fetch buyer shipping address (via RDT if available)
            $address = $this->fetchBuyerAddress($amazonId, $order);

            $this->insertOrder($order, $items, $matched, $unmatched, $address, $shippingTotal, $shippingTax, $orderTax);

            // Remote Cart: an unpaid Amazon order still takes the stock off
            // the shelf so no other channel can sell the same unit.
            if (isset($order['OrderStatus']) && $order['OrderStatus'] === 'Pending') {
                require_once dirname(__FILE__) . '/AmazonRemoteCart.php';
                $held = AmazonRemoteCart::reserve($amazonId, $items);
                if ($held > 0) {
                    $summary['reserved'] = (isset($summary['reserved']) ? $summary['reserved'] : 0) + $held;
                }
            }

            $summary['imported_new']++;
            $summary['items_matched'] += $matched;
            $summary['items_unmatched'] += $unmatched;
        }

        if ($skippedFba > 0) {
            $summary['skipped_fba'] = $skippedFba;
        }

        // Reservations whose order has since been paid or cancelled are
        // settled on every import, not only by the cron.
        require_once dirname(__FILE__) . '/AmazonRemoteCart.php';
        if (AmazonRemoteCart::isEnabled()) {
            $summary['reservations_settled'] = AmazonRemoteCart::convertConfirmed();
            $expired = AmazonRemoteCart::releaseExpired();
            $summary['reservations_expired'] = $expired['orders'];
        }

        return $summary;
    }

    /**
     * The CreatedAfter date for order imports, from the configured lookback
     * window (e.g. "7 days" or "12 hours").
     *
     * @return string ISO-8601 timestamp
     */
    public static function configuredCreatedAfter()
    {
        $value = (int) Configuration::get('AMZPRO_ORDER_LOOKBACK_VALUE');
        if ($value <= 0) {
            $value = 7;
        }
        $unit = (Configuration::get('AMZPRO_ORDER_LOOKBACK_UNIT') === 'hours') ? 3600 : 86400;

        return gmdate('Y-m-d\TH:i:s\Z', time() - $value * $unit);
    }

    /**
     * @return array Staged orders (newest first), without the heavy raw_json blob.
     */
    public function listStagedOrders()
    {
        $sql = 'SELECT `amazon_order_id`, `purchase_date`, `order_status`, `order_total`,
                       `currency`, `buyer_email`, `fulfillment_channel`, `is_prime`, `shipping_total`,
                       `order_tax`, `items_matched`, `items_unmatched`,
                       `id_order`, `import_status`, `date_add`
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                ORDER BY `purchase_date` DESC, `id_amazonmarketplacepro_order` DESC';
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : array();
    }

    private function orderExists($amazonId)
    {
        $val = Db::getInstance()->getValue(
            'SELECT `id_amazonmarketplacepro_order` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `amazon_order_id` = \'' . pSQL($amazonId) . '\''
        );

        return (bool) $val;
    }

    /**
     * Fetch order line items from Amazon (best-effort).
     * Now captures tax, shipping price, shipping tax, and promotion discount per item.
     *
     * @return array List of normalized item rows (possibly empty)
     */
    private function fetchItems($amazonOrderId)
    {
        $out = array();

        $resp = $this->client->request(
            'GET',
            '/orders/v0/orders/' . rawurlencode($amazonOrderId) . '/orderItems',
            array()
        );

        if ($resp === false || $resp['status'] >= 400) {
            return $out; // sandbox may not mock items for these IDs — not fatal
        }

        $items = array();
        if (is_array($resp['body']) && isset($resp['body']['payload']['OrderItems'])) {
            $items = $resp['body']['payload']['OrderItems'];
        }

        foreach ($items as $i) {
            $out[] = array(
                'order_item_id' => isset($i['OrderItemId']) ? $i['OrderItemId'] : '',
                'seller_sku' => isset($i['SellerSKU']) ? $i['SellerSKU'] : '',
                'asin' => isset($i['ASIN']) ? $i['ASIN'] : '',
                'title' => isset($i['Title']) ? $i['Title'] : '',
                'quantity' => isset($i['QuantityOrdered']) ? (int) $i['QuantityOrdered'] : 0,
                'item_price' => isset($i['ItemPrice']['Amount']) ? (float) $i['ItemPrice']['Amount'] : 0,
                'item_tax' => isset($i['ItemTax']['Amount']) ? (float) $i['ItemTax']['Amount'] : 0,
                'shipping_price' => isset($i['ShippingPrice']['Amount']) ? (float) $i['ShippingPrice']['Amount'] : 0,
                'shipping_tax' => isset($i['ShippingTax']['Amount']) ? (float) $i['ShippingTax']['Amount'] : 0,
                'promotion_discount' => isset($i['PromotionDiscount']['Amount']) ? (float) $i['PromotionDiscount']['Amount'] : 0,
                'currency' => isset($i['ItemPrice']['CurrencyCode']) ? $i['ItemPrice']['CurrencyCode'] : '',
                'id_product' => 0,
                'id_product_attribute' => 0,
                'match_status' => 'unmatched',
            );
        }

        return $out;
    }

    /**
     * Fetch buyer shipping address. Tries RDT-based address endpoint first,
     * falls back to order-level ShippingAddress data (available for some order types).
     *
     * @param string $amazonOrderId
     * @param array  $orderData The order payload from getOrders
     * @return array Normalized address fields
     */
    private function fetchBuyerAddress($amazonOrderId, $orderData)
    {
        $address = array(
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country_code' => '',
            'phone' => '',
        );

        // Try order-level ShippingAddress first (available without RDT for some fields)
        if (isset($orderData['ShippingAddress']) && is_array($orderData['ShippingAddress'])) {
            $sa = $orderData['ShippingAddress'];
            $address['address1'] = isset($sa['AddressLine1']) ? $sa['AddressLine1'] : '';
            $address['address2'] = isset($sa['AddressLine2']) ? $sa['AddressLine2'] : '';
            $address['city'] = isset($sa['City']) ? $sa['City'] : '';
            $address['state'] = isset($sa['StateOrRegion']) ? $sa['StateOrRegion'] : '';
            $address['postal_code'] = isset($sa['PostalCode']) ? $sa['PostalCode'] : '';
            $address['country_code'] = isset($sa['CountryCode']) ? $sa['CountryCode'] : '';
            $address['phone'] = isset($sa['Phone']) ? $sa['Phone'] : '';
        }

        // If we already have address data, no need for RDT
        if ($address['address1'] !== '' || $address['city'] !== '') {
            return $address;
        }

        // Try fetching via RDT (Restricted Data Token) for PII access
        $rdtToken = $this->client->getRestrictedDataToken(array(
            array(
                'method' => 'GET',
                'path' => '/orders/v0/orders/' . $amazonOrderId . '/address',
                'dataElements' => array('shippingAddress'),
            ),
        ));

        if ($rdtToken === false) {
            return $address; // RDT not available — app may lack the required role
        }

        $resp = $this->client->requestWithRDT(
            $rdtToken,
            'GET',
            '/orders/v0/orders/' . rawurlencode($amazonOrderId) . '/address',
            array()
        );

        if ($resp !== false && $resp['status'] < 400 && is_array($resp['body'])) {
            $sa = null;
            if (isset($resp['body']['payload']['ShippingAddress'])) {
                $sa = $resp['body']['payload']['ShippingAddress'];
            }
            if ($sa && is_array($sa)) {
                $address['address1'] = isset($sa['AddressLine1']) ? $sa['AddressLine1'] : '';
                $address['address2'] = isset($sa['AddressLine2']) ? $sa['AddressLine2'] : '';
                $address['city'] = isset($sa['City']) ? $sa['City'] : '';
                $address['state'] = isset($sa['StateOrRegion']) ? $sa['StateOrRegion'] : '';
                $address['postal_code'] = isset($sa['PostalCode']) ? $sa['PostalCode'] : '';
                $address['country_code'] = isset($sa['CountryCode']) ? $sa['CountryCode'] : '';
                $address['phone'] = isset($sa['Phone']) ? $sa['Phone'] : '';
            }
        }

        return $address;
    }

    /**
     * Resolve an Amazon SellerSKU to a PrestaShop product via the `reference` field.
     * Checks combinations (product_attribute) first, then the base product.
     * Also tries matching by EAN13 as fallback.
     *
     * @return array array('id_product' => int, 'id_product_attribute' => int)
     */
    private function resolveProduct($sku, $asin = '')
    {
        $res = array('id_product' => 0, 'id_product_attribute' => 0);

        // Optional: trust the ASIN before the SKU. Uses the staged product
        // table, where "Match ASINs by EAN" / Amazon syncs record the mapping.
        $asin = trim((string) $asin);
        if ($asin !== '' && Configuration::get('AMZPRO_PRIORITIZE_ASIN')) {
            $row = Db::getInstance()->getRow(
                'SELECT `id_product`, `id_product_attribute`
                 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
                 WHERE `amazon_asin` = \'' . pSQL($asin) . '\' AND `id_product` > 0'
            );
            if ($row && (int) $row['id_product']) {
                $res['id_product'] = (int) $row['id_product'];
                $res['id_product_attribute'] = (int) $row['id_product_attribute'];
                return $res;
            }
        }

        $sku = trim((string) $sku);
        if ($sku === '') {
            return $res;
        }
        $ref = pSQL($sku);

        // Merchants already selling on Amazon under different seller SKUs
        // record the Amazon SKU on the product's Amazon tab. Checked in every
        // mode except the id one, where the SKU is not a reference at all.
        $strategy = (string) Configuration::get('AMZPRO_ORDER_MATCH');
        if ($strategy !== 'id') {
            $idProduct = (int) Db::getInstance()->getValue(
                'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`
                 WHERE `override_sku` = \'' . $ref . '\' AND `override_sku` <> \'\''
            );
            if ($idProduct) {
                $res['id_product'] = $idProduct;
                return $res;
            }
        }

        // "SKU is built from PrestaShop ids" shops: 123 or 123_45.
        if ($strategy === 'id') {
            if (preg_match('/^(\d+)(?:[_-](\d+))?$/', $sku, $m)) {
                $idProduct = (int) Db::getInstance()->getValue(
                    'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
                     WHERE `id_product` = ' . (int) $m[1]
                );
                if ($idProduct) {
                    $res['id_product'] = $idProduct;
                    $res['id_product_attribute'] = isset($m[2]) ? (int) $m[2] : 0;
                    return $res;
                }
            }
        }

        // A configured SKU prefix is not part of the PrestaShop reference.
        $prefix = trim((string) Configuration::get('AMZPRO_SKU_PREFIX'));
        if ($prefix !== '' && strpos($sku, $prefix . '-') === 0) {
            $ref = pSQL(Tools::substr($sku, Tools::strlen($prefix) + 1));
        }

        // Try combination reference first
        $row = Db::getInstance()->getRow(
            'SELECT `id_product`, `id_product_attribute`
             FROM `' . _DB_PREFIX_ . 'product_attribute`
             WHERE `reference` = \'' . $ref . '\''
        );
        if ($row && (int) $row['id_product']) {
            $res['id_product'] = (int) $row['id_product'];
            $res['id_product_attribute'] = (int) $row['id_product_attribute'];
            return $res;
        }

        // Try base product reference
        $idProduct = (int) Db::getInstance()->getValue(
            'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
             WHERE `reference` = \'' . $ref . '\''
        );
        if ($idProduct) {
            $res['id_product'] = $idProduct;
            return $res;
        }

        // Fallback: try matching by EAN13
        $row = Db::getInstance()->getRow(
            'SELECT `id_product`, `id_product_attribute`
             FROM `' . _DB_PREFIX_ . 'product_attribute`
             WHERE `ean13` = \'' . $ref . '\''
        );
        if ($row && (int) $row['id_product']) {
            $res['id_product'] = (int) $row['id_product'];
            $res['id_product_attribute'] = (int) $row['id_product_attribute'];
            return $res;
        }

        $idProduct = (int) Db::getInstance()->getValue(
            'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
             WHERE `ean13` = \'' . $ref . '\''
        );
        $res['id_product'] = $idProduct;

        return $res;
    }

    private function insertOrder($order, $items, $matched, $unmatched, $address, $shippingTotal, $shippingTax, $orderTax)
    {
        $amazonId = $order['AmazonOrderId'];
        $purchase = isset($order['PurchaseDate']) ? date('Y-m-d H:i:s', strtotime($order['PurchaseDate'])) : null;
        $status = isset($order['OrderStatus']) ? $order['OrderStatus'] : '';
        $total = isset($order['OrderTotal']['Amount']) ? (float) $order['OrderTotal']['Amount'] : 0;
        $currency = isset($order['OrderTotal']['CurrencyCode']) ? $order['OrderTotal']['CurrencyCode'] : '';
        $email = isset($order['BuyerInfo']['BuyerEmail'])
            ? $order['BuyerInfo']['BuyerEmail']
            : (isset($order['BuyerEmail']) ? $order['BuyerEmail'] : '');
        $name = isset($order['BuyerInfo']['BuyerName']) ? $order['BuyerInfo']['BuyerName'] : '';
        $marketplace = isset($order['MarketplaceId']) ? $order['MarketplaceId'] : $this->marketplaceId;

        // FBA vs MFN, Prime, Amazon Business, and the shipping speed Amazon
        // promised the buyer — all three drive PS status/carrier routing.
        $fulfillmentChannel = isset($order['FulfillmentChannel']) ? $order['FulfillmentChannel'] : 'MFN';
        $isPrime = !empty($order['IsPrime']) ? 1 : 0;
        $isBusiness = !empty($order['IsBusinessOrder']) ? 1 : 0;
        $shipServiceLevel = '';
        if (!empty($order['ShipmentServiceLevelCategory'])) {
            $shipServiceLevel = $order['ShipmentServiceLevelCategory'];
        } elseif (!empty($order['ShipServiceLevel'])) {
            $shipServiceLevel = $order['ShipServiceLevel'];
        }

        $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
            (`amazon_order_id`, `purchase_date`, `order_status`, `order_total`, `currency`,
             `buyer_email`, `buyer_name`, `marketplace_id`, `fulfillment_channel`, `is_prime`,
             `shipping_total`, `shipping_tax`, `order_tax`, `amazon_fees`,
             `ship_address1`, `ship_address2`, `ship_city`, `ship_state`,
             `ship_postal_code`, `ship_country_code`, `ship_phone`,
             `items_matched`, `items_unmatched`, `ship_service_level`, `is_business`,
             `raw_json`, `id_order`, `import_status`, `date_add`, `date_upd`)
            VALUES (
                \'' . pSQL($amazonId) . '\',
                ' . ($purchase ? '\'' . pSQL($purchase) . '\'' : 'NULL') . ',
                \'' . pSQL($status) . '\',
                ' . (float) $total . ',
                \'' . pSQL($currency) . '\',
                \'' . pSQL($email) . '\',
                \'' . pSQL($name) . '\',
                \'' . pSQL($marketplace) . '\',
                \'' . pSQL($fulfillmentChannel) . '\',
                ' . (int) $isPrime . ',
                ' . (float) $shippingTotal . ',
                ' . (float) $shippingTax . ',
                ' . (float) $orderTax . ',
                0,
                \'' . pSQL($address['address1']) . '\',
                \'' . pSQL($address['address2']) . '\',
                \'' . pSQL($address['city']) . '\',
                \'' . pSQL($address['state']) . '\',
                \'' . pSQL($address['postal_code']) . '\',
                \'' . pSQL($address['country_code']) . '\',
                \'' . pSQL($address['phone']) . '\',
                ' . (int) $matched . ',
                ' . (int) $unmatched . ',
                \'' . pSQL($shipServiceLevel) . '\',
                ' . (int) $isBusiness . ',
                \'' . pSQL(json_encode($order), true) . '\',
                0,
                \'imported\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )';
        Db::getInstance()->execute($sql);

        $idStaged = (int) Db::getInstance()->Insert_ID();
        if (!$idStaged) {
            return;
        }

        foreach ($items as $it) {
            $sqlItem = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item`
                (`id_amazonmarketplacepro_order`, `amazon_order_id`, `order_item_id`, `seller_sku`,
                 `asin`, `title`, `quantity`, `item_price`, `item_tax`,
                 `shipping_price`, `shipping_tax`, `promotion_discount`, `currency`,
                 `id_product`, `id_product_attribute`, `match_status`)
                VALUES (
                    ' . (int) $idStaged . ',
                    \'' . pSQL($amazonId) . '\',
                    \'' . pSQL($it['order_item_id']) . '\',
                    \'' . pSQL($it['seller_sku']) . '\',
                    \'' . pSQL($it['asin']) . '\',
                    \'' . pSQL($it['title']) . '\',
                    ' . (int) $it['quantity'] . ',
                    ' . (float) $it['item_price'] . ',
                    ' . (float) $it['item_tax'] . ',
                    ' . (float) $it['shipping_price'] . ',
                    ' . (float) $it['shipping_tax'] . ',
                    ' . (float) $it['promotion_discount'] . ',
                    \'' . pSQL($it['currency']) . '\',
                    ' . (int) $it['id_product'] . ',
                    ' . (int) $it['id_product_attribute'] . ',
                    \'' . pSQL($it['match_status']) . '\'
                )';
            Db::getInstance()->execute($sqlItem);
        }
    }
}
