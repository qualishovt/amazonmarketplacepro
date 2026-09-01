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
 * 1.3.0 — seeds the settings introduced after 1.2.0.
 *
 * install() only ever runs on a fresh install, so an upgraded shop would be
 * left with unset keys. That is not merely cosmetic: Configuration::get()
 * answers false for a missing key, and false compares differently in PHP
 * (=== '0') than in a Smarty template (== '0'), so the settings page could
 * show the opposite of what the exporter actually does. Writing explicit
 * defaults removes the ambiguity.
 *
 * Tables added in the same period create themselves on first use
 * (AmazonRemoteCart::ensureTable, AmazonProductOverride::ensureTable and the
 * ALTERs in AmazonProductSync/AmazonFeedManager), so no DDL is needed here.
 */
function upgrade_module_1_3_0($module)
{
    $defaults = array(
        // Listing refinements
        'AMZPRO_ROUNDING' => 'cents',
        'AMZPRO_SEND_SALE_PRICE' => '0',
        'AMZPRO_SEND_LIST_PRICE' => '0',
        'AMZPRO_PREORDER' => '0',
        'AMZPRO_TITLE_FORMAT' => 'name',
        'AMZPRO_CONDITION_MAP' => '{"new":"new_new","used":"used_good","refurbished":"refurbished_refurbished"}',

        // Order handling
        'AMZPRO_ORDER_MATCH' => 'reference',
        'AMZPRO_CARRIER_MAP_IN' => '',
        'AMZPRO_STATUS_RULES' => '',
        'AMZPRO_INVOICE_EMAIL' => '0',
        'AMZPRO_INVOICE_EMAIL_STATE' => '0',
        'AMZPRO_INVOICE_ATTACHMENT' => '',

        // Remote Cart
        'AMZPRO_REMOTE_CART' => '0',
        'AMZPRO_REMOTE_CART_TTL' => '4',

        // Feed composition
        'AMZPRO_SEND_IMAGES' => '1',
        'AMZPRO_EXTENDED_DATA' => '1',
        'AMZPRO_FULL_CATALOG' => '0',

        // Amazon Business + fulfilment
        'AMZPRO_BUSINESS_GROUP' => '0',
        'AMZPRO_FBA_CHANNEL_CODE' => '',

        // Inbound buyer messages
        'AMZPRO_IMAP_ENABLED' => '0',
        'AMZPRO_IMAP_HOST' => '',
        'AMZPRO_IMAP_PORT' => '993',
        'AMZPRO_IMAP_USER' => '',
        'AMZPRO_IMAP_PASSWORD' => '',
        'AMZPRO_IMAP_FOLDER' => 'INBOX',
        'AMZPRO_IMAP_SSL' => '1',
    );

    foreach ($defaults as $key => $value) {
        // Only fill the blanks — never overwrite a merchant's choice.
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $value);
        }
    }

    return true;
}
