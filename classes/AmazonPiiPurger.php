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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

/**
 * Removes buyer personal data from finished Amazon orders.
 *
 * WHY. Amazon's Data Protection Policy requires that Personally Identifiable
 * Information obtained through the Selling Partner API is kept no longer than
 * 30 days after order delivery, except where a law obliges the merchant to
 * keep it - tax records above all. Importing an order copies the buyer's name,
 * e-mail and shipping address into this module's staging table; without this
 * class that copy would live there for ever.
 *
 * WHAT IS REMOVED, AND WHAT IS NOT. The staging row keeps everything needed to
 * reconcile the order - Amazon order id, dates, status, totals, tax, fees,
 * marketplace, destination country - and loses everything that identifies the
 * buyer: name, e-mail, street, city, region, postcode, phone, and the raw
 * Amazon payload the order was built from. The raw payload matters most: it is
 * the complete API response, and it holds every field Amazon returned.
 *
 * The destination country is deliberately kept. It is not identifying on its
 * own and it determines the VAT treatment the merchant has to be able to
 * justify afterwards.
 *
 * THE ANCHOR DATE. The policy counts from delivery. Amazon does not give us a
 * delivery date on the order record, so this counts from the purchase date,
 * which is necessarily on or before dispatch and therefore before delivery.
 * The effective window is consequently at least as strict as the policy asks
 * for, never looser. Only orders in a finished state are touched, so nothing
 * is stripped from an order still being fulfilled.
 *
 * SHOPS. The automatic purge covers every shop, whichever shop's cron starts
 * it, and each shop's orders follow that shop's own settings (switch,
 * retention window, PrestaShop-side records). A shop whose cron is never
 * called still has its buyers' data cleared on time. Staged rows of a shop
 * that no longer exists follow the default shop's settings, so they are never
 * left behind.
 *
 * PRESTASHOP'S OWN RECORDS are a separate question and off by default. See
 * purgeShopSide().
 */
class AmazonPiiPurger
{
    /** Amazon's requirement, and the default. */
    public static $DEFAULT_RETENTION_DAYS = 30;
    public static $MIN_RETENTION_DAYS = 1;
    public static $MAX_RETENTION_DAYS = 365;

    /** Rows per shop per run, so a large backlog cannot exhaust a cron request. */
    public static $DEFAULT_BATCH = 500;

    /**
     * Order states in which Amazon is finished with the order.
     *
     * Pending, Unshipped, PartiallyShipped and PendingAvailability are absent
     * on purpose: those orders are still being fulfilled and the merchant
     * still needs the address.
     */
    private static $finishedStatuses = [
        'Shipped',
        'Delivered',
        'Canceled',
        'Cancelled',
        'Unfulfillable',
    ];

    /** Staging columns cleared to an empty string. */
    private static $piiColumns = [
        'buyer_email',
        'buyer_name',
        'ship_address1',
        'ship_address2',
        'ship_city',
        'ship_state',
        'ship_postal_code',
        'ship_phone',
        'ship_name',
    ];

    /** @var string|null */
    private $lastError;

    /** @return string|null */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param int|null $idShop default: the current shop
     *
     * @return bool
     */
    public static function isEnabled($idShop = null)
    {
        $v = AmzproShop::get('AMZPRO_PII_PURGE', $idShop);

        // Absent configuration means an install that predates this feature.
        // Default to on: the compliant behaviour is the safe one to assume.
        return ((string) $v === '') ? true : (bool) $v;
    }

