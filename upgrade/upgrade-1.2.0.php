<?php
/**
 * 2007-2026 PrestaShop
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.2.0 — listing profiles driven by Amazon's Product Type Definitions:
 * cached per-marketplace attribute schemas, per-attribute value resolution
 * (PrestaShop field / Amazon allowed value / fixed literal), category
 * bindings, browse nodes and GTIN exemptions.
 */
function upgrade_module_1_2_0($module)
{
    $sql = array();

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema` (
        `id_amazonmarketplacepro_pt_schema` INT(11) NOT NULL AUTO_INCREMENT,
        `product_type` VARCHAR(128) NOT NULL,
        `marketplace_id` VARCHAR(32) NOT NULL,
        `display_name` VARCHAR(255) NOT NULL DEFAULT \'\',
        `attributes_json` LONGTEXT NULL,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_amazonmarketplacepro_pt_schema`),
        UNIQUE KEY `type_marketplace` (`product_type`, `marketplace_id`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` (
        `id_amazonmarketplacepro_profile` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(128) NOT NULL DEFAULT \'\',
        `product_type` VARCHAR(128) NOT NULL DEFAULT \'\',
        `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
        `browse_nodes` VARCHAR(255) NOT NULL DEFAULT \'\',
        `is_variation` TINYINT(1) NOT NULL DEFAULT 0,
        `variation_attributes` VARCHAR(255) NOT NULL DEFAULT \'\',
        `attributes_json` LONGTEXT NULL,
        `raw_attributes_json` TEXT NULL,
        `latency` INT(11) NOT NULL DEFAULT -1,
        `shipping_template` VARCHAR(128) NOT NULL DEFAULT \'\',
        `price_markup` VARCHAR(16) NOT NULL DEFAULT \'\',
        `gtin_exemption` TINYINT(1) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_amazonmarketplacepro_profile`),
        KEY `marketplace_id` (`marketplace_id`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` (
        `id_amazonmarketplacepro_profile_category` INT(11) NOT NULL AUTO_INCREMENT,
        `id_profile` INT(11) NOT NULL,
        `id_category` INT(11) NOT NULL,
        `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
        PRIMARY KEY (`id_amazonmarketplacepro_profile_category`),
        UNIQUE KEY `cat_marketplace` (`id_category`, `marketplace_id`),
        KEY `id_profile` (`id_profile`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }

    return true;
}
