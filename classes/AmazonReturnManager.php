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
 * Amazon Return/Refund Manager.
 *
 * Handles:
 * - Importing returns from Amazon (via Orders API getOrders with status filter)
 * - Creating PrestaShop credit slips (OrderSlip) for Amazon returns
 * - Pushing cancellations from PrestaShop to Amazon
 * - Pushing refunds from PrestaShop to Amazon
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonReturnManager
{
    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $sellerId;
    private $lastError = null;
    private $notices = array();

    public function __construct(AmazonSpApiClient $client, $marketplaceId, $sellerId = '')
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
        $this->sellerId = trim((string) $sellerId);
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
     * Ensure the returns table exists (idempotent).
     */
    public function ensureTables()
    {
        $engine = defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB';

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_return` (
                `id_amazonmarketplacepro_return` INT(11) NOT NULL AUTO_INCREMENT,
                `amazon_order_id` VARCHAR(64) NOT NULL,
                `amazon_return_id` VARCHAR(64) NOT NULL DEFAULT \'\',
                `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
                `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
                `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
                `title` VARCHAR(512) NOT NULL DEFAULT \'\',
                `quantity` INT(11) NOT NULL DEFAULT 0,
                `reason` VARCHAR(255) NOT NULL DEFAULT \'\',
                `status` VARCHAR(64) NOT NULL DEFAULT \'\',
                `refund_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
                `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
                `id_order` INT(11) NOT NULL DEFAULT 0,
                `id_order_slip` INT(11) NOT NULL DEFAULT 0,
                `return_status` VARCHAR(32) NOT NULL DEFAULT \'imported\',
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_return`),
                KEY `amazon_order_id` (`amazon_order_id`),
                KEY `return_status` (`return_status`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8;'
        );
    }

    /**
     * Import cancelled/returned orders from Amazon.
     *
     * Fetches orders with Cancelled status and cross-references with our
     * staged orders to detect returns. Also checks for status changes on
     * previously imported orders.
     *
     * @param string $createdAfter ISO8601 date
     * @return array Summary
     */
    public function importReturns($createdAfter)
    {
        $this->ensureTables();
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'checked' => 0,
            'new_returns' => 0,
            'new_cancellations' => 0,
            'already' => 0,
        );

        // Fetch cancelled orders from Amazon (all pages, capped for safety)
        $orders = array();
        $nextToken = null;
        $pages = 0;
        do {
            $query = array('MarketplaceIds' => $this->marketplaceId);
            if ($nextToken !== null) {
                $query['NextToken'] = $nextToken;
            } else {
                $query['CreatedAfter'] = $createdAfter;
                $query['OrderStatuses'] = 'Canceled';
            }

            $resp = $this->client->request('GET', '/orders/v0/orders', $query);

            if ($resp === false) {
                if ($pages > 0) {
                    break; // keep what we have
                }
                $this->lastError = $this->client->getLastError();
                return $summary;
            }
            if ($resp['status'] === 429 && $pages > 0) {
                break; // throttled mid-pagination: process what we have
            }
            if ($resp['status'] >= 400) {
                $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
                $this->lastError = 'getOrders (Canceled) HTTP ' . $resp['status'] . ': ' . $body;
                return $summary;
            }

            $payload = (is_array($resp['body']) && isset($resp['body']['payload']))
                ? $resp['body']['payload'] : array();
            if (isset($payload['Orders']) && is_array($payload['Orders'])) {
                foreach ($payload['Orders'] as $o) {
                    $orders[] = $o;
                }
            }

            $nextToken = (isset($payload['NextToken']) && $payload['NextToken'] !== '')
                ? $payload['NextToken'] : null;
            $pages++;
        } while ($nextToken !== null && $pages < 30);

        $summary['checked'] = count($orders);

        foreach ($orders as $order) {
            $amazonId = isset($order['AmazonOrderId']) ? $order['AmazonOrderId'] : '';
            if ($amazonId === '') {
                continue;
            }

            // Check if we already have this return
            $exists = (bool) Db::getInstance()->getValue(
                'SELECT `id_amazonmarketplacepro_return` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
                 WHERE `amazon_order_id` = \'' . pSQL($amazonId) . '\'
                   AND `status` = \'Canceled\'
                 LIMIT 1'
            );
            if ($exists) {
                $summary['already']++;
                continue;
            }

            // Find our staged order
            $stagedOrder = Db::getInstance()->getRow(
                'SELECT `id_amazonmarketplacepro_order`, `id_order`, `order_total`, `currency`
                 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 WHERE `amazon_order_id` = \'' . pSQL($amazonId) . '\''
            );

            $idOrder = ($stagedOrder && isset($stagedOrder['id_order'])) ? (int) $stagedOrder['id_order'] : 0;
            $total = ($stagedOrder && isset($stagedOrder['order_total'])) ? (float) $stagedOrder['order_total'] : 0;
            $currency = ($stagedOrder && isset($stagedOrder['currency'])) ? $stagedOrder['currency'] : '';

            $now = date('Y-m-d H:i:s');
            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
                 (`amazon_order_id`, `amazon_return_id`, `order_item_id`, `seller_sku`,
                  `asin`, `title`, `quantity`, `reason`, `status`,
                  `refund_amount`, `currency`, `id_order`, `id_order_slip`,
                  `return_status`, `date_add`, `date_upd`)
                 VALUES (
                    \'' . pSQL($amazonId) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    \'Full order cancellation\',
                    0,
                    \'Order cancelled on Amazon\',
                    \'Canceled\',
                    ' . (float) $total . ',
                    \'' . pSQL($currency) . '\',
                    ' . $idOrder . ',
                    0,
                    \'imported\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                 )'
            );

            $summary['new_cancellations']++;

            // Update the staged order status
            if ($stagedOrder) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` SET
                        `order_status` = \'Canceled\',
                        `import_status` = \'cancelled\',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `id_amazonmarketplacepro_order` = ' . (int) $stagedOrder['id_amazonmarketplacepro_order']
                );
            }
        }

        // Also check for refunded orders (status = Unfulfillable or items returned)
        $this->checkForReturnedItems($createdAfter, $summary);

        return $summary;
    }

    /**
     * Check for individual item returns on already-imported orders.
     * Uses order items' QuantityShipped vs QuantityReturned.
     */
    private function checkForReturnedItems($createdAfter, &$summary)
    {
        // Get our staged orders that have been created in PS
        $stagedOrders = Db::getInstance()->executeS(
            'SELECT `amazon_order_id`, `id_order` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `id_order` > 0 AND `import_status` = \'created\'
             ORDER BY `date_add` DESC
             LIMIT 100'
        );

        if (!is_array($stagedOrders) || empty($stagedOrders)) {
            return;
        }

        foreach ($stagedOrders as $so) {
            $amazonId = $so['amazon_order_id'];

            // Fetch current order items from Amazon
            $resp = $this->client->request(
                'GET',
                '/orders/v0/orders/' . rawurlencode($amazonId) . '/orderItems',
                array()
            );

            if ($resp === false || $resp['status'] >= 400) {
                continue;
            }

            $items = array();
            if (is_array($resp['body']) && isset($resp['body']['payload']['OrderItems'])) {
                $items = $resp['body']['payload']['OrderItems'];
            }

            foreach ($items as $item) {
                $qtyReturned = isset($item['QuantityReturned']) ? (int) $item['QuantityReturned'] : 0;
                if ($qtyReturned <= 0) {
                    continue;
                }

                $orderItemId = isset($item['OrderItemId']) ? $item['OrderItemId'] : '';

                // Check if already recorded
                $exists = (bool) Db::getInstance()->getValue(
                    'SELECT `id_amazonmarketplacepro_return` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
                     WHERE `amazon_order_id` = \'' . pSQL($amazonId) . '\'
                       AND `order_item_id` = \'' . pSQL($orderItemId) . '\'
                     LIMIT 1'
                );
                if ($exists) {
                    continue;
                }

                $sku = isset($item['SellerSKU']) ? $item['SellerSKU'] : '';
                $asin = isset($item['ASIN']) ? $item['ASIN'] : '';
                $title = isset($item['Title']) ? $item['Title'] : '';
                $itemPrice = isset($item['ItemPrice']['Amount']) ? (float) $item['ItemPrice']['Amount'] : 0;
                $currency = isset($item['ItemPrice']['CurrencyCode']) ? $item['ItemPrice']['CurrencyCode'] : '';
                $qtyOrdered = isset($item['QuantityOrdered']) ? (int) $item['QuantityOrdered'] : 0;

                // Pro-rate refund amount
                $refundAmount = ($qtyOrdered > 0) ? ($itemPrice / $qtyOrdered * $qtyReturned) : 0;

                $now = date('Y-m-d H:i:s');
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
                     (`amazon_order_id`, `amazon_return_id`, `order_item_id`, `seller_sku`,
                      `asin`, `title`, `quantity`, `reason`, `status`,
                      `refund_amount`, `currency`, `id_order`, `id_order_slip`,
                      `return_status`, `date_add`, `date_upd`)
                     VALUES (
                        \'' . pSQL($amazonId) . '\',
                        \'\',
                        \'' . pSQL($orderItemId) . '\',
                        \'' . pSQL($sku) . '\',
                        \'' . pSQL($asin) . '\',
                        \'' . pSQL($title) . '\',
                        ' . (int) $qtyReturned . ',
                        \'Item returned by buyer\',
                        \'Returned\',
                        ' . (float) $refundAmount . ',
                        \'' . pSQL($currency) . '\',
                        ' . (int) $so['id_order'] . ',
                        0,
                        \'imported\',
                        \'' . pSQL($now) . '\',
                        \'' . pSQL($now) . '\'
                     )'
                );

                $summary['new_returns']++;
            }
        }
    }

    /**
     * Process imported returns: create PS credit slips for returns that
     * haven't been processed yet.
     *
     * @return array Summary
     */
    public function processReturns()
    {
        $this->ensureTables();
        $this->notices = array();

        $summary = array(
            'total' => 0,
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        // Get unprocessed returns that have a PS order
        $returns = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
             WHERE `return_status` = \'imported\' AND `id_order` > 0
             ORDER BY `date_add` ASC
             LIMIT 50'
        );

        if (!is_array($returns)) {
            $returns = array();
        }

        $summary['total'] = count($returns);

        foreach ($returns as $ret) {
            $idReturn = (int) $ret['id_amazonmarketplacepro_return'];
            $idOrder = (int) $ret['id_order'];

            $order = new Order($idOrder);
            if (!Validate::isLoadedObject($order)) {
                $summary['skipped']++;
                $this->notices[] = 'Return #' . $idReturn . ': PS order #' . $idOrder . ' not found';
                continue;
            }

            $now = date('Y-m-d H:i:s');

            if ($ret['status'] === 'Canceled') {
                // Full cancellation — cancel the PS order
                $cancelledStateId = (int) Configuration::get('PS_OS_CANCELED');
                if ($cancelledStateId && (int) $order->current_state !== $cancelledStateId) {
                    $history = new OrderHistory();
                    $history->id_order = $idOrder;
                    $history->id_employee = 0;
                    $history->changeIdOrderState($cancelledStateId, $idOrder);
                    $history->add();

                    $this->notices[] = 'Order #' . $idOrder . ' (Amazon: ' . $ret['amazon_order_id'] . ') cancelled';
                }

                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_return` SET
                        `return_status` = \'processed\',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `id_amazonmarketplacepro_return` = ' . $idReturn
                );
                $summary['processed']++;

            } elseif ($ret['status'] === 'Returned') {
                // Item return — create credit slip
                $slipId = $this->createCreditSlip($order, $ret);

                if ($slipId) {
                    Db::getInstance()->execute(
                        'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_return` SET
                            `return_status` = \'refunded\',
                            `id_order_slip` = ' . (int) $slipId . ',
                            `date_upd` = \'' . pSQL($now) . '\'
                         WHERE `id_amazonmarketplacepro_return` = ' . $idReturn
                    );
                    $summary['processed']++;
                    $this->notices[] = 'Credit slip #' . $slipId . ' created for order #' . $idOrder;
                } else {
                    Db::getInstance()->execute(
                        'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_return` SET
                            `return_status` = \'processed\',
                            `date_upd` = \'' . pSQL($now) . '\'
                         WHERE `id_amazonmarketplacepro_return` = ' . $idReturn
                    );
                    $summary['processed']++;
                    $this->notices[] = 'Return processed for order #' . $idOrder . ' (no credit slip — partial return)';
                }
            } else {
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    /**
     * Create a PrestaShop credit slip (OrderSlip) for a returned item.
     *
     * @param Order $order
     * @param array $returnData
     * @return int|false Credit slip ID
     */
    private function createCreditSlip($order, $returnData)
    {
        // Find the matching OrderDetail
        $sku = $returnData['seller_sku'];
        $qty = (int) $returnData['quantity'];

        if (!$sku || $qty <= 0) {
            return false;
        }

        $orderDetail = Db::getInstance()->getRow(
            'SELECT `id_order_detail`, `product_id`, `product_attribute_id`,
                    `unit_price_tax_incl`, `unit_price_tax_excl`
             FROM `' . _DB_PREFIX_ . 'order_detail`
             WHERE `id_order` = ' . (int) $order->id . '
               AND `product_reference` = \'' . pSQL($sku) . '\'
             LIMIT 1'
        );

        if (!$orderDetail) {
            return false;
        }

        $unitPriceTaxIncl = (float) $orderDetail['unit_price_tax_incl'];
        $unitPriceTaxExcl = (float) $orderDetail['unit_price_tax_excl'];
        $totalAmount = $unitPriceTaxIncl * $qty;

        // Create OrderSlip
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int) $order->id_customer;
        $orderSlip->id_order = (int) $order->id;
        $orderSlip->conversion_rate = 1;
        $orderSlip->total_products_tax_excl = $unitPriceTaxExcl * $qty;
        $orderSlip->total_products_tax_incl = $totalAmount;
        $orderSlip->total_shipping_tax_excl = 0;
        $orderSlip->total_shipping_tax_incl = 0;
        $orderSlip->amount = $totalAmount;
        $orderSlip->shipping_cost = 0;
        $orderSlip->partial = ($qty < (int) Db::getInstance()->getValue(
            'SELECT `product_quantity` FROM `' . _DB_PREFIX_ . 'order_detail`
             WHERE `id_order_detail` = ' . (int) $orderDetail['id_order_detail']
        )) ? 1 : 0;

        if (!$orderSlip->add()) {
            return false;
        }

        // Create OrderSlipDetail
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'order_slip_detail`
             (`id_order_slip`, `id_order_detail`, `product_quantity`,
              `unit_price_tax_excl`, `unit_price_tax_incl`,
              `total_price_tax_excl`, `total_price_tax_incl`, `amount_tax_excl`, `amount_tax_incl`)
             VALUES (
                ' . (int) $orderSlip->id . ',
                ' . (int) $orderDetail['id_order_detail'] . ',
                ' . $qty . ',
                ' . (float) $unitPriceTaxExcl . ',
                ' . (float) $unitPriceTaxIncl . ',
                ' . (float) ($unitPriceTaxExcl * $qty) . ',
                ' . (float) $totalAmount . ',
                ' . (float) ($unitPriceTaxExcl * $qty) . ',
                ' . (float) $totalAmount . '
             )'
        );

        return (int) $orderSlip->id;
    }

    /**
     * Cancel an order on Amazon.
     * Called when a PrestaShop order (Amazon-imported) is set to Cancelled.
     *
     * @param string $amazonOrderId
     * @return bool
     */
    public function cancelOrderOnAmazon($amazonOrderId)
    {
        $this->lastError = null;

        // Seller-fulfilled (MFN) orders are cancelled by acknowledging the
        // order with StatusCode "Failure" via a POST_ORDER_ACKNOWLEDGEMENT_DATA
        // feed. FBA orders cannot be cancelled this way (Amazon fulfills them).
        require_once dirname(__FILE__) . '/AmazonFeedManager.php';

        $sellerId = (string) Configuration::get('AMZPRO_SELLER_ID');
        if ($sellerId === '') {
            $this->lastError = 'No seller id configured — cannot submit the cancellation feed.';
            return false;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">' . "\n"
            . '  <Header>' . "\n"
            . '    <DocumentVersion>1.01</DocumentVersion>' . "\n"
            . '    <MerchantIdentifier>' . htmlspecialchars($sellerId) . '</MerchantIdentifier>' . "\n"
            . '  </Header>' . "\n"
            . '  <MessageType>OrderAcknowledgement</MessageType>' . "\n"
            . '  <Message>' . "\n"
            . '    <MessageID>1</MessageID>' . "\n"
            . '    <OrderAcknowledgement>' . "\n"
            . '      <AmazonOrderID>' . htmlspecialchars($amazonOrderId) . '</AmazonOrderID>' . "\n"
            . '      <StatusCode>Failure</StatusCode>' . "\n"
            . '    </OrderAcknowledgement>' . "\n"
            . '  </Message>' . "\n"
            . '</AmazonEnvelope>';

        $feeds = new AmazonFeedManager($this->client, $this->marketplaceId, $sellerId);
        $env = Configuration::get('AMZPRO_ENVIRONMENT');
        $feeds->setMock(Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production');

        $feedId = $feeds->submitFeed(
            'POST_ORDER_ACKNOWLEDGEMENT_DATA',
            'text/xml; charset=UTF-8',
            $xml,
            1
        );

        if ($feedId === false) {
            $this->lastError = 'Cancellation feed failed: ' . $feeds->getLastError();
            $this->notices[] = 'Cancellation feed could not be submitted. '
                . 'Manual cancellation in Seller Central may be required.';
            return false;
        }

        $this->notices[] = 'Cancellation feed ' . $feedId . ' submitted for order ' . $amazonOrderId
            . '. Amazon processes it asynchronously (check feed status in the Products tab).';

        return true;
    }

    /**
     * List staged returns (for admin UI).
     *
     * @param int $limit
     * @return array
     */
    public function listReturns($limit = 50)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
                ORDER BY `date_add` DESC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }
}
