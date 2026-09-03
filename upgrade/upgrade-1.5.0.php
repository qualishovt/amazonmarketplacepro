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
 * 1.5.0 - the schedule moves into the module.
 *
 * Automation was previously a list of nineteen URLs and the instruction to add
 * them to the server's crontab. That is not something most merchants can do -
 * plenty of shared hosting has no crontab at all - and it made the module's
 * automation only as good as the merchant's sysadmin skills.
 *
 * This creates the schedule table and seeds it with the same intervals the old
 * instructions recommended, so nothing changes behaviour on its own.
 *
 * Every task is seeded switched OFF, and that is deliberate. An upgrade must
 * not quietly start calling Amazon on a shop whose owner had not set up cron
 * and therefore has never had these tasks run. The merchant turns on what they
 * want, which is also the moment they see the schedule exists.
 *
 * Shops that already have per-task crontab lines keep working untouched: the
 * old ?action=<task> endpoints still run their task.
 */
function upgrade_module_1_5_0($module)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amzpro_scheduled_task` (
        `id_task` INT(11) NOT NULL AUTO_INCREMENT,
        `id_shop` INT(11) NOT NULL DEFAULT 1,
        `task_key` VARCHAR(64) NOT NULL,
        `interval_minutes` INT(11) NOT NULL DEFAULT 60,
        `active` TINYINT(1) NOT NULL DEFAULT 0,
        `last_run_at` DATETIME NULL,
        `next_run_at` DATETIME NULL,
        `last_status` VARCHAR(16) NULL,
        `last_message` VARCHAR(500) NULL,
        `last_duration_ms` INT(11) NOT NULL DEFAULT 0,
        `run_count` INT(11) NOT NULL DEFAULT 0,
        `fail_count` INT(11) NOT NULL DEFAULT 0,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_task`),
        KEY `due` (`id_shop`, `active`, `next_run_at`),
        KEY `task_key` (`task_key`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    if (!Db::getInstance()->execute($sql)) {
        return false;
    }

    if (Configuration::get('AMZPRO_CRON_AUTO') === false) {
        // Off on upgrade. A shop that already has crontab entries does not
        // need traffic-triggered runs as well, and doubling them up would be
        // a surprise rather than a feature.
        Configuration::updateValue('AMZPRO_CRON_AUTO', '0');
    }

    require_once dirname(__FILE__) . '/../classes/AmazonScheduler.php';

    // Seed for every shop, not only the one the upgrade happens to run under.
    foreach (Shop::getShops(false, null, true) as $idShop) {
        AmazonScheduler::seedDefaults((int) $idShop);
    }

    return $module->registerHook('actionFrontControllerAfterInit')
        || true;   // the hook is a convenience; its absence must not fail the upgrade
}
