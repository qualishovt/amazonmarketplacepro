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
 * PRESTASHOP'S OWN RECORDS are a separate question and off by default. See
 * purgeShopSide().
 */
class AmazonPiiPurger
{
    /** Amazon's requirement, and the default. */
    const DEFAULT_RETENTION_DAYS = 30;
    const MIN_RETENTION_DAYS = 1;
    const MAX_RETENTION_DAYS = 365;

    /** Rows per run, so a large backlog cannot exhaust a cron request. */
    const DEFAULT_BATCH = 500;

    /**
     * Order states in which Amazon is finished with the order.
     *
     * Pending, Unshipped, PartiallyShipped and PendingAvailability are absent
     * on purpose: those orders are still being fulfilled and the merchant
     * still needs the address.
     */
    private static $finishedStatuses = array(
        'Shipped',
        'Delivered',
        'Canceled',
        'Cancelled',
        'Unfulfillable',
    );

    /** Staging columns cleared to an empty string. */
    private static $piiColumns = array(
        'buyer_email',
        'buyer_name',
        'ship_address1',
        'ship_address2',
        'ship_city',
        'ship_state',
        'ship_postal_code',
        'ship_phone',
    );

    /** @var string|null */
    private $lastError = null;

    /** @return string|null */
    public function getLastError()
    {
        return $this->lastError;
    }

    /** @return bool */
    public static function isEnabled()
    {
        $v = Configuration::get('AMZPRO_PII_PURGE');
        // Absent configuration means an install that predates this feature.
        // Default to on: the compliant behaviour is the safe one to assume.
        return ($v === false || $v === null || $v === '') ? true : (bool) $v;
    }

    /**
     * Retention window in days, clamped to a sane range.
     *
     * @return int
     */
    public static function retentionDays()
    {
        $days = (int) Configuration::get('AMZPRO_PII_RETENTION_DAYS');
        if ($days <= 0) {
            $days = self::DEFAULT_RETENTION_DAYS;
        }
        if ($days < self::MIN_RETENTION_DAYS) {
            $days = self::MIN_RETENTION_DAYS;
        }
        if ($days > self::MAX_RETENTION_DAYS) {
            $days = self::MAX_RETENTION_DAYS;
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
     * @return string
     */
    public static function cutoff()
    {
        return date('Y-m-d H:i:s', time() - (self::retentionDays() * 86400));
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
    }

    /**
     * SQL fragment matching rows that are due, without the leading WHERE.
     *
     * @return string
     */
    private function dueCondition()
    {
        $quoted = array();
        foreach (self::$finishedStatuses as $s) {
            $quoted[] = '\'' . pSQL($s) . '\'';
        }

        return '`pii_purged_at` IS NULL
                AND `purchase_date` IS NOT NULL
                AND `purchase_date` < \'' . pSQL(self::cutoff()) . '\'
                AND `order_status` IN (' . implode(', ', $quoted) . ')';
    }

    /**
     * How many orders are waiting to be purged.
     *
     * @return int
     */
    public function dueCount()
    {
        $this->ensureSchema();

        $n = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE ' . $this->dueCondition()
        );

        return (int) $n;
    }

    /**
     * Purge buyer data from finished orders past the retention window.
     *
     * @param int $limit Rows to process this run
     * @return array|false Summary counts, or false on failure
     */
    public function purge($limit = self::DEFAULT_BATCH)
    {
        $this->lastError = null;
        $this->ensureSchema();

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = self::DEFAULT_BATCH;
        }

        $summary = array(
            'retention_days' => self::retentionDays(),
            'cutoff' => self::cutoff(),
            'orders' => 0,
            'remaining' => 0,
            'customers' => 0,
            'addresses' => 0,
            'shop_side' => (bool) Configuration::get('AMZPRO_PII_PURGE_PS'),
        );

        // Collect the linked PrestaShop orders before clearing, because the
        // shop-side step needs them and the rows stop being identifiable as
        // "just purged" once pii_purged_at is set.
        $rows = Db::getInstance()->executeS(
            'SELECT `id_amazonmarketplacepro_order`, `id_order`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE ' . $this->dueCondition() . '
             ORDER BY `purchase_date` ASC
             LIMIT ' . $limit
        );

        if (empty($rows)) {
            return $summary;
        }

        $ids = array();
        $psOrderIds = array();
        foreach ($rows as $r) {
            $ids[] = (int) $r['id_amazonmarketplacepro_order'];
            if ((int) $r['id_order'] > 0) {
                $psOrderIds[] = (int) $r['id_order'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $sets = array();
        foreach (self::$piiColumns as $col) {
            $sets[] = '`' . bqSQL($col) . '` = \'\'';
        }
        $sets[] = '`raw_json` = NULL';
        $sets[] = '`pii_purged_at` = \'' . pSQL($now) . '\'';
        $sets[] = '`date_upd` = \'' . pSQL($now) . '\'';

        $ok = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             SET ' . implode(', ', $sets) . '
             WHERE `id_amazonmarketplacepro_order` IN (' . implode(', ', $ids) . ')'
        );

        if (!$ok) {
            $this->lastError = 'Could not clear buyer data from the order staging table.';
            $this->log('error', $this->lastError);

            return false;
        }

        $summary['orders'] = count($ids);

        if ($summary['shop_side'] && $psOrderIds) {
            $shop = $this->purgeShopSide($psOrderIds);
            $summary['customers'] = $shop['customers'];
            $summary['addresses'] = $shop['addresses'];
        }

        $summary['remaining'] = $this->dueCount();

        $this->log('info', sprintf(
            'Purged buyer data from %d order(s) older than %d days (cut-off %s). %d still due.',
            $summary['orders'],
            $summary['retention_days'],
            $summary['cutoff'],
            $summary['remaining']
        ));

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
     * @return array
     */
    private function purgeShopSide(array $psOrderIds)
    {
        $result = array('customers' => 0, 'addresses' => 0);

        $ids = array();
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

        $addressIds = array();
        $customerIds = array();
        foreach ($orders as $o) {
            foreach (array('id_address_delivery', 'id_address_invoice') as $k) {
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
                $result['addresses']++;
            }
        }

        foreach (array_keys($customerIds) as $idCustomer) {
            if (!$this->customerIsAmazonOnly($idCustomer)) {
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
                $result['customers']++;
            }
        }

        return $result;
    }

    /**
     * True when every order this customer has is a purged Amazon order.
     *
     * A customer with any other order - a direct sale, or an Amazon order
     * still inside the retention window - is left untouched.
     *
     * @param int $idCustomer
     * @return bool
     */
    private function customerIsAmazonOnly($idCustomer)
    {
        $total = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'orders`
             WHERE `id_customer` = ' . (int) $idCustomer
        );

        if ($total === 0) {
            return false;
        }

        $purgedAmazon = (int) Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT o.`id_order`)
             FROM `' . _DB_PREFIX_ . 'orders` o
             INNER JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` a
                ON a.`id_order` = o.`id_order`
             WHERE o.`id_customer` = ' . (int) $idCustomer . '
               AND a.`pii_purged_at` IS NOT NULL'
        );

        return $total === $purgedAmazon;
    }

    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    private function log($level, $message)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`level`, `source`, `message`, `date_add`)
             VALUES (\'' . pSQL($level) . '\', \'pii_purge\', \''
             . pSQL($message) . '\', \'' . pSQL(date('Y-m-d H:i:s')) . '\')'
        );
    }
}
