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

$sql = [];

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amzpro_scheduled_task`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_fee`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`';

// eBay integration — kept together so it can move out with the marketplace.

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
