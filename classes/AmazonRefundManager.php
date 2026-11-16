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
 * Refunds a seller-fulfilled Amazon order from the PrestaShop order page, the
 * way Seller Central's "Refund order" form does: an amount per item, the
 * shipping, an optional extra refund, and a reason. Amazon takes refunds
 * through the order adjustment feed (POST_PAYMENT_ADJUSTMENT_DATA).
 *
 * Amounts are what the buyer paid, VAT included, as on Amazon's EU form.
 * Every refund sent is logged per order, and what was already refunded is
 * subtracted from what can still be refunded.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';
require_once dirname(__FILE__) . '/AmazonFeedManager.php';

class AmazonRefundManager
{
    /**
     * Amazon's adjustment reasons, in Seller Central's order, with the
     * English label the admin shows (translated there).
     */
    public static function reasons()
    {
        // Amazon's AdjustmentReason values (order adjustment feed schema).
        return [
            'CouldNotShip' => 'Could not ship',
            'CustomerReturn' => 'Customer return',
            'GeneralAdjustment' => 'General adjustment',
            'RefusedDelivery' => 'Delivery refused',
            'CustomerCancel' => 'Buyer cancelled',
            'DifferentItem' => 'Different item',
            'CarrierCreditDecision' => 'Carrier credit decision',
            'Undeliverable' => 'Undeliverable to address',
            'CustomerAddressIncorrect' => 'Incorrect delivery address',
            'NoInventory' => 'Out of stock',
            'PriceError' => 'Pricing error',
            'Other' => 'Other',
        ];
    }

    public static function ensureTable()
    {
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_refund` (
            `id_amazonmarketplacepro_refund` INT(11) NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `id_order` INT(11) NOT NULL DEFAULT 0,
            `amazon_order_id` VARCHAR(64) NOT NULL,
            `reason` VARCHAR(64) NOT NULL DEFAULT \'\',
            `amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
            `lines_json` TEXT NULL,
            `feed_id` VARCHAR(64) NOT NULL DEFAULT \'\',
            `id_employee` INT(11) NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_amazonmarketplacepro_refund`),
            KEY `id_order` (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');
    }

    /**
     * What the order page shows and what can still be refunded.
     *
     * @param int $idOrder PrestaShop order
     *
     * @return array|null null when the order did not come from Amazon
     */
    public static function orderData($idOrder)
    {
        self::ensureTable();
        $p = _DB_PREFIX_;
        $staged = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
            WHERE `id_order` = ' . (int) $idOrder);
        if (!$staged) {
            return null;
        }
        $raw = json_decode((string) $staged['raw_json'], true);
        $raw = is_array($raw) ? $raw : [];
        $order = new Order((int) $idOrder);

        // Already refunded, per Amazon order item and for shipping / extra.
        $done = ['items' => [], 'shipping' => 0.0, 'extra' => 0.0, 'total' => 0.0];
        $history = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_refund`
            WHERE `amazon_order_id` = \'' . pSQL($staged['amazon_order_id']) . '\' ORDER BY `date_add` ASC');
        foreach ((array) $history as $h) {
            $lines = json_decode((string) $h['lines_json'], true);
            foreach ((isset($lines['items']) ? $lines['items'] : []) as $code => $amount) {
                $done['items'][$code] = (isset($done['items'][$code]) ? $done['items'][$code] : 0) + (float) $amount;
            }
            $done['shipping'] += isset($lines['shipping']) ? (float) $lines['shipping'] : 0;
            $done['extra'] += isset($lines['extra']) ? (float) $lines['extra'] : 0;
            $done['total'] += (float) $h['amount'];
        }

