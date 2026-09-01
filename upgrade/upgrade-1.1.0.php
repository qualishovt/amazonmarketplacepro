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
 * 1.1.0 — markup/delay/GPSR rules per category/manufacturer/supplier,
 * per-product sync rules, change queue, shipping template ranges,
 * export filters, sync modes and extended order-import options.
 */
function upgrade_module_1_1_0($module)
{
    $sql = array();

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting` (
        `id_amazonmarketplacepro_entity_setting` INT(11) NOT NULL AUTO_INCREMENT,
        `entity_type` VARCHAR(16) NOT NULL,
        `id_entity` INT(11) NOT NULL,
        `price_markup` VARCHAR(16) NOT NULL DEFAULT \'\',
        `shipping_delay` INT(11) NOT NULL DEFAULT -1,
        `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
        `country_of_origin` VARCHAR(4) NOT NULL DEFAULT \'\',
        `sync` TINYINT(1) NOT NULL DEFAULT 1,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_amazonmarketplacepro_entity_setting`),
        UNIQUE KEY `entity` (`entity_type`, `id_entity`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` (
        `id_amazonmarketplacepro_product_setting` INT(11) NOT NULL AUTO_INCREMENT,
        `id_product` INT(11) NOT NULL,
        `sync` TINYINT(1) NOT NULL DEFAULT 1,
        `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_amazonmarketplacepro_product_setting`),
        UNIQUE KEY `id_product` (`id_product`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` (
        `id_amazonmarketplacepro_queue` INT(11) NOT NULL AUTO_INCREMENT,
        `id_product` INT(11) NOT NULL,
        `reason` VARCHAR(128) NOT NULL DEFAULT \'\',
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_amazonmarketplacepro_queue`),
        UNIQUE KEY `id_product` (`id_product`),
        KEY `active` (`active`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template` (
        `id_amazonmarketplacepro_shipping_template` INT(11) NOT NULL AUTO_INCREMENT,
        `basis` VARCHAR(16) NOT NULL DEFAULT \'price\',
        `min_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
        `max_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
        `template_name` VARCHAR(128) NOT NULL DEFAULT \'\',
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_amazonmarketplacepro_shipping_template`),
        KEY `basis` (`basis`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }

    $defaults = array(
        'AMZPRO_SKU_PREFIX' => '',
        'AMZPRO_SKU_SOURCE' => 'reference',
        'AMZPRO_PRICE_MIN' => '0',
        'AMZPRO_PRICE_MAX' => '0',
        'AMZPRO_QTY_MIN' => '0',
        'AMZPRO_USE_SPECIFIC_PRICES' => '0',
        'AMZPRO_SPECIFIC_PRICE_GROUP' => '0',
        'AMZPRO_REPORT_EMAIL' => '',
        'AMZPRO_DEFAULT_MARKUP' => '',
        'AMZPRO_MARKUP_SOURCES' => 'category,manufacturer,supplier',
        'AMZPRO_DEFAULT_DELAY' => '0',
        'AMZPRO_DELAY_SOURCES' => 'category,manufacturer,supplier',
        'AMZPRO_GPSR_PRIORITY' => 'manufacturer',
        'AMZPRO_SYNC_MODE' => 'normal',
        'AMZPRO_FORCE_ZERO_QTY' => '0',
        'AMZPRO_ONLY_WITH_ASIN' => '0',
        'AMZPRO_EXPORT_LIMIT' => '0',
        'AMZPRO_EAN_AS' => 'EAN',
        'AMZPRO_DELTA_HOURS' => '0',
        'AMZPRO_COND_NOTE_USED' => '',
        'AMZPRO_COND_NOTE_REFURB' => '',
        'AMZPRO_QUEUE_TTL_DAYS' => '7',
        'AMZPRO_SHIP_TPL_ENABLED' => '0',
        'AMZPRO_SHIP_TPL_BASIS' => 'price',
        'AMZPRO_ORDER_LOOKBACK_VALUE' => '7',
        'AMZPRO_ORDER_LOOKBACK_UNIT' => 'days',
        'AMZPRO_IMPORT_FBA_ORDERS' => '1',
        'AMZPRO_FBA_ORDER_STATE' => '0',
        'AMZPRO_ORDER_STATE_SHIPPED' => '0',
        'AMZPRO_PRIORITIZE_ASIN' => '0',
        'AMZPRO_FAKE_EMAIL' => '0',
        'AMZPRO_CUSTOMER_GROUP' => '0',
        'AMZPRO_SKIP_NO_STOCK' => '0',
    );
    foreach ($defaults as $key => $value) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $value);
        }
    }

    return true;
}
