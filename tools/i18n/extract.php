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
 * Scans the constructs PrestaShop's own translation tool scans:
 * {l s='...' mod='amazonmarketplacepro'} in templates, $this->l('...') in the
 * module class, and ->l('...', 'source') in the plain classes
 * (AmazonI18n::get()->l) and the front controllers ($this->module->l).
 * Include this file to get the strings back as
 * array(relative file => array(string, ...)); run it directly for a summary.
 *
 * The file grouping matters: PrestaShop's translation key contains the source
 * file name, so the same English sentence used in two files needs two entries
 * in the generated language file.
 */

if (!function_exists('amzproI18nSource')) {
    /** The `source` a file contributes to the key: its basename, lower case. */
    function amzproI18nSource($file)
    {
        return strtolower(pathinfo($file, PATHINFO_FILENAME));
    }
}

$amzproI18nScan = function () {
    $root = dirname(dirname(__DIR__)) . '/';
    $files = array(
        'views/templates/admin/configure.tpl',
        'views/templates/admin/product_tab.tpl',
        'amazonmarketplacepro.php',
    );
    // Plain classes and front controllers name their source explicitly.
    foreach (array('classes', 'controllers/front') as $dir) {
        foreach (glob($root . $dir . '/*.php') as $path) {
            // AmazonI18n.php is the helper itself: its l() takes variables.
            if (!in_array(basename($path), array('index.php', 'AmazonI18n.php'), true)) {
                $files[] = $dir . '/' . basename($path);
            }
        }
    }

    // {l s='...'} or {l s="..."}, honouring backslash escapes inside.
    $tplRe = "/\\{l\\s+s=(?:'((?:[^'\\\\]|\\\\.)*)'|\"((?:[^\"\\\\]|\\\\.)*)\")/";
    $phpRe = "/\\\$this->l\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/";
    // ->l('text', 'source'), and any ->l( that is not a literal pair.
    $classRe = "/->l\\(\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*,\\s*'([^']*)'\\s*\\)/";
    $anyRe = "/->l\\(/";

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
        } elseif ($f === 'amazonmarketplacepro.php') {
            if (preg_match_all($phpRe, $s, $m) === false) {
                fwrite(STDERR, "i18n: php regex failed on $f\n");
                exit(1);
            }
            $list = $m[1];
        } else {
            preg_match_all($classRe, $s, $m);
            // A call the pattern cannot read would never be translated: stop.
            if (preg_match_all($anyRe, $s) !== count($m[0])) {
                fwrite(STDERR, "i18n: $f has an ->l() call that is not ->l('literal', 'source')\n");
                exit(1);
            }
            foreach ($m[2] as $i => $source) {
                if ($source !== amzproI18nSource($f)) {
                    fwrite(STDERR, "i18n: $f passes source '$source', expected '" . amzproI18nSource($f) . "'\n");
                    exit(1);
                }
            }
            $list = $m[1];
            if (!$list) {
                continue;
            }
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
