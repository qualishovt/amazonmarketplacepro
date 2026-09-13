<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Bulk repair of the identifiers Amazon syncing depends on.
 *
 * Every listing and every imported order line is matched on the PrestaShop
 * reference and the barcode, so a catalogue with blank or duplicated ones
 * cannot sync at all. Fixing that product by product is impractical, hence
 * this CSV round-trip.
 *
 * Nothing here talks to Amazon — it only edits PrestaShop.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';

class AmazonReferenceTool
{
    const SEPARATOR = ';';

    /** Header of the exported file, in column order. */
    public static $columns = array(
        'key', 'product_name', 'reference', 'ean13', 'upc', 'supplier_reference',
    );

    /**
     * Build the CSV of every product and combination.
     *
     * Barcodes are prefixed with an apostrophe so spreadsheets keep them as
     * text instead of turning 4006381333931 into 4.006E+12; the import strips
     * it again.
     *
     * @return string CSV body (UTF-8 with BOM)
     */
    public static function exportCsv()
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $rows = Db::getInstance()->executeS(
            'SELECT p.`id_product`, 0 AS id_product_attribute, pl.`name`,
                    p.`reference`, p.`ean13`, p.`upc`, p.`supplier_reference`
             FROM `' . _DB_PREFIX_ . 'product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ')
             GROUP BY p.`id_product`
             UNION ALL
             SELECT pa.`id_product`, pa.`id_product_attribute`, pl.`name`,
                    pa.`reference`, pa.`ean13`, pa.`upc`, pa.`supplier_reference`
             FROM `' . _DB_PREFIX_ . 'product_attribute` pa
             INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = pa.`id_product` AND pl.`id_lang` = ' . $idLang . ')
             GROUP BY pa.`id_product_attribute`
             ORDER BY 1, 2'
        );
        if (!is_array($rows)) {
            $rows = array();
        }

        $out = "\xEF\xBB\xBF" . implode(self::SEPARATOR, self::$columns) . "\r\n";
        foreach ($rows as $r) {
            $line = array(
                (int) $r['id_product'] . '_' . (int) $r['id_product_attribute'],
                self::escape($r['name']),
                self::escape($r['reference']),
                self::escapeCode($r['ean13']),
                self::escapeCode($r['upc']),
                self::escape($r['supplier_reference']),
            );
            $out .= implode(self::SEPARATOR, $line) . "\r\n";
        }

        return $out;
    }

    /**
     * Apply an edited CSV back onto the catalogue.
     *
     * Only reference, ean13, upc and supplier_reference are writable; the key
     * and the name are there to orient the person editing the file.
     *
     * @param string $content Raw uploaded file
     * @return array array('updated' => int, 'skipped' => int, 'errors' => array)
     */
    public static function importCsv($content)
    {
        $summary = array('updated' => 0, 'skipped' => 0, 'errors' => array());

        $content = str_replace("\xEF\xBB\xBF", '', (string) $content);
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (count($lines) < 2) {
            $summary['errors'][] = AmazonI18n::get()->l('The file has no data rows.', 'amazonreferencetool');
            return $summary;
        }

        $header = str_getcsv(array_shift($lines), self::SEPARATOR);
        $index = array();
        foreach ($header as $i => $name) {
            $index[trim(Tools::strtolower($name))] = $i;
        }
        if (!isset($index['key'])) {
            $summary['errors'][] = AmazonI18n::get()->l('The first column must be \'key\' — export a fresh file and edit that.', 'amazonreferencetool');
            return $summary;
        }

        $seenReferences = array();
        foreach ($lines as $lineNo => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line, self::SEPARATOR);
            $key = isset($cells[$index['key']]) ? trim($cells[$index['key']]) : '';
            if (!preg_match('/^(\d+)_(\d+)$/', $key, $m)) {
                $summary['skipped']++;
                continue;
            }
            $idProduct = (int) $m[1];
            $idPa = (int) $m[2];

            $values = array();
            foreach (array('reference', 'ean13', 'upc', 'supplier_reference') as $field) {
                if (!isset($index[$field]) || !isset($cells[$index[$field]])) {
                    continue;
                }
                $values[$field] = self::unescapeCode($cells[$index[$field]]);
            }
            if (empty($values)) {
                $summary['skipped']++;
                continue;
            }

            // Duplicate references break both listing sync and order matching,
            // so they are reported rather than written.
            if (isset($values['reference']) && $values['reference'] !== '') {
                $ref = Tools::strtolower($values['reference']);
                if (isset($seenReferences[$ref])) {
                    $summary['errors'][] = sprintf(
                        AmazonI18n::get()->l('Line %1$d: reference \'%2$s\' is already used on row %3$s.', 'amazonreferencetool'),
                        $lineNo + 2,
                        $values['reference'],
                        $seenReferences[$ref]
                    );
                    $summary['skipped']++;
                    continue;
                }
                $seenReferences[$ref] = $key;
            }

            $sets = array();
            foreach ($values as $field => $value) {
                $sets[] = '`' . bqSQL($field) . '` = \'' . pSQL($value) . '\'';
            }

            if ($idPa > 0) {
                $ok = Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'product_attribute` SET ' . implode(', ', $sets) . '
                     WHERE `id_product_attribute` = ' . $idPa
                );
            } else {
                $ok = Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'product` SET ' . implode(', ', $sets) . '
                     WHERE `id_product` = ' . $idProduct
                );
            }

            if ($ok) {
                $summary['updated']++;
            } else {
                $summary['errors'][] = sprintf(
                    AmazonI18n::get()->l('Line %1$d: could not update %2$s.', 'amazonreferencetool'),
                    $lineNo + 2,
                    $key
                );
            }
        }

        return $summary;
    }

    /** Catalogue problems that will stop a sync, for a pre-flight panel. */
    public static function auditCatalogue()
    {
        $noReference = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product`
             WHERE `active` = 1 AND (`reference` IS NULL OR `reference` = \'\')'
        );
        $noBarcode = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product`
             WHERE `active` = 1 AND (`ean13` IS NULL OR `ean13` = \'\')
               AND (`upc` IS NULL OR `upc` = \'\')'
        );
        $duplicates = Db::getInstance()->executeS(
            'SELECT `reference`, COUNT(*) AS c FROM `' . _DB_PREFIX_ . 'product`
             WHERE `reference` <> \'\' GROUP BY `reference` HAVING c > 1'
        );
        $comboNoReference = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_attribute`
             WHERE `reference` IS NULL OR `reference` = \'\''
        );

        return array(
            'no_reference' => $noReference,
            'no_barcode' => $noBarcode,
            'duplicate_references' => is_array($duplicates) ? count($duplicates) : 0,
            'combinations_no_reference' => $comboNoReference,
        );
    }

    private static function escape($value)
    {
        $value = str_replace(array("\r", "\n", self::SEPARATOR), ' ', (string) $value);

        return $value;
    }

    /** Keep spreadsheets from mangling long numeric codes. */
    private static function escapeCode($value)
    {
        $value = trim((string) $value);

        return ($value !== '') ? '\'' . $value : '';
    }

    private static function unescapeCode($value)
    {
        return trim(ltrim(trim((string) $value), '\''));
    }
}
