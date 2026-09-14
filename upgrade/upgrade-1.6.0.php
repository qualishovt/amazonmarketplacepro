<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.6.0 - multistore.
 *
 * Every module table gets a shop column (see AmzproShop). Existing rows go to
 * the shop they belong to: staged orders to the shop of their PrestaShop
 * order, returns and fees to the shop of their Amazon order, everything else
 * that belongs to a shop to the default shop. Rules, profiles, category
 * mappings and overrides stay shared by all shops.
 *
 * The existing Amazon connection stays with the default shop, which reads
 * the values stored for all shops; the other shops start unconnected and
 * each connects its own seller account. Each of them also gets its own cron
 * token, so the default shop's crontab line keeps working unchanged.
 *
 * On a single-shop install none of this changes behaviour.
 */
function upgrade_module_1_6_0($module)
{
    require_once dirname(__FILE__) . '/../classes/AmzproShop.php';
    require_once dirname(__FILE__) . '/../classes/AmazonScheduler.php';

    if (!AmzproShop::ensureSchema()) {
        return false;
    }

    // The scheduler used to keep the shop's cron address under
    // AMZPRO_RELAY_URL, the key the Amazon client reads its token relay
    // from, so while a shop was registered its token requests went to its own
    // cron URL. Move those values, in whatever scope they were saved, to
    // their own key.
    Db::getInstance()->execute(
        'UPDATE `' . _DB_PREFIX_ . 'configuration`
         SET `name` = \'AMZPRO_RELAY_CRON_URL\'
         WHERE `name` = \'AMZPRO_RELAY_URL\'
           AND `value` LIKE \'%amazonmarketplacepro%\''
    );
    Configuration::loadConfiguration();

    // Install-wide switches are now read for all shops only. One that an older
    // version saved while a shop was selected moves up, the default shop's
    // value first.
    foreach (AmzproShop::$globalKeys as $key) {
        if ((string) Configuration::getGlobalValue($key) !== '') {
            continue;
        }
        $value = Db::getInstance()->getValue(
            'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration`
             WHERE `name` = \'' . pSQL($key) . '\' AND `value` <> \'\' AND `id_shop` IS NOT NULL
             ORDER BY (`id_shop` = ' . (int) AmzproShop::defaultShopId() . ') DESC, `id_shop` ASC'
        );
        if ($value !== false && $value !== null) {
            Configuration::updateGlobalValue($key, $value);
        }
    }

    foreach (AmzproShop::shopIds() as $idShop) {
        AmazonScheduler::seedDefaults($idShop);
        if ($idShop !== AmzproShop::defaultShopId() && (string) AmzproShop::get('AMZPRO_CRON_TOKEN', $idShop) === '') {
            AmzproShop::set('AMZPRO_CRON_TOKEN', AmazonMarketplacePro::newCronToken(), $idShop);
        }
    }

    // New shops get their schedule and token when they are created.
    $module->registerHook('actionShopDataDuplication');
    // Registered by an early version, with no method behind it.
    if ($module->isRegisteredInHook('displayBackOfficeHeader')) {
        $module->unregisterHook('displayBackOfficeHeader');
    }

    return true;
}
