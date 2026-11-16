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
 * 1.6.27 - the Amazon box on the order page (with refunds), the Amazon order
 * number in the order list, and the weight of the order's carrier line.
 * Orders created before get their lines' weight and the carrier line's.
 */
function upgrade_module_1_6_27($module)
{
    require_once dirname(__FILE__) . '/../classes/AmazonOrderCreator.php';
    require_once dirname(__FILE__) . '/../classes/AmazonRefundManager.php';
    $module->registerOrderPageHooks();
    $module->installOrderTab();
    AmazonRefundManager::ensureTable();

    $ids = Db::getInstance()->executeS('SELECT `id_order` FROM `' . _DB_PREFIX_ . 'orders`
        WHERE `module` = \'' . pSQL($module->name) . '\'');
    foreach ((array) $ids as $row) {
        AmazonOrderCreator::ensureOrderCarrier((int) $row['id_order']);
    }

    return true;
}
