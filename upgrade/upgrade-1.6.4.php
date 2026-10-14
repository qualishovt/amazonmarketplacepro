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
 * 1.6.4 - "Prices sent to Amazon: tax included / tax excluded".
 *
 * Amazon's price means the price the buyer pays, VAT included; earlier
 * versions sent PrestaShop's price without tax. New installs send it with
 * tax. A shop that already sells keeps sending it without tax, so no live
 * price changes when the module is updated; the merchant switches when ready.
 */
function upgrade_module_1_6_4($module)
{
    if ((string) Configuration::getGlobalValue('AMZPRO_PRICE_TAX') === '') {
        Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', 'excl');
    }

    return true;
}
