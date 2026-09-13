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
 * Ask PrestaShop itself to translate every string, for one language.
 *
 *     php tools/i18n/verify.php fr C:/xampp/7.0.33/htdocs/p16124
 *
 * This deliberately does not re-implement the key format. It boots the shop
 * and calls Translate::getModuleTranslation exactly as smartyTranslate and
 * Module::l() do, then checks the answer is the value data/ intended. A wrong
 * key shows up as the English source coming back.
 *
 * One language per process on purpose: PS 1.6 caches a module's translation
 * file once per request ($translations_merged is keyed by module name alone,
 * without the language), so a loop over languages inside one process would
 * test the first language five times over. The caching is harmless in
 * production - a request only ever renders one language - but it makes an
 * in-process loop lie.
 */
$iso  = isset($argv[1]) ? $argv[1] : 'fr';
$root = isset($argv[2]) ? rtrim(str_replace('\\', '/', $argv[2]), '/') : 'C:/xampp/7.0.33/htdocs/p16124';

$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';
require $root . '/config/config.inc.php';

$strings = require __DIR__ . '/extract.php';

function amzproI18nNormalise($s)
{
    return preg_replace("/\\\\*'/", "\\'", $s);
}

// What we meant to say, straight from the data files.
$want = array();
foreach (require __DIR__ . '/data/all.php' as $en => $row) {
    $want[amzproI18nNormalise($en)] = $row;
}

// PS 9 falls back to the Symfony catalogue whenever the module lookup returns
// the source unchanged - which happens legitimately for the few strings that
// read the same in every language. That fallback needs a real locale, so give
// the stand-in language one; without it PS 9 throws "Invalid locale" before it
// can report anything useful. The language need not be installed: the lookup
// only reads iso_code to pick the file.
$locales = array('fr' => 'fr-FR', 'es' => 'es-ES', 'de' => 'de-DE', 'it' => 'it-IT', 'pl' => 'pl-PL');
$lang = new Language();
$lang->iso_code = $iso;
$lang->locale = isset($locales[$iso]) ? $locales[$iso] : 'en-US';
$lang->id = 1;
Context::getContext()->language = $lang;

$unresolved = array();
$wrong = array();
$placeholder = array();
$n = 0;

foreach ($strings as $file => $list) {
    $source = amzproI18nSource($file);
    foreach ($list as $s) {
        $n++;
        $key = amzproI18nNormalise($s);
        if (!isset($want[$key][$iso])) {
            $unresolved[] = "[$source] no $iso data for: " . substr($s, 0, 60);
            continue;
        }
        $expected = $want[$key][$iso];
        $got = html_entity_decode(
            Translate::getModuleTranslation('amazonmarketplacepro', $s, $source),
            ENT_QUOTES,
            'UTF-8'
        );
        if ($got !== $expected) {
            if ($got === str_replace("\\'", "'", $s) || $got === $s) {
                $unresolved[] = "[$source] " . substr($s, 0, 70);
            } else {
                $wrong[] = "[$source] " . substr($s, 0, 45)
                    . "\n      want: " . substr($expected, 0, 60)
                    . "\n      got:  " . substr($got, 0, 60);
            }
            continue;
        }
        // Any printf placeholder in the source must survive translation.
        preg_match_all('/%[0-9]*\$?[a-z]/', $s, $a);
        preg_match_all('/%[0-9]*\$?[a-z]/', $got, $b);
        sort($a[0]);
        sort($b[0]);
        if ($a[0] !== $b[0]) {
            $placeholder[] = substr($s, 0, 60);
        }
    }
}

printf(
    "%s: %d lookups, %d unresolved, %d mismatched, %d placeholder issues\n",
    strtoupper($iso),
    $n,
    count($unresolved),
    count($wrong),
    count($placeholder)
);
foreach (array_slice($unresolved, 0, 10) as $e) {
    echo "   UNRESOLVED $e\n";
}
foreach (array_slice($wrong, 0, 10) as $w) {
    echo "   MISMATCH $w\n";
}
foreach ($placeholder as $p) {
    echo "   PLACEHOLDER $p\n";
}
exit(($unresolved || $wrong || $placeholder) ? 1 : 0);
