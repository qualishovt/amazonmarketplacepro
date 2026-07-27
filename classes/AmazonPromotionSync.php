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
 * Amazon Promotion/Coupon Sync.
 *
 * Handles bidirectional promotion synchronization:
 * - Import: Amazon order-level promotions → PS cart rule creation
 * - Export: PS cart rules → Amazon promotion data
 * - Track promotion discount amounts from order items
 *
 * Note: Amazon's promotion creation is limited — most sellers create promotions
 * in Seller Central. This class primarily imports promotion data from orders
 * and creates matching PS cart rules for reporting/tracking.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonPromotionSync
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
     * Import promotions from Amazon orders into the promotions table.
     *
     * Scans order items for PromotionIds and PromotionDiscount amounts,
     * then creates or updates promotion records.
     *
     * @return array Summary
     */
    public function importPromotionsFromOrders()
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'orders_scanned' => 0,
            'promotions_found' => 0,
            'promotions_new' => 0,
            'promotions_updated' => 0,
        );

        // Get recent order items with promotion discounts
        $items = Db::getInstance()->executeS(
            'SELECT oi.`amazon_order_id`, oi.`order_item_id`, oi.`seller_sku`,
                    oi.`asin`, oi.`title`, oi.`promotion_discount`, oi.`currency`,
                    o.`marketplace_id`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` oi
             INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`id_amazonmarketplacepro_order` = oi.`id_amazonmarketplacepro_order`)
             WHERE oi.`promotion_discount` > 0
             ORDER BY o.`date_add` DESC
             LIMIT 200'
        );

        if (!is_array($items) || empty($items)) {
            $this->notices[] = 'No orders with promotion discounts found.';
            return $summary;
        }

        $seenOrders = array();
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $amazonOrderId = $item['amazon_order_id'];
            if (!isset($seenOrders[$amazonOrderId])) {
                $seenOrders[$amazonOrderId] = true;
                $summary['orders_scanned']++;
            }

            $summary['promotions_found']++;

            // Fetch detailed promotion info from order items API
            $promotionIds = $this->fetchPromotionIds($amazonOrderId, $item['order_item_id']);

            foreach ($promotionIds as $promoId) {
                $exists = (bool) Db::getInstance()->getValue(
                    'SELECT `id_amazonmarketplacepro_promotion`
                     FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                     WHERE `amazon_promotion_id` = \'' . pSQL($promoId) . '\'
                       AND `seller_sku` = \'' . pSQL($item['seller_sku']) . '\'
                     LIMIT 1'
                );

                if ($exists) {
                    // Update discount amount (accumulate)
                    Db::getInstance()->execute(
                        'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion` SET
                            `discount_value` = `discount_value` + ' . (float) $item['promotion_discount'] . ',
                            `date_upd` = \'' . pSQL($now) . '\'
                         WHERE `amazon_promotion_id` = \'' . pSQL($promoId) . '\'
                           AND `seller_sku` = \'' . pSQL($item['seller_sku']) . '\''
                    );
                    $summary['promotions_updated']++;
                } else {
                    Db::getInstance()->execute(
                        'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                         (`amazon_promotion_id`, `promotion_type`, `description`,
                          `discount_type`, `discount_value`, `status`,
                          `marketplace_id`, `seller_sku`, `asin`,
                          `sync_direction`, `date_add`, `date_upd`)
                         VALUES (
                            \'' . pSQL($promoId) . '\',
                            \'OrderDiscount\',
                            \'' . pSQL('Promotion on: ' . Tools::substr($item['title'], 0, 200)) . '\',
                            \'fixed\',
                            ' . (float) $item['promotion_discount'] . ',
                            \'active\',
                            \'' . pSQL($item['marketplace_id']) . '\',
                            \'' . pSQL($item['seller_sku']) . '\',
                            \'' . pSQL($item['asin']) . '\',
                            \'amazon_to_ps\',
                            \'' . pSQL($now) . '\',
                            \'' . pSQL($now) . '\'
                         )'
                    );
                    $summary['promotions_new']++;
                }
            }

            // If no specific promotion IDs, create a generic entry
            if (empty($promotionIds)) {
                $genericId = 'ORDER_DISCOUNT_' . $item['amazon_order_id'] . '_' . $item['order_item_id'];
                $exists = (bool) Db::getInstance()->getValue(
                    'SELECT `id_amazonmarketplacepro_promotion`
                     FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                     WHERE `amazon_promotion_id` = \'' . pSQL($genericId) . '\'
                     LIMIT 1'
                );

                if (!$exists) {
                    Db::getInstance()->execute(
                        'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                         (`amazon_promotion_id`, `promotion_type`, `description`,
                          `discount_type`, `discount_value`, `status`,
                          `marketplace_id`, `seller_sku`, `asin`,
                          `sync_direction`, `date_add`, `date_upd`)
                         VALUES (
                            \'' . pSQL($genericId) . '\',
                            \'OrderDiscount\',
                            \'' . pSQL('Order discount on: ' . Tools::substr($item['title'], 0, 200)) . '\',
                            \'fixed\',
                            ' . (float) $item['promotion_discount'] . ',
                            \'active\',
                            \'' . pSQL($item['marketplace_id']) . '\',
                            \'' . pSQL($item['seller_sku']) . '\',
                            \'' . pSQL($item['asin']) . '\',
                            \'amazon_to_ps\',
                            \'' . pSQL($now) . '\',
                            \'' . pSQL($now) . '\'
                         )'
                    );
                    $summary['promotions_new']++;
                }
            }
        }

        return $summary;
    }

    /**
     * Create PS cart rules from imported Amazon promotions.
     *
     * Creates CartRule objects in PrestaShop that mirror Amazon promotions,
     * useful for reporting and discount tracking.
     *
     * @return array Summary
     */
    public function createPsCartRules()
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'total' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
        );

        // Get promotions without PS cart rules
        $promos = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
             WHERE `id_cart_rule` = 0
               AND `discount_value` > 0
               AND `sync_direction` = \'amazon_to_ps\'
             ORDER BY `date_add` DESC
             LIMIT 50'
        );

        if (!is_array($promos)) {
            return $summary;
        }

        $summary['total'] = count($promos);
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $now = date('Y-m-d H:i:s');

        foreach ($promos as $promo) {
            $idPromo = (int) $promo['id_amazonmarketplacepro_promotion'];

            // Create a cart rule
            $cartRule = new CartRule();
            $cartRule->name = array($idLang => 'Amazon: ' . Tools::substr($promo['description'], 0, 200));
            $cartRule->code = 'AMZPROMO_' . $idPromo;
            $cartRule->description = 'Imported from Amazon promotion: ' . $promo['amazon_promotion_id'];
            $cartRule->quantity = 0;
            $cartRule->quantity_per_user = 0;
            $cartRule->active = 0; // Inactive by default — informational only
            $cartRule->date_from = $promo['date_add'];
            $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));

            if ($promo['discount_type'] === 'percentage') {
                $cartRule->reduction_percent = (float) $promo['discount_value'];
                $cartRule->reduction_amount = 0;
            } else {
                $cartRule->reduction_percent = 0;
                $cartRule->reduction_amount = (float) $promo['discount_value'];
                $cartRule->reduction_tax = 1;

                // Resolve currency
                $idCurrency = (int) Currency::getIdByIsoCode('EUR');
                if (!$idCurrency) {
                    $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
                }
                $cartRule->reduction_currency = $idCurrency;
            }

            if ($cartRule->add()) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion` SET
                        `id_cart_rule` = ' . (int) $cartRule->id . ',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `id_amazonmarketplacepro_promotion` = ' . $idPromo
                );
                $summary['created']++;
            } else {
                $summary['errors']++;
            }
        }

        return $summary;
    }

    /**
     * Export PS cart rules to Amazon (as promotion data).
     *
     * Creates promotion records from active PS cart rules that haven't been
     * synced yet. Note: actual Amazon Promotions API is limited for 3P sellers;
     * this primarily tracks the intent for when Deals/Coupons API is available.
     *
     * @return array Summary
     */
    public function exportPsCartRules()
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'scanned' => 0,
            'exported' => 0,
            'already' => 0,
        );

        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Get active PS cart rules not yet synced
        $cartRules = Db::getInstance()->executeS(
            'SELECT cr.`id_cart_rule`, cr.`code`, cr.`description`,
                    cr.`reduction_percent`, cr.`reduction_amount`,
                    cr.`date_from`, cr.`date_to`,
                    crl.`name`
             FROM `' . _DB_PREFIX_ . 'cart_rule` cr
             LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_lang` crl
                 ON (crl.`id_cart_rule` = cr.`id_cart_rule` AND crl.`id_lang` = ' . $idLang . ')
             WHERE cr.`active` = 1
               AND cr.`date_to` >= NOW()
               AND cr.`id_cart_rule` NOT IN (
                   SELECT `id_cart_rule` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                   WHERE `id_cart_rule` > 0
               )
             ORDER BY cr.`id_cart_rule` DESC
             LIMIT 50'
        );

        if (!is_array($cartRules)) {
            return $summary;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($cartRules as $cr) {
            $summary['scanned']++;

            $discountType = 'fixed';
            $discountValue = 0;

            if ((float) $cr['reduction_percent'] > 0) {
                $discountType = 'percentage';
                $discountValue = (float) $cr['reduction_percent'];
            } elseif ((float) $cr['reduction_amount'] > 0) {
                $discountType = 'fixed';
                $discountValue = (float) $cr['reduction_amount'];
            } else {
                continue; // No discount value
            }

            $promotionId = 'PS_CARTRULE_' . $cr['id_cart_rule'];

            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                 (`amazon_promotion_id`, `promotion_type`, `description`,
                  `discount_type`, `discount_value`, `start_date`, `end_date`,
                  `status`, `marketplace_id`, `id_cart_rule`,
                  `sync_direction`, `date_add`, `date_upd`)
                 VALUES (
                    \'' . pSQL($promotionId) . '\',
                    \'CartRule\',
                    \'' . pSQL($cr['name'] ? $cr['name'] : $cr['description']) . '\',
                    \'' . pSQL($discountType) . '\',
                    ' . $discountValue . ',
                    ' . ($cr['date_from'] ? '\'' . pSQL($cr['date_from']) . '\'' : 'NULL') . ',
                    ' . ($cr['date_to'] ? '\'' . pSQL($cr['date_to']) . '\'' : 'NULL') . ',
                    \'pending_export\',
                    \'' . pSQL($this->marketplaceId) . '\',
                    ' . (int) $cr['id_cart_rule'] . ',
                    \'ps_to_amazon\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                 )'
            );

            $summary['exported']++;
        }

        if ($summary['exported'] > 0) {
            $this->notices[] = $summary['exported'] . ' PS cart rule(s) exported to promotion tracking. '
                . 'Note: Amazon Promotions API for 3P sellers is limited. '
                . 'Manage Amazon-side promotions in Seller Central.';
        }

        return $summary;
    }

    /**
     * Fetch promotion IDs from order items (best-effort).
     *
     * @param string $amazonOrderId
     * @param string $orderItemId
     * @return array List of promotion ID strings
     */
    private function fetchPromotionIds($amazonOrderId, $orderItemId)
    {
        $ids = array();

        $resp = $this->client->request(
            'GET',
            '/orders/v0/orders/' . rawurlencode($amazonOrderId) . '/orderItems',
            array()
        );

        if ($resp === false || $resp['status'] >= 400) {
            return $ids;
        }

        $items = array();
        if (is_array($resp['body']) && isset($resp['body']['payload']['OrderItems'])) {
            $items = $resp['body']['payload']['OrderItems'];
        }

        foreach ($items as $item) {
            $itemId = isset($item['OrderItemId']) ? $item['OrderItemId'] : '';
            if ($itemId !== $orderItemId) {
                continue;
            }

            if (isset($item['PromotionIds']) && is_array($item['PromotionIds'])) {
                $ids = $item['PromotionIds'];
            }
            break;
        }

        return $ids;
    }

    /**
     * List promotions for admin display.
     *
     * @param int $limit
     * @return array
     */
    public function listPromotions($limit = 100)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                ORDER BY `date_add` DESC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Get promotion stats summary.
     *
     * @return array
     */
    public function getPromotionStats()
    {
        $stats = array(
            'total' => 0,
            'from_amazon' => 0,
            'from_ps' => 0,
            'total_discount' => 0,
            'with_cart_rule' => 0,
        );

        $row = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN `sync_direction` = \'amazon_to_ps\' THEN 1 ELSE 0 END) AS from_amazon,
                    SUM(CASE WHEN `sync_direction` = \'ps_to_amazon\' THEN 1 ELSE 0 END) AS from_ps,
                    SUM(`discount_value`) AS total_discount,
                    SUM(CASE WHEN `id_cart_rule` > 0 THEN 1 ELSE 0 END) AS with_cart_rule
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`'
        );

        if ($row) {
            $stats['total'] = (int) $row['total'];
            $stats['from_amazon'] = (int) $row['from_amazon'];
            $stats['from_ps'] = (int) $row['from_ps'];
            $stats['total_discount'] = (float) $row['total_discount'];
            $stats['with_cart_rule'] = (int) $row['with_cart_rule'];
        }

        return $stats;
    }
}
