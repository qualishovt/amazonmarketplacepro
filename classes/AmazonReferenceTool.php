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
 *
 * Multistore: with a shop selected, export, import and audit cover the
 * products and combinations associated with that shop; in "All shops" the
 * whole catalogue. References and barcodes themselves are not per shop in
 * PrestaShop, so a change made from one shop shows in every shop that sells
 * the product.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonReferenceTool
{
    public static $SEPARATOR = ';';

    /** Header of the exported file, in column order. */
    public static $columns = [
        'key', 'product_name', 'reference', 'ean13', 'upc', 'supplier_reference',
    ];

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
        $idShop = (int) AmzproShop::id();

        if ($idShop) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT', null, AmzproShop::groupId($idShop), $idShop);
            $rows = Db::getInstance()->executeS(
                'SELECT p.`id_product`, 0 AS id_product_attribute, pl.`name`,
                        p.`reference`, p.`ean13`, p.`upc`, p.`supplier_reference`
                 FROM `' . _DB_PREFIX_ . 'product` p
                 INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                     ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ')
                 INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                     ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . '
                         AND pl.`id_shop` = ' . $idShop . ')
                 UNION ALL
                 SELECT pa.`id_product`, pa.`id_product_attribute`, pl.`name`,
                        pa.`reference`, pa.`ean13`, pa.`upc`, pa.`supplier_reference`
                 FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                 INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas
                     ON (pas.`id_product_attribute` = pa.`id_product_attribute` AND pas.`id_shop` = ' . $idShop . ')
                 INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                     ON (pl.`id_product` = pa.`id_product` AND pl.`id_lang` = ' . $idLang . '
                         AND pl.`id_shop` = ' . $idShop . ')
                 ORDER BY 1, 2'
            );
        } else {
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
        }
        if (!is_array($rows)) {
            $rows = [];
        }

        $out = "\xEF\xBB\xBF" . implode(self::$SEPARATOR, self::$columns) . "\r\n";
        foreach ($rows as $r) {
            $line = [
                (int) $r['id_product'] . '_' . (int) $r['id_product_attribute'],
                self::escape($r['name']),
                self::escape($r['reference']),
                self::escapeCode($r['ean13']),
                self::escapeCode($r['upc']),
                self::escape($r['supplier_reference']),
            ];
            $out .= implode(self::$SEPARATOR, $line) . "\r\n";
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
     *
     * @return array array('updated' => int, 'skipped' => int, 'errors' => array)
     */
    public static function importCsv($content)
    {
        $summary = ['updated' => 0, 'skipped' => 0, 'errors' => []];

        $content = str_replace("\xEF\xBB\xBF", '', (string) $content);
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (count($lines) < 2) {
            $summary['errors'][] = AmazonI18n::get()->l('The file has no data rows.', 'amazonreferencetool');

            return $summary;
        }

        $header = str_getcsv(array_shift($lines), self::$SEPARATOR);
        $index = [];
        foreach ($header as $i => $name) {
            $index[trim(Tools::strtolower($name))] = $i;
        }
        if (!isset($index['key'])) {
            $summary['errors'][] = AmazonI18n::get()->l('The first column must be \'key\' — export a fresh file and edit that.', 'amazonreferencetool');

            return $summary;
        }

        // With a shop selected only its products are written.
        $idShop = (int) AmzproShop::id();

        $seenReferences = [];
        foreach ($lines as $lineNo => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line, self::$SEPARATOR);
            $key = isset($cells[$index['key']]) ? trim($cells[$index['key']]) : '';
            if (!preg_match('/^(\d+)_(\d+)$/', $key, $m)) {
                ++$summary['skipped'];
                continue;
            }
            $idProduct = (int) $m[1];
            $idPa = (int) $m[2];

            if ($idShop && !self::inShop($idProduct, $idPa, $idShop)) {
                $summary['errors'][] = sprintf(
                    AmazonI18n::get()->l('Line %1$d: %2$s is not a product of this shop, so it was left unchanged.', 'amazonreferencetool'),
                    $lineNo + 2,
                    $key
                );
                ++$summary['skipped'];
                continue;
            }

            $values = [];
            foreach (['reference', 'ean13', 'upc', 'supplier_reference'] as $field) {
                if (!isset($index[$field]) || !isset($cells[$index[$field]])) {
                    continue;
                }
                $values[$field] = self::unescapeCode($cells[$index[$field]]);
            }
            if (empty($values)) {
                ++$summary['skipped'];
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
                    ++$summary['skipped'];
                    continue;
                }
                $seenReferences[$ref] = $key;
            }

            $sets = [];
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
                ++$summary['updated'];
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

    /**
     * Whether a product (or one of its combinations) is associated with the
     * shop.
     */
    private static function inShop($idProduct, $idProductAttribute, $idShop)
    {
        if ($idProductAttribute > 0) {
            return (bool) Db::getInstance()->getValue(
                'SELECT pas.`id_product_attribute`
                 FROM `' . _DB_PREFIX_ . 'product_attribute_shop` pas
                 INNER JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                     ON (pa.`id_product_attribute` = pas.`id_product_attribute`)
                 WHERE pas.`id_product_attribute` = ' . (int) $idProductAttribute . '
                   AND pa.`id_product` = ' . (int) $idProduct . '
                   AND pas.`id_shop` = ' . (int) $idShop
            );
        }

        return (bool) Db::getInstance()->getValue(
            'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product_shop`
             WHERE `id_product` = ' . (int) $idProduct . ' AND `id_shop` = ' . (int) $idShop
        );
    }

    /**
     * Catalogue problems that will stop a sync, for a pre-flight panel: the
     * selected shop's products, or the whole catalogue in "All shops".
     */
    public static function auditCatalogue()
    {
        $idShop = (int) AmzproShop::id();
        $p = '`' . _DB_PREFIX_ . 'product` p';
        $active = 'p.`active` = 1';
        $pa = '`' . _DB_PREFIX_ . 'product_attribute` pa';
        if ($idShop) {
            // Active is the shop's own status.
            $p .= ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                     ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ')';
            $active = 'ps.`active` = 1';
            $pa .= ' INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas
                      ON (pas.`id_product_attribute` = pa.`id_product_attribute` AND pas.`id_shop` = ' . $idShop . ')';
        }

        $noReference = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . $p . '
             WHERE ' . $active . ' AND (p.`reference` IS NULL OR p.`reference` = \'\')'
        );
        $noBarcode = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . $p . '
             WHERE ' . $active . ' AND (p.`ean13` IS NULL OR p.`ean13` = \'\')
               AND (p.`upc` IS NULL OR p.`upc` = \'\')'
        );
        $duplicates = Db::getInstance()->executeS(
            'SELECT p.`reference`, COUNT(*) AS c FROM ' . $p . '
             WHERE p.`reference` <> \'\' GROUP BY p.`reference` HAVING c > 1'
        );
        $comboNoReference = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . $pa . '
             WHERE pa.`reference` IS NULL OR pa.`reference` = \'\''
        );

        return [
            'no_reference' => $noReference,
            'no_barcode' => $noBarcode,
            'duplicate_references' => is_array($duplicates) ? count($duplicates) : 0,
            'combinations_no_reference' => $comboNoReference,
        ];
    }

    private static function escape($value)
    {
        $value = str_replace(["\r", "\n", self::$SEPARATOR], ' ', (string) $value);

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
