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
 * 1.6.29 - listings already on Amazon get a partial price and stock update
 * instead of a full replace, prices are rounded once after VAT, and products
 * that cannot be listed are no longer sent. A shop that used to send full data
 * to existing listings with a product type keeps doing so ("full"); new
 * installs start with "price and stock only". Also: an Amazon title,
 * description and manufacturer of its own per product.
 */
function upgrade_module_1_6_29($module)
{
    require_once dirname(__FILE__) . '/../classes/AmazonProductOverride.php';
    if (Configuration::get('AMZPRO_EXISTING_LISTINGS') === false) {
        Configuration::updateGlobalValue('AMZPRO_EXISTING_LISTINGS', 'full');
    }
    AmazonProductOverride::ensureTable();

    return true;
}
