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
 * Fills buyer names and shipping addresses in from an Amazon order report.
 *
 * WHY. Until Amazon grants the app its restricted role for personal data, the
 * Orders API returns only the non-identifying part of an address - city,
 * region, postcode, country - with no street, no recipient and no phone. The
 * importer stages what it gets and the creator fills the gaps with
 * placeholders. The merchant can still download the same orders from Seller
 * Central as a report with the full addresses in it; this class reads that
 * file and puts the missing fields where they belong.
 *
 * WHAT IT TOUCHES. Only orders the importer has already staged. A report line
 * for an order that is not imported yet is counted and dropped, not kept for
 * later: holding it would be a second copy of the buyer's data with its own
 * retention to manage. Staged fields are filled only where they are empty, so
 * a value the API supplied is never overwritten. On the PrestaShop side an
 * address changes only while its street is still the placeholder - once the
 * merchant has filled it in or edited it, it is theirs, and all of it is left
 * alone. Orders whose buyer data has already been purged are skipped, so an
 * upload cannot bring back what the retention policy removed.
 *
 * NOTHING IS KEPT. The report is parsed in memory and dropped; the caller logs
 * counts only.
 *
 * ONE SHOP. An upload fills the orders of the shop it is made in. A report
 * line for an order another shop imported is counted and left alone: each
 * shop has its own seller account, so the file most likely came from the
 * wrong one.
 *
 * PHP 5.6+ compatible (no scalar type hints, no ?? operator).
 */

