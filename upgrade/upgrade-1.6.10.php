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
 * 1.6.10 - the repricer reads the shipping of our own offer, so the
 * competitive price table gets an our_shipping column.
 */
function upgrade_module_1_6_10($module)
{
    require_once dirname(__FILE__) . '/../classes/AmzproShop.php';

    return AmzproShop::ensureSchema();
}
