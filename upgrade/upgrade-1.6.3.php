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
 * 1.6.3 - each marketplace keeps its own comparison.
 *
 * The staged product table gets a marketplace column: '' for the shop's main
 * marketplace (every existing row), a marketplace id for one added under
 * Multi-Account. The unique key becomes shop + marketplace + SKU, so a
 * product live on one marketplace is still compared with, and sent to, the
 * others.
 */
function upgrade_module_1_6_3($module)
{
    require_once dirname(__FILE__) . '/../classes/AmzproShop.php';

    return AmzproShop::ensureSchema();
}
