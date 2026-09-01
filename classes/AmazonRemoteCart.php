<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Remote Cart — stock reservation for Amazon orders that are not paid yet.
 *
 * The overselling window in multichannel selling is the gap between a buyer
 * checking out on Amazon and the order becoming payable. Amazon exposes that
 * moment as an order in the "Pending" state (buyer details suppressed), which
 * is the earliest signal available — there is no cart webhook.
 *
 * On seeing one, the module takes the items out of PrestaShop stock so no
 * other channel can sell them. Then either:
 *   - the order confirms  -> the reservation is handed over to the real order
 *     (stock is given back first, because creating the PrestaShop order
 *     decrements it again through the normal flow), or
 *   - it never confirms   -> the reservation expires after a grace period and
 *     the stock is returned.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonRemoteCart
{
    const STATUS_RESERVED = 'reserved';
    const STATUS_CONVERTED = 'converted';
    const STATUS_RELEASED = 'released';

    public static function isEnabled()
    {
        return (bool) Configuration::get('AMZPRO_REMOTE_CART');
    }

    /** Grace period, in hours, before an unconfirmed reservation is returned. */
    public static function ttlHours()
    {
        $ttl = (int) Configuration::get('AMZPRO_REMOTE_CART_TTL');

        return ($ttl > 0) ? $ttl : 4;
    }

    public static function ensureTable()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` (
                `id_amazonmarketplacepro_reservation` INT(11) NOT NULL AUTO_INCREMENT,
                `amazon_order_id` VARCHAR(64) NOT NULL,
                `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
                `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
                `id_product` INT(11) NOT NULL DEFAULT 0,
                `id_product_attribute` INT(11) NOT NULL DEFAULT 0,
                `quantity` INT(11) NOT NULL DEFAULT 0,
                `id_shop` INT(11) NOT NULL DEFAULT 1,
                `status` VARCHAR(16) NOT NULL DEFAULT \'reserved\',
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_reservation`),
                UNIQUE KEY `order_item` (`amazon_order_id`, `order_item_id`),
                KEY `status` (`status`),
                KEY `id_product` (`id_product`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );
    }

    /**
     * Reserve the matched items of a pending Amazon order.
     *
     * Idempotent: an order already holding reservations is left alone, so a
     * re-import never double-books the same stock.
     *
     * @param string $amazonOrderId
     * @param array  $items Resolved item rows (id_product, quantity, ...)
     * @param int    $idShop
     * @return int Number of items reserved
     */
    public static function reserve($amazonOrderId, $items, $idShop = 0)
    {
        self::ensureTable();
        if (!self::isEnabled()) {
            return 0;
        }
        $idShop = (int) $idShop ? (int) $idShop : (int) Context::getContext()->shop->id;
        $now = date('Y-m-d H:i:s');
        $reserved = 0;

        foreach ($items as $item) {
            $idProduct = isset($item['id_product']) ? (int) $item['id_product'] : 0;
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;
            if ($idProduct <= 0 || $quantity <= 0) {
                continue; // unmatched lines own no PrestaShop stock
            }
            $idPa = isset($item['id_product_attribute']) ? (int) $item['id_product_attribute'] : 0;
            $orderItemId = isset($item['order_item_id']) ? (string) $item['order_item_id'] : '';
            if ($orderItemId === '') {
                $orderItemId = $item['seller_sku'] . '-' . $idProduct;
            }

            // UNIQUE(amazon_order_id, order_item_id) makes this a no-op on re-import.
            $inserted = Db::getInstance()->execute(
                'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
                    (`amazon_order_id`, `order_item_id`, `seller_sku`, `id_product`,
                     `id_product_attribute`, `quantity`, `id_shop`, `status`, `date_add`, `date_upd`)
                 VALUES (\'' . pSQL($amazonOrderId) . '\', \'' . pSQL($orderItemId) . '\',
                     \'' . pSQL(isset($item['seller_sku']) ? $item['seller_sku'] : '') . '\',
                     ' . $idProduct . ', ' . $idPa . ', ' . $quantity . ', ' . $idShop . ',
                     \'' . self::STATUS_RESERVED . '\', \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')'
            );
            if ($inserted && Db::getInstance()->Affected_Rows() > 0) {
                self::moveStock($idProduct, $idPa, -$quantity, $idShop);
                $reserved++;
            }
        }

        return $reserved;
    }

    /**
     * Hand the reservations of an order back before the PrestaShop order is
     * created: order creation performs its own stock decrement, so leaving the
     * reservation in place would take the quantity twice.
     *
     * @return int Items handed over
     */
    public static function convert($amazonOrderId)
    {
        self::ensureTable();

        $rows = self::rowsFor($amazonOrderId, self::STATUS_RESERVED);
        foreach ($rows as $r) {
            self::moveStock(
                (int) $r['id_product'], (int) $r['id_product_attribute'],
                (int) $r['quantity'], (int) $r['id_shop']
            );
        }
        self::setStatus($amazonOrderId, self::STATUS_CONVERTED);

        return count($rows);
    }

    /**
     * Return the stock of an order that will never be paid (cancelled on
     * Amazon, or expired).
     *
     * @return int Items released
     */
    public static function release($amazonOrderId)
    {
        self::ensureTable();

        $rows = self::rowsFor($amazonOrderId, self::STATUS_RESERVED);
        foreach ($rows as $r) {
            self::moveStock(
                (int) $r['id_product'], (int) $r['id_product_attribute'],
                (int) $r['quantity'], (int) $r['id_shop']
            );
        }
        self::setStatus($amazonOrderId, self::STATUS_RELEASED);

        return count($rows);
    }

    /**
     * Release every reservation older than the grace period whose order never
     * left the Pending state.
     *
     * @return array array('orders' => int, 'items' => int)
     */
    public static function releaseExpired()
    {
        self::ensureTable();
        $summary = array('orders' => 0, 'items' => 0);
        if (!self::isEnabled()) {
            return $summary;
        }

        // The cutoff is computed in PHP, not with MySQL's NOW(): date_add was
        // written with PHP's clock, and the two can sit in different
        // timezones — comparing across them would expire holds immediately.
        $cutoff = date('Y-m-d H:i:s', time() - self::ttlHours() * 3600);

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT r.`amazon_order_id`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` r
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`amazon_order_id` = r.`amazon_order_id`)
             WHERE r.`status` = \'' . self::STATUS_RESERVED . '\'
               AND r.`date_add` < \'' . pSQL($cutoff) . '\'
               AND (o.`order_status` IS NULL OR o.`order_status` = \'Pending\'
                    OR o.`order_status` = \'Canceled\')'
        );
        if (!is_array($rows)) {
            return $summary;
        }

        foreach ($rows as $r) {
            $items = self::release($r['amazon_order_id']);
            if ($items > 0) {
                $summary['orders']++;
                $summary['items'] += $items;
            }
        }

        return $summary;
    }

    /**
     * Reservations whose Amazon order has moved on from Pending get handed
     * over automatically, so a confirmed order never keeps a stale hold.
     *
     * @return int Orders converted
     */
    public static function convertConfirmed()
    {
        self::ensureTable();
        if (!self::isEnabled()) {
            return 0;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT r.`amazon_order_id`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` r
             INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`amazon_order_id` = r.`amazon_order_id`)
             WHERE r.`status` = \'' . self::STATUS_RESERVED . '\'
               AND o.`order_status` NOT IN (\'Pending\', \'Canceled\')'
        );
        $count = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                self::convert($r['amazon_order_id']);
                $count++;
            }
        }

        return $count;
    }

    /** Active reservations, newest first, for the admin list. */
    public static function listActive($limit = 200)
    {
        self::ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT r.*, pl.`name` AS product_name, o.`order_status`, o.`purchase_date`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` r
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = r.`id_product`
                     AND pl.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT') . ')
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`amazon_order_id` = r.`amazon_order_id`)
             WHERE r.`status` = \'' . self::STATUS_RESERVED . '\'
             GROUP BY r.`id_amazonmarketplacepro_reservation`
             ORDER BY r.`date_add` DESC
             LIMIT ' . (int) $limit
        );

        return is_array($rows) ? $rows : array();
    }

    /** Units currently held per product, for display next to stock figures. */
    public static function reservedQuantity($idProduct, $idProductAttribute = 0)
    {
        self::ensureTable();

        return (int) Db::getInstance()->getValue(
            'SELECT SUM(`quantity`) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
             WHERE `status` = \'' . self::STATUS_RESERVED . '\'
               AND `id_product` = ' . (int) $idProduct . '
               AND `id_product_attribute` = ' . (int) $idProductAttribute
        );
    }

    /* ─────────────────── internals ─────────────────── */

    private static function rowsFor($amazonOrderId, $status)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
               AND `status` = \'' . pSQL($status) . '\''
        );

        return is_array($rows) ? $rows : array();
    }

    private static function setStatus($amazonOrderId, $status)
    {
        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
             SET `status` = \'' . pSQL($status) . '\', `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
               AND `status` = \'' . self::STATUS_RESERVED . '\''
        );
    }

    /** Apply a signed delta to PrestaShop stock. */
    private static function moveStock($idProduct, $idProductAttribute, $delta, $idShop)
    {
        if (!$idProduct || !$delta) {
            return;
        }
        StockAvailable::updateQuantity($idProduct, $idProductAttribute, (int) $delta, (int) $idShop);
    }
}