    /**
     * True when at least one shop has the automatic purge on. The cron of any
     * shop runs purge() for all of them, so this is the check a caller makes
     * before calling it.
     *
     * @return bool
     */
    public static function isEnabledInAnyShop()
    {
        foreach (AmzproShop::shopIds() as $idShop) {
            if (self::isEnabled($idShop)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retention window in days, clamped to a sane range.
     *
     * @param int|null $idShop default: the current shop
     *
     * @return int
     */
    public static function retentionDays($idShop = null)
    {
        $days = (int) AmzproShop::get('AMZPRO_PII_RETENTION_DAYS', $idShop);
        if ($days <= 0) {
            $days = self::$DEFAULT_RETENTION_DAYS;
        }
        $days = max(self::$MIN_RETENTION_DAYS, $days);
        if ($days > self::$MAX_RETENTION_DAYS) {
            $days = self::$MAX_RETENTION_DAYS;
        }

        return $days;
    }

    /**
     * The cut-off, computed in PHP rather than with MySQL's NOW().
     *
     * The two clocks are not always the same on shared hosting, and mixing
     * them has bitten this module before. Every timestamp this class reads or
     * writes comes from PHP, so the comparison is at least self-consistent.
     *
     * @param int|null $idShop default: the current shop
     *
     * @return string
     */
    public static function cutoff($idShop = null)
    {
        return date('Y-m-d H:i:s', time() - (self::retentionDays($idShop) * 86400));
    }

    /** Add the audit column on installs that predate this feature. */
    public function ensureSchema()
    {
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` LIKE \'pii_purged_at\''
        );
        if (empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 ADD COLUMN `pii_purged_at` DATETIME NULL DEFAULT NULL,
                 ADD KEY `pii_purged_at` (`pii_purged_at`)'
            );
        }

        // The recipient column is cleared with the rest, so it has to exist
        // even on an install that has never had an order report uploaded.
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` LIKE \'ship_name\''
        );
        if (empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 ADD COLUMN `ship_name` VARCHAR(255) NOT NULL DEFAULT \'\''
            );
        }
    }

    /**
     * The shop that also looks after staged rows whose shop no longer
     * exists: the default shop, or the first shop if that is not listed.
     *
     * @return int
     */
    private static function catchAllShopId()
    {
        $ids = AmzproShop::shopIds();
        $default = AmzproShop::defaultShopId();

        return in_array($default, $ids, true) ? $default : (int) reset($ids);
    }

    /**
     * SQL condition for the staged rows one shop is responsible for.
     *
     * @param int $idShop
     *
     * @return string
     */
    private function shopCondition($idShop)
    {
        $idShop = (int) $idShop;
        $condition = AmzproShop::sqlWhere('', $idShop);
        if ($idShop === self::catchAllShopId()) {
            $condition = '(' . $condition . ' OR `id_shop` NOT IN ('
                . implode(', ', array_map('intval', AmzproShop::shopIds())) . '))';
        }

        return $condition;
    }

    /**
     * SQL fragment matching one shop's rows that are due, without the
     * leading WHERE.
     *
     * @param int $idShop
     *
     * @return string
     */
    private function dueCondition($idShop)
    {
        $quoted = [];
        foreach (self::$finishedStatuses as $s) {
            $quoted[] = '\'' . pSQL($s) . '\'';
        }

        return $this->shopCondition($idShop) . '
                AND `pii_purged_at` IS NULL
                AND `purchase_date` IS NOT NULL
                AND `purchase_date` < \'' . pSQL(self::cutoff($idShop)) . '\'
                AND `order_status` IN (' . implode(', ', $quoted) . ')';
    }

    /**
     * The shops a count covers: the one asked for, or every shop for 0
     * ("All shops" in the back office).
     *
     * @param int|null $idShop default: the current shop
     *
     * @return int[]
     */
    private static function countShops($idShop)
    {
        $idShop = ($idShop === null) ? AmzproShop::id() : (int) $idShop;

        return $idShop ? [$idShop] : AmzproShop::shopIds();
    }

    /**
     * How many orders are waiting to be purged, whether or not the automatic
     * purge is on.
     *
     * @param int|null $idShop default: the current shop; 0 = every shop
     *
     * @return int
     */
    public function dueCount($idShop = null)
    {
        $this->ensureSchema();

        $n = 0;
        foreach (self::countShops($idShop) as $id) {
            // Each shop has its own window, so shops are counted one by one.
            $n += (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 WHERE ' . $this->dueCondition($id)
            );
        }

        return $n;
    }

    /**
     * How many orders have already had their buyer data cleared.
     *
     * @param int|null $idShop default: the current shop; 0 = every shop
     *
     * @return int
     */
    public function purgedCount($idShop = null)
    {
        $this->ensureSchema();

        $n = 0;
        foreach (self::countShops($idShop) as $id) {
            $n += (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 WHERE ' . $this->shopCondition($id) . ' AND `pii_purged_at` IS NOT NULL'
            );
        }

        return $n;
    }

    /**
     * Purge buyer data from finished orders past the retention window.
     *
     * Without $idShop this is the automatic purge: every shop that has it
     * switched on, each with its own settings. With $idShop it is the
     * merchant clearing one shop by hand (0 = every shop), which does not
     * wait for the switch.
     *
     * The totals add up every shop; 'shops' holds each shop's own summary
     * by shop id. retention_days, cutoff and shop_side are the settings of
     * the shop the request acts for.
     *
     * @param int $limit Rows to process per shop this run, 0 for the default
     * @param int|null $idShop see above
     *
     * @return array|false Summary counts, or false when a shop failed
     */
    public function purge($limit = 0, $idShop = null)
    {
        $this->lastError = null;
        $this->ensureSchema();

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = self::$DEFAULT_BATCH;
        }

        $byHand = ($idShop !== null);
        $shops = $byHand ? self::countShops($idShop) : AmzproShop::shopIds();
        $settingsShop = ($byHand && (int) $idShop) ? (int) $idShop : AmzproShop::actingId();

        $summary = [
            'retention_days' => self::retentionDays($settingsShop),
            'cutoff' => self::cutoff($settingsShop),
            'orders' => 0,
            'remaining' => 0,
            'customers' => 0,
            'addresses' => 0,
            'shop_side' => (bool) AmzproShop::get('AMZPRO_PII_PURGE_PS', $settingsShop),
            'shops' => [],
        ];

        $failed = false;
        foreach ($shops as $id) {
            if (!$byHand && !self::isEnabled($id)) {
                $summary['shops'][$id] = ['disabled' => true];
                continue;
            }

            $shopSummary = AmzproShop::runInShop($id, function ($idShop) use ($limit) {
                return $this->purgeShop($idShop, $limit);
            });
            if ($shopSummary === false) {
                $failed = true;
                continue;
            }

            $summary['shops'][$id] = $shopSummary;
            foreach (['orders', 'remaining', 'customers', 'addresses'] as $k) {
                $summary[$k] += $shopSummary[$k];
            }
        }

        return $failed ? false : $summary;
    }

