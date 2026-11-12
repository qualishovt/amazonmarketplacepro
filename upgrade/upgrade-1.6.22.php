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
 * 1.6.22 - stored Amazon reports are served only through the back office:
 * the folders they were saved in get their web-access block now.
 */
function upgrade_module_1_6_22($module)
{
    require_once dirname(__FILE__) . '/../classes/AmazonReportManager.php';
    $data = dirname(dirname(__FILE__)) . '/data';
    AmazonReportManager::protectDir($data);
    AmazonReportManager::protectDir($data . '/reports');

    return true;
}
