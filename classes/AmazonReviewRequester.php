<?php
/**
 * Review requests via the SP-API Solicitations API (v1).
 *
 * Sends Amazon's standard "Request a Review" solicitation for an order.
 * The message is fully templated by Amazon (no custom text) and Amazon
 * enforces the rules: one solicitation per order, only within roughly
 * 5-30 days after delivery. We ask Amazon per order whether the
 * solicitation is currently available before sending.
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

class AmazonReviewRequester
{
    const ACTION_NAME = 'productReviewAndSellerFeedback';

    /** review_requested column states */
    const STATE_PENDING = 0;
    const STATE_SENT = 1;
    const STATE_INELIGIBLE = 2; // window closed or Amazon refused; never retried

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError = null;
    private $useMock = false;

    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
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
     * Make sure the review_requested column exists (older installs).
     */
    public function ensureSchema()
    {
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` LIKE \'review_requested\''
        );
        if (empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 ADD COLUMN `review_requested` TINYINT(1) NOT NULL DEFAULT 0'
            );
        }
    }

    /**
     * Is the review solicitation currently available for this order?
     *
     * @param string $amazonOrderId
     * @return bool|null true/false, or null on API error (see getLastError())
     */
    public function isReviewRequestAvailable($amazonOrderId)
    {
        $this->lastError = null;

        if ($this->useMock) {
            return true;
        }

        $resp = $this->client->request(
            'GET',
            '/solicitations/v1/orders/' . rawurlencode($amazonOrderId),
            array('marketplaceIds' => $this->marketplaceId)
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return null;
        }
        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'getSolicitationActions HTTP ' . $resp['status'] . ': ' . $body;
            return null;
        }

        if (isset($resp['body']['_links']['actions']) && is_array($resp['body']['_links']['actions'])) {
            foreach ($resp['body']['_links']['actions'] as $a) {
                if (isset($a['name']) && $a['name'] === self::ACTION_NAME) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Send the review request for one order (checks availability first).
     *
     * @param string $amazonOrderId
     * @return bool
     */
    public function requestReview($amazonOrderId)
    {
        $this->lastError = null;

        $amazonOrderId = trim((string) $amazonOrderId);
        if ($amazonOrderId === '') {
            $this->lastError = 'Order id is required.';
            return false;
        }

        $available = $this->isReviewRequestAvailable($amazonOrderId);
        if ($available === null) {
            return false; // lastError already set
        }
        if ($available === false) {
            $this->lastError = 'Amazon does not allow a review request for this order '
                . '(already sent, outside the 5-30 day window, or buyer opted out).';
            return false;
        }

        if ($this->useMock) {
            $this->markOrder($amazonOrderId, self::STATE_SENT);
            $this->log('info', 'MOCK review request for ' . $amazonOrderId);
            return true;
        }

        $resp = $this->client->request(
            'POST',
            '/solicitations/v1/orders/' . rawurlencode($amazonOrderId)
                . '/solicitations/' . self::ACTION_NAME,
            array('marketplaceIds' => $this->marketplaceId),
            new stdClass()
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            $this->log('error', 'review request for ' . $amazonOrderId . ' failed: ' . $this->lastError);
            return false;
        }
        if ($resp['status'] >= 400) {
            $detail = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'createProductReviewAndSellerFeedbackSolicitation HTTP '
                . $resp['status'] . ': ' . $detail;
            $this->log('error', 'review request for ' . $amazonOrderId . ' failed: ' . $this->lastError);
            return false;
        }

        $this->markOrder($amazonOrderId, self::STATE_SENT);
        $this->log('info', 'review request sent for ' . $amazonOrderId);
        return true;
    }

    /**
     * Cron: request reviews for all staged orders in the eligible window
     * that have not been solicited yet.
     *
     * @param int $limit Max orders to process this run (API is rate-limited)
     * @return array Summary counts
     */
    public function requestAllEligible($limit = 25)
    {
        $this->ensureSchema();

        $summary = array('checked' => 0, 'sent' => 0, 'unavailable' => 0, 'errors' => 0);

        // Purchase date is a proxy for the delivery-based window: skip orders
        // younger than 5 days; orders older than 35 days are marked ineligible
        // for good so we stop spending API calls on them.
        $rows = Db::getInstance()->executeS(
            'SELECT `amazon_order_id`, `purchase_date`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `review_requested` = ' . (int) self::STATE_PENDING . '
               AND `order_status` NOT IN (\'Canceled\', \'Cancelled\')
               AND `purchase_date` IS NOT NULL
               AND `purchase_date` < \'' . pSQL(date('Y-m-d H:i:s', time() - 5 * 86400)) . '\'
             ORDER BY `purchase_date` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            return $summary;
        }

        foreach ($rows as $row) {
            $orderId = $row['amazon_order_id'];
            $summary['checked']++;

            $tooOld = (strtotime($row['purchase_date']) < strtotime('-35 days'));

            if ($this->requestReview($orderId)) {
                $summary['sent']++;
                continue;
            }

            if ($this->lastError !== null && strpos($this->lastError, 'does not allow') !== false) {
                $summary['unavailable']++;
                if ($tooOld) {
                    $this->markOrder($orderId, self::STATE_INELIGIBLE);
                }
            } else {
                $summary['errors']++;
            }
        }

        return $summary;
    }

    private function markOrder($amazonOrderId, $state)
    {
        $this->ensureSchema();
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             SET `review_requested` = ' . (int) $state . '
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\''
        );
    }

    private function log($level, $message)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`level`, `source`, `message`, `date_add`)
             VALUES (
                \'' . pSQL($level) . '\',
                \'review_request\',
                \'' . pSQL(Tools::substr($message, 0, 1000)) . '\',
                \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             )'
        );
    }
}
