<?php
/**
 * Buyer messaging via the SP-API Messaging API (v1).
 *
 * Amazon only allows seller-initiated, order-related messages of specific
 * types (e.g. confirmOrderDetails, warranty). The API is send-only: buyer
 * replies arrive in Seller Central, not through SP-API. For each order we
 * first ask Amazon which message types are currently allowed, then send.
 *
 * PHP 5.6+ compatible (no scalar type hints, no ?? operator, no enums).
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonBuyerMessaging
{
    /** Message types that carry a free-text body. */
    private static $textActions = array(
        'confirmCustomizationDetails',
        'confirmDeliveryDetails',
        'confirmOrderDetails',
        'confirmServiceDetails',
        'digitalAccessKey',
        'unexpectedProblem',
        'warranty',
        'amazonMotors',
        'legalDisclosure',
    );

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError = null;
    private $useMock = false;

    /**
     * @param AmazonSpApiClient $client the current shop's client
     * @param string $marketplaceId default ('' or null): the current shop's marketplace
     */
    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = ((string) $marketplaceId !== '')
            ? $marketplaceId
            : (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
    }

    public function setMock($enabled)
    {
        $this->useMock = (bool) $enabled;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Ask Amazon which message types may currently be sent for an order.
     *
     * @param string $amazonOrderId
     * @return array|false List of action names (possibly empty), or false on error
     */
    public function getAllowedActions($amazonOrderId)
    {
        $this->lastError = null;

        if ($this->isOtherShopsOrder($amazonOrderId)) {
            $this->lastError = AmazonI18n::get()->l('This order belongs to another shop. Select that shop at the top of the page to work on it.', 'amazonbuyermessaging');
            return false;
        }

        if ($this->useMock) {
            return array('confirmOrderDetails', 'warranty', 'unexpectedProblem');
        }

        $resp = $this->client->request(
            'GET',
            '/messaging/v1/orders/' . rawurlencode($amazonOrderId),
            array('marketplaceIds' => $this->marketplaceId)
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return false;
        }
        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'getMessagingActions HTTP ' . $resp['status'] . ': ' . $body;
            return false;
        }

        $actions = array();
        if (isset($resp['body']['_links']['actions']) && is_array($resp['body']['_links']['actions'])) {
            foreach ($resp['body']['_links']['actions'] as $a) {
                if (isset($a['name'])) {
                    $actions[] = $a['name'];
                }
            }
        }

        return $actions;
    }

    /**
     * Send one message to the buyer of an order.
     *
     * @param string $amazonOrderId
     * @param string $actionName One of the names returned by getAllowedActions()
     * @param string $text       Message text (ignored for body-less types)
     * @return bool
     */
    public function sendMessage($amazonOrderId, $actionName, $text)
    {
        $this->lastError = null;

        $actionName = trim((string) $actionName);
        $text = trim((string) $text);

        if ($amazonOrderId === '' || $actionName === '') {
            $this->lastError = AmazonI18n::get()->l('Order id and message type are required.', 'amazonbuyermessaging');
            return false;
        }
        if (in_array($actionName, self::$textActions) && $text === '') {
            $this->lastError = AmazonI18n::get()->l('This message type requires a text body.', 'amazonbuyermessaging');
            return false;
        }
        if ($this->isOtherShopsOrder($amazonOrderId)) {
            $this->lastError = AmazonI18n::get()->l('This order belongs to another shop. Select that shop at the top of the page to work on it.', 'amazonbuyermessaging');
            return false;
        }

        if ($this->useMock) {
            $this->log('info', 'MOCK send ' . $actionName . ' for ' . $amazonOrderId);
            return true;
        }

        // Types like negativeFeedbackRemoval take an empty JSON object body.
        $body = in_array($actionName, self::$textActions)
            ? array('text' => $text)
            : new stdClass();

        $resp = $this->client->request(
            'POST',
            '/messaging/v1/orders/' . rawurlencode($amazonOrderId) . '/messages/' . rawurlencode($actionName),
            array('marketplaceIds' => $this->marketplaceId),
            $body
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            $this->log('error', 'send ' . $actionName . ' for ' . $amazonOrderId . ' failed: ' . $this->lastError);
            return false;
        }

        if ($resp['status'] >= 400) {
            $detail = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'sendMessage HTTP ' . $resp['status'] . ': ' . $detail;
            $this->log('error', 'send ' . $actionName . ' for ' . $amazonOrderId . ' failed: ' . $this->lastError);
            return false;
        }

        $this->log('info', 'sent ' . $actionName . ' to buyer of ' . $amazonOrderId);
        return true;
    }

    /**
     * List the current shop's staged orders eligible for messaging
     * (imported, with an order id). Every shop's in "All shops".
     *
     * @param int $limit
     * @return array
     */
    public function listMessagableOrders($limit = 100)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `amazon_order_id`, `purchase_date`, `order_status`, `fulfillment_channel`, `id_shop`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE ' . AmzproShop::sqlWhere() . '
             ORDER BY `purchase_date` DESC
             LIMIT ' . (int) $limit
        );

        return is_array($rows) ? $rows : array();
    }

    /**
     * True when the order was imported by a shop other than the one this
     * request works for. An order id that was never imported is not refused:
     * Amazon itself checks that it belongs to this shop's seller account.
     *
     * @param string $amazonOrderId
     * @return bool
     */
    private function isOtherShopsOrder($amazonOrderId)
    {
        if ((string) $amazonOrderId === '' || !AmzproShop::isMultistore() || AmzproShop::isAllShops()) {
            return false;
        }
        $idShop = Db::getInstance()->getValue(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\''
        );

        return $idShop !== false && (int) $idShop !== AmzproShop::id();
    }

    private function log($level, $message)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`level`, `source`, `message`, `date_add`, `id_shop`)
             VALUES (
                \'' . pSQL($level) . '\',
                \'buyer_messaging\',
                \'' . pSQL(Tools::substr($message, 0, 1000)) . '\',
                \'' . pSQL(date('Y-m-d H:i:s')) . '\',
                ' . (int) AmzproShop::id() . '
             )'
        );
    }
}
