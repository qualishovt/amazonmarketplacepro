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
 * The shop's half of the IntelliPresta scheduler.
 *
 * There are two ways the schedule can be driven, and the difference is only
 * ever who calls the run_due endpoint:
 *
 *   cron    the merchant's own crontab. The default, and the one we recommend,
 *           because nothing sits between the shop and its schedule.
 *   relay   intellipresta.com calls it every five minutes. For hosting with no
 *           crontab, and for merchants who would rather not keep one.
 *
 * Registering sends the relay two things: where to call, and the cron token to
 * call it with. That token is what makes a run possible at all, so it is worth
 * being plain about the consequence - in relay mode a second party holds a
 * credential for this shop, and the merchant is told so on the screen where
 * they choose it. Deregistering removes it.
 *
 * The relay proves the shop asked, rather than taking its word: it calls back
 * with a nonce before storing anything. That stops anyone registering someone
 * else's shop, and stops the relay being talked into calling arbitrary hosts.
 *
 * With multistore, a registration belongs to one shop: each shop registers
 * its own address and token, and keeps its own registration state (the
 * AMZPRO_RELAY_* keys are its own, see AmzproShop::$ownKeys). The relay tells
 * registrations apart by address, so it needs to know nothing about shops.
 * The registered address lives under AMZPRO_RELAY_CRON_URL; AMZPRO_RELAY_URL
 * is the Amazon token relay and is never written here.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonRelaySchedule
{
    /** Where the scheduler lives. */
    public static $ENDPOINT = 'https://intellipresta.com/spapi/schedule.php';

    /** Long enough for a shared host to answer, short enough not to hang the
     *  back office if the relay is down. */
    public static $TIMEOUT = 15;

    /**
     * @param int|null $idShop default: the shop the request acts for
     *
     * @return string 'cron' or 'relay'
     */
    public static function mode($idShop = null)
    {
        $mode = AmzproShop::get('AMZPRO_CRON_MODE', $idShop);

        return $mode === 'relay' ? 'relay' : 'cron';
    }

    /**
     * Save which of the two drives the schedule.
     *
     * @param string $mode 'cron' or 'relay'
     * @param int|null $idShop default: the shop the request acts for
     *
     * @return bool
     */
    public static function setMode($mode, $idShop = null)
    {
        return AmzproShop::set('AMZPRO_CRON_MODE', $mode === 'relay' ? 'relay' : 'cron', $idShop);
    }

    /**
     * What the shop believes about its registration.
     *
     * Read from local configuration rather than asked of the relay: this is
     * called on every render of the Automation screen, and a screen that
     * cannot draw when the relay is unreachable would be a poor trade.
     *
     * @param int|null $idShop default: the shop the request acts for
     */
    public static function status($idShop = null)
    {
        $idShop = self::shopId($idShop);

        return [
            'registered' => (bool) AmzproShop::get('AMZPRO_RELAY_REGISTERED', $idShop),
            'since' => AmzproShop::get('AMZPRO_RELAY_SINCE', $idShop),
            'url' => AmzproShop::get('AMZPRO_RELAY_CRON_URL', $idShop),
            'last_error' => AmzproShop::get('AMZPRO_RELAY_ERROR', $idShop),
        ];
    }

    /**
     * The address the relay will call: the shop's own, so that each shop of
     * a multistore install registers its own URL with its own token.
     *
     * @param int|null $idShop default: the shop the request acts for
     */
    public static function cronUrl($idShop = null)
    {
        $idShop = self::shopId($idShop);
        $link = AmzproShop::context()->link;
        if (!$link) {
            $link = new Link();
        }

        return $link->getModuleLink('amazonmarketplacepro', 'cron', [], true, null, $idShop);
    }

    /**
     * Ask the relay to start calling this shop.
     *
     * @return array success flag and a message fit to show a merchant
     */
    public static function register()
    {
        $idShop = self::shopId();
        $token = AmzproShop::get('AMZPRO_CRON_TOKEN', $idShop);
        if (!$token) {
            return self::refuse(AmazonI18n::get()->l('This shop has no cron token yet. Save the settings once and try again.', 'amazonrelayschedule'), $idShop);
        }

        $url = self::cronUrl($idShop);
        if (Tools::substr($url, 0, 8) !== 'https://') {
            // The token would otherwise cross the network in clear text on
            // every call, several hundred times a day.
            return self::refuse(sprintf(
                AmazonI18n::get()->l('The scheduler needs the shop to be reachable over HTTPS, and this shop\'s address is %s. Enable SSL in Shop Parameters > General, then register again.', 'amazonrelayschedule'),
                $url
            ), $idShop);
        }

        $module = Module::getInstanceByName('amazonmarketplacepro');

        $reply = self::call([
            'action' => 'register',
            'cron_url' => $url,
            'token' => $token,
            'shop_name' => Configuration::get('PS_SHOP_NAME', null, AmzproShop::groupId($idShop), $idShop),
            'seller_id' => AmzproShop::get('AMZPRO_SELLER_ID', $idShop),
            'module_version' => $module ? (string) $module->version : '',
        ]);

        if (empty($reply['success'])) {
            AmzproShop::set('AMZPRO_RELAY_ERROR', isset($reply['error']) ? $reply['error'] : AmazonI18n::get()->l('Unknown error', 'amazonrelayschedule'), $idShop);

            return $reply;
        }

        AmzproShop::set('AMZPRO_RELAY_REGISTERED', 1, $idShop);
        AmzproShop::set('AMZPRO_RELAY_SINCE', date('Y-m-d H:i:s'), $idShop);
        AmzproShop::set('AMZPRO_RELAY_CRON_URL', $url, $idShop);
        AmzproShop::set('AMZPRO_RELAY_ERROR', '', $idShop);

        return $reply;
    }

    /**
     * A refusal that never reached the relay. Recorded the same way as one
     * that did, so the reason stays on the screen next to the button rather
     * than only in a notice the merchant may not be looking at.
     */
    private static function refuse($why, $idShop)
    {
        AmzproShop::set('AMZPRO_RELAY_ERROR', $why, $idShop);

        return ['success' => false, 'error' => $why];
    }

    /** Ask the relay to stop, and forget the registration locally either way. */
    public static function unregister()
    {
        $idShop = self::shopId();

        // The address that was registered, which is what the relay knows the
        // shop by. Rebuilt only when none was stored.
        $url = (string) AmzproShop::get('AMZPRO_RELAY_CRON_URL', $idShop);
        if ($url === '') {
            $url = self::cronUrl($idShop);
        }

        $reply = self::call([
            'action' => 'unregister',
            'cron_url' => $url,
            'token' => AmzproShop::get('AMZPRO_CRON_TOKEN', $idShop),
        ]);

        // Local state is cleared even if the relay could not be reached. A
        // merchant who has switched away should not be left looking at a screen
        // that still says they are registered; the relay drops shops that stop
        // answering anyway.
        AmzproShop::set('AMZPRO_RELAY_REGISTERED', 0, $idShop);
        AmzproShop::set('AMZPRO_RELAY_SINCE', '', $idShop);
        AmzproShop::set('AMZPRO_RELAY_CRON_URL', '', $idShop);

        return $reply;
    }

    /**
     * The shop a registration belongs to. Never 0: relay changes are refused
     * in "All shops" before they get here.
     */
    protected static function shopId($idShop = null)
    {
        return ($idShop === null) ? (int) AmzproShop::actingId() : (int) $idShop;
    }

    /** One POST to the relay, returning its decoded reply. */
    protected static function call(array $fields)
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => AmazonI18n::get()->l('This server cannot reach the scheduler because the PHP cURL extension is missing. Ask your hosting provider to enable it.', 'amazonrelayschedule')];
        }

        $ch = curl_init(self::$ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return ['success' => false, 'error' => sprintf(AmazonI18n::get()->l('Could not reach the scheduler: %s', 'amazonrelayschedule'), $error)];
        }

        $reply = json_decode($body, true);
        if (!is_array($reply)) {
            return ['success' => false, 'error' => sprintf(AmazonI18n::get()->l('The scheduler answered with HTTP %d.', 'amazonrelayschedule'), $code)];
        }

        return $reply;
    }
}