    /**
     * Purge one shop's due orders with that shop's settings. Runs inside
     * AmzproShop::runInShop() for that shop.
     *
     * @param int $idShop
     * @param int $limit
     *
     * @return array|false
     */
    private function purgeShop($idShop, $limit)
    {
        $idShop = (int) $idShop;

        $summary = [
            'disabled' => false,
            'retention_days' => self::retentionDays($idShop),
            'cutoff' => self::cutoff($idShop),
            'orders' => 0,
            'remaining' => 0,
            'customers' => 0,
            'addresses' => 0,
            'shop_side' => (bool) AmzproShop::get('AMZPRO_PII_PURGE_PS', $idShop),
        ];

        // Collect the linked PrestaShop orders before clearing, because the
        // shop-side step needs them and the rows stop being identifiable as
        // "just purged" once pii_purged_at is set.
        $rows = Db::getInstance()->executeS(
            'SELECT `id_amazonmarketplacepro_order`, `id_order`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE ' . $this->dueCondition($idShop) . '
             ORDER BY `purchase_date` ASC
             LIMIT ' . (int) $limit
        );

        if (empty($rows)) {
            return $summary;
        }

        $ids = [];
        $psOrderIds = [];
        foreach ($rows as $r) {
            $ids[] = (int) $r['id_amazonmarketplacepro_order'];
            if ((int) $r['id_order'] > 0) {
                $psOrderIds[] = (int) $r['id_order'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $sets = [];
        foreach (self::$piiColumns as $col) {
            $sets[] = '`' . bqSQL($col) . '` = \'\'';
        }
        $sets[] = '`raw_json` = NULL';
        $sets[] = '`pii_purged_at` = \'' . pSQL($now) . '\'';
        $sets[] = '`date_upd` = \'' . pSQL($now) . '\'';

        $ok = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             SET ' . implode(', ', $sets) . '
             WHERE `id_amazonmarketplacepro_order` IN (' . implode(', ', $ids) . ')
               AND ' . $this->shopCondition($idShop)
        );

        if (!$ok) {
            $this->lastError = AmazonI18n::get()->l('Could not clear buyer data from the imported Amazon orders.', 'amazonpiipurger');
            $this->log('error', $this->lastError, $idShop);

            return false;
        }

        $summary['orders'] = count($ids);

        if ($summary['shop_side'] && $psOrderIds) {
            $shop = $this->purgeShopSide($psOrderIds, $idShop);
            $summary['customers'] = $shop['customers'];
            $summary['addresses'] = $shop['addresses'];
        }

        $summary['remaining'] = $this->dueCount($idShop);

        $this->log('info', sprintf(
            'Purged buyer data from %d order(s) older than %d days (cut-off %s). %d still due.',
            $summary['orders'],
            $summary['retention_days'],
            $summary['cutoff'],
            $summary['remaining']
        ), $idShop);

        return $summary;
    }

    /**
     * Anonymise the PrestaShop customer and address records too.
     *
     * OFF BY DEFAULT, and deliberately so. A PrestaShop invoice is rendered
     * from the stored address, so changing it changes documents the merchant
     * may be legally required to keep intact. The Data Protection Policy
     * exempts data held under a legal obligation, and in most countries an
     * issued invoice is exactly that. A merchant who has taken their own
     * advice can switch this on.
     *
     * Even then it is conservative: a customer is only anonymised when every
     * order they have is an Amazon order that has itself been purged. A buyer
     * who also shops directly with the merchant is left alone, because that
     * relationship is not Amazon's data.
     *
     * City, postcode and country stay on the address: they determine the VAT
     * treatment, and without them the invoice cannot be justified later.
     *
     * @param array $psOrderIds
     * @param int $idShop the shop whose purge this is
     *
     * @return array
     */
    private function purgeShopSide(array $psOrderIds, $idShop)
    {
        $result = ['customers' => 0, 'addresses' => 0];

        $ids = [];
        foreach ($psOrderIds as $id) {
            $ids[] = (int) $id;
        }
        if (!$ids) {
            return $result;
        }

        $orders = Db::getInstance()->executeS(
            'SELECT `id_order`, `id_customer`, `id_address_delivery`, `id_address_invoice`
             FROM `' . _DB_PREFIX_ . 'orders`
             WHERE `id_order` IN (' . implode(', ', $ids) . ')'
        );

        if (empty($orders)) {
            return $result;
        }

        $addressIds = [];
        $customerIds = [];
        foreach ($orders as $o) {
            foreach (['id_address_delivery', 'id_address_invoice'] as $k) {
                if ((int) $o[$k] > 0) {
                    $addressIds[(int) $o[$k]] = true;
                }
            }
            if ((int) $o['id_customer'] > 0) {
                $customerIds[(int) $o['id_customer']] = true;
            }
        }

        $now = date('Y-m-d H:i:s');

        foreach (array_keys($addressIds) as $idAddress) {
            $ok = Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'address`
                 SET `firstname` = \'Amazon\', `lastname` = \'Buyer\',
                     `address1` = \'\', `address2` = \'\',
                     `phone` = \'\', `phone_mobile` = \'\',
                     `other` = \'\', `company` = \'\',
                     `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_address` = ' . (int) $idAddress
            );
            if ($ok) {
                ++$result['addresses'];
            }
        }

        $shopSideShops = $this->shopSideShopIds($idShop);
        foreach (array_keys($customerIds) as $idCustomer) {
            if (!$this->customerIsAmazonOnly($idCustomer, $shopSideShops)) {
                continue;
            }
            $ok = Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'customer`
                 SET `firstname` = \'Amazon\', `lastname` = \'Buyer\',
                     `email` = \'purged-' . (int) $idCustomer . '@marketplace.invalid\',
                     `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_customer` = ' . (int) $idCustomer
            );
            if ($ok) {
                ++$result['customers'];
            }
        }

        return $result;
    }

