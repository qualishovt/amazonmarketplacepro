<?php
/**
 * 2007-2026 PrestaShop
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` (
    `id_amazonmarketplacepro_order` INT(11) NOT NULL AUTO_INCREMENT,
    `amazon_order_id` VARCHAR(64) NOT NULL,
    `purchase_date` DATETIME NULL,
    `order_status` VARCHAR(64) NOT NULL DEFAULT \'\',
    `order_total` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
    `buyer_email` VARCHAR(255) NOT NULL DEFAULT \'\',
    `buyer_name` VARCHAR(255) NOT NULL DEFAULT \'\',
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `fulfillment_channel` VARCHAR(16) NOT NULL DEFAULT \'MFN\',
    `is_prime` TINYINT(1) NOT NULL DEFAULT 0,
    `shipping_total` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `shipping_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `order_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `amazon_fees` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `ship_address1` VARCHAR(255) NOT NULL DEFAULT \'\',
    `ship_address2` VARCHAR(255) NOT NULL DEFAULT \'\',
    `ship_city` VARCHAR(128) NOT NULL DEFAULT \'\',
    `ship_state` VARCHAR(64) NOT NULL DEFAULT \'\',
    `ship_postal_code` VARCHAR(20) NOT NULL DEFAULT \'\',
    `ship_country_code` VARCHAR(8) NOT NULL DEFAULT \'\',
    `ship_phone` VARCHAR(32) NOT NULL DEFAULT \'\',
    `items_matched` INT(11) NOT NULL DEFAULT 0,
    `items_unmatched` INT(11) NOT NULL DEFAULT 0,
    `raw_json` LONGTEXT NULL,
    `id_order` INT(11) NOT NULL DEFAULT 0,
    `import_status` VARCHAR(32) NOT NULL DEFAULT \'imported\',
    `review_requested` TINYINT(1) NOT NULL DEFAULT 0,
    `vcs_uploaded` TINYINT(1) NOT NULL DEFAULT 0,
    `pii_purged_at` DATETIME NULL DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_order`),
    UNIQUE KEY `amazon_order_id` (`amazon_order_id`),
    KEY `import_status` (`import_status`),
    KEY `id_order` (`id_order`),
    KEY `fulfillment_channel` (`fulfillment_channel`),
    KEY `pii_purged_at` (`pii_purged_at`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` (
    `id_amazonmarketplacepro_order_item` INT(11) NOT NULL AUTO_INCREMENT,
    `id_amazonmarketplacepro_order` INT(11) NOT NULL,
    `amazon_order_id` VARCHAR(64) NOT NULL,
    `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
    `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
    `title` VARCHAR(512) NOT NULL DEFAULT \'\',
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `item_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `item_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `shipping_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `shipping_tax` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `promotion_discount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
    `id_product` INT(11) NOT NULL DEFAULT 0,
    `id_product_attribute` INT(11) NOT NULL DEFAULT 0,
    `match_status` VARCHAR(16) NOT NULL DEFAULT \'unmatched\',
    PRIMARY KEY (`id_amazonmarketplacepro_order_item`),
    KEY `id_amazonmarketplacepro_order` (`id_amazonmarketplacepro_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` (
    `id_amazonmarketplacepro_product` INT(11) NOT NULL AUTO_INCREMENT,
    `seller_sku` VARCHAR(255) NOT NULL,
    `id_product` INT(11) NOT NULL DEFAULT 0,
    `id_product_attribute` INT(11) NOT NULL DEFAULT 0,
    `ps_exists` TINYINT(1) NOT NULL DEFAULT 0,
    `ps_name` VARCHAR(255) NOT NULL DEFAULT \'\',
    `ps_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `ps_quantity` INT(11) NOT NULL DEFAULT 0,
    `ps_description` TEXT NULL,
    `ps_description_short` TEXT NULL,
    `ps_manufacturer` VARCHAR(128) NOT NULL DEFAULT \'\',
    `ps_ean13` VARCHAR(16) NOT NULL DEFAULT \'\',
    `ps_id_category_default` INT(11) NOT NULL DEFAULT 0,
    `ps_images` TEXT NULL,
    `amazon_exists` TINYINT(1) NOT NULL DEFAULT 0,
    `amazon_asin` VARCHAR(32) NOT NULL DEFAULT \'\',
    `amazon_title` VARCHAR(512) NOT NULL DEFAULT \'\',
    `amazon_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `amazon_quantity` INT(11) NOT NULL DEFAULT 0,
    `amazon_status` VARCHAR(64) NOT NULL DEFAULT \'\',
    `amazon_description` TEXT NULL,
    `amazon_bullet_points` TEXT NULL,
    `amazon_brand` VARCHAR(128) NOT NULL DEFAULT \'\',
    `amazon_images` TEXT NULL,
    `amazon_product_type` VARCHAR(128) NOT NULL DEFAULT \'\',
    `amazon_browse_node` VARCHAR(32) NOT NULL DEFAULT \'\',
    `sync_direction` VARCHAR(32) NOT NULL DEFAULT \'\',
    `parent_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
    `is_parent` TINYINT(1) NOT NULL DEFAULT 0,
    `variation_theme` VARCHAR(64) NOT NULL DEFAULT \'\',
    `variation_attributes` TEXT NULL,
    `raw_amazon_json` LONGTEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_product`),
    UNIQUE KEY `seller_sku` (`seller_sku`),
    KEY `sync_direction` (`sync_direction`),
    KEY `parent_sku` (`parent_sku`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_return` (
    `id_amazonmarketplacepro_return` INT(11) NOT NULL AUTO_INCREMENT,
    `amazon_order_id` VARCHAR(64) NOT NULL,
    `amazon_return_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
    `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
    `title` VARCHAR(512) NOT NULL DEFAULT \'\',
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `reason` VARCHAR(255) NOT NULL DEFAULT \'\',
    `status` VARCHAR(64) NOT NULL DEFAULT \'\',
    `refund_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
    `id_order` INT(11) NOT NULL DEFAULT 0,
    `id_order_slip` INT(11) NOT NULL DEFAULT 0,
    `return_status` VARCHAR(32) NOT NULL DEFAULT \'imported\',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_return`),
    KEY `amazon_order_id` (`amazon_order_id`),
    KEY `return_status` (`return_status`),
    KEY `id_order` (`id_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map` (
    `id_amazonmarketplacepro_category_map` INT(11) NOT NULL AUTO_INCREMENT,
    `id_category` INT(11) NOT NULL,
    `amazon_product_type` VARCHAR(128) NOT NULL DEFAULT \'\',
    `amazon_browse_node` VARCHAR(32) NOT NULL DEFAULT \'\',
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `attributes_json` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_category_map`),
    UNIQUE KEY `cat_marketplace` (`id_category`, `marketplace_id`),
    KEY `id_category` (`id_category`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory` (
    `id_amazonmarketplacepro_fba` INT(11) NOT NULL AUTO_INCREMENT,
    `seller_sku` VARCHAR(255) NOT NULL,
    `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
    `fn_sku` VARCHAR(64) NOT NULL DEFAULT \'\',
    `product_name` VARCHAR(512) NOT NULL DEFAULT \'\',
    `fulfillable_qty` INT(11) NOT NULL DEFAULT 0,
    `inbound_working_qty` INT(11) NOT NULL DEFAULT 0,
    `inbound_shipped_qty` INT(11) NOT NULL DEFAULT 0,
    `inbound_receiving_qty` INT(11) NOT NULL DEFAULT 0,
    `reserved_qty` INT(11) NOT NULL DEFAULT 0,
    `unfulfillable_qty` INT(11) NOT NULL DEFAULT 0,
    `total_qty` INT(11) NOT NULL DEFAULT 0,
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `id_product` INT(11) NOT NULL DEFAULT 0,
    `id_product_attribute` INT(11) NOT NULL DEFAULT 0,
    `last_synced` DATETIME NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_fba`),
    UNIQUE KEY `sku_marketplace` (`seller_sku`, `marketplace_id`),
    KEY `asin` (`asin`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule` (
    `id_amazonmarketplacepro_pricing_rule` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(128) NOT NULL DEFAULT \'\',
    `rule_type` VARCHAR(32) NOT NULL DEFAULT \'match_lowest\',
    `price_adjustment` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `adjustment_type` VARCHAR(16) NOT NULL DEFAULT \'percentage\',
    `min_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `max_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `target_buybox` TINYINT(1) NOT NULL DEFAULT 0,
    `id_category` INT(11) NOT NULL DEFAULT 0,
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_pricing_rule`),
    KEY `active` (`active`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` (
    `id_amazonmarketplacepro_cp` INT(11) NOT NULL AUTO_INCREMENT,
    `seller_sku` VARCHAR(255) NOT NULL,
    `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
    `buybox_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `buybox_shipping` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `buybox_landed` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `buybox_seller` VARCHAR(64) NOT NULL DEFAULT \'\',
    `is_buybox_winner` TINYINT(1) NOT NULL DEFAULT 0,
    `lowest_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `lowest_shipping` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `lowest_landed` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `number_of_offers` INT(11) NOT NULL DEFAULT 0,
    `our_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `suggested_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `last_repriced` DATETIME NULL,
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_cp`),
    UNIQUE KEY `sku_marketplace` (`seller_sku`, `marketplace_id`),
    KEY `asin` (`asin`),
    KEY `is_buybox_winner` (`is_buybox_winner`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_fee` (
    `id_amazonmarketplacepro_order_fee` INT(11) NOT NULL AUTO_INCREMENT,
    `amazon_order_id` VARCHAR(64) NOT NULL,
    `fee_type` VARCHAR(64) NOT NULL DEFAULT \'\',
    `fee_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `currency` VARCHAR(8) NOT NULL DEFAULT \'\',
    `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_order_fee`),
    KEY `amazon_order_id` (`amazon_order_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_report` (
    `id_amazonmarketplacepro_report` INT(11) NOT NULL AUTO_INCREMENT,
    `report_id` VARCHAR(128) NOT NULL DEFAULT \'\',
    `report_document_id` VARCHAR(128) NOT NULL DEFAULT \'\',
    `report_type` VARCHAR(128) NOT NULL DEFAULT \'\',
    `status` VARCHAR(32) NOT NULL DEFAULT \'requested\',
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `data_start_time` DATETIME NULL,
    `data_end_time` DATETIME NULL,
    `row_count` INT(11) NOT NULL DEFAULT 0,
    `file_path` VARCHAR(512) NOT NULL DEFAULT \'\',
    `error_message` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_report`),
    KEY `report_type` (`report_type`),
    KEY `status` (`status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config` (
    `id_amazonmarketplacepro_mp` INT(11) NOT NULL AUTO_INCREMENT,
    `marketplace_id` VARCHAR(32) NOT NULL,
    `marketplace_name` VARCHAR(128) NOT NULL DEFAULT \'\',
    `client_id` VARCHAR(255) NOT NULL DEFAULT \'\',
    `client_secret` VARCHAR(255) NOT NULL DEFAULT \'\',
    `refresh_token` VARCHAR(512) NOT NULL DEFAULT \'\',
    `seller_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `default_carrier` INT(11) NOT NULL DEFAULT 0,
    `default_order_state` INT(11) NOT NULL DEFAULT 0,
    `sync_orders` TINYINT(1) NOT NULL DEFAULT 1,
    `sync_products` TINYINT(1) NOT NULL DEFAULT 1,
    `sync_stock` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_mp`),
    UNIQUE KEY `marketplace_id` (`marketplace_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion` (
    `id_amazonmarketplacepro_promotion` INT(11) NOT NULL AUTO_INCREMENT,
    `amazon_promotion_id` VARCHAR(128) NOT NULL DEFAULT \'\',
    `promotion_type` VARCHAR(64) NOT NULL DEFAULT \'\',
    `description` VARCHAR(512) NOT NULL DEFAULT \'\',
    `discount_type` VARCHAR(32) NOT NULL DEFAULT \'percentage\',
    `discount_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT \'active\',
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
    `asin` VARCHAR(32) NOT NULL DEFAULT \'\',
    `id_cart_rule` INT(11) NOT NULL DEFAULT 0,
    `sync_direction` VARCHAR(16) NOT NULL DEFAULT \'amazon_to_ps\',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_promotion`),
    KEY `amazon_promotion_id` (`amazon_promotion_id`),
    KEY `seller_sku` (`seller_sku`),
    KEY `status` (`status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed` (
    `id_amazonmarketplacepro_feed` INT(11) NOT NULL AUTO_INCREMENT,
    `feed_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `feed_type` VARCHAR(64) NOT NULL DEFAULT \'\',
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `processing_status` VARCHAR(32) NOT NULL DEFAULT \'SUBMITTED\',
    `messages_count` INT(11) NOT NULL DEFAULT 0,
    `accepted` INT(11) NOT NULL DEFAULT 0,
    `errors` INT(11) NOT NULL DEFAULT 0,
    `warnings` INT(11) NOT NULL DEFAULT 0,
    `issues_json` TEXT NULL,
    `error_message` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_feed`),
    KEY `processing_status` (`processing_status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting` (
    `id_amazonmarketplacepro_entity_setting` INT(11) NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(16) NOT NULL,
    `id_entity` INT(11) NOT NULL,
    `price_markup` VARCHAR(16) NOT NULL DEFAULT \'\',
    `shipping_delay` INT(11) NOT NULL DEFAULT -1,
    `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
    `country_of_origin` VARCHAR(4) NOT NULL DEFAULT \'\',
    `sync` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_entity_setting`),
    UNIQUE KEY `entity` (`entity_type`, `id_entity`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product_setting` (
    `id_amazonmarketplacepro_product_setting` INT(11) NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) NOT NULL,
    `sync` TINYINT(1) NOT NULL DEFAULT 1,
    `gpsr_contact` VARCHAR(255) NOT NULL DEFAULT \'\',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_product_setting`),
    UNIQUE KEY `id_product` (`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_queue` (
    `id_amazonmarketplacepro_queue` INT(11) NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) NOT NULL,
    `reason` VARCHAR(128) NOT NULL DEFAULT \'\',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_queue`),
    UNIQUE KEY `id_product` (`id_product`),
    KEY `active` (`active`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_shipping_template` (
    `id_amazonmarketplacepro_shipping_template` INT(11) NOT NULL AUTO_INCREMENT,
    `basis` VARCHAR(16) NOT NULL DEFAULT \'price\',
    `min_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `max_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `template_name` VARCHAR(128) NOT NULL DEFAULT \'\',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_shipping_template`),
    KEY `basis` (`basis`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_reservation` (
    `id_amazonmarketplacepro_reservation` INT(11) NOT NULL AUTO_INCREMENT,
    `amazon_order_id` VARCHAR(64) NOT NULL,
    `order_item_id` VARCHAR(64) NOT NULL DEFAULT \'\',
    `seller_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
    `id_product` INT(11) NOT NULL DEFAULT 0,
    `id_product_attribute` INT(11) NOT NULL DEFAULT 0,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `id_shop` INT(11) NOT NULL DEFAULT 1,
    `status` VARCHAR(16) NOT NULL DEFAULT \'reserved\',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_reservation`),
    UNIQUE KEY `order_item` (`amazon_order_id`, `order_item_id`),
    KEY `status` (`status`),
    KEY `id_product` (`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema` (
    `id_amazonmarketplacepro_pt_schema` INT(11) NOT NULL AUTO_INCREMENT,
    `product_type` VARCHAR(128) NOT NULL,
    `marketplace_id` VARCHAR(32) NOT NULL,
    `display_name` VARCHAR(255) NOT NULL DEFAULT \'\',
    `attributes_json` LONGTEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_pt_schema`),
    UNIQUE KEY `type_marketplace` (`product_type`, `marketplace_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile` (
    `id_amazonmarketplacepro_profile` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(128) NOT NULL DEFAULT \'\',
    `product_type` VARCHAR(128) NOT NULL DEFAULT \'\',
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    `browse_nodes` VARCHAR(255) NOT NULL DEFAULT \'\',
    `is_variation` TINYINT(1) NOT NULL DEFAULT 0,
    `variation_attributes` VARCHAR(255) NOT NULL DEFAULT \'\',
    `attributes_json` LONGTEXT NULL,
    `raw_attributes_json` TEXT NULL,
    `latency` INT(11) NOT NULL DEFAULT -1,
    `shipping_template` VARCHAR(128) NOT NULL DEFAULT \'\',
    `price_markup` VARCHAR(16) NOT NULL DEFAULT \'\',
    `gtin_exemption` TINYINT(1) NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_profile`),
    KEY `marketplace_id` (`marketplace_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_profile_category` (
    `id_amazonmarketplacepro_profile_category` INT(11) NOT NULL AUTO_INCREMENT,
    `id_profile` INT(11) NOT NULL,
    `id_category` INT(11) NOT NULL,
    `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
    PRIMARY KEY (`id_amazonmarketplacepro_profile_category`),
    UNIQUE KEY `cat_marketplace` (`id_category`, `marketplace_id`),
    KEY `id_profile` (`id_profile`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_log` (
    `id_amazonmarketplacepro_log` INT(11) NOT NULL AUTO_INCREMENT,
    `level` VARCHAR(16) NOT NULL DEFAULT \'info\',
    `source` VARCHAR(64) NOT NULL DEFAULT \'\',
    `message` TEXT NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_amazonmarketplacepro_log`),
    KEY `level` (`level`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
