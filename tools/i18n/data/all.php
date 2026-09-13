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
 * The translation data, as one array: english => array(iso => translation).
 *
 * The chunks are listed literally rather than globbed. It keeps the include
 * paths constant - the security scan rejects a require() from a variable, and
 * rightly so - and it makes a chunk that was added but never wired up show up
 * as a missing translation at build time instead of silently disappearing.
 *
 * Adding a chunk file means adding a line here.
 */
$amzproI18nChunks = array(
    require __DIR__ . '/chunk01.php',
    require __DIR__ . '/chunk02.php',
    require __DIR__ . '/chunk03.php',
    require __DIR__ . '/chunk04.php',
    require __DIR__ . '/chunk05.php',
    require __DIR__ . '/chunk06.php',
    require __DIR__ . '/chunk07.php',
    require __DIR__ . '/chunk08.php',
    require __DIR__ . '/chunk09.php',
    require __DIR__ . '/chunk10.php',
    require __DIR__ . '/chunk11.php',
    require __DIR__ . '/chunk12.php',
    require __DIR__ . '/chunk13.php',
);

$amzproI18nAll = array();
foreach ($amzproI18nChunks as $i => $chunk) {
    foreach ($chunk as $en => $row) {
        if (isset($amzproI18nAll[$en])) {
            fwrite(STDERR, sprintf(
                "i18n: duplicate entry in chunk%02d.php: %s\n",
                $i + 1,
                substr($en, 0, 60)
            ));
            exit(1);
        }
        $amzproI18nAll[$en] = $row;
    }
}

return $amzproI18nAll;
