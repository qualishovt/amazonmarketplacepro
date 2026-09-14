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
 * Amazon FBA (Fulfillment by Amazon) Manager.
 *
 * Handles:
 * - Syncing FBA inventory levels from Amazon to local tracking table
 * - Creating Multi-Channel Fulfillment (MCF) orders for non-Amazon sales
 * - Tracking FBA-specific order handling (AFN channel detection + display)
 *
 * Uses SP-API FBA Inventory API and Fulfillment Outbound API.
 *
 * Multistore: everything works for the current shop - its FBA inventory
 * rows, its products' stock and its PrestaShop orders.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonFbaManager
{
    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError = null;
    private $notices = array();

    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
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
     * Sync FBA inventory from Amazon into local tracking table.
     *
     * Uses the FBA Inventory API (v1) to fetch fulfillable quantities
     * and other inventory breakdowns for all FBA-managed SKUs.
     *
     * @return array Summary
     */
    public function syncFbaInventory()
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'fetched' => 0,
            'updated' => 0,
            'new' => 0,
            'errors' => 0,
        );

        $resp = $this->client->request(
            'GET',
            '/fba/inventory/v1/summaries',
            array(
                'granularityType' => 'Marketplace',
                'granularityId' => $this->marketplaceId,
                'marketplaceIds' => $this->marketplaceId,
                'details' => 'true',
            )
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return $summary;
        }
        if ($resp['status'] >= 400) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
            $this->lastError = 'FBA Inventory API HTTP ' . $resp['status'] . ': ' . $body;
            return $summary;
        }

        $inventories = array();
        if (is_array($resp['body']) && isset($resp['body']['payload']['inventorySummaries'])) {
            $inventories = $resp['body']['payload']['inventorySummaries'];
        }

        $summary['fetched'] = count($inventories);
        $now = date('Y-m-d H:i:s');
        $idShop = (int) AmzproShop::actingId();

        foreach ($inventories as $inv) {
            $sku = isset($inv['sellerSku']) ? $inv['sellerSku'] : '';
            if ($sku === '') {
                continue;
            }

            $asin = isset($inv['asin']) ? $inv['asin'] : '';
            $fnSku = isset($inv['fnSku']) ? $inv['fnSku'] : '';
            $productName = isset($inv['productName']) ? $inv['productName'] : '';

            $fulfillable = isset($inv['inventoryDetails']['fulfillableQuantity'])
                ? (int) $inv['inventoryDetails']['fulfillableQuantity'] : 0;
            $inboundWorking = isset($inv['inventoryDetails']['inboundWorkingQuantity'])
                ? (int) $inv['inventoryDetails']['inboundWorkingQuantity'] : 0;
            $inboundShipped = isset($inv['inventoryDetails']['inboundShippedQuantity'])
                ? (int) $inv['inventoryDetails']['inboundShippedQuantity'] : 0;
            $inboundReceiving = isset($inv['inventoryDetails']['inboundReceivingQuantity'])
                ? (int) $inv['inventoryDetails']['inboundReceivingQuantity'] : 0;
            $reserved = isset($inv['inventoryDetails']['reservedQuantity']['totalReservedQuantity'])
                ? (int) $inv['inventoryDetails']['reservedQuantity']['totalReservedQuantity'] : 0;
            $unfulfillable = isset($inv['inventoryDetails']['unfulfillableQuantity']['totalUnfulfillableQuantity'])
                ? (int) $inv['inventoryDetails']['unfulfillableQuantity']['totalUnfulfillableQuantity'] : 0;

            $totalQty = $fulfillable + $inboundWorking + $inboundShipped + $inboundReceiving + $reserved + $unfulfillable;

            // Resolve PS product
            $psProduct = $this->resolveProduct($sku, $idShop);

            $exists = (bool) Db::getInstance()->getValue(
                'SELECT `id_amazonmarketplacepro_fba` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory`
                 WHERE `seller_sku` = \'' . pSQL($sku) . '\'
                   AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                   AND `id_shop` = ' . $idShop
            );

            if ($exists) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory` SET
                        `asin` = \'' . pSQL($asin) . '\',
                        `fn_sku` = \'' . pSQL($fnSku) . '\',
                        `product_name` = \'' . pSQL($productName) . '\',
                        `fulfillable_qty` = ' . $fulfillable . ',
                        `inbound_working_qty` = ' . $inboundWorking . ',
                        `inbound_shipped_qty` = ' . $inboundShipped . ',
                        `inbound_receiving_qty` = ' . $inboundReceiving . ',
                        `reserved_qty` = ' . $reserved . ',
                        `unfulfillable_qty` = ' . $unfulfillable . ',
                        `total_qty` = ' . $totalQty . ',
                        `id_product` = ' . (int) $psProduct['id_product'] . ',
                        `id_product_attribute` = ' . (int) $psProduct['id_product_attribute'] . ',
                        `last_synced` = \'' . pSQL($now) . '\',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `seller_sku` = \'' . pSQL($sku) . '\'
                       AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                       AND `id_shop` = ' . $idShop
                );
                $summary['updated']++;
            } else {
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory`
                     (`id_shop`, `seller_sku`, `asin`, `fn_sku`, `product_name`,
                      `fulfillable_qty`, `inbound_working_qty`, `inbound_shipped_qty`,
                      `inbound_receiving_qty`, `reserved_qty`, `unfulfillable_qty`, `total_qty`,
                      `marketplace_id`, `id_product`, `id_product_attribute`,
                      `last_synced`, `date_add`, `date_upd`)
                     VALUES (
                        ' . $idShop . ',
                        \'' . pSQL($sku) . '\',
                        \'' . pSQL($asin) . '\',
                        \'' . pSQL($fnSku) . '\',
                        \'' . pSQL($productName) . '\',
                        ' . $fulfillable . ',
                        ' . $inboundWorking . ',
                        ' . $inboundShipped . ',
                        ' . $inboundReceiving . ',
                        ' . $reserved . ',
                        ' . $unfulfillable . ',
                        ' . $totalQty . ',
                        \'' . pSQL($this->marketplaceId) . '\',
                        ' . (int) $psProduct['id_product'] . ',
                        ' . (int) $psProduct['id_product_attribute'] . ',
                        \'' . pSQL($now) . '\',
                        \'' . pSQL($now) . '\',
                        \'' . pSQL($now) . '\'
                     )'
                );
                $summary['new']++;
            }
        }

        // Handle pagination if present
        if (is_array($resp['body']) && isset($resp['body']['pagination']['nextToken'])) {
            $this->notices[] = AmazonI18n::get()->l('More FBA inventory pages available. Run again to fetch more.', 'amazonfbamanager');
        }

        return $summary;
    }

    /**
     * Create a Multi-Channel Fulfillment (MCF) order on Amazon.
     *
     * This sends a PS order to Amazon for fulfillment from FBA inventory.
     * Used when the seller wants Amazon to ship an order from a non-Amazon channel.
     *
     * @param int $idOrder PrestaShop order ID
     * @return array Result
     */
    public function createMcfOrder($idOrder)
    {
        $this->lastError = null;

        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order)) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('PrestaShop order #%d not found.', 'amazonfbamanager'),
                (int) $idOrder
            );
            return array('success' => false, 'error' => $this->lastError);
        }

        // Amazon ships it from the stock of the seller account this shop is
        // connected to, so the order must belong to this shop.
        $idShop = (int) AmzproShop::actingId();
        if ((int) $order->id_shop !== $idShop) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('PrestaShop order #%d belongs to another shop. Select that shop at the top of the page and try again.', 'amazonfbamanager'),
                (int) $idOrder
            );
            return array('success' => false, 'error' => $this->lastError);
        }

        // Get delivery address
        $address = new Address((int) $order->id_address_delivery);
        if (!Validate::isLoadedObject($address)) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Delivery address not found for order #%d', 'amazonfbamanager'),
                (int) $idOrder
            );
            return array('success' => false, 'error' => $this->lastError);
        }

        $country = new Country((int) $address->id_country);
        $countryCode = $country->iso_code;

        $stateName = '';
        if ($address->id_state) {
            $state = new State((int) $address->id_state);
            $stateName = $state->iso_code ? $state->iso_code : $state->name;
        }

        // Get order items
        $orderDetails = OrderDetail::getList((int) $order->id);
        if (empty($orderDetails)) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('No items in order #%d', 'amazonfbamanager'),
                (int) $idOrder
            );
            return array('success' => false, 'error' => $this->lastError);
        }

        // Build MCF items
        $items = array();
        foreach ($orderDetails as $detail) {
            $sku = $detail['product_reference'];
            if (empty($sku)) {
                continue;
            }

            // Verify this SKU exists in FBA inventory
            $fbaQty = (int) Db::getInstance()->getValue(
                'SELECT `fulfillable_qty` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory`
                 WHERE `seller_sku` = \'' . pSQL($sku) . '\'
                   AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                   AND `id_shop` = ' . $idShop
            );

            if ($fbaQty < (int) $detail['product_quantity']) {
                $this->notices[] = sprintf(
                    AmazonI18n::get()->l('SKU %1$s: Amazon holds %2$d in FBA stock, but %3$d were ordered.', 'amazonfbamanager'),
                    $sku,
                    $fbaQty,
                    (int) $detail['product_quantity']
                );
            }

            $items[] = array(
                'sellerSku' => $sku,
                'sellerFulfillmentOrderItemId' => 'PS-' . $order->id . '-' . $detail['id_order_detail'],
                'quantity' => (int) $detail['product_quantity'],
            );
        }

        if (empty($items)) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('No FBA-eligible items (no reference/SKU) in order #%d', 'amazonfbamanager'),
                (int) $idOrder
            );
            return array('success' => false, 'error' => $this->lastError);
        }

        // Customer name
        $customer = new Customer((int) $order->id_customer);
        $displayName = $address->firstname . ' ' . $address->lastname;

        // Build the MCF fulfillment order request
        $body = array(
            'sellerFulfillmentOrderId' => 'PS-' . $order->id . '-' . date('Ymd'),
            'displayableOrderId' => $order->reference,
            'displayableOrderDate' => gmdate('Y-m-d\TH:i:s\Z', strtotime($order->date_add)),
            'displayableOrderComment' => 'PrestaShop order #' . $order->reference,
            'shippingSpeedCategory' => 'Standard',
            'destinationAddress' => array(
                'name' => $displayName,
                'addressLine1' => $address->address1,
                'addressLine2' => $address->address2 ? $address->address2 : '',
                'city' => $address->city,
                'stateOrRegion' => $stateName,
                'postalCode' => $address->postcode,
                'countryCode' => $countryCode,
                'phone' => $address->phone ? $address->phone : $address->phone_mobile,
            ),
            'items' => $items,
            'marketplaceId' => $this->marketplaceId,
        );

        $resp = $this->client->request(
            'POST',
            '/fba/outbound/2020-07-01/fulfillmentOrders',
            array(),
            $body
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return array('success' => false, 'error' => $this->lastError);
        }

        if ($resp['status'] >= 400) {
            $errorBody = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'MCF order HTTP ' . $resp['status'] . ': ' . $errorBody;
            return array('success' => false, 'error' => $this->lastError);
        }

        return array(
            'success' => true,
            'mcf_order_id' => 'PS-' . $order->id . '-' . date('Ymd'),
            'items_count' => count($items),
        );
    }

    /**
     * Get the status of an MCF fulfillment order.
     *
     * @param string $mcfOrderId
     * @return array|false
     */
    public function getMcfOrderStatus($mcfOrderId)
    {
        $resp = $this->client->request(
            'GET',
            '/fba/outbound/2020-07-01/fulfillmentOrders/' . rawurlencode($mcfOrderId),
            array()
        );

        if ($resp === false || $resp['status'] >= 400) {
            $this->lastError = $resp === false
                ? $this->client->getLastError()
                : 'HTTP ' . $resp['status'];
            return false;
        }

        $payload = is_array($resp['body']) && isset($resp['body']['payload'])
            ? $resp['body']['payload']
            : $resp['body'];

        $status = isset($payload['fulfillmentOrder']['fulfillmentOrderStatus'])
            ? $payload['fulfillmentOrder']['fulfillmentOrderStatus']
            : 'UNKNOWN';

        $shipments = array();
        if (isset($payload['fulfillmentShipments']) && is_array($payload['fulfillmentShipments'])) {
            foreach ($payload['fulfillmentShipments'] as $ship) {
                $shipments[] = array(
                    'status' => isset($ship['fulfillmentShipmentStatus']) ? $ship['fulfillmentShipmentStatus'] : '',
                    'tracking' => isset($ship['fulfillmentShipmentPackage'][0]['trackingNumber'])
                        ? $ship['fulfillmentShipmentPackage'][0]['trackingNumber'] : '',
                    'carrier' => isset($ship['fulfillmentShipmentPackage'][0]['carrierCode'])
                        ? $ship['fulfillmentShipmentPackage'][0]['carrierCode'] : '',
                );
            }
        }

        return array(
            'status' => $status,
            'shipments' => $shipments,
        );
    }

    /**
     * Update PS stock from FBA fulfillable quantities.
     * Optionally sync FBA stock levels into PrestaShop StockAvailable.
     *
     * @return array Summary
     */
    public function syncFbaStockToPs()
    {
        $summary = array('updated' => 0, 'skipped' => 0);
        $idShop = (int) AmzproShop::actingId();

        $rows = Db::getInstance()->executeS(
            'SELECT `seller_sku`, `fulfillable_qty`, `id_product`, `id_product_attribute`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory`
             WHERE `id_product` > 0
               AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
               AND `id_shop` = ' . $idShop
        );

        if (!is_array($rows)) {
            return $summary;
        }

        // StockAvailable resolves the shop to its group when the group shares stock.
        foreach ($rows as $row) {
            $idProduct = (int) $row['id_product'];
            $idPa = (int) $row['id_product_attribute'];
            $fbaQty = (int) $row['fulfillable_qty'];

            $currentQty = (int) StockAvailable::getQuantityAvailableByProduct($idProduct, $idPa, $idShop);

            if ($currentQty !== $fbaQty) {
                StockAvailable::setQuantity($idProduct, $idPa, $fbaQty, $idShop);
                $summary['updated']++;
            } else {
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    /**
     * List FBA inventory for admin display.
     *
     * @param int $limit
     * @return array
     */
    public function listFbaInventory($limit = 100)
    {
        $sql = 'SELECT f.*, s.`name` AS shop_name
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory` f
                LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON (s.`id_shop` = f.`id_shop`)
                WHERE f.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                  AND ' . AmzproShop::sqlWhere('f') . '
                ORDER BY f.`seller_sku` ASC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Resolve a SKU to a product of the shop (same logic as OrderImporter).
     */
    private function resolveProduct($sku, $idShop)
    {
        $res = array('id_product' => 0, 'id_product_attribute' => 0);
        $sku = trim((string) $sku);
        if ($sku === '') {
            return $res;
        }
        $ref = pSQL($sku);
        $idShop = (int) $idShop;

        $row = Db::getInstance()->getRow(
            'SELECT pa.`id_product`, pa.`id_product_attribute`
             FROM `' . _DB_PREFIX_ . 'product_attribute` pa
             INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas
                 ON (pas.`id_product_attribute` = pa.`id_product_attribute` AND pas.`id_shop` = ' . $idShop . ')
             WHERE pa.`reference` = \'' . $ref . '\''
        );
        if ($row && (int) $row['id_product']) {
            $res['id_product'] = (int) $row['id_product'];
            $res['id_product_attribute'] = (int) $row['id_product_attribute'];
            return $res;
        }

        $idProduct = (int) Db::getInstance()->getValue(
            'SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                 ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ')
             WHERE p.`reference` = \'' . $ref . '\''
        );
        if ($idProduct) {
            $res['id_product'] = $idProduct;
        }

        return $res;
    }
}