    /**
     * The shops that have the PrestaShop-side purge on, always including the
     * shop being purged.
     *
     * @param int $idShop
     *
     * @return int[]
     */
    private function shopSideShopIds($idShop)
    {
        $ids = [(int) $idShop];
        foreach (AmzproShop::shopIds() as $id) {
            if ($id !== (int) $idShop && AmzproShop::get('AMZPRO_PII_PURGE_PS', $id)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * True when every order this customer has is a purged Amazon order of a
     * shop that allows the PrestaShop-side purge.
     *
     * A customer with any other order - a direct sale, an Amazon order still
     * inside the retention window, or an order of a shop that keeps its
     * PrestaShop records - is left untouched. A customer shared between shops
     * counts the orders of every shop, which is why this is not limited to
     * the shop being purged.
     *
     * @param int $idCustomer
     * @param int[] $shopIds shops whose purged orders count
     *
     * @return bool
     */
    private function customerIsAmazonOnly($idCustomer, array $shopIds)
    {
        $total = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'orders`
             WHERE `id_customer` = ' . (int) $idCustomer
        );

        if ($total === 0) {
            return false;
        }

        // Scoped through the PrestaShop order's shop: the staged row of an
        // order always belongs to the same shop as the order.
        $purgedAmazon = (int) Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT o.`id_order`)
             FROM `' . _DB_PREFIX_ . 'orders` o
             INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` a
                ON a.`id_order` = o.`id_order`
             WHERE o.`id_customer` = ' . (int) $idCustomer . '
               AND o.`id_shop` IN (' . implode(', ', array_map('intval', $shopIds)) . ')
               AND a.`pii_purged_at` IS NOT NULL'
        );

        return $total === $purgedAmazon;
    }

    /**
     * @param string $level
     * @param string $message
     * @param int $idShop
     *
     * @return void
     */
    private function log($level, $message, $idShop)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`level`, `source`, `message`, `date_add`, `id_shop`)
             VALUES (\'' . pSQL($level) . '\', \'pii_purge\', \''
             . pSQL($message) . '\', \'' . pSQL(date('Y-m-d H:i:s')) . '\', ' . (int) $idShop . ')'
        );
    }
}
