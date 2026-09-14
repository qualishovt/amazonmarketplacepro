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

/*
 * Translation for the module's plain classes.
 *
 * A class has no module of its own to call l() on, so it borrows this one:
 *
 *     AmazonI18n::get()->l('Order not found.', 'amazonfbamanager')
 *
 * The call keeps the ->l('...') shape that PrestaShop's translation screen and
 * tools/i18n/extract.php look for. The second argument must be the lower-case
 * basename of the calling file: PrestaShop puts the file name in the key, so
 * any other value would never be found.
 *
 * PrestaShop HTML-escapes what it returns. These messages travel as JSON or
 * plain values and are escaped where the page shows them, so the escaping is
 * undone here; otherwise a quote would reach the screen as &quot;. Values are
 * put in with sprintf() after this call, never inside the translated text.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonI18n
{
    /** @var AmazonI18n|null */
    private static $instance;

    /** @var Module|false|null null until first use, false when unavailable */
    private $module;

    /** @return AmazonI18n */
    public static function get()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $string English source text
     * @param string $source lower-case basename of the calling file
     *
     * @return string
     */
    public function l($string, $source)
    {
        if ($this->module === null) {
            $module = Module::getInstanceByName('amazonmarketplacepro');
            $this->module = $module ? $module : false;
        }
        if (!$this->module) {
            return $string;
        }

        $text = html_entity_decode($this->module->l($string, $source), ENT_QUOTES, 'UTF-8');

        // PS 1.6 hands the source back with its apostrophes still slashed when
        // no translation file is loaded in the request (English, typically).
        return str_replace("\\'", "'", $text);
    }
}
