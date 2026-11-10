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
 * 1.6.20 - shipment confirmations record whether Amazon accepted them, and
 * a tracking number entered after shipping confirms the shipment then.
 */
function upgrade_module_1_6_20($module)
{
    require_once dirname(__FILE__) . '/../classes/AmzproShop.php';

    return AmzproShop::ensureSchema()
        && $module->registerHook('actionAdminOrdersTrackingNumberUpdate');
}
