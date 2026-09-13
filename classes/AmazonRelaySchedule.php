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
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';

class AmazonRelaySchedule
{
    /** Where the scheduler lives. */
    const ENDPOINT = 'https://intellipresta.com/spapi/schedule.php';

    /** Long enough for a shared host to answer, short enough not to hang the
     *  back office if the relay is down. */
    const TIMEOUT = 15;

    /** @return string 'cron' or 'relay' */
    public static function mode()
    {
        $mode = Configuration::get('AMZPRO_CRON_MODE');

        return $mode === 'relay' ? 'relay' : 'cron';
    }

    /**
     * What the shop believes about its registration.
     *
     * Read from local configuration rather than asked of the relay: this is
     * called on every render of the Automation screen, and a screen that
     * cannot draw when the relay is unreachable would be a poor trade.
     */
    public static function status()
    {
        return array(
            'registered' => (bool) Configuration::get('AMZPRO_RELAY_REGISTERED'),
            'since' => Configuration::get('AMZPRO_RELAY_SINCE'),
            'url' => Configuration::get('AMZPRO_RELAY_URL'),
            'last_error' => Configuration::get('AMZPRO_RELAY_ERROR'),
        );
    }

    /** The address the relay will call. */
    public static function cronUrl()
    {
        $link = Context::getContext()->link;

        return $link->getModuleLink('amazonmarketplacepro', 'cron', array(), true);
    }

    /**
     * Ask the relay to start calling this shop.
     *
     * @return array success flag and a message fit to show a merchant
     */
    public static function register()
    {
        $token = Configuration::get('AMZPRO_CRON_TOKEN');
        if (!$token) {
            return self::refuse(AmazonI18n::get()->l('This shop has no cron token yet. Save the settings once and try again.', 'amazonrelayschedule'));
        }

        $url = self::cronUrl();
        if (Tools::substr($url, 0, 8) !== 'https://') {
            // The token would otherwise cross the network in clear text on
            // every call, several hundred times a day.
            return self::refuse(sprintf(
                AmazonI18n::get()->l('The scheduler needs the shop to be reachable over HTTPS, and this shop\'s address is %s. Enable SSL in Shop Parameters > General, then register again.', 'amazonrelayschedule'),
                $url
            ));
        }

        $reply = self::call(array(
            'action' => 'register',
            'cron_url' => $url,
            'token' => $token,
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'seller_id' => Configuration::get('AMZPRO_SELLER_ID'),
            'module_version' => '1.5.0',
        ));

        if (empty($reply['success'])) {
            Configuration::updateValue('AMZPRO_RELAY_ERROR', isset($reply['error']) ? $reply['error'] : AmazonI18n::get()->l('Unknown error', 'amazonrelayschedule'));

            return $reply;
        }

        Configuration::updateValue('AMZPRO_RELAY_REGISTERED', 1);
        Configuration::updateValue('AMZPRO_RELAY_SINCE', date('Y-m-d H:i:s'));
        Configuration::updateValue('AMZPRO_RELAY_URL', $url);
        Configuration::updateValue('AMZPRO_RELAY_ERROR', '');

        return $reply;
    }

    /**
     * A refusal that never reached the relay. Recorded the same way as one
     * that did, so the reason stays on the screen next to the button rather
     * than only in a notice the merchant may not be looking at.
     */
    private static function refuse($why)
    {
        Configuration::updateValue('AMZPRO_RELAY_ERROR', $why);

        return array('success' => false, 'error' => $why);
    }

    /** Ask the relay to stop, and forget the registration locally either way. */
    public static function unregister()
    {
        $reply = self::call(array(
            'action' => 'unregister',
            'cron_url' => self::cronUrl(),
            'token' => Configuration::get('AMZPRO_CRON_TOKEN'),
        ));

        // Local state is cleared even if the relay could not be reached. A
        // merchant who has switched away should not be left looking at a screen
        // that still says they are registered; the relay drops shops that stop
        // answering anyway.
        Configuration::updateValue('AMZPRO_RELAY_REGISTERED', 0);
        Configuration::updateValue('AMZPRO_RELAY_SINCE', '');
        Configuration::updateValue('AMZPRO_RELAY_URL', '');

        return $reply;
    }

    /** One POST to the relay, returning its decoded reply. */
    protected static function call(array $fields)
    {
        if (!function_exists('curl_init')) {
            return array('success' => false, 'error' => AmazonI18n::get()->l('This server cannot reach the scheduler because the PHP cURL extension is missing. Ask your hosting provider to enable it.', 'amazonrelayschedule'));
        }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return array('success' => false, 'error' => sprintf(AmazonI18n::get()->l('Could not reach the scheduler: %s', 'amazonrelayschedule'), $error));
        }

        $reply = json_decode($body, true);
        if (!is_array($reply)) {
            return array('success' => false, 'error' => sprintf(AmazonI18n::get()->l('The scheduler answered with HTTP %d.', 'amazonrelayschedule'), $code));
        }

        return $reply;
    }
}
