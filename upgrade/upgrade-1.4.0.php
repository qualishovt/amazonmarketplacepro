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
 * 1.4.0 - buyer data retention.
 *
 * Amazon's Data Protection Policy caps retention of buyer personal data at 30
 * days after delivery. Until now the module kept its imported copy - name,
 * e-mail, shipping address and the raw Amazon payload - indefinitely. This
 * turns the deletion on for shops upgrading, not only for new installs.
 *
 * It is enabled by default and deliberately so: leaving it off would leave
 * every existing shop out of compliance until someone noticed a new setting.
 * The PrestaShop-side pass stays off, because it rewrites the addresses that
 * invoices are rendered from - see AmazonPiiPurger::purgeShopSide().
 *
 * The pii_purged_at column is added by AmazonPiiPurger::ensureSchema() on
 * first use, so no DDL is needed here.
 */
function upgrade_module_1_4_0($module)
{
    $defaults = [
        'AMZPRO_PII_PURGE' => '1',
        'AMZPRO_PII_RETENTION_DAYS' => '30',
        'AMZPRO_PII_PURGE_PS' => '0',
    ];

    foreach ($defaults as $key => $value) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $value);
        }
    }

    return true;
}
