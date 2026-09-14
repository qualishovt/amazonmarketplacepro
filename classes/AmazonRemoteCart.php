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
 *
 * Multistore: a reservation belongs to the shop of its staged order and moves
 * that shop's stock. Settling works on one shop's reservations at a time.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonRemoteCart
{
    const STATUS_RESERVED = 'reserved';
    const STATUS_CONVERTED = 'converted';
    const STATUS_RELEASED = 'released';

    /** @param int|null $idShop default: the current shop */
    public static function isEnabled($idShop = null)
    {
        return (bool) AmzproShop::get('AMZPRO_REMOTE_CART', $idShop);
    }

    /** Grace period, in hours, before an unconfirmed reservation is returned. */
    public static function ttlHours($idShop = null)
    {
        $ttl = (int) AmzproShop::get('AMZPRO_REMOTE_CART_TTL', $idShop);

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
                `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                `status` VARCHAR(16) NOT NULL DEFAULT \'reserved\',
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_reservation`),
                UNIQUE KEY `order_item` (`amazon_order_id`, `order_item_id`),
                KEY `status` (`status`),
                KEY `id_product` (`id_product`),
                KEY `shop_status` (`id_shop`, `status`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );
        AmzproShop::ensureTableShop('amazonmarketplacepro_reservation');
    }

    /**
     * Reserve the matched items of a pending Amazon order.
     *
     * Idempotent: an order already holding reservations is left alone, so a
     * re-import never double-books the same stock.
     *
     * @param string $amazonOrderId
     * @param array $items Resolved item rows (id_product, quantity, ...)
     * @param int $idShop The staged order's shop (0 = the shop the request acts for)
     *
     * @return int Number of items reserved
     */
    public static function reserve($amazonOrderId, $items, $idShop = 0)
    {
        self::ensureTable();
        $idShop = (int) $idShop ? (int) $idShop : AmzproShop::actingId();
        if (!self::isEnabled($idShop)) {
            return 0;
        }
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
                ++$reserved;
            }
        }

        return $reserved;
    }

    /**
     * Hand the reservations of an order back before the PrestaShop order is
     * created: order creation performs its own stock decrement, so leaving the
     * reservation in place would take the quantity twice.
     *
     * @param string $amazonOrderId
     * @param int|null $idShop The order's shop (default: the shop the request acts for)
     *
     * @return int Items handed over
     */
    public static function convert($amazonOrderId, $idShop = null)
    {
        self::ensureTable();
        $idShop = self::shopFor($idShop);

        $rows = self::rowsFor($amazonOrderId, self::STATUS_RESERVED, $idShop);
        foreach ($rows as $r) {
            self::moveStock(
                (int) $r['id_product'], (int) $r['id_product_attribute'],
                (int) $r['quantity'], (int) $r['id_shop']
            );
        }
        self::setStatus($amazonOrderId, self::STATUS_CONVERTED, $idShop);

        return count($rows);
    }

    /**
     * Return the stock of an order that will never be paid (cancelled on
     * Amazon, or expired).
     *
     * @param string $amazonOrderId
     * @param int|null $idShop The order's shop (default: the shop the request acts for)
     *
     * @return int Items released
     */
    public static function release($amazonOrderId, $idShop = null)
    {
        self::ensureTable();
        $idShop = self::shopFor($idShop);

        $rows = self::rowsFor($amazonOrderId, self::STATUS_RESERVED, $idShop);
        foreach ($rows as $r) {
            self::moveStock(
                (int) $r['id_product'], (int) $r['id_product_attribute'],
                (int) $r['quantity'], (int) $r['id_shop']
            );
        }
        self::setStatus($amazonOrderId, self::STATUS_RELEASED, $idShop);

        return count($rows);
    }

    /**
     * Release every reservation of the shop older than the grace period whose
     * order never left the Pending state.
     *
     * @param int|null $idShop default: the shop the request acts for
     *
     * @return array array('orders' => int, 'items' => int)
     */
    public static function releaseExpired($idShop = null)
    {
        self::ensureTable();
        $idShop = self::shopFor($idShop);
        $summary = ['orders' => 0, 'items' => 0];
        if (!self::isEnabled($idShop)) {
            return $summary;
        }

        // The cutoff is computed in PHP, not with MySQL's NOW(): date_add was
        // written with PHP's clock, and the two can sit in different
        // timezones — comparing across them would expire holds immediately.
        $cutoff = date('Y-m-d H:i:s', time() - self::ttlHours($idShop) * 3600);

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT r.`amazon_order_id`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` r
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`amazon_order_id` = r.`amazon_order_id` AND o.`id_shop` = r.`id_shop`)
             WHERE r.`status` = \'' . self::STATUS_RESERVED . '\'
               AND r.`id_shop` = ' . $idShop . '
               AND r.`date_add` < \'' . pSQL($cutoff) . '\'
               AND (o.`order_status` IS NULL OR o.`order_status` = \'Pending\'
                    OR o.`order_status` = \'Canceled\')'
        );
        if (!is_array($rows)) {
            return $summary;
        }

        foreach ($rows as $r) {
            $items = self::release($r['amazon_order_id'], $idShop);
            if ($items > 0) {
                ++$summary['orders'];
                $summary['items'] += $items;
            }
        }

        return $summary;
    }

    /**
     * Reservations whose Amazon order has moved on from Pending get handed
     * over automatically, so a confirmed order never keeps a stale hold.
     *
     * @param int|null $idShop default: the shop the request acts for
     *
     * @return int Orders converted
     */
    public static function convertConfirmed($idShop = null)
    {
        self::ensureTable();
        $idShop = self::shopFor($idShop);
        if (!self::isEnabled($idShop)) {
            return 0;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT r.`amazon_order_id`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` r
             INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`amazon_order_id` = r.`amazon_order_id` AND o.`id_shop` = r.`id_shop`)
             WHERE r.`status` = \'' . self::STATUS_RESERVED . '\'
               AND r.`id_shop` = ' . $idShop . '
               AND o.`order_status` NOT IN (\'Pending\', \'Canceled\')'
        );
        $count = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                self::convert($r['amazon_order_id'], $idShop);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Active reservations, newest first, for the admin list: the current
     * shop's, or every shop's in "All shops" (each row carries id_shop).
     */
    public static function listActive($limit = 200)
    {
        self::ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT r.*, pl.`name` AS product_name, o.`order_status`, o.`purchase_date`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` r
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = r.`id_product`
                     AND pl.`id_shop` = r.`id_shop`
                     AND pl.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT') . ')
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
                 ON (o.`amazon_order_id` = r.`amazon_order_id` AND o.`id_shop` = r.`id_shop`)
             WHERE r.`status` = \'' . self::STATUS_RESERVED . '\'
               AND ' . AmzproShop::sqlWhere('r') . '
             GROUP BY r.`id_amazonmarketplacepro_reservation`
             ORDER BY r.`date_add` DESC
             LIMIT ' . (int) $limit
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * Units currently held per product, for display next to stock figures.
     *
     * @param int|null $idShop default: the current shop (every shop in "All shops")
     */
    public static function reservedQuantity($idProduct, $idProductAttribute = 0, $idShop = null)
    {
        self::ensureTable();

        return (int) Db::getInstance()->getValue(
            'SELECT SUM(`quantity`) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
             WHERE `status` = \'' . self::STATUS_RESERVED . '\'
               AND `id_product` = ' . (int) $idProduct . '
               AND `id_product_attribute` = ' . (int) $idProductAttribute . '
               AND ' . AmzproShop::sqlWhere('', $idShop)
        );
    }

    /* ─────────────────── internals ─────────────────── */

    /** A concrete shop: the one given, else the shop the request acts for. */
    private static function shopFor($idShop)
    {
        return (int) $idShop ? (int) $idShop : AmzproShop::actingId();
    }

    private static function rowsFor($amazonOrderId, $status, $idShop)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
               AND `status` = \'' . pSQL($status) . '\'
               AND `id_shop` = ' . (int) $idShop
        );

        return is_array($rows) ? $rows : [];
    }

    private static function setStatus($amazonOrderId, $status, $idShop)
    {
        return Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`
             SET `status` = \'' . pSQL($status) . '\', `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
               AND `status` = \'' . self::STATUS_RESERVED . '\'
               AND `id_shop` = ' . (int) $idShop
        );
    }

    /** Apply a signed delta to the stock of the reservation's shop. */
    private static function moveStock($idProduct, $idProductAttribute, $delta, $idShop)
    {
        if (!$idProduct || !$delta) {
            return;
        }
        StockAvailable::updateQuantity($idProduct, $idProductAttribute, (int) $delta, (int) $idShop);
    }
}
