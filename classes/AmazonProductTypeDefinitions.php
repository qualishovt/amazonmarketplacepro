<?php
/**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *
 * Amazon Product Type Definitions (SP-API definitions/2020-09-01).
 *
 * Amazon describes every product type (SHIRT, FASHION_RING, TOY...) with a
 * JSON schema listing the attributes a listing may carry, which of them are
 * required, and — for constrained attributes — the exact set of values it
 * accepts. Merchants cannot be expected to hand-write that, so the module
 * downloads the schema, flattens it into a form definition, and caches it.
 *
 * The cache is keyed by (product type, marketplace) because both the
 * attribute set and the allowed values differ per marketplace.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonProductTypeDefinitions
{
    /** Refetch a cached schema after this many days. */
    const CACHE_TTL_DAYS = 30;

    /** Attributes every listing carries; the profile form never asks for them. */
    private static $handledInternally = array(
        'item_name', 'brand', 'product_description', 'bullet_point',
        'purchasable_offer', 'fulfillment_availability', 'condition_type',
        'condition_note', 'merchant_suggested_asin', 'main_product_image_locator',
        'other_product_image_locator_1', 'other_product_image_locator_2',
        'other_product_image_locator_3', 'other_product_image_locator_4',
        'other_product_image_locator_5', 'externally_assigned_product_identifier',
        'merchant_shipping_group', 'parentage_level', 'child_parent_sku_relationship',
        'variation_theme', 'recommended_browse_nodes', 'supplier_declared_has_product_identifier_exemption',
        'list_price', 'country_of_origin', 'gpsr_manufacturer_email_address',
        'gpsr_manufacturer_reference',
    );

    private $client;
    private $marketplaceId;
    private $lastError = null;

    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function ensureTable()
    {
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema` (
                `id_amazonmarketplacepro_pt_schema` INT(11) NOT NULL AUTO_INCREMENT,
                `product_type` VARCHAR(128) NOT NULL,
                `marketplace_id` VARCHAR(32) NOT NULL,
                `display_name` VARCHAR(255) NOT NULL DEFAULT \'\',
                `attributes_json` LONGTEXT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_pt_schema`),
                UNIQUE KEY `type_marketplace` (`product_type`, `marketplace_id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );
    }

    /**
     * Search Amazon's product type catalogue.
     *
     * @param string $keywords Free text ("shirt", "ring"); empty lists all types
     * @return array|false List of array('name' => ..., 'displayName' => ...)
     */
    public function searchProductTypes($keywords = '')
    {
        $query = array('marketplaceIds' => $this->marketplaceId);
        $keywords = trim((string) $keywords);
        if ($keywords !== '') {
            $query['keywords'] = $keywords;
        }

        $resp = $this->client->request('GET', '/definitions/2020-09-01/productTypes', $query);
        if ($resp === false) {
            $this->lastError = (string) $this->client->getLastError();
            return false;
        }
        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $this->lastError = 'productTypes HTTP ' . $resp['status'] . ': '
                . (is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body']);
            return false;
        }

        $out = array();
        if (isset($resp['body']['productTypes']) && is_array($resp['body']['productTypes'])) {
            foreach ($resp['body']['productTypes'] as $pt) {
                if (!isset($pt['name'])) {
                    continue;
                }
                $out[] = array(
                    'name' => $pt['name'],
                    'displayName' => isset($pt['displayName']) ? $pt['displayName'] : $pt['name'],
                );
            }
        }

        return $out;
    }

    /**
     * The flattened attribute list for a product type, from cache when fresh.
     *
     * @param string $productType e.g. SHIRT
     * @param bool   $forceRefresh Bypass the cache
     * @return array|false array('display_name' => string, 'attributes' => array)
     */
    public function getDefinition($productType, $forceRefresh = false)
    {
        $this->ensureTable();
        $productType = trim((string) $productType);
        if ($productType === '') {
            $this->lastError = AmazonI18n::get()->l('No product type given.', 'amazonproducttypedefinitions');
            return false;
        }

        if (!$forceRefresh) {
            $cached = $this->readCache($productType);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Passing sellerId makes Amazon return the values THIS account may
        // use — notably the fulfilment channel codes it is enrolled in.
        $query = array(
            'marketplaceIds' => $this->marketplaceId,
            'requirements' => 'LISTING',
            'locale' => 'DEFAULT',
        );
        $sellerId = trim((string) AmzproShop::get('AMZPRO_SELLER_ID'));
        if ($sellerId !== '') {
            $query['sellerId'] = $sellerId;
        }

        $resp = $this->client->request(
            'GET',
            '/definitions/2020-09-01/productTypes/' . rawurlencode($productType),
            $query
        );
        if ($resp === false) {
            $this->lastError = (string) $this->client->getLastError();
            return false;
        }
        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $this->lastError = 'productType definition HTTP ' . $resp['status'] . ': '
                . (is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body']);
            return false;
        }

        // The definition points at the real JSON schema behind a presigned URL.
        $schemaUrl = isset($resp['body']['schema']['link']['resource'])
            ? $resp['body']['schema']['link']['resource'] : '';
        if ($schemaUrl === '') {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Amazon did not send the list of fields for product type %s.', 'amazonproducttypedefinitions'),
                $productType
            );
            return false;
        }

        $raw = $this->client->downloadDocument($schemaUrl);
        if ($raw === false) {
            $this->lastError = sprintf(
                AmazonI18n::get()->l('Could not download the list of fields for this product type from Amazon: %s', 'amazonproducttypedefinitions'),
                (string) $this->client->getLastError()
            );
            return false;
        }
        $schema = json_decode($raw, true);
        if (!is_array($schema)) {
            $this->lastError = AmazonI18n::get()->l('The list of fields downloaded from Amazon could not be read. Please try again.', 'amazonproducttypedefinitions');
            return false;
        }

        $displayName = isset($resp['body']['displayName']) ? $resp['body']['displayName'] : $productType;
        $attributes = $this->flattenSchema($schema);

        $this->writeCache($productType, $displayName, $attributes);

        return array('display_name' => $displayName, 'attributes' => $attributes);
    }

    /**
     * Turn Amazon's listing JSON schema into a flat form definition.
     *
     * Every listing attribute is an array of objects; the interesting parts
     * live under items.properties (the value, its enum, its unit). Attributes
     * the module always fills in itself are dropped so the profile form only
     * shows what the merchant actually has to decide.
     *
     * @return array List of array('name', 'title', 'description', 'required',
     *                             'enum' => array('value' => label), 'has_unit')
     */
    private function flattenSchema($schema)
    {
        $properties = isset($schema['properties']) && is_array($schema['properties'])
            ? $schema['properties'] : array();
        $required = (isset($schema['required']) && is_array($schema['required']))
            ? array_flip($schema['required']) : array();

        $out = array();
        foreach ($properties as $name => $prop) {
            if (in_array($name, self::$handledInternally)) {
                continue;
            }
            if (!is_array($prop)) {
                continue;
            }

            $item = isset($prop['items']) && is_array($prop['items']) ? $prop['items'] : array();
            $itemProps = isset($item['properties']) && is_array($item['properties'])
                ? $item['properties'] : array();

            // Constrained attributes carry their allowed values on the "value"
            // sub-property (enum + human-readable enumNames).
            $enum = array();
            $valueProp = isset($itemProps['value']) && is_array($itemProps['value'])
                ? $itemProps['value'] : array();
            if (isset($valueProp['enum']) && is_array($valueProp['enum'])) {
                $names = (isset($valueProp['enumNames']) && is_array($valueProp['enumNames']))
                    ? $valueProp['enumNames'] : array();
                foreach ($valueProp['enum'] as $i => $v) {
                    $enum[(string) $v] = isset($names[$i]) ? $names[$i] : (string) $v;
                }
            }

            $out[] = array(
                'name' => $name,
                'title' => isset($prop['title']) ? $prop['title'] : $name,
                'description' => isset($prop['description']) ? Tools::substr($prop['description'], 0, 300) : '',
                'required' => isset($required[$name]),
                'enum' => $enum,
                'has_unit' => isset($itemProps['unit']),
            );
        }

        // Required attributes first, then alphabetically by label.
        usort($out, function ($a, $b) {
            if ($a['required'] !== $b['required']) {
                return $a['required'] ? -1 : 1;
            }
            return strcasecmp($a['title'], $b['title']);
        });

        return $out;
    }

    /** @return array|false Cached definition, or false when missing/stale */
    private function readCache($productType)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `display_name`, `attributes_json`, `date_upd`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema`
             WHERE `product_type` = \'' . pSQL($productType) . '\'
               AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\''
        );
        if (!$row || empty($row['attributes_json'])) {
            return false;
        }
        if (strtotime($row['date_upd']) < time() - self::CACHE_TTL_DAYS * 86400) {
            return false;
        }
        $attributes = json_decode($row['attributes_json'], true);
        if (!is_array($attributes)) {
            return false;
        }

        return array('display_name' => $row['display_name'], 'attributes' => $attributes);
    }

    private function writeCache($productType, $displayName, $attributes)
    {
        $now = date('Y-m-d H:i:s');
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema`
                (`product_type`, `marketplace_id`, `display_name`, `attributes_json`, `date_add`, `date_upd`)
             VALUES (\'' . pSQL($productType) . '\', \'' . pSQL($this->marketplaceId) . '\',
                 \'' . pSQL($displayName) . '\', \'' . pSQL(json_encode($attributes)) . '\',
                 \'' . pSQL($now) . '\', \'' . pSQL($now) . '\')
             ON DUPLICATE KEY UPDATE
                `display_name` = VALUES(`display_name`),
                `attributes_json` = VALUES(`attributes_json`),
                `date_upd` = VALUES(`date_upd`)'
        );
    }

    /** Cached product types for this marketplace (for the profile form). */
    public function listCachedTypes()
    {
        $this->ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT `product_type`, `display_name`, `date_upd`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pt_schema`
             WHERE `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
             ORDER BY `display_name` ASC'
        );

        return is_array($rows) ? $rows : array();
    }
}