        $items = [];
        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item`
            WHERE `id_amazonmarketplacepro_order` = ' . (int) $staged['id_amazonmarketplacepro_order'] . '
            ORDER BY `id_amazonmarketplacepro_order_item` ASC');
        foreach ((array) $rows as $it) {
            // What the buyer paid for the line: the PrestaShop line when
            // there is one (VAT included), else Amazon's amounts.
            $paid = 0.0;
            if (Validate::isLoadedObject($order) && (int) $it['id_product'] > 0) {
                $paid = (float) Db::getInstance()->getValue('SELECT SUM(`total_price_tax_incl`) FROM `' . $p . 'order_detail`
                    WHERE `id_order` = ' . (int) $order->id . ' AND `product_id` = ' . (int) $it['id_product'] . '
                      AND `product_attribute_id` = ' . (int) $it['id_product_attribute']);
            }
            if ($paid <= 0) {
                $paid = (float) $it['item_price'] + (float) $it['item_tax'];
            }
            $code = (string) $it['order_item_id'];
            $refunded = isset($done['items'][$code]) ? $done['items'][$code] : 0.0;
            $items[] = [
                'code' => $code,
                'title' => $it['title'],
                'sku' => $it['seller_sku'],
                'quantity' => (int) $it['quantity'],
                'paid' => round($paid, 2),
                'refunded' => round($refunded, 2),
                'left' => round(max(0, $paid - $refunded), 2),
            ];
        }
        $shipPaid = Validate::isLoadedObject($order) ? (float) $order->total_shipping_tax_incl
            : (float) $staged['shipping_total'] + (float) $staged['shipping_tax'];

        $marketplaceId = (string) $staged['marketplace_id'];

        return [
            'amazon_order_id' => $staged['amazon_order_id'],
            'id_shop' => (int) $staged['id_shop'],
            'marketplace_id' => $marketplaceId,
            'sales_channel' => isset($raw['SalesChannel']) ? $raw['SalesChannel'] : '',
            'order_status' => $staged['order_status'],
            'fulfillment_channel' => $staged['fulfillment_channel'],
            'ship_service_level' => isset($raw['ShipServiceLevel']) ? $raw['ShipServiceLevel'] : (string) $staged['ship_service_level'],
            'earliest_ship' => self::localDate(isset($raw['EarliestShipDate']) ? $raw['EarliestShipDate'] : ''),
            'latest_ship' => self::localDate(isset($raw['LatestShipDate']) ? $raw['LatestShipDate'] : ''),
            'latest_delivery' => self::localDate(isset($raw['LatestDeliveryDate']) ? $raw['LatestDeliveryDate'] : ''),
            'is_business' => (int) $staged['is_business'],
            'is_prime' => (int) $staged['is_prime'],
            'currency' => $staged['currency'] !== '' ? $staged['currency']
                : (Validate::isLoadedObject($order) ? Currency::getIsoCodeById((int) $order->id_currency) : 'EUR'),
            'items' => $items,
            'shipping_paid' => round($shipPaid, 2),
            'shipping_left' => round(max(0, $shipPaid - $done['shipping']), 2),
            'refunded_total' => round($done['total'], 2),
            'history' => is_array($history) ? $history : [],
        ];
    }

    /** An Amazon ISO date in the shop's time zone, or '' . */
    private static function localDate($iso)
    {
        $ts = $iso !== '' ? strtotime($iso) : false;

        return $ts ? date('Y-m-d H:i:s', $ts) : '';
    }

    /**
     * Check a refund request against what can still be refunded.
     *
     * @param array $data from orderData()
     * @param array $input items[code] => amount, shipping, extra, reason
     *
     * @return array [error string|null, normalised request]
     */
    public static function validate(array $data, array $input)
    {
        $l = AmazonI18n::get();
        $req = ['items' => [], 'shipping' => 0.0, 'extra' => 0.0, 'reason' => '', 'total' => 0.0];
        $reason = isset($input['reason']) ? (string) $input['reason'] : '';
        if (!isset(self::reasons()[$reason])) {
            return [$l->l('Choose a reason for the refund.', 'amazonrefundmanager'), $req];
        }
        $req['reason'] = $reason;
        $byCode = [];
        foreach ($data['items'] as $it) {
            $byCode[$it['code']] = $it;
        }
        $given = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
        foreach ($given as $code => $amount) {
            $amount = round((float) str_replace(',', '.', (string) $amount), 2);
            if ($amount <= 0) {
                continue;
            }
            if (!isset($byCode[$code])) {
                return [$l->l('This item is not part of the order.', 'amazonrefundmanager'), $req];
            }
            if ($amount > $byCode[$code]['left'] + 0.001) {
                return [sprintf($l->l('At most %1$s can still be refunded for %2$s.', 'amazonrefundmanager'),
                    number_format($byCode[$code]['left'], 2, ',', '.'), $byCode[$code]['title']), $req];
            }
            $req['items'][$code] = $amount;
        }
        $shipping = round((float) str_replace(',', '.', (string) (isset($input['shipping']) ? $input['shipping'] : 0)), 2);
        if ($shipping > $data['shipping_left'] + 0.001) {
            return [sprintf($l->l('At most %s of the shipping can still be refunded.', 'amazonrefundmanager'),
                number_format($data['shipping_left'], 2, ',', '.')), $req];
        }
        $req['shipping'] = max(0, $shipping);
        $req['extra'] = max(0, round((float) str_replace(',', '.', (string) (isset($input['extra']) ? $input['extra'] : 0)), 2));
        if (($req['shipping'] > 0 || $req['extra'] > 0) && !$data['items']) {
            return [$l->l('The order has no Amazon items to attach the refund to.', 'amazonrefundmanager'), $req];
        }
        $req['total'] = round(array_sum($req['items']) + $req['shipping'] + $req['extra'], 2);
        if ($req['total'] <= 0) {
            return [$l->l('Enter an amount to refund.', 'amazonrefundmanager'), $req];
        }

        return [null, $req];
    }

    /**
     * The order adjustment feed for one refund. Shipping and the extra
     * refund ride on the first item, as Amazon allows only item lines.
     *
     * @return string XML
     */
    public static function buildXml($sellerId, $amazonOrderId, array $data, array $req)
    {
        $cur = htmlspecialchars($data['currency']);
        $codes = array_keys($req['items']);
        $first = (string) ($codes ? $codes[0] : $data['items'][0]['code']);
        if (!isset($req['items'][$first])) {
            $req['items'] = [$first => 0.0] + $req['items'];
        }
        $amount = function ($v) {
            return number_format((float) $v, 2, '.', '');
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">' . "\n"
            . '  <Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>' . htmlspecialchars($sellerId)
            . '</MerchantIdentifier></Header>' . "\n"
            . '  <MessageType>OrderAdjustment</MessageType>' . "\n"
            . '  <Message>' . "\n"
            . '    <MessageID>1</MessageID>' . "\n"
            . '    <OrderAdjustment>' . "\n"
            . '      <AmazonOrderID>' . htmlspecialchars($amazonOrderId) . '</AmazonOrderID>' . "\n";
        foreach ($req['items'] as $code => $principal) {
            $xml .= '      <AdjustedItem>' . "\n"
                . '        <AmazonOrderItemCode>' . htmlspecialchars($code) . '</AmazonOrderItemCode>' . "\n"
                . '        <AdjustmentReason>' . htmlspecialchars($req['reason']) . '</AdjustmentReason>' . "\n"
                . '        <ItemPriceAdjustments>' . "\n"
                . '          <Component><Type>Principal</Type><Amount currency="' . $cur . '">' . $amount($principal) . '</Amount></Component>' . "\n";
            if ((string) $code === (string) $first && $req['shipping'] > 0) {
                $xml .= '          <Component><Type>Shipping</Type><Amount currency="' . $cur . '">' . $amount($req['shipping']) . '</Amount></Component>' . "\n";
            }
            if ((string) $code === (string) $first && $req['extra'] > 0) {
                $xml .= '          <Component><Type>Goodwill</Type><Amount currency="' . $cur . '">' . $amount($req['extra']) . '</Amount></Component>' . "\n";
            }
            $xml .= '        </ItemPriceAdjustments>' . "\n"
                . '      </AdjustedItem>' . "\n";
        }

        return $xml . '    </OrderAdjustment>' . "\n" . '  </Message>' . "\n" . '</AmazonEnvelope>';
    }

    /**
     * Send the refund to Amazon and log it.
     *
     * @return array success, feed_id or error
     */
    public static function submit(AmazonSpApiClient $client, $idOrder, array $input, $idEmployee = 0)
    {
        $l = AmazonI18n::get();
        $data = self::orderData($idOrder);
        if ($data === null) {
            return ['success' => false, 'error' => $l->l('This order did not come from Amazon.', 'amazonrefundmanager')];
        }
        if ($data['fulfillment_channel'] === 'AFN') {
            return ['success' => false, 'error' => $l->l('Amazon refunds orders it fulfilled (FBA) itself.', 'amazonrefundmanager')];
        }
        list($error, $req) = self::validate($data, $input);
        if ($error !== null) {
            return ['success' => false, 'error' => $error];
        }
        $sellerId = (string) AmzproShop::get('AMZPRO_SELLER_ID', $data['id_shop']);
        $xml = self::buildXml($sellerId, $data['amazon_order_id'], $data, $req);

        $feeds = new AmazonFeedManager($client, $data['marketplace_id'], $sellerId);
        $feedId = $feeds->submitFeed('POST_PAYMENT_ADJUSTMENT_DATA', 'text/xml; charset=UTF-8', $xml, 1);
        if ($feedId === false) {
            return ['success' => false, 'error' => $feeds->getLastError()];
        }

        Db::getInstance()->insert('amazonmarketplacepro_refund', [
            'id_shop' => (int) $data['id_shop'],
            'id_order' => (int) $idOrder,
            'amazon_order_id' => pSQL($data['amazon_order_id']),
            'reason' => pSQL($req['reason']),
            'amount' => (float) $req['total'],
            'currency' => pSQL($data['currency']),
            'lines_json' => pSQL(json_encode(['items' => $req['items'], 'shipping' => $req['shipping'], 'extra' => $req['extra']])),
            'feed_id' => pSQL($feedId),
            'id_employee' => (int) $idEmployee,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'feed_id' => $feedId, 'amount' => $req['total']];
    }
}