require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonOrderReportImporter
{
    /** Largest file accepted, in bytes. */
    const MAX_BYTES = 10485760;

    /** Report columns accepted for each field; the first one present wins. */
    private static $columns = [
        'order_id' => ['order-id', 'amazon-order-id'],
        'ship_name' => ['recipient-name', 'ship-name', 'shipping-name'],
        'buyer_name' => ['buyer-name'],
        'address1' => ['ship-address-1', 'shipping-address-1'],
        'address2' => ['ship-address-2', 'shipping-address-2'],
        'address3' => ['ship-address-3', 'shipping-address-3'],
        'city' => ['ship-city', 'shipping-city'],
        'state' => ['ship-state', 'shipping-state'],
        'postal_code' => ['ship-postal-code', 'shipping-postal-code'],
        'country' => ['ship-country', 'shipping-country'],
        'ship_phone' => ['ship-phone-number', 'shipping-phone-number'],
        'buyer_phone' => ['buyer-phone-number'],
    ];

    /**
     * What each PrestaShop validator objects to, stripped before validating.
     *
     * The first three are the characters Validate rejects; the last two are
     * the complement of what it allows. Taken from PrestaShop 1.6 - PrestaShop
     * 9 accepts a slash in phone numbers as well, so the stricter set works on
     * both. Whatever survives is still checked against the running shop's own
     * Validate before it is written.
     */
    private static $filters = [
        'isName' => '/[0-9!<>,;?=+()@#"°{}_$%:¤|]/u',
        'isAddress' => '/[!<>?=+@{}_$%]/u',
        'isCityName' => '/[!<>;?=+@#"°{}_$%]/u',
        'isPostCode' => '/[^a-zA-Z 0-9-]/',
        'isPhoneNumber' => '/[^+0-9. ()-]/',
    ];

    /**
     * Read the report into one record per order.
     *
     * Amazon's order reports are tab-separated with a line per order item, so
     * an order of several items repeats its address on each line; the first
     * non-empty value of every field is kept. Semicolon- and comma-separated
     * files are accepted too, for a report that was opened in a spreadsheet
     * and saved again.
     *
     * @param string $content Raw file contents
     *
     * @return array 'orders' => array(order id => fields), 'rows' => lines
     *               read, 'invalid' => lines without a valid order id,
     *               'error' => null, 'no_order_id' or 'no_address'
     */
    public function parse($content)
    {
        $result = ['orders' => [], 'rows' => 0, 'invalid' => 0, 'error' => null];

        $lines = preg_split('/\r\n|\n|\r/', self::toUtf8((string) $content));
        $header = null;
        while ($lines && $header === null) {
            $line = array_shift($lines);
            if (trim($line) !== '') {
                $header = $line;
            }
        }
        if ($header === null) {
            $result['error'] = 'no_order_id';

            return $result;
        }

        $delimiter = self::delimiter($header);
        $index = self::mapColumns(self::split($header, $delimiter));

        if (!isset($index['order_id'])) {
            $result['error'] = 'no_order_id';

            return $result;
        }
        if (!isset($index['address1']) && !isset($index['ship_name'])) {
            $result['error'] = 'no_address';

            return $result;
        }

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = self::split($line, $delimiter);
            $orderId = self::cell($cells, $index, 'order_id');
            if (!preg_match('/^\d{3}-\d{7}-\d{7}$/', $orderId)) {
                ++$result['invalid'];
                continue;
            }
            ++$result['rows'];

            $second = [];
            foreach (['address2', 'address3'] as $field) {
                $value = self::cell($cells, $index, $field);
                if ($value !== '') {
                    $second[] = $value;
                }
            }
            $shipPhone = self::cell($cells, $index, 'ship_phone');

            $record = [
                'ship_name' => self::cell($cells, $index, 'ship_name'),
                'buyer_name' => self::cell($cells, $index, 'buyer_name'),
                'address1' => self::cell($cells, $index, 'address1'),
                'address2' => implode(', ', $second),
                'city' => self::cell($cells, $index, 'city'),
                'state' => self::cell($cells, $index, 'state'),
                'postal_code' => self::cell($cells, $index, 'postal_code'),
                'country' => self::cell($cells, $index, 'country'),
                'phone' => $shipPhone !== '' ? $shipPhone : self::cell($cells, $index, 'buyer_phone'),
            ];

            if (!isset($result['orders'][$orderId])) {
                $result['orders'][$orderId] = $record;
                continue;
            }
            foreach ($record as $field => $value) {
                if ($result['orders'][$orderId][$field] === '' && $value !== '') {
                    $result['orders'][$orderId][$field] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Put the report's names and addresses on the orders they belong to.
     *
     * @param array $orders Output of parse(): order id => fields
     * @param int $idShop The shop whose orders are filled (0 = the shop the
     *                    request acts for)
     *
     * @return array Counts: in_report, updated, addresses, customers, already,
     *               kept, not_imported, purged, other_shop
     */
    public function apply(array $orders, $idShop = 0)
    {
        require_once dirname(__FILE__) . '/AmazonOrderCreator.php';
        $this->ensureSchema();
        $idShop = (int) $idShop ? (int) $idShop : AmzproShop::actingId();

        $summary = [
            'in_report' => count($orders),
            'updated' => 0,
            'addresses' => 0,
            'customers' => 0,
            'already' => 0,
            'kept' => 0,
            'not_imported' => 0,
            'purged' => 0,
            'other_shop' => 0,
        ];

        foreach ($orders as $orderId => $fields) {
            // No LIMIT: getRow() appends its own, and two make the query fail.
            // Looked up in every shop (the order number is unique across
            // them) so that another shop's order is told apart from one that
            // is not imported at all.
            $row = Db::getInstance()->getRow(
                'SELECT `id_amazonmarketplacepro_order`, `id_order`, `buyer_name`, `ship_name`,
                        `ship_address1`, `ship_address2`, `ship_city`, `ship_state`,
                        `ship_postal_code`, `ship_country_code`, `ship_phone`, `pii_purged_at`,
                        `id_shop`
                 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 WHERE `amazon_order_id` = \'' . pSQL($orderId) . '\''
            );
            if (!$row) {
                ++$summary['not_imported'];
                continue;
            }
            if ((int) $row['id_shop'] !== $idShop) {
                ++$summary['other_shop'];
                continue;
            }
            if (!empty($row['pii_purged_at'])) {
                ++$summary['purged'];
                continue;
            }

            $clean = self::cleanFields($fields);
            $hadStreet = trim((string) $row['ship_address1']) !== '';
            $stagedChanged = $this->fillStaged($row, $clean);

            $shopChanged = false;
            if ((int) $row['id_order'] > 0) {
                $shop = $this->fillShopOrder((int) $row['id_order'], $clean, $idShop);
                $summary['addresses'] += $shop['addresses'];
                $summary['customers'] += $shop['customers'];
                $shopChanged = ((int) $shop['addresses'] + (int) $shop['customers']) > 0;
                // Only worth mentioning when this upload brought a street the
                // order did not have - otherwise an address that was complete
                // all along would be reported as "edited".
                if (!$hadStreet) {
                    $summary['kept'] += $shop['kept'];
                }
            }

            if ($stagedChanged || $shopChanged) {
                ++$summary['updated'];
            } else {
                ++$summary['already'];
            }
        }

        return $summary;
    }

    /** Add the recipient column on installs that predate it. */
    public function ensureSchema()
    {
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` LIKE \'ship_name\''
        );
        if (is_array($cols) && empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 ADD COLUMN `ship_name` VARCHAR(255) NOT NULL DEFAULT \'\''
            );
        }
    }

    /**
     * Fill the staged order's empty fields.
     *
     * @return bool Whether anything was written
     */
    private function fillStaged(array $row, array $clean)
    {
        $map = [
            'ship_name' => 'ship_name',
            'buyer_name' => 'buyer_name',
            'ship_address1' => 'address1',
            'ship_address2' => 'address2',
            'ship_city' => 'city',
            'ship_state' => 'state',
            'ship_postal_code' => 'postal_code',
            'ship_country_code' => 'country',
            'ship_phone' => 'phone',
        ];

        $sets = [];
        foreach ($map as $column => $field) {
            $current = isset($row[$column]) ? trim((string) $row[$column]) : '';
            if ($current === '' && $clean[$field] !== '') {
                $sets[] = '`' . bqSQL($column) . '` = \'' . pSQL($clean[$field]) . '\'';
            }
        }
        if (!$sets) {
            return false;
        }
        $sets[] = '`date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'';

        return (bool) Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             SET ' . implode(', ', $sets) . '
             WHERE `id_amazonmarketplacepro_order` = ' . (int) $row['id_amazonmarketplacepro_order'] . '
               AND `id_shop` = ' . (int) $row['id_shop']
        );
    }

    /**
     * Fill the PrestaShop order's addresses and, while it is still the
     * placeholder, its customer's name.
     *
     * @return array Counts: addresses, customers, kept
     */
    private function fillShopOrder($idOrder, array $clean, $idShop)
    {
        $result = ['addresses' => 0, 'customers' => 0, 'kept' => 0];

        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order) || (int) $order->id_shop !== (int) $idShop) {
            return $result;
        }

        // The recipient is who the parcel goes to; the buyer is who paid.
        // They differ for every gift order.
        $recipient = $clean['ship_name'] !== '' ? $clean['ship_name'] : $clean['buyer_name'];

        $ids = array_unique(array_filter([
            (int) $order->id_address_delivery,
            (int) $order->id_address_invoice,
        ]));
        foreach ($ids as $idAddress) {
            $outcome = $this->fillAddress($idAddress, $clean, $recipient);
            if ($outcome === 'filled') {
                ++$result['addresses'];
            } elseif ($outcome === 'kept') {
                ++$result['kept'];
            }
        }

        if ($this->fillCustomer((int) $order->id_customer, $clean['buyer_name'])) {
            ++$result['customers'];
        }

        return $result;
    }

    /**
     * Fill one PrestaShop address, if it still carries the placeholder street.
     *
     * @return string 'filled', 'kept' (someone filled or edited it already),
     *                'unchanged' (nothing to fill it with), 'missing' or 'failed'
     */
    private function fillAddress($idAddress, array $clean, $recipient)
    {
        $address = new Address((int) $idAddress);
        if (!Validate::isLoadedObject($address)) {
            return 'missing';
        }

        $street = trim((string) $address->address1);
        if ($street !== '' && $street !== AmazonOrderCreator::PLACEHOLDER_ADDRESS1) {
            return $clean['address1'] !== '' ? 'kept' : 'unchanged';
        }
        if ($clean['address1'] === '') {
            return 'unchanged';
        }

        $address->address1 = $clean['address1'];
        if ($clean['address2'] !== '') {
            $address->address2 = $clean['address2'];
        }

        if ($address->firstname === AmazonOrderCreator::PLACEHOLDER_FIRSTNAME
            && $address->lastname === AmazonOrderCreator::PLACEHOLDER_LASTNAME
            && $recipient !== ''
        ) {
            $parts = AmazonOrderCreator::splitFullName($recipient);
            $address->firstname = $parts['firstname'];
            $address->lastname = $parts['lastname'];
        }

        $placeholders = [
            'city' => ['city', AmazonOrderCreator::PLACEHOLDER_CITY],
            'postcode' => ['postal_code', AmazonOrderCreator::PLACEHOLDER_POSTCODE],
            'phone' => ['phone', AmazonOrderCreator::PLACEHOLDER_PHONE],
        ];
        foreach ($placeholders as $property => $pair) {
            $current = trim((string) $address->$property);
            if (($current === '' || $current === $pair[1]) && $clean[$pair[0]] !== '') {
                $address->$property = $clean[$pair[0]];
            }
        }

        if (!(int) $address->id_state && $clean['state'] !== '' && (int) $address->id_country) {
            // No LIMIT here either - see getRow() above.
            $idState = (int) Db::getInstance()->getValue(
                'SELECT `id_state` FROM `' . _DB_PREFIX_ . 'state`
                 WHERE `id_country` = ' . (int) $address->id_country . '
                   AND (`iso_code` = \'' . pSQL($clean['state']) . '\'
                        OR `name` = \'' . pSQL($clean['state']) . '\')'
            );
            if ($idState) {
                $address->id_state = $idState;
            }
        }

        // The country is left as it is: it decided the tax on an order that
        // exists already, and the non-identifying country from the API is the
        // same one the report carries.
        try {
            return $address->update() ? 'filled' : 'failed';
        } catch (Exception $e) {
            return 'failed';
        }
    }

    /**
     * Replace the placeholder name on the customer the order was filed under.
     *
     * Written with a guarded UPDATE rather than Customer::update(): in an
     * admin request that method also rewrites the customer's groups.
     *
     * @return bool Whether the name was replaced
     */
    private function fillCustomer($idCustomer, $buyerName)
    {
        if ($idCustomer <= 0 || $buyerName === '') {
            return false;
        }
        $parts = AmazonOrderCreator::splitFullName($buyerName);
        if ($parts['firstname'] === AmazonOrderCreator::PLACEHOLDER_FIRSTNAME
            && $parts['lastname'] === AmazonOrderCreator::PLACEHOLDER_LASTNAME
        ) {
            return false;
        }

        $db = Db::getInstance();
        $ok = $db->execute(
            'UPDATE `' . _DB_PREFIX_ . 'customer`
             SET `firstname` = \'' . pSQL($parts['firstname']) . '\',
                 `lastname` = \'' . pSQL($parts['lastname']) . '\',
                 `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `id_customer` = ' . (int) $idCustomer . '
               AND `firstname` = \'' . pSQL(AmazonOrderCreator::PLACEHOLDER_FIRSTNAME) . '\'
               AND `lastname` = \'' . pSQL(AmazonOrderCreator::PLACEHOLDER_LASTNAME) . '\''
        );

        if (!$ok || (int) $db->Affected_Rows() === 0) {
            return false;
        }
        // The UPDATE went round ObjectModel, so drop any copy of this customer
        // cached earlier in the request, as Customer::update() itself would.
        Cache::clean('objectmodel_Customer_' . (int) $idCustomer . '_*');

        return true;
    }

    /**
     * Sanitise every field to what PrestaShop will accept.
     *
     * Sized to PrestaShop's address columns rather than the staging table's,
     * so a staged value is always one the creator can later turn into an
     * address without failing validation.
     *
     * @return array
     */
    private static function cleanFields(array $fields)
    {
        $country = strtoupper(trim((string) $fields['country']));

        return [
            'ship_name' => self::fit($fields['ship_name'], 'isName', 255),
            'buyer_name' => self::fit($fields['buyer_name'], 'isName', 255),
            'address1' => self::fit($fields['address1'], 'isAddress', self::size('address1', 128)),
            'address2' => self::fit($fields['address2'], 'isAddress', self::size('address2', 128)),
            'city' => self::fit($fields['city'], 'isCityName', self::size('city', 64)),
            'state' => self::fit($fields['state'], 'isCityName', 64),
            'postal_code' => self::fit($fields['postal_code'], 'isPostCode', self::size('postcode', 12)),
            'country' => preg_match('/^[A-Z]{2}$/', $country) ? $country : '',
            'phone' => self::fit($fields['phone'], 'isPhoneNumber', self::size('phone', 32)),
        ];
    }

    /**
     * Strip what the validator rejects, fit the column, then validate.
     *
     * @return string The value, or '' when nothing valid is left
     */
    private static function fit($value, $validator, $size)
    {
        // The tables are utf8 - three bytes - and cannot store emoji.
        $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', (string) $value);
        if ($value !== null) {
            $value = preg_replace(self::$filters[$validator], '', $value);
        }
        if ($value !== null) {
            $value = preg_replace('/\s+/u', ' ', $value);
        }
        if ($value === null) {
            return '';
        }
        $value = trim($value);
        if ($size > 0 && Tools::strlen($value) > $size) {
            $value = trim(Tools::substr($value, 0, $size));
        }
        if ($value === '' || !call_user_func(['Validate', $validator], $value)) {
            return '';
        }

        return $value;
    }

    /** A PrestaShop address column's size, which differs between versions. */
    private static function size($field, $default)
    {
        $def = Address::$definition;

        return isset($def['fields'][$field]['size']) ? (int) $def['fields'][$field]['size'] : $default;
    }

    /** @return string */
    private static function toUtf8($content)
    {
        $bom = substr($content, 0, 2);
        if ($bom === "\xFF\xFE" || $bom === "\xFE\xFF") {
            $from = $bom === "\xFF\xFE" ? 'UTF-16LE' : 'UTF-16BE';

            return function_exists('mb_convert_encoding')
                ? mb_convert_encoding(substr($content, 2), 'UTF-8', $from)
                : '';
        }
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }
        if (preg_match('//u', $content)) {
            return $content;
        }

        // Not UTF-8. Older Seller Central downloads, and reports saved again
        // from a spreadsheet, arrive as Windows-1252.
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $converted = function_exists('iconv') ? iconv('Windows-1252', 'UTF-8//IGNORE', $content) : false;

        return $converted === false ? '' : $converted;
    }

    /** @return string */
    private static function delimiter($header)
    {
        if (strpos($header, "\t") !== false) {
            return "\t";
        }

        return substr_count($header, ';') > substr_count($header, ',') ? ';' : ',';
    }

    /** @return array */
    private static function split($line, $delimiter)
    {
        // Amazon never quotes its tab-separated fields, and a street can hold
        // a literal quote mark, so a tab-separated line is split verbatim.
        if ($delimiter === "\t") {
            return explode("\t", $line);
        }

        return str_getcsv($line, $delimiter);
    }

    /**
     * Column position of each field, from the header line.
     *
     * @return array field => position
     */
    private static function mapColumns(array $header)
    {
        $normal = [];
        foreach ($header as $position => $name) {
            $normal[$position] = trim(preg_replace('/[\s_]+/', '-', strtolower(trim((string) $name))), '-');
        }

        $index = [];
        foreach (self::$columns as $field => $aliases) {
            foreach ($aliases as $alias) {
                $position = array_search($alias, $normal, true);
                if ($position !== false) {
                    $index[$field] = $position;
                    break;
                }
            }
        }

        return $index;
    }

    /** @return string */
    private static function cell(array $cells, array $index, $field)
    {
        if (!isset($index[$field]) || !isset($cells[$index[$field]])) {
            return '';
        }
        $value = preg_replace('/\s+/u', ' ', (string) $cells[$index[$field]]);

        return $value === null ? '' : trim($value);
    }
}
