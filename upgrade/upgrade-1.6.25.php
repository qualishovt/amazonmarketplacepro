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
 * 1.6.25 - invoices of Amazon orders show their VAT rates and breakdown.
 * Orders created before lacked the per-line tax rows and the shipping tax
 * rate; they are completed here (totals unchanged).
 */
function upgrade_module_1_6_25($module)
{
    require_once dirname(__FILE__) . '/../classes/AmazonOrderCreator.php';
    $module->registerHook('actionSetInvoice');

    $ids = Db::getInstance()->executeS('SELECT `id_order` FROM `' . _DB_PREFIX_ . 'orders`
        WHERE `module` = \'' . pSQL($module->name) . '\'');
    foreach ((array) $ids as $row) {
        AmazonOrderCreator::repairOrderTaxes((int) $row['id_order']);
    }

    return true;
}
