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

/**
 * Amazon Fees & Commissions Tracker.
 *
 * Fetches financial event data from the SP-API Finances API to track
 * Amazon fees (referral fees, FBA fees, commissions) per order.
 *
 * Stores fee breakdowns in amazonmarketplacepro_order_fee and updates
 * the order-level amazon_fees total.
 *
 * Uses SP-API Finances API v0.
 *
 * Multistore: a tracker works for one shop (the shop whose Amazon account the
 * client is connected to) and only fetches fees for that shop's orders. Fee
 * rows carry the shop of their order.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonFeesTracker
{
    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError = null;
    private $notices = array();
    /** The shop whose orders are tracked. */
    private $idShop;
    /** The shop the caller named, or 0 for "the request's shop". */
    private $shopGiven;

    /**
     * @param AmazonSpApiClient $client
     * @param string            $marketplaceId
     * @param int               $idShop 0 = the shop the request acts for
     */
    public function __construct(AmazonSpApiClient $client, $marketplaceId, $idShop = 0)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
        $this->shopGiven = (int) $idShop;
        $this->idShop = $this->shopGiven ? $this->shopGiven : AmzproShop::actingId();
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
     * Fetch financial events for recent orders and store fee breakdowns.
     *
     * Processes orders that have amazon_fees = 0 and have been imported.
     *
     * @param int $limit Max orders to process per run
     * @return array Summary
     */
    public function fetchOrderFees($limit = 50)
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'checked' => 0,
            'updated' => 0,
            'no_fees' => 0,
            'errors' => 0,
        );

        // Get orders with no fees tracked yet
        $orders = Db::getInstance()->executeS(
            'SELECT `amazon_order_id`, `id_amazonmarketplacepro_order`, `id_shop`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `amazon_fees` = 0
               AND `import_status` IN (\'imported\', \'created\')
               AND `id_shop` = ' . (int) $this->idShop . '
             ORDER BY `date_add` DESC
             LIMIT ' . (int) $limit
        );

        if (!is_array($orders) || empty($orders)) {
            $this->notices[] = AmazonI18n::get()->l('No orders pending fee tracking.', 'amazonfeestracker');
            return $summary;
        }

        foreach ($orders as $order) {
            $summary['checked']++;
            $amazonId = $order['amazon_order_id'];

            $fees = $this->fetchFeesForOrder($amazonId);
            if ($fees === false) {
                $summary['errors']++;
                continue;
            }

            if (empty($fees)) {
                $summary['no_fees']++;
                continue;
            }

            // Store individual fee records
            $totalFees = 0;
            $now = date('Y-m-d H:i:s');

            foreach ($fees as $fee) {
                $feeAmount = (float) $fee['amount'];
                $totalFees += $feeAmount;

                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_fee`
                     (`amazon_order_id`, `fee_type`, `fee_amount`, `currency`,
                      `order_item_id`, `seller_sku`, `id_shop`, `date_add`)
                     VALUES (
                        \'' . pSQL($amazonId) . '\',
                        \'' . pSQL($fee['type']) . '\',
                        ' . $feeAmount . ',
                        \'' . pSQL($fee['currency']) . '\',
                        \'' . pSQL($fee['order_item_id']) . '\',
                        \'' . pSQL($fee['seller_sku']) . '\',
                        ' . (int) $order['id_shop'] . ',
                        \'' . pSQL($now) . '\'
                     )'
                );
            }

            // Update order-level total
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` SET
                    `amazon_fees` = ' . abs($totalFees) . ',
                    `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `amazon_order_id` = \'' . pSQL($amazonId) . '\'
                   AND `id_shop` = ' . (int) $order['id_shop']
            );

            $summary['updated']++;
        }

        return $summary;
    }

    /**
     * Fetch fee breakdown for a single order via Finances API.
     *
     * @param string $amazonOrderId
     * @return array|false List of fee records, or false on error
     */
    private function fetchFeesForOrder($amazonOrderId)
    {
        $resp = $this->client->request(
            'GET',
            '/finances/v0/orders/' . rawurlencode($amazonOrderId) . '/financialEvents',
            array()
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return false;
        }

        if ($resp['status'] >= 400) {
            // 404 is normal for orders not yet settled
            if ($resp['status'] === 404) {
                return array();
            }
            $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
            $this->lastError = 'Finances API HTTP ' . $resp['status'] . ': ' . $body;
            return false;
        }

        $fees = array();

        if (!is_array($resp['body'])) {
            return $fees;
        }

        $events = isset($resp['body']['payload']['FinancialEvents'])
            ? $resp['body']['payload']['FinancialEvents']
            : $resp['body'];

        // Process ShipmentEventList (most common for order fees)
        if (isset($events['ShipmentEventList']) && is_array($events['ShipmentEventList'])) {
            foreach ($events['ShipmentEventList'] as $shipmentEvent) {
                if (!isset($shipmentEvent['ShipmentItemList'])) {
                    continue;
                }

                foreach ($shipmentEvent['ShipmentItemList'] as $item) {
                    $orderItemId = isset($item['OrderItemId']) ? $item['OrderItemId'] : '';
                    $sku = isset($item['SellerSKU']) ? $item['SellerSKU'] : '';

                    // ItemFeeList contains all fees for this item
                    if (isset($item['ItemFeeList']) && is_array($item['ItemFeeList'])) {
                        foreach ($item['ItemFeeList'] as $feeEntry) {
                            $feeType = isset($feeEntry['FeeType']) ? $feeEntry['FeeType'] : '';
                            $amount = isset($feeEntry['FeeAmount']['CurrencyAmount'])
                                ? (float) $feeEntry['FeeAmount']['CurrencyAmount'] : 0;
                            $currency = isset($feeEntry['FeeAmount']['CurrencyCode'])
                                ? $feeEntry['FeeAmount']['CurrencyCode'] : '';

                            if ($amount != 0) {
                                $fees[] = array(
                                    'type' => $feeType,
                                    'amount' => $amount,
                                    'currency' => $currency,
                                    'order_item_id' => $orderItemId,
                                    'seller_sku' => $sku,
                                );
                            }
                        }
                    }

                    // ItemFeeAdjustmentList (fee adjustments)
                    if (isset($item['ItemFeeAdjustmentList']) && is_array($item['ItemFeeAdjustmentList'])) {
                        foreach ($item['ItemFeeAdjustmentList'] as $feeEntry) {
                            $feeType = isset($feeEntry['FeeType']) ? $feeEntry['FeeType'] : '';
                            $amount = isset($feeEntry['FeeAmount']['CurrencyAmount'])
                                ? (float) $feeEntry['FeeAmount']['CurrencyAmount'] : 0;
                            $currency = isset($feeEntry['FeeAmount']['CurrencyCode'])
                                ? $feeEntry['FeeAmount']['CurrencyCode'] : '';

                            if ($amount != 0) {
                                $fees[] = array(
                                    'type' => $feeType . '_adjustment',
                                    'amount' => $amount,
                                    'currency' => $currency,
                                    'order_item_id' => $orderItemId,
                                    'seller_sku' => $sku,
                                );
                            }
                        }
                    }
                }
            }
        }

        // Process RefundEventList
        if (isset($events['RefundEventList']) && is_array($events['RefundEventList'])) {
            foreach ($events['RefundEventList'] as $refundEvent) {
                if (!isset($refundEvent['ShipmentItemAdjustmentList'])) {
                    continue;
                }

                foreach ($refundEvent['ShipmentItemAdjustmentList'] as $item) {
                    $orderItemId = isset($item['OrderItemId']) ? $item['OrderItemId'] : '';
                    $sku = isset($item['SellerSKU']) ? $item['SellerSKU'] : '';

                    if (isset($item['ItemFeeAdjustmentList']) && is_array($item['ItemFeeAdjustmentList'])) {
                        foreach ($item['ItemFeeAdjustmentList'] as $feeEntry) {
                            $feeType = isset($feeEntry['FeeType']) ? $feeEntry['FeeType'] : '';
                            $amount = isset($feeEntry['FeeAmount']['CurrencyAmount'])
                                ? (float) $feeEntry['FeeAmount']['CurrencyAmount'] : 0;
                            $currency = isset($feeEntry['FeeAmount']['CurrencyCode'])
                                ? $feeEntry['FeeAmount']['CurrencyCode'] : '';

                            if ($amount != 0) {
                                $fees[] = array(
                                    'type' => 'refund_' . $feeType,
                                    'amount' => $amount,
                                    'currency' => $currency,
                                    'order_item_id' => $orderItemId,
                                    'seller_sku' => $sku,
                                );
                            }
                        }
                    }
                }
            }
        }

        return $fees;
    }

    /**
     * Get fee summary for display (grouped by fee type): the tracker's shop,
     * or every shop in "All shops".
     *
     * @return array
     */
    public function getFeeSummary()
    {
        $sql = 'SELECT `fee_type`, SUM(ABS(`fee_amount`)) AS total_amount, COUNT(*) AS count,
                       `currency`
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_fee`
                WHERE ' . $this->listScope() . '
                GROUP BY `fee_type`, `currency`
                ORDER BY total_amount DESC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Get fees for a specific order.
     *
     * @param string $amazonOrderId
     * @return array
     */
    public function getFeesForOrder($amazonOrderId)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_fee`
                WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
                  AND ' . $this->listScope() . '
                ORDER BY `fee_type` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Fetch financial events for a date range (for settlement reconciliation).
     *
     * @param string $startDate ISO8601
     * @param string $endDate ISO8601
     * @return array Summary of all financial events
     */
    public function fetchFinancialEventsByDate($startDate, $endDate)
    {
        $this->lastError = null;

        $resp = $this->client->request(
            'GET',
            '/finances/v0/financialEvents',
            array(
                'PostedAfter' => $startDate,
                'PostedBefore' => $endDate,
            )
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return array();
        }
        if ($resp['status'] >= 400) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
            $this->lastError = 'Finances API HTTP ' . $resp['status'] . ': ' . $body;
            return array();
        }

        $events = isset($resp['body']['payload']['FinancialEvents'])
            ? $resp['body']['payload']['FinancialEvents']
            : array();

        $summary = array(
            'shipment_events' => 0,
            'refund_events' => 0,
            'total_revenue' => 0,
            'total_fees' => 0,
            'total_refunds' => 0,
        );

        if (isset($events['ShipmentEventList'])) {
            $summary['shipment_events'] = count($events['ShipmentEventList']);
        }
        if (isset($events['RefundEventList'])) {
            $summary['refund_events'] = count($events['RefundEventList']);
        }

        return $summary;
    }

    /** Rows shown: the shop named by the caller, else the request's shop (all in "All shops"). */
    private function listScope()
    {
        return AmzproShop::sqlWhere('', $this->shopGiven ? $this->shopGiven : null);
    }
}
