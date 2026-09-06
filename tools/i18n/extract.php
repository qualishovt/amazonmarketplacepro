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

/**
 * Pull every translatable literal out of the module.
 *
 * Scans the same two constructs PrestaShop's own translation tool scans:
 * {l s='...' mod='amazonmarketplacepro'} in templates and $this->l('...') in
 * the module class. Include this file to get the strings back as
 * array(relative file => array(string, ...)); run it directly for a summary.
 *
 * The file grouping matters: PrestaShop's translation key contains the source
 * file name, so the same English sentence used in two templates needs two
 * entries in the generated language file.
 */

$amzproI18nScan = function () {
    $root = dirname(dirname(__DIR__)) . '/';
    $files = array(
        'views/templates/admin/configure.tpl',
        'views/templates/admin/product_tab.tpl',
        'amazonmarketplacepro.php',
    );

    // {l s='...'} or {l s="..."}, honouring backslash escapes inside.
    $tplRe = "/\\{l\\s+s=(?:'((?:[^'\\\\]|\\\\.)*)'|\"((?:[^\"\\\\]|\\\\.)*)\")/";
    $phpRe = "/\\\$this->l\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/";

    $out = array();
    foreach ($files as $f) {
        $s = file_get_contents($root . $f);
        if ($s === false) {
            fwrite(STDERR, "i18n: cannot read $f\n");
            exit(1);
        }
        $list = array();
        if (substr($f, -4) === '.tpl') {
            if (preg_match_all($tplRe, $s, $m) === false) {
                fwrite(STDERR, "i18n: template regex failed on $f\n");
                exit(1);
            }
            foreach ($m[1] as $i => $q) {
                $list[] = ($q !== '') ? $q : $m[2][$i];
            }
        } else {
            if (preg_match_all($phpRe, $s, $m) === false) {
                fwrite(STDERR, "i18n: php regex failed on $f\n");
                exit(1);
            }
            $list = $m[1];
        }
        $out[$f] = array_values(array_unique($list));
    }

    return $out;
};

$amzproI18nStrings = $amzproI18nScan();

if (isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $distinct = array();
    foreach ($amzproI18nStrings as $f => $list) {
        echo str_pad($f, 42) . count($list) . " unique\n";
        foreach ($list as $s) {
            $distinct[$s] = true;
        }
    }
    echo "\nentries (file x string): " . array_sum(array_map('count', $amzproI18nStrings)) . "\n";
    echo "distinct strings:        " . count($distinct) . "\n";
    echo "characters:              " . array_sum(array_map('strlen', array_keys($distinct))) . "\n";
}

return $amzproI18nStrings;
