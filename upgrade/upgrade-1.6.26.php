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
 * 1.6.26 - customer groups for Amazon Business buyers and deliveries abroad,
 * and the order's carrier line. Orders created before get their missing
 * order_carrier row (the "Shipping" tab showed no carrier).
 */
function upgrade_module_1_6_26($module)
{
    require_once dirname(__FILE__) . '/../classes/AmazonOrderCreator.php';
    foreach (['AMZPRO_GROUP_BUSINESS', 'AMZPRO_GROUP_ABROAD', 'AMZPRO_GROUP_BUSINESS_ABROAD'] as $key) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, '0');
        }
    }

    $ids = Db::getInstance()->executeS('SELECT `id_order` FROM `' . _DB_PREFIX_ . 'orders`
        WHERE `module` = \'' . pSQL($module->name) . '\'');
    foreach ((array) $ids as $row) {
        AmazonOrderCreator::ensureOrderCarrier((int) $row['id_order']);
    }

    return true;
}
