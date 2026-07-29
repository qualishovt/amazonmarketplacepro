<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Two-way product reconciliation between PrestaShop and Amazon.
 *
 * Links products by SKU (PrestaShop `reference` == Amazon `SellerSKU`),
 * snapshots both sides into amazonmarketplacepro_product, and computes a
 * `sync_direction` per row (ps_only / amazon_only / conflict / in_sync).
 *
 * Now captures full product data: description, bullet points, brand, images,
 * category mapping, EAN, manufacturer. Pushes complete listings (not just
 * offer-only) when a category mapping exists.
 *
 * PHP 5.6+ compatible (no scalar type hints, no ?? operator, no enums).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonProductSync
{
    /** Native content language (ISO 639-1) per marketplace, for auto mode. */
    private static $marketplaceLanguages = array(
        'ATVPDKIKX0DER'  => 'en', // US
        'A2EUQ1WTGCTBG2' => 'en', // Canada
        'A1AM78C64UM0Y8' => 'es', // Mexico
        'A2Q3Y263D00KMC' => 'pt', // Brazil
        'A1F83G8C2ARO7P' => 'en', // UK
        'A1PA6795UKMFR9' => 'de', // Germany
        'A13V1IB3VIYZZH' => 'fr', // France
        'APJ6JRA9NG5V4'  => 'it', // Italy
        'A1RKKUPIHCS9HS' => 'es', // Spain
        'A1805IZSGTT6HS' => 'nl', // Netherlands
        'A1C3SOZRARQ6R3' => 'pl', // Poland
        'A2NODRKZP88ZB9' => 'sv', // Sweden
        'AMEN7PMS3EDWL'  => 'fr', // Belgium
        'A33AVAJ2PDY3EV' => 'tr', // Turkey
        'A21TJRUUN4KGV'  => 'en', // India
        'A2VIGQ35RCS4UG' => 'en', // UAE
        'A17E79C6D8DWNP' => 'en', // Saudi Arabia
        'A19VAU5U5O7RUS' => 'en', // Singapore
        'A39IBJ37TRP1C6' => 'en', // Australia
        'A1VC38T7YXB528' => 'ja', // Japan
    );

    /** Cap how many products we scan per run, to stay responsive. */
    const MAX_PRODUCTS = 2000;

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $sellerId;
    private $lastError = null;
    private $notices = array();
    private $useMock = false;

    public function __construct(AmazonSpApiClient $client, $marketplaceId, $sellerId = '')
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
        $this->sellerId = trim((string) $sellerId);
    }

    /**
     * Enable the dev mock: Amazon read/list/reconcile/push paths use labeled
     * SAMPLE data instead of (or in addition to) live API calls.
     */
    public function setMock($enabled)
    {
        $this->useMock = (bool) $enabled;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @return array Human-readable notices about the run (e.g. skipped Amazon side).
     */
    public function getNotices()
    {
        return $this->notices;
    }

    public function ensureTables()
    {
        $engine = defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB';

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` (
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
            UNIQUE KEY `seller_sku` (`seller_sku`)
        ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8;';

        Db::getInstance()->execute($sql);

        // Older installs: add the variation-family columns if they're missing.
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` LIKE \'parent_sku\''
        );
        if (empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
                 ADD COLUMN `parent_sku` VARCHAR(255) NOT NULL DEFAULT \'\',
                 ADD COLUMN `is_parent` TINYINT(1) NOT NULL DEFAULT 0,
                 ADD COLUMN `variation_theme` VARCHAR(64) NOT NULL DEFAULT \'\',
                 ADD COLUMN `variation_attributes` TEXT NULL,
                 ADD KEY `parent_sku` (`parent_sku`)'
            );
        }
    }

    /**
     * Direction 1 — PrestaShop → Amazon: snapshot the PS catalog into staging.
     * Now captures full product data: description, images, manufacturer, EAN, category.
     *
     * @return array Summary counts (also includes 'ps_scanned')
     */
    public function syncPrestashopSide()
    {
        $this->ensureTables();
        $this->notices = array();

        $now = date('Y-m-d H:i:s');

        $psRows = $this->collectPrestashopProducts();
        foreach ($psRows as $row) {
            $this->upsertPsSide($row, $now);
        }

        if (empty($psRows)) {
            $this->notices[] = 'No PrestaShop products with a reference (SKU) were found.';
        }

        $this->recomputeDirections();

        $summary = $this->buildSummary(0, 0);
        $summary['ps_scanned'] = count($psRows);

        return $summary;
    }

    /**
     * Direction 2 — Amazon → PrestaShop: pull the Amazon listing for each known SKU.
     *
     * @return array Summary counts (also includes 'amazon_checked' / 'amazon_found')
     */
    public function syncAmazonSide()
    {
        $this->ensureTables();
        $this->notices = array();

        $now = date('Y-m-d H:i:s');

        // Dev mock: populate the Amazon side from sample data.
        if ($this->useMock) {
            $mock = $this->buildMockListings();
            $checked = 0;
            $found = 0;
            foreach ($mock as $sku => $listing) {
                $this->upsertAmazonSide($sku, $listing, $now);
                $checked++;
                $found++;
            }
            $this->recomputeDirections();
            $this->notices[] = 'MOCK MODE: Amazon side filled with SAMPLE data — not real Amazon listings.';
            return $this->buildSummary($checked, $found);
        }

        if ($this->sellerId === '') {
            $this->notices[] = 'Amazon side skipped: no seller id configured (set AMAZON_SELLER_ID). '
                . 'A seller id and production access are required to read Amazon listings.';
            return $this->buildSummary(0, 0);
        }

        $skus = $this->stagedSkus();
        if (empty($skus)) {
            $this->notices[] = 'No SKUs in staging yet. Run "Sync PrestaShop → Amazon" first to populate SKUs '
                . '(detecting Amazon-only listings needs the Reports API, not added yet).';
        }

        $amazonChecked = 0;
        $amazonFound = 0;
        foreach ($skus as $sku) {
            $listing = $this->fetchAmazonListing($sku);
            $amazonChecked++;
            if ($listing !== null) {
                $this->updateAmazonSide($sku, $listing, $now);
                $amazonFound++;
            }
        }

        $this->recomputeDirections();

        return $this->buildSummary($amazonChecked, $amazonFound);
    }

    /**
     * Recompute the two-way direction for every staged row in one pass.
     */
    private function recomputeDirections()
    {
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` SET `sync_direction` = CASE
                WHEN `ps_exists` = 1 AND `amazon_exists` = 0 THEN \'ps_only\'
                WHEN `ps_exists` = 0 AND `amazon_exists` = 1 THEN \'amazon_only\'
                WHEN `is_parent` = 1 THEN \'in_sync\'
                WHEN `ps_price` <> `amazon_price` OR `ps_quantity` <> `amazon_quantity` THEN \'conflict\'
                ELSE \'in_sync\'
            END'
        );
    }

    /**
     * Read-only: list the seller's products/listings directly from Amazon.
     * Does not touch the staging table.
     *
     * @param int $pageSize How many listings to request
     * @return array List of normalized Amazon products
     */
    public function listAmazonProducts($pageSize = 20)
    {
        $this->notices = array();
        $this->lastError = null;

        if ($this->useMock) {
            $this->notices[] = 'MOCK MODE: showing SAMPLE data — not real Amazon listings.';
            return array_values($this->buildMockListings());
        }

        if ($this->sellerId === '') {
            $this->notices[] = 'Cannot list Amazon products: no seller id configured (set AMAZON_SELLER_ID). '
                . 'A seller id and production access are required to read Amazon listings.';
            return array();
        }

        $resp = $this->client->request(
            'GET',
            '/listings/2021-08-01/items/' . rawurlencode($this->sellerId),
            array(
                'marketplaceIds' => $this->marketplaceId,
                'includedData' => 'summaries,offers,fulfillmentAvailability,attributes',
                'pageSize' => (int) $pageSize,
            )
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();
            return array();
        }
        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
            $this->lastError = 'searchListingsItems HTTP ' . $resp['status'] . ': ' . $body;
            return array();
        }

        $items = isset($resp['body']['items']) ? $resp['body']['items'] : array();

        $out = array();
        foreach ($items as $it) {
            $out[] = $this->normalizeListingItem($it);
        }

        if (empty($out)) {
            $this->notices[] = 'Amazon returned no products for this seller/marketplace.';
        }

        return $out;
    }

    /**
     * Normalize an Amazon listing item into a flat array.
     */
    private function normalizeListingItem($it)
    {
        $sku = isset($it['sku']) ? $it['sku'] : '';

        $title = '';
        $asin = '';
        $status = '';
        $productType = '';
        $browseNode = '';
        if (isset($it['summaries'][0])) {
            $sum = $it['summaries'][0];
            $title = isset($sum['itemName']) ? $sum['itemName'] : '';
            $asin = isset($sum['asin']) ? $sum['asin'] : '';
            if (isset($sum['status'])) {
                $status = is_array($sum['status']) ? implode(',', $sum['status']) : (string) $sum['status'];
            }
            $productType = isset($sum['productType']) ? $sum['productType'] : '';
            $browseNode = isset($sum['displayGroupBrowseNode']) ? $sum['displayGroupBrowseNode'] : '';
        }

        $price = 0;
        if (isset($it['offers'][0]['price']['amount'])) {
            $price = (float) $it['offers'][0]['price']['amount'];
        }

        $quantity = 0;
        if (isset($it['fulfillmentAvailability'][0]['quantity'])) {
            $quantity = (int) $it['fulfillmentAvailability'][0]['quantity'];
        }

        // Extract rich attributes
        $description = '';
        $bulletPoints = '';
        $brand = '';
        $images = array();

        if (isset($it['attributes'])) {
            $attrs = $it['attributes'];

            if (isset($attrs['product_description'][0]['value'])) {
                $description = $attrs['product_description'][0]['value'];
            }

            if (isset($attrs['bullet_point']) && is_array($attrs['bullet_point'])) {
                $bps = array();
                foreach ($attrs['bullet_point'] as $bp) {
                    if (isset($bp['value'])) {
                        $bps[] = $bp['value'];
                    }
                }
                $bulletPoints = implode("\n", $bps);
            }

            if (isset($attrs['brand'][0]['value'])) {
                $brand = $attrs['brand'][0]['value'];
            }

            if (isset($attrs['main_product_image_locator'][0]['media_location'])) {
                $images[] = $attrs['main_product_image_locator'][0]['media_location'];
            }
            if (isset($attrs['other_product_image_locator_1'][0]['media_location'])) {
                $images[] = $attrs['other_product_image_locator_1'][0]['media_location'];
            }
            if (isset($attrs['other_product_image_locator_2'][0]['media_location'])) {
                $images[] = $attrs['other_product_image_locator_2'][0]['media_location'];
            }
        }

        return array(
            'seller_sku' => $sku,
            'asin' => $asin,
            'title' => $title,
            'price' => $price,
            'quantity' => $quantity,
            'status' => $status,
            'description' => $description,
            'bullet_points' => $bulletPoints,
            'brand' => $brand,
            'images' => $images,
            'product_type' => $productType,
            'browse_node' => $browseNode,
        );
    }

    /**
     * @return array All SKUs currently in the staging table.
     */
    private function stagedSkus()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `seller_sku` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`'
        );

        $out = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[] = $r['seller_sku'];
            }
        }

        return $out;
    }

    /**
     * @return array Staged rows (capped), newest activity first.
     */
    public function listStaged($limit = 500)
    {
        $limit = (int) $limit;
        $sql = 'SELECT `seller_sku`, `id_product`, `ps_exists`, `ps_name`, `ps_price`, `ps_quantity`,
                       `ps_manufacturer`, `ps_ean13`,
                       `amazon_exists`, `amazon_asin`, `amazon_title`, `amazon_price`, `amazon_quantity`,
                       `amazon_status`, `amazon_brand`, `amazon_product_type`,
                       `sync_direction`, `parent_sku`, `is_parent`, `variation_theme`
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
                ORDER BY `sync_direction` ASC, `seller_sku` ASC
                LIMIT ' . $limit;
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : array();
    }

    /**
     * Collect PrestaShop products and combinations that carry a reference (SKU).
     * Now captures full product data: description, images, manufacturer, EAN, category.
     *
     * @return array
     */
    /**
     * Which PrestaShop language to use for listing content.
     *
     * AMZPRO_LISTING_LANG: '' = shop default, 'auto' = match the marketplace's
     * native language (falls back to default if the shop lacks it), or a
     * numeric id_lang for a fixed language.
     *
     * @return int id_lang
     */
    public function resolveListingLanguage()
    {
        $default = (int) Configuration::get('PS_LANG_DEFAULT');
        $mode = trim((string) Configuration::get('AMZPRO_LISTING_LANG'));

        if ($mode === '' || $mode === '0') {
            return $default;
        }

        if ($mode === 'auto') {
            if (isset(self::$marketplaceLanguages[$this->marketplaceId])) {
                $idLang = (int) Language::getIdByIso(self::$marketplaceLanguages[$this->marketplaceId]);
                if ($idLang) {
                    return $idLang;
                }
            }
            return $default;
        }

        $idLang = (int) $mode;
        if ($idLang && Language::getLanguage($idLang)) {
            return $idLang;
        }

        return $default;
    }

    private function collectPrestashopProducts()
    {
        $idLang = $this->resolveListingLanguage();
        $idShop = (int) Context::getContext()->shop->id;
        if (!$idShop) {
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }
        if (!$idShop) {
            $idShop = 1;
        }

        $shopUrl = $this->getShopBaseUrl();

        require_once dirname(__FILE__) . '/AmazonListingSettings.php';

        // Export filters & delta-sync context.
        $priceMin = (float) Configuration::get('AMZPRO_PRICE_MIN');
        $priceMax = (float) Configuration::get('AMZPRO_PRICE_MAX');
        $qtyMin = (int) Configuration::get('AMZPRO_QTY_MIN');
        $deltaHours = (int) Configuration::get('AMZPRO_DELTA_HOURS');
        $queuedIds = ($deltaHours > 0) ? AmazonListingSettings::getQueuedProductIds() : array();
        $deltaCutoff = ($deltaHours > 0) ? date('Y-m-d H:i:s', time() - $deltaHours * 3600) : null;
        $filteredOut = 0;

        $out = array();
        $seen = array();
        $baseRowIndex = array();   // id_product => index in $out (base product rows)
        $comboRowIndexes = array(); // id_product => list of indexes in $out (combination rows)
        $familyAttrKeys = array(); // id_product => array('color' => true, 'size' => true, ...)

        // Base products. The SKU is built from the configured source field
        // (reference / EAN / supplier reference) plus the optional prefix,
        // so rows are pre-filtered only on being active.
        $sql = 'SELECT p.`id_product`, p.`reference`, p.`supplier_reference`, p.`price`, p.`ean13`,
                       p.`weight`, p.`date_upd`, p.`id_category_default`,
                       pl.`name`, pl.`description`, pl.`description_short`,
                       m.`name` AS manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product` p
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                WHERE p.`active` = 1
                ORDER BY p.`id_product`
                LIMIT ' . (int) self::MAX_PRODUCTS;
        $rows = Db::getInstance()->executeS($sql);
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $sku = AmazonListingSettings::buildSku($r);
                if ($sku === '' || isset($seen[$sku])) {
                    continue;
                }
                $idProduct = (int) $r['id_product'];

                if (!AmazonListingSettings::isSyncEnabled($idProduct)) {
                    $filteredOut++;
                    continue;
                }
                if ($deltaCutoff !== null && !isset($queuedIds[$idProduct])
                    && $r['date_upd'] < $deltaCutoff) {
                    continue;
                }

                $price = AmazonListingSettings::applySpecificPrice((float) $r['price'], $idProduct, 0);
                $price = AmazonListingSettings::applyMarkup($price, $idProduct);
                $quantity = $this->getQuantity($idProduct, 0, $idShop);

                if (($priceMin > 0 && $price < $priceMin) || ($priceMax > 0 && $price > $priceMax)
                    || ($qtyMin > 0 && $quantity < $qtyMin)) {
                    $filteredOut++;
                    continue;
                }

                $seen[$sku] = true;
                $images = $this->getProductImageUrls($idProduct, 0, $shopUrl);

                $baseRowIndex[$idProduct] = count($out);
                $out[] = array(
                    'sku' => $sku,
                    'id_product' => $idProduct,
                    'id_product_attribute' => 0,
                    'name' => $r['name'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'description' => (string) $r['description'],
                    'description_short' => (string) $r['description_short'],
                    'manufacturer' => (string) $r['manufacturer_name'],
                    'ean13' => (string) $r['ean13'],
                    'id_category_default' => (int) $r['id_category_default'],
                    'images' => $images,
                );
            }
        }

        // Combinations (their own reference / EAN / supplier reference).
        $sql = 'SELECT pa.`id_product`, pa.`id_product_attribute`, pa.`reference`,
                       pa.`supplier_reference`, pa.`ean13` AS combo_ean,
                       (p.`price` + pa.`price`) AS price, pl.`name`,
                       pl.`description`, pl.`description_short`, p.`date_upd`,
                       p.`ean13` AS product_ean, p.`supplier_reference` AS product_supplier_ref,
                       p.`id_category_default`,
                       m.`name` AS manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                INNER JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = pa.`id_product`)
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                WHERE p.`active` = 1
                ORDER BY pa.`id_product_attribute`
                LIMIT ' . (int) self::MAX_PRODUCTS;
        $rows = Db::getInstance()->executeS($sql);
        if (is_array($rows)) {
            foreach ($rows as $r) {
                // Combination falls back to the base product's identifiers.
                $skuRow = array(
                    'reference' => $r['reference'],
                    'ean13' => (trim((string) $r['combo_ean']) !== '') ? $r['combo_ean'] : $r['product_ean'],
                    'supplier_reference' => (trim((string) $r['supplier_reference']) !== '')
                        ? $r['supplier_reference'] : $r['product_supplier_ref'],
                );
                $sku = AmazonListingSettings::buildSku($skuRow);
                if ($sku === '' || isset($seen[$sku])) {
                    continue;
                }
                $idProduct = (int) $r['id_product'];
                $idPa = (int) $r['id_product_attribute'];

                if (!AmazonListingSettings::isSyncEnabled($idProduct)) {
                    $filteredOut++;
                    continue;
                }
                if ($deltaCutoff !== null && !isset($queuedIds[$idProduct])
                    && $r['date_upd'] < $deltaCutoff) {
                    continue;
                }

                $price = AmazonListingSettings::applySpecificPrice((float) $r['price'], $idProduct, $idPa);
                $price = AmazonListingSettings::applyMarkup($price, $idProduct);
                $quantity = $this->getQuantity($idProduct, $idPa, $idShop);

                if (($priceMin > 0 && $price < $priceMin) || ($priceMax > 0 && $price > $priceMax)
                    || ($qtyMin > 0 && $quantity < $qtyMin)) {
                    $filteredOut++;
                    continue;
                }

                $seen[$sku] = true;

                // Get variation-specific name (append attribute values)
                $comboName = $r['name'];
                $attrValues = $this->getCombinationAttributeValues($idPa, $idLang);
                if ($attrValues !== '') {
                    $comboName .= ' - ' . $attrValues;
                }

                // Use combination EAN if set, else base product EAN
                $ean = trim((string) $r['combo_ean']);
                if ($ean === '') {
                    $ean = trim((string) $r['product_ean']);
                }

                $images = $this->getProductImageUrls($idProduct, $idPa, $shopUrl);

                // Amazon-mappable variation attributes (color / size) for this combination
                $amzAttrs = $this->getCombinationAmazonAttributes($idPa, $idLang);
                if (!empty($amzAttrs)) {
                    if (!isset($familyAttrKeys[$idProduct])) {
                        $familyAttrKeys[$idProduct] = array();
                    }
                    foreach (array_keys($amzAttrs) as $k) {
                        $familyAttrKeys[$idProduct][$k] = true;
                    }
                    if (!isset($comboRowIndexes[$idProduct])) {
                        $comboRowIndexes[$idProduct] = array();
                    }
                    $comboRowIndexes[$idProduct][] = count($out);
                }

                $out[] = array(
                    'sku' => $sku,
                    'id_product' => $idProduct,
                    'id_product_attribute' => $idPa,
                    'name' => $comboName,
                    'price' => $price,
                    'quantity' => $quantity,
                    'description' => (string) $r['description'],
                    'description_short' => (string) $r['description_short'],
                    'manufacturer' => (string) $r['manufacturer_name'],
                    'ean13' => $ean,
                    'id_category_default' => (int) $r['id_category_default'],
                    'images' => $images,
                    'variation_attributes' => $amzAttrs,
                );
            }
        }

        // Link variation families: a base product with combinations that carry
        // mappable attributes becomes the Amazon parent; its combinations
        // become children pointing at the parent SKU.
        foreach ($comboRowIndexes as $idProduct => $indexes) {
            if (!isset($baseRowIndex[$idProduct])) {
                $this->notices[] = 'Product #' . (int) $idProduct . ' has variation combinations but no base '
                    . 'reference (SKU) — pushed as standalone listings, not an Amazon variation family.';
                continue;
            }

            $theme = $this->buildVariationTheme($familyAttrKeys[$idProduct]);
            if ($theme === '') {
                continue;
            }

            $parentIdx = $baseRowIndex[$idProduct];
            $parentSku = $out[$parentIdx]['sku'];

            $out[$parentIdx]['is_parent'] = 1;
            $out[$parentIdx]['variation_theme'] = $theme;

            foreach ($indexes as $i) {
                $out[$i]['parent_sku'] = $parentSku;
                $out[$i]['variation_theme'] = $theme;
            }
        }

        if ($filteredOut > 0) {
            $this->notices[] = $filteredOut . ' product(s) excluded by export filters '
                . '(price min/max, quantity min, or sync switched off).';
        }
        if ($deltaCutoff !== null) {
            $this->notices[] = 'Delta export: only products updated in the last ' . $deltaHours
                . ' hour(s) or queued by rule changes were collected.';
        }

        return $out;
    }

    /**
     * Map a combination's PrestaShop attributes to Amazon variation attributes.
     *
     * Only attribute groups recognizable as color or size are returned —
     * those are the variation axes Amazon accepts across most product types.
     *
     * @return array e.g. array('color' => 'Red', 'size' => 'XL')
     */
    private function getCombinationAmazonAttributes($idProductAttribute, $idLang)
    {
        $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attr_value
                FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                INNER JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                    ON (agl.`id_attribute_group` = a.`id_attribute_group` AND agl.`id_lang` = ' . (int) $idLang . ')
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                    ON (al.`id_attribute` = pac.`id_attribute` AND al.`id_lang` = ' . (int) $idLang . ')
                WHERE pac.`id_product_attribute` = ' . (int) $idProductAttribute;
        $rows = Db::getInstance()->executeS($sql);

        $attrs = array();
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $key = $this->mapAttributeGroupToAmazon($r['group_name']);
                if ($key !== null && !isset($attrs[$key])) {
                    $attrs[$key] = (string) $r['attr_value'];
                }
            }
        }

        return $attrs;
    }

    /**
     * Normalize a PrestaShop attribute group name to an Amazon variation
     * attribute ('color' or 'size'), or null if not mappable.
     */
    private function mapAttributeGroupToAmazon($groupName)
    {
        $g = Tools::strtolower(trim((string) $groupName));

        $colorNames = array('color', 'colour', 'couleur', 'farbe', 'colore', 'kolor', 'cor', 'renk');
        $sizeNames = array('size', 'taille', 'talla', 'taglia', 'tamanho', 'rozmiar', 'maat', 'beden', 'größe', 'grösse', 'groesse');

        foreach ($colorNames as $n) {
            if (strpos($g, $n) !== false) {
                return 'color';
            }
        }
        foreach ($sizeNames as $n) {
            if (strpos($g, $n) !== false) {
                return 'size';
            }
        }

        return null;
    }

    /**
     * Amazon variation theme from the set of mapped attribute keys.
     *
     * @param array $attrKeys e.g. array('color' => true, 'size' => true)
     * @return string 'SIZE/COLOR', 'COLOR', 'SIZE', or '' if none
     */
    private function buildVariationTheme($attrKeys)
    {
        $hasColor = isset($attrKeys['color']);
        $hasSize = isset($attrKeys['size']);

        if ($hasColor && $hasSize) {
            return 'SIZE/COLOR';
        }
        if ($hasColor) {
            return 'COLOR';
        }
        if ($hasSize) {
            return 'SIZE';
        }

        return '';
    }

    /**
     * Get attribute values for a combination (e.g. "Red / XL").
     */
    private function getCombinationAttributeValues($idProductAttribute, $idLang)
    {
        $sql = 'SELECT al.`name`
                FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                    ON (al.`id_attribute` = pac.`id_attribute` AND al.`id_lang` = ' . (int) $idLang . ')
                WHERE pac.`id_product_attribute` = ' . (int) $idProductAttribute;
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows) || empty($rows)) {
            return '';
        }

        $vals = array();
        foreach ($rows as $r) {
            $vals[] = $r['name'];
        }
        return implode(' / ', $vals);
    }

    /**
     * Get public image URLs for a product (or specific combination).
     *
     * @return array List of image URLs
     */
    private function getProductImageUrls($idProduct, $idProductAttribute, $shopBaseUrl)
    {
        $urls = array();

        if ($idProductAttribute > 0) {
            // Combination-specific images
            $sql = 'SELECT i.`id_image`
                    FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
                    INNER JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_image` = pai.`id_image`)
                    WHERE pai.`id_product_attribute` = ' . (int) $idProductAttribute . '
                    ORDER BY i.`position` ASC
                    LIMIT 5';
            $rows = Db::getInstance()->executeS($sql);
            if (is_array($rows) && !empty($rows)) {
                foreach ($rows as $r) {
                    $urls[] = $shopBaseUrl . $idProduct . '-' . $r['id_image'] . '-large_default.jpg';
                }
                return $urls;
            }
        }

        // Base product images
        $sql = 'SELECT `id_image`
                FROM `' . _DB_PREFIX_ . 'image`
                WHERE `id_product` = ' . (int) $idProduct . '
                ORDER BY `position` ASC
                LIMIT 5';
        $rows = Db::getInstance()->executeS($sql);
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $urls[] = $shopBaseUrl . $idProduct . '-' . $r['id_image'] . '-large_default.jpg';
            }
        }

        return $urls;
    }

    /**
     * Get the shop base URL for product images.
     */
    private function getShopBaseUrl()
    {
        $ssl = (int) Configuration::get('PS_SSL_ENABLED');
        $domain = $ssl ? Configuration::get('PS_SHOP_DOMAIN_SSL') : Configuration::get('PS_SHOP_DOMAIN');
        if (!$domain) {
            $domain = Tools::getHttpHost(false);
        }
        $protocol = $ssl ? 'https://' : 'http://';
        return $protocol . $domain . '/img/p/';
    }

    private function getQuantity($idProduct, $idProductAttribute, $idShop)
    {
        if (class_exists('StockAvailable')) {
            return (int) StockAvailable::getQuantityAvailableByProduct($idProduct, $idProductAttribute, $idShop);
        }

        return 0;
    }

    private function upsertPsSide($row, $now)
    {
        $imagesJson = json_encode(isset($row['images']) ? $row['images'] : array());

        $parentSku = isset($row['parent_sku']) ? $row['parent_sku'] : '';
        $isParent = !empty($row['is_parent']) ? 1 : 0;
        $variationTheme = isset($row['variation_theme']) ? $row['variation_theme'] : '';
        $variationAttrs = json_encode(
            (isset($row['variation_attributes']) && is_array($row['variation_attributes']))
                ? $row['variation_attributes']
                : array()
        );

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
            (`seller_sku`, `id_product`, `id_product_attribute`, `ps_exists`, `ps_name`, `ps_price`,
             `ps_quantity`, `ps_description`, `ps_description_short`, `ps_manufacturer`,
             `ps_ean13`, `ps_id_category_default`, `ps_images`,
             `parent_sku`, `is_parent`, `variation_theme`, `variation_attributes`,
             `amazon_exists`, `sync_direction`, `date_add`, `date_upd`)
            VALUES (
                \'' . pSQL($row['sku']) . '\',
                ' . (int) $row['id_product'] . ',
                ' . (int) $row['id_product_attribute'] . ',
                1,
                \'' . pSQL($row['name']) . '\',
                ' . (float) $row['price'] . ',
                ' . (int) $row['quantity'] . ',
                \'' . pSQL($row['description'], true) . '\',
                \'' . pSQL($row['description_short'], true) . '\',
                \'' . pSQL($row['manufacturer']) . '\',
                \'' . pSQL($row['ean13']) . '\',
                ' . (int) $row['id_category_default'] . ',
                \'' . pSQL($imagesJson) . '\',
                \'' . pSQL($parentSku) . '\',
                ' . $isParent . ',
                \'' . pSQL($variationTheme) . '\',
                \'' . pSQL($variationAttrs) . '\',
                0,
                \'\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )
            ON DUPLICATE KEY UPDATE
                `id_product` = VALUES(`id_product`),
                `id_product_attribute` = VALUES(`id_product_attribute`),
                `ps_exists` = 1,
                `ps_name` = VALUES(`ps_name`),
                `ps_price` = VALUES(`ps_price`),
                `ps_quantity` = VALUES(`ps_quantity`),
                `ps_description` = VALUES(`ps_description`),
                `ps_description_short` = VALUES(`ps_description_short`),
                `ps_manufacturer` = VALUES(`ps_manufacturer`),
                `ps_ean13` = VALUES(`ps_ean13`),
                `ps_id_category_default` = VALUES(`ps_id_category_default`),
                `ps_images` = VALUES(`ps_images`),
                `parent_sku` = VALUES(`parent_sku`),
                `is_parent` = VALUES(`is_parent`),
                `variation_theme` = VALUES(`variation_theme`),
                `variation_attributes` = VALUES(`variation_attributes`),
                `date_upd` = VALUES(`date_upd`)';
        Db::getInstance()->execute($sql);
    }

    /**
     * Look up a single Amazon listing by SKU (best-effort).
     * Now extracts full product attributes (description, bullets, brand, images).
     *
     * @return array|null Normalized listing fields, or null if not found
     */
    private function fetchAmazonListing($sku)
    {
        if ($this->useMock) {
            $mock = $this->buildMockListings();
            return isset($mock[$sku]) ? $mock[$sku] : null;
        }

        $resp = $this->client->request(
            'GET',
            '/listings/2021-08-01/items/' . rawurlencode($this->sellerId) . '/' . rawurlencode($sku),
            array(
                'marketplaceIds' => $this->marketplaceId,
                'includedData' => 'summaries,offers,fulfillmentAvailability,attributes',
            )
        );

        if ($resp === false || $resp['status'] >= 400 || !is_array($resp['body'])) {
            return null;
        }

        $body = $resp['body'];

        $title = '';
        $asin = '';
        $status = '';
        $productType = '';
        $browseNode = '';
        if (isset($body['summaries'][0])) {
            $sum = $body['summaries'][0];
            $title = isset($sum['itemName']) ? $sum['itemName'] : '';
            $asin = isset($sum['asin']) ? $sum['asin'] : '';
            if (isset($sum['status'])) {
                $status = is_array($sum['status']) ? implode(',', $sum['status']) : (string) $sum['status'];
            }
            $productType = isset($sum['productType']) ? $sum['productType'] : '';
            $browseNode = isset($sum['displayGroupBrowseNode']) ? $sum['displayGroupBrowseNode'] : '';
        }

        $price = 0;
        if (isset($body['offers'][0]['price']['amount'])) {
            $price = (float) $body['offers'][0]['price']['amount'];
        }

        $quantity = 0;
        if (isset($body['fulfillmentAvailability'][0]['quantity'])) {
            $quantity = (int) $body['fulfillmentAvailability'][0]['quantity'];
        }

        // Extract rich attributes
        $description = '';
        $bulletPoints = '';
        $brand = '';
        $images = array();

        if (isset($body['attributes'])) {
            $attrs = $body['attributes'];

            if (isset($attrs['product_description'][0]['value'])) {
                $description = $attrs['product_description'][0]['value'];
            }

            if (isset($attrs['bullet_point']) && is_array($attrs['bullet_point'])) {
                $bps = array();
                foreach ($attrs['bullet_point'] as $bp) {
                    if (isset($bp['value'])) {
                        $bps[] = $bp['value'];
                    }
                }
                $bulletPoints = implode("\n", $bps);
            }

            if (isset($attrs['brand'][0]['value'])) {
                $brand = $attrs['brand'][0]['value'];
            }

            if (isset($attrs['main_product_image_locator'][0]['media_location'])) {
                $images[] = $attrs['main_product_image_locator'][0]['media_location'];
            }
        }

        return array(
            'asin' => $asin,
            'title' => $title,
            'status' => $status,
            'price' => $price,
            'quantity' => $quantity,
            'description' => $description,
            'bullet_points' => $bulletPoints,
            'brand' => $brand,
            'images' => $images,
            'product_type' => $productType,
            'browse_node' => $browseNode,
            'raw' => json_encode($body),
        );
    }

    private function updateAmazonSide($sku, $listing, $now)
    {
        $imagesJson = json_encode(isset($listing['images']) ? $listing['images'] : array());

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` SET
                    `amazon_exists` = 1,
                    `amazon_asin` = \'' . pSQL($listing['asin']) . '\',
                    `amazon_title` = \'' . pSQL($listing['title']) . '\',
                    `amazon_price` = ' . (float) $listing['price'] . ',
                    `amazon_quantity` = ' . (int) $listing['quantity'] . ',
                    `amazon_status` = \'' . pSQL($listing['status']) . '\',
                    `amazon_description` = \'' . pSQL(isset($listing['description']) ? $listing['description'] : '', true) . '\',
                    `amazon_bullet_points` = \'' . pSQL(isset($listing['bullet_points']) ? $listing['bullet_points'] : '', true) . '\',
                    `amazon_brand` = \'' . pSQL(isset($listing['brand']) ? $listing['brand'] : '') . '\',
                    `amazon_images` = \'' . pSQL($imagesJson) . '\',
                    `amazon_product_type` = \'' . pSQL(isset($listing['product_type']) ? $listing['product_type'] : '') . '\',
                    `amazon_browse_node` = \'' . pSQL(isset($listing['browse_node']) ? $listing['browse_node'] : '') . '\',
                    `raw_amazon_json` = \'' . pSQL(isset($listing['raw']) ? $listing['raw'] : '', true) . '\',
                    `date_upd` = \'' . pSQL($now) . '\'
                WHERE `seller_sku` = \'' . pSQL($sku) . '\'';
        Db::getInstance()->execute($sql);
    }

    /**
     * Insert or update the Amazon side of a row.
     */
    private function upsertAmazonSide($sku, $listing, $now)
    {
        $raw = isset($listing['raw']) ? $listing['raw'] : json_encode($listing);
        $imagesJson = json_encode(isset($listing['images']) ? $listing['images'] : array());

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
            (`seller_sku`, `ps_exists`, `amazon_exists`, `amazon_asin`, `amazon_title`,
             `amazon_price`, `amazon_quantity`, `amazon_status`,
             `amazon_description`, `amazon_bullet_points`, `amazon_brand`, `amazon_images`,
             `amazon_product_type`, `amazon_browse_node`,
             `raw_amazon_json`, `sync_direction`, `date_add`, `date_upd`)
            VALUES (
                \'' . pSQL($sku) . '\', 0, 1,
                \'' . pSQL($listing['asin']) . '\',
                \'' . pSQL($listing['title']) . '\',
                ' . (float) $listing['price'] . ',
                ' . (int) $listing['quantity'] . ',
                \'' . pSQL($listing['status']) . '\',
                \'' . pSQL(isset($listing['description']) ? $listing['description'] : '', true) . '\',
                \'' . pSQL(isset($listing['bullet_points']) ? $listing['bullet_points'] : '', true) . '\',
                \'' . pSQL(isset($listing['brand']) ? $listing['brand'] : '') . '\',
                \'' . pSQL($imagesJson) . '\',
                \'' . pSQL(isset($listing['product_type']) ? $listing['product_type'] : '') . '\',
                \'' . pSQL(isset($listing['browse_node']) ? $listing['browse_node'] : '') . '\',
                \'' . pSQL($raw, true) . '\',
                \'\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )
            ON DUPLICATE KEY UPDATE
                `amazon_exists` = 1,
                `amazon_asin` = VALUES(`amazon_asin`),
                `amazon_title` = VALUES(`amazon_title`),
                `amazon_price` = VALUES(`amazon_price`),
                `amazon_quantity` = VALUES(`amazon_quantity`),
                `amazon_status` = VALUES(`amazon_status`),
                `amazon_description` = VALUES(`amazon_description`),
                `amazon_bullet_points` = VALUES(`amazon_bullet_points`),
                `amazon_brand` = VALUES(`amazon_brand`),
                `amazon_images` = VALUES(`amazon_images`),
                `amazon_product_type` = VALUES(`amazon_product_type`),
                `amazon_browse_node` = VALUES(`amazon_browse_node`),
                `raw_amazon_json` = VALUES(`raw_amazon_json`),
                `date_upd` = VALUES(`date_upd`)';
        Db::getInstance()->execute($sql);
    }

    /**
     * Build labeled SAMPLE Amazon listings for dev/testing.
     */
    private function buildMockListings()
    {
        $mock = array();

        $rows = Db::getInstance()->executeS(
            'SELECT `seller_sku`, `ps_name`, `ps_price`, `ps_quantity`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             WHERE `ps_exists` = 1
             ORDER BY `seller_sku` ASC
             LIMIT 3'
        );

        $i = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $i++;
                $sku = $r['seller_sku'];
                $conflict = ($i === 1);
                $price = $conflict ? ((float) $r['ps_price'] + 5) : (float) $r['ps_price'];
                $qty = $conflict ? ((int) $r['ps_quantity'] + 10) : (int) $r['ps_quantity'];

                $entry = array(
                    'seller_sku' => $sku,
                    'asin' => 'B0MOCK' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'title' => '[MOCK] ' . $r['ps_name'],
                    'price' => $price,
                    'quantity' => $qty,
                    'status' => 'MOCK_ACTIVE',
                    'description' => '[MOCK] Sample product description for ' . $r['ps_name'],
                    'bullet_points' => "High quality product\nFast shipping\nGreat value",
                    'brand' => 'MockBrand',
                    'images' => array('https://example.com/mock-image-' . $i . '.jpg'),
                    'product_type' => 'PRODUCT',
                    'browse_node' => '12345',
                );
                $entry['raw'] = json_encode($entry);
                $mock[$sku] = $entry;
            }
        }

        // Two Amazon-only SKUs
        $amazonOnly = array(
            array('AMZ-ONLY-001', '[MOCK] Amazon-only widget', 19.99, 7),
            array('AMZ-ONLY-002', '[MOCK] Amazon-only gadget', 29.50, 3),
        );
        foreach ($amazonOnly as $idx => $o) {
            $entry = array(
                'seller_sku' => $o[0],
                'asin' => 'B0MOCKONLY' . ($idx + 1),
                'title' => $o[1],
                'price' => $o[2],
                'quantity' => $o[3],
                'status' => 'MOCK_ACTIVE',
                'description' => '[MOCK] Description for ' . $o[1],
                'bullet_points' => "Sample bullet point 1\nSample bullet point 2",
                'brand' => 'MockBrand',
                'images' => array(),
                'product_type' => 'PRODUCT',
                'browse_node' => '12345',
            );
            $entry['raw'] = json_encode($entry);
            $mock[$o[0]] = $entry;
        }

        return $mock;
    }

    /**
     * Push PrestaShop products to Amazon with full listing data.
     *
     * Uses category mapping (if configured) to send complete listings with
     * title, description, bullet points, brand, images. Falls back to
     * offer-only when no category mapping exists.
     *
     * @return array Summary + per-SKU results
     */
    public function pushToAmazon($limit = 25)
    {
        require_once dirname(__FILE__) . '/AmazonListingSettings.php';
        $this->ensureTables();
        $this->notices = array();

        $limit = (int) $limit;
        // A configured export line limit caps every push batch.
        $exportLimit = (int) Configuration::get('AMZPRO_EXPORT_LIMIT');
        if ($exportLimit > 0 && $exportLimit < $limit) {
            $limit = $exportLimit;
        }

        $onlyWithAsin = (bool) Configuration::get('AMZPRO_ONLY_WITH_ASIN');

        $rows = Db::getInstance()->executeS(
            'SELECT p.`seller_sku`, p.`id_product`, p.`id_product_attribute`, p.`ps_name`, p.`ps_price`, p.`ps_quantity`,
                    p.`ps_description`, p.`ps_description_short`, p.`ps_manufacturer`,
                    p.`ps_ean13`, p.`ps_id_category_default`, p.`ps_images`, p.`amazon_asin`,
                    p.`parent_sku`, p.`is_parent`, p.`variation_theme`, p.`variation_attributes`,
                    cm.`amazon_product_type`, cm.`amazon_browse_node`, cm.`attributes_json`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map` cm
                 ON (cm.`id_category` = p.`ps_id_category_default`
                     AND cm.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\')
             WHERE p.`ps_exists` = 1 AND p.`sync_direction` IN (\'ps_only\', \'conflict\')'
             . ($onlyWithAsin ? ' AND (p.`amazon_asin` <> \'\' OR p.`is_parent` = 1)' : '') . '
             ORDER BY p.`is_parent` DESC, p.`seller_sku` ASC
             LIMIT ' . $limit
        );
        if (!is_array($rows)) {
            $rows = array();
        }

        if (empty($rows)) {
            $this->notices[] = 'Nothing to push. Run "Sync PrestaShop -> Amazon" first, '
                . 'and make sure there are ps_only / conflict rows to send.'
                . ($onlyWithAsin ? ' Note: "Export only products with ASIN" is enabled.' : '');
        }

        $results = array();
        $pushed = 0;
        $failed = 0;
        $pushedProductIds = array();
        foreach ($rows as $r) {
            $res = $this->pushOne($r);
            $results[] = $res;
            if ($res['status'] === 'ACCEPTED') {
                $pushed++;
                if (!empty($r['id_product'])) {
                    $pushedProductIds[] = (int) $r['id_product'];
                }
            } else {
                $failed++;
            }
        }

        // Synced products leave the change queue (they're no longer "modified").
        AmazonListingSettings::deactivateQueued($pushedProductIds);

        if ($this->useMock) {
            $this->notices[] = 'MOCK MODE: submissions are simulated — no real Amazon call was made.';
        }

        return array(
            'candidates' => count($rows),
            'pushed' => $pushed,
            'failed' => $failed,
            'results' => $results,
        );
    }

    /**
     * Submit a single product to Amazon with full listing data.
     *
     * @return array array('sku' => string, 'status' => string, 'issues' => string)
     */
    private function pushOne($r)
    {
        $sku = $r['seller_sku'];

        if ($this->useMock) {
            return array('sku' => $sku, 'status' => 'ACCEPTED', 'issues' => '(mock) submission accepted');
        }
        if ($this->sellerId === '') {
            return array('sku' => $sku, 'status' => 'SKIPPED', 'issues' => 'No seller id configured (production only).');
        }

        // Price-only / quantity-only modes send a partial PATCH instead of a
        // full listing replace.
        $syncMode = (string) Configuration::get('AMZPRO_SYNC_MODE');
        if (($syncMode === 'price' || $syncMode === 'quantity') && empty($r['is_parent'])) {
            $body = $this->buildPatchBody($r, $syncMode);
            $resp = $this->client->request(
                'PATCH',
                '/listings/2021-08-01/items/' . rawurlencode($this->sellerId) . '/' . rawurlencode($sku),
                array('marketplaceIds' => $this->marketplaceId),
                $body
            );
            if ($resp === false) {
                return array('sku' => $sku, 'status' => 'ERROR', 'issues' => (string) $this->client->getLastError());
            }
            $status = (is_array($resp['body']) && isset($resp['body']['status']))
                ? $resp['body']['status'] : ('HTTP ' . $resp['status']);
            $issues = (is_array($resp['body']) && isset($resp['body']['issues']))
                ? json_encode($resp['body']['issues']) : '';
            return array('sku' => $sku, 'status' => $status, 'issues' => $issues);
        }

        // Optional: delete the listing entirely when it runs out of stock
        // (instead of publishing quantity 0). Parents are never deleted.
        // Skipped while "force all quantities to zero" is on — that mode means
        // "publish 0 everywhere", not "delist the whole catalog".
        if (Configuration::get('AMZPRO_DELETE_WHEN_OOS')
            && !Configuration::get('AMZPRO_FORCE_ZERO_QTY')
            && empty($r['is_parent'])
            && AmazonSpApiClient::effectiveQuantity($r['ps_quantity']) <= 0) {
            $resp = $this->client->request(
                'DELETE',
                '/listings/2021-08-01/items/' . rawurlencode($this->sellerId) . '/' . rawurlencode($sku),
                array('marketplaceIds' => $this->marketplaceId)
            );
            if ($resp === false) {
                return array('sku' => $sku, 'status' => 'ERROR', 'issues' => (string) $this->client->getLastError());
            }
            $status = (is_array($resp['body']) && isset($resp['body']['status']))
                ? $resp['body']['status'] : ('HTTP ' . $resp['status']);
            return array('sku' => $sku, 'status' => $status, 'issues' => 'Out of stock: listing deletion requested.');
        }

        $body = $this->buildListingRequestBody($r);
        if (isset($body['_skip'])) {
            return array('sku' => $sku, 'status' => 'SKIPPED', 'issues' => $body['_skip']);
        }

        $resp = $this->client->request(
            'PUT',
            '/listings/2021-08-01/items/' . rawurlencode($this->sellerId) . '/' . rawurlencode($sku),
            array('marketplaceIds' => $this->marketplaceId),
            $body
        );

        if ($resp === false) {
            return array('sku' => $sku, 'status' => 'ERROR', 'issues' => (string) $this->client->getLastError());
        }
        if (!is_array($resp['body'])) {
            return array('sku' => $sku, 'status' => 'HTTP ' . $resp['status'], 'issues' => (string) $resp['body']);
        }

        $status = isset($resp['body']['status']) ? $resp['body']['status'] : ('HTTP ' . $resp['status']);
        $issues = isset($resp['body']['issues']) ? json_encode($resp['body']['issues']) : '';

        return array('sku' => $sku, 'status' => $status, 'issues' => $issues);
    }

    /**
     * Collect feed messages for every pending (ps_only / conflict) staged row.
     *
     * Reuses the exact same body building as the per-SKU push, so feed and
     * direct pushes always agree. Parents come first.
     *
     * @param int $limit
     * @return array array('messages' => array, 'skipped' => array of array(sku, reason))
     */
    public function collectFeedMessages($limit = 500)
    {
        require_once dirname(__FILE__) . '/AmazonListingSettings.php';
        $this->ensureTables();

        $exportLimit = (int) Configuration::get('AMZPRO_EXPORT_LIMIT');
        if ($exportLimit > 0 && $exportLimit < (int) $limit) {
            $limit = $exportLimit;
        }
        $onlyWithAsin = (bool) Configuration::get('AMZPRO_ONLY_WITH_ASIN');
        $syncMode = (string) Configuration::get('AMZPRO_SYNC_MODE');

        $rows = Db::getInstance()->executeS(
            'SELECT p.`seller_sku`, p.`id_product`, p.`id_product_attribute`, p.`ps_name`, p.`ps_price`, p.`ps_quantity`,
                    p.`ps_description`, p.`ps_description_short`, p.`ps_manufacturer`,
                    p.`ps_ean13`, p.`ps_id_category_default`, p.`ps_images`, p.`amazon_asin`,
                    p.`parent_sku`, p.`is_parent`, p.`variation_theme`, p.`variation_attributes`,
                    cm.`amazon_product_type`, cm.`amazon_browse_node`, cm.`attributes_json`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map` cm
                 ON (cm.`id_category` = p.`ps_id_category_default`
                     AND cm.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\')
             WHERE p.`ps_exists` = 1 AND p.`sync_direction` IN (\'ps_only\', \'conflict\')'
             . ($onlyWithAsin ? ' AND (p.`amazon_asin` <> \'\' OR p.`is_parent` = 1)' : '') . '
             ORDER BY p.`is_parent` DESC, p.`seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            $rows = array();
        }

        $messages = array();
        $skipped = array();
        $idProducts = array();
        $messageId = 1;

        foreach ($rows as $r) {
            $sku = $r['seller_sku'];

            // Price-only / quantity-only modes patch the offer instead of
            // replacing the whole listing. Parents carry no offer — skip them.
            if ($syncMode === 'price' || $syncMode === 'quantity') {
                if (!empty($r['is_parent'])) {
                    continue;
                }
                $messages[] = array_merge(
                    array(
                        'messageId' => $messageId++,
                        'sku' => $sku,
                        'operationType' => 'PATCH',
                    ),
                    $this->buildPatchBody($r, $syncMode)
                );
                $idProducts[] = (int) $r['id_product'];
                continue;
            }

            if (Configuration::get('AMZPRO_DELETE_WHEN_OOS')
                && !Configuration::get('AMZPRO_FORCE_ZERO_QTY')
                && empty($r['is_parent'])
                && AmazonSpApiClient::effectiveQuantity($r['ps_quantity']) <= 0) {
                $messages[] = array(
                    'messageId' => $messageId++,
                    'sku' => $sku,
                    'operationType' => 'DELETE',
                    'productType' => 'PRODUCT',
                );
                $idProducts[] = (int) $r['id_product'];
                continue;
            }

            $body = $this->buildListingRequestBody($r);
            if (isset($body['_skip'])) {
                $skipped[] = array('sku' => $sku, 'reason' => $body['_skip']);
                continue;
            }

            $messages[] = array_merge(
                array(
                    'messageId' => $messageId++,
                    'sku' => $sku,
                    'operationType' => 'UPDATE',
                ),
                $body
            );
            $idProducts[] = (int) $r['id_product'];
        }

        return array('messages' => $messages, 'skipped' => $skipped, 'id_products' => $idProducts);
    }

    /**
     * Partial update body (price-only or quantity-only sync mode) for both the
     * Listings Items PATCH call and JSON_LISTINGS_FEED PATCH messages.
     *
     * @param array  $r    Staged row
     * @param string $mode 'price' | 'quantity'
     * @return array array('productType' => ..., 'patches' => array(...))
     */
    private function buildPatchBody($r, $mode)
    {
        if ($mode === 'quantity') {
            $patch = array(
                'op' => 'replace',
                'path' => '/attributes/fulfillment_availability',
                'value' => array(array(
                    'fulfillment_channel_code' => 'DEFAULT',
                    'quantity' => AmazonSpApiClient::effectiveQuantity($r['ps_quantity']),
                    'marketplace_id' => $this->marketplaceId,
                )),
            );
        } else {
            $offer = array(
                'currency' => AmazonSpApiClient::currencyForMarketplace($this->marketplaceId),
                'marketplace_id' => $this->marketplaceId,
                'our_price' => array(array(
                    'schedule' => array(array('value_with_tax' => (float) $r['ps_price'])),
                )),
            );
            $patch = array(
                'op' => 'replace',
                'path' => '/attributes/purchasable_offer',
                'value' => array($offer),
            );
        }

        return array(
            'productType' => !empty($r['amazon_product_type']) ? $r['amazon_product_type'] : 'PRODUCT',
            'patches' => array($patch),
        );
    }

    /**
     * Build the Listings Items API request body for a staged row.
     *
     * Handles the four listing shapes: variation parent, variation child,
     * standalone full listing, and offer-only fallback.
     *
     * @param array $r Staged product row
     * @return array Request body; contains a '_skip' key with a reason when
     *               the row cannot be pushed at all
     */
    private function buildListingRequestBody($r)
    {
        // Determine if we can do a full listing or offer-only
        $hasProductType = !empty($r['amazon_product_type']);

        // Parse images from JSON
        $images = array();
        if (!empty($r['ps_images'])) {
            $decoded = json_decode($r['ps_images'], true);
            if (is_array($decoded)) {
                $images = $decoded;
            }
        }

        // Build bullet points from short description
        $bulletPoints = array();
        $shortDesc = isset($r['ps_description_short']) ? strip_tags(trim((string) $r['ps_description_short'])) : '';
        if ($shortDesc !== '') {
            // Split by periods or newlines into bullet points
            $parts = preg_split('/[\r\n]+|(?<=\.)\s+/', $shortDesc);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '' && $part !== '.') {
                    $bulletPoints[] = $part;
                }
            }
        }
        // Cap at 5 bullet points (Amazon limit)
        $bulletPoints = array_slice($bulletPoints, 0, 5);

        $isParent = !empty($r['is_parent']);
        $parentSku = isset($r['parent_sku']) ? trim((string) $r['parent_sku']) : '';
        $variationTheme = isset($r['variation_theme']) ? trim((string) $r['variation_theme']) : '';

        // Build the listing body
        if ($isParent) {
            // Variation parent: a non-sellable listing that groups the children.
            // Amazon requires full product data for parents — offer-only is impossible.
            if (!$hasProductType) {
                return array('_skip' => 'Variation parent needs a category mapping (Amazon product type). '
                    . 'Map category #' . (int) $r['ps_id_category_default'] . ' first.');
            }
            $body = $this->buildFullListingBody($r, $bulletPoints, $images);
            // Parents carry no offer or stock.
            unset($body['attributes']['purchasable_offer']);
            unset($body['attributes']['fulfillment_availability']);
            $body['attributes']['parentage_level'] = array(array(
                'value' => 'parent',
                'marketplace_id' => $this->marketplaceId,
            ));
            $body['attributes']['variation_theme'] = array(array(
                'name' => $variationTheme,
                'marketplace_id' => $this->marketplaceId,
            ));
        } elseif ($parentSku !== '' && $hasProductType) {
            // Variation child: full listing linked to its parent.
            $body = $this->buildFullListingBody($r, $bulletPoints, $images);
            $body['attributes']['parentage_level'] = array(array(
                'value' => 'child',
                'marketplace_id' => $this->marketplaceId,
            ));
            $body['attributes']['child_parent_sku_relationship'] = array(array(
                'child_relationship_type' => 'variation',
                'parent_sku' => $parentSku,
                'marketplace_id' => $this->marketplaceId,
            ));
            $body['attributes']['variation_theme'] = array(array(
                'name' => $variationTheme,
                'marketplace_id' => $this->marketplaceId,
            ));
            // Variation axes (color / size values from the PS combination)
            $varAttrs = array();
            if (!empty($r['variation_attributes'])) {
                $decoded = json_decode($r['variation_attributes'], true);
                if (is_array($decoded)) {
                    $varAttrs = $decoded;
                }
            }
            foreach ($varAttrs as $attrKey => $attrValue) {
                $body['attributes'][$attrKey] = array(array(
                    'value' => $attrValue,
                    'marketplace_id' => $this->marketplaceId,
                ));
            }
        } elseif ($hasProductType) {
            // Standalone full listing with all attributes
            $body = $this->buildFullListingBody($r, $bulletPoints, $images);
        } else {
            // Offer-only (price + stock). For variation children without a
            // category mapping this updates price/stock but cannot create the
            // parent/child relationship.
            $body = $this->buildOfferOnlyBody($r);
        }

        return $body;
    }

    /**
     * Build a full listing body with title, description, bullets, brand, images, category.
     */
    private function buildFullListingBody($r, $bulletPoints, $images)
    {
        require_once dirname(__FILE__) . '/AmazonListingSettings.php';
        $idProduct = isset($r['id_product']) ? (int) $r['id_product'] : 0;

        $availability = array(
            'fulfillment_channel_code' => 'DEFAULT',
            'quantity' => AmazonSpApiClient::effectiveQuantity($r['ps_quantity']),
        );
        // Handling time (category/manufacturer/supplier cascade, module default)
        $delay = AmazonListingSettings::resolveDelay($idProduct);
        if ($delay > 0) {
            $availability['lead_time_to_ship_max_days'] = $delay;
        }

        $attributes = array(
            'condition_type' => array(array('value' => AmazonSpApiClient::listingCondition())),
            'item_name' => array(array(
                'value' => $r['ps_name'],
                'marketplace_id' => $this->marketplaceId,
            )),
            'purchasable_offer' => array(array(
                'currency' => AmazonSpApiClient::currencyForMarketplace($this->marketplaceId),
                'marketplace_id' => $this->marketplaceId,
                'our_price' => array(array(
                    'schedule' => array(array('value_with_tax' => (float) $r['ps_price'])),
                )),
            )),
            'fulfillment_availability' => array($availability),
        );

        // Condition note for used / refurbished listings
        $condNote = $this->conditionNote();
        if ($condNote !== '') {
            $attributes['condition_note'] = array(array(
                'value' => $condNote,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // GPSR responsible-person contact (product > manufacturer/supplier priority)
        $gpsr = AmazonListingSettings::resolveGpsrContact($idProduct);
        if ($gpsr !== '') {
            if (filter_var($gpsr, FILTER_VALIDATE_EMAIL)) {
                $attributes['gpsr_manufacturer_email_address'] = array(array(
                    'value' => $gpsr,
                    'marketplace_id' => $this->marketplaceId,
                ));
            } else {
                $attributes['gpsr_manufacturer_reference'] = array(array(
                    'value' => $gpsr,
                    'marketplace_id' => $this->marketplaceId,
                ));
            }
        }

        // Country of origin (from the manufacturer rule)
        $coo = AmazonListingSettings::resolveCountryOfOrigin($idProduct);
        if ($coo !== '') {
            $attributes['country_of_origin'] = array(array(
                'value' => $coo,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // Description
        $desc = isset($r['ps_description']) ? strip_tags(trim((string) $r['ps_description'])) : '';
        if ($desc !== '') {
            // Amazon limit: 2000 chars for most categories
            $desc = Tools::substr($desc, 0, 2000);
            $attributes['product_description'] = array(array(
                'value' => $desc,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // Bullet points
        if (!empty($bulletPoints)) {
            $bpArr = array();
            foreach ($bulletPoints as $bp) {
                $bpArr[] = array(
                    'value' => Tools::substr($bp, 0, 500),
                    'marketplace_id' => $this->marketplaceId,
                );
            }
            $attributes['bullet_point'] = $bpArr;
        }

        // Brand / manufacturer
        $brand = isset($r['ps_manufacturer']) ? trim((string) $r['ps_manufacturer']) : '';
        if ($brand !== '') {
            $attributes['brand'] = array(array(
                'value' => $brand,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // EAN / external product ID. The merchant can force the field to be
        // treated as UPC (US barcodes stored in the EAN-13 field).
        $ean = isset($r['ps_ean13']) ? trim((string) $r['ps_ean13']) : '';
        if ($ean !== '' && strlen($ean) >= 8) {
            if (Configuration::get('AMZPRO_EAN_AS') === 'UPC') {
                $idType = 'UPC';
            } else {
                $idType = (strlen($ean) === 13) ? 'EAN' : 'UPC';
            }
            $attributes['externally_assigned_product_identifier'] = array(array(
                'type' => $idType,
                'value' => $ean,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // Main image
        if (!empty($images) && isset($images[0])) {
            $attributes['main_product_image_locator'] = array(array(
                'media_location' => $images[0],
                'marketplace_id' => $this->marketplaceId,
            ));
        }
        // Additional images
        for ($i = 1; $i < min(count($images), 6); $i++) {
            $key = 'other_product_image_locator_' . $i;
            $attributes[$key] = array(array(
                'media_location' => $images[$i],
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // Merge the category's attribute template (product-type specific
        // required fields, GPSR/compliance data, etc.)
        $attributes = $this->applyAttributeTemplate($attributes, $r);

        // Shipping template (price/weight ranges when enabled) + B2B price
        $attributes = AmazonSpApiClient::enrichOfferAttributes(
            $attributes, $this->marketplaceId, (float) $r['ps_price'],
            AmazonListingSettings::resolveShippingTemplate(
                (float) $r['ps_price'], $this->productWeight($r)
            )
        );

        return array(
            'productType' => $r['amazon_product_type'],
            'requirements' => 'LISTING',
            'attributes' => $attributes,
        );
    }

    /** Condition note for the configured (non-new) listing condition, or ''. */
    private function conditionNote()
    {
        $condition = AmazonSpApiClient::listingCondition();
        if (strpos($condition, 'used') === 0) {
            return trim((string) Configuration::get('AMZPRO_COND_NOTE_USED'));
        }
        if (strpos($condition, 'refurbished') === 0) {
            return trim((string) Configuration::get('AMZPRO_COND_NOTE_REFURB'));
        }

        return '';
    }

    /** Product (+combination impact) weight for shipping-template ranges. */
    private function productWeight($r)
    {
        $idProduct = isset($r['id_product']) ? (int) $r['id_product'] : 0;
        if (!$idProduct) {
            return 0.0;
        }
        $idPa = isset($r['id_product_attribute']) ? (int) $r['id_product_attribute'] : 0;

        $weight = (float) Db::getInstance()->getValue(
            'SELECT `weight` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . $idProduct
        );
        if ($idPa) {
            $weight += (float) Db::getInstance()->getValue(
                'SELECT `weight` FROM `' . _DB_PREFIX_ . 'product_attribute`
                 WHERE `id_product_attribute` = ' . $idPa
            );
        }

        return $weight;
    }

    /**
     * Merge the category mapping's attribute template into a listing body.
     *
     * Template format (JSON object on the category mapping):
     *   "attribute_name": "static value"          -> wrapped Amazon-style
     *   "attribute_name": "feature:Material"      -> value of that PS product feature
     *   "attribute_name": [ ...raw structure... ] -> passed through unchanged
     *
     * Values resolving to '' are skipped. Existing attributes are not
     * overwritten by scalar/feature entries; raw structures win.
     *
     * @param array $attributes Already-built listing attributes
     * @param array $r          Staged row (needs attributes_json, id_product)
     * @return array
     */
    private function applyAttributeTemplate($attributes, $r)
    {
        if (empty($r['attributes_json'])) {
            return $attributes;
        }
        $template = json_decode($r['attributes_json'], true);
        if (!is_array($template)) {
            return $attributes;
        }

        $idProduct = isset($r['id_product']) ? (int) $r['id_product'] : 0;

        foreach ($template as $attrName => $spec) {
            if (is_array($spec)) {
                // Raw Amazon structure — expert escape hatch, takes precedence.
                $attributes[$attrName] = $spec;
                continue;
            }

            if (isset($attributes[$attrName])) {
                continue; // don't overwrite computed attributes with simple values
            }

            $value = (string) $spec;
            if (strpos($value, 'feature:') === 0) {
                $value = $this->resolveFeatureValue($idProduct, trim(Tools::substr($value, 8)));
            }
            if ($value === '') {
                continue;
            }

            $attributes[$attrName] = array(array(
                'value' => $value,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        return $attributes;
    }

    /**
     * Value of a PrestaShop product feature (e.g. "Material") for a product.
     *
     * @return string '' when the product doesn't have the feature
     */
    private function resolveFeatureValue($idProduct, $featureName)
    {
        if (!$idProduct || $featureName === '') {
            return '';
        }
        $idLang = $this->resolveListingLanguage();

        $value = Db::getInstance()->getValue(
            'SELECT fvl.`value`
             FROM `' . _DB_PREFIX_ . 'feature_product` fp
             INNER JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
                 ON (fl.`id_feature` = fp.`id_feature` AND fl.`id_lang` = ' . $idLang . ')
             INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
                 ON (fvl.`id_feature_value` = fp.`id_feature_value` AND fvl.`id_lang` = ' . $idLang . ')
             WHERE fp.`id_product` = ' . (int) $idProduct . '
               AND fl.`name` = \'' . pSQL($featureName) . '\''
        );

        return (string) $value;
    }

    /**
     * Build an offer-only listing body (price + stock only).
     */
    private function buildOfferOnlyBody($r)
    {
        require_once dirname(__FILE__) . '/AmazonListingSettings.php';
        $idProduct = isset($r['id_product']) ? (int) $r['id_product'] : 0;

        $availability = array(
            'fulfillment_channel_code' => 'DEFAULT',
            'quantity' => AmazonSpApiClient::effectiveQuantity($r['ps_quantity']),
        );
        $delay = AmazonListingSettings::resolveDelay($idProduct);
        if ($delay > 0) {
            $availability['lead_time_to_ship_max_days'] = $delay;
        }

        $attributes = array(
            'condition_type' => array(array('value' => AmazonSpApiClient::listingCondition())),
            'purchasable_offer' => array(array(
                'currency' => AmazonSpApiClient::currencyForMarketplace($this->marketplaceId),
                'marketplace_id' => $this->marketplaceId,
                'our_price' => array(array(
                    'schedule' => array(array('value_with_tax' => (float) $r['ps_price'])),
                )),
            )),
            'fulfillment_availability' => array($availability),
        );

        $condNote = $this->conditionNote();
        if ($condNote !== '') {
            $attributes['condition_note'] = array(array(
                'value' => $condNote,
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // A known ASIN lets Amazon match the offer to the right catalog page.
        if (!empty($r['amazon_asin'])) {
            $attributes['merchant_suggested_asin'] = array(array(
                'value' => $r['amazon_asin'],
                'marketplace_id' => $this->marketplaceId,
            ));
        }

        // Shipping template (price/weight ranges when enabled) + B2B price
        $attributes = AmazonSpApiClient::enrichOfferAttributes(
            $attributes, $this->marketplaceId, (float) $r['ps_price'],
            AmazonListingSettings::resolveShippingTemplate(
                (float) $r['ps_price'], $this->productWeight($r)
            )
        );

        return array(
            'productType' => 'PRODUCT',
            'requirements' => 'LISTING_OFFER_ONLY',
            'attributes' => $attributes,
        );
    }

    /**
     * Get category mappings for admin UI.
     */
    public function getCategoryMappings()
    {
        $sql = 'SELECT cm.*, cl.`name` AS category_name
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map` cm
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON (cl.`id_category` = cm.`id_category`
                        AND cl.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT') . '
                        AND cl.`id_shop` = ' . (int) Context::getContext()->shop->id . ')
                ORDER BY cl.`name` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Save a category mapping.
     */
    public function saveCategoryMapping($idCategory, $amazonProductType, $amazonBrowseNode, $marketplaceId, $attributesJson = '')
    {
        $attributesJson = trim((string) $attributesJson);
        if ($attributesJson !== '' && json_decode($attributesJson, true) === null) {
            $this->lastError = 'Extra attributes must be a valid JSON object.';
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map`
                (`id_category`, `amazon_product_type`, `amazon_browse_node`, `marketplace_id`, `attributes_json`, `date_add`, `date_upd`)
                VALUES (
                    ' . (int) $idCategory . ',
                    \'' . pSQL($amazonProductType) . '\',
                    \'' . pSQL($amazonBrowseNode) . '\',
                    \'' . pSQL($marketplaceId) . '\',
                    \'' . pSQL($attributesJson, true) . '\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                )
                ON DUPLICATE KEY UPDATE
                    `amazon_product_type` = VALUES(`amazon_product_type`),
                    `amazon_browse_node` = VALUES(`amazon_browse_node`),
                    `attributes_json` = VALUES(`attributes_json`),
                    `date_upd` = VALUES(`date_upd`)';
        return Db::getInstance()->execute($sql);
    }

    /**
     * Delete a category mapping.
     */
    public function deleteCategoryMapping($idCategoryMap)
    {
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map`
             WHERE `id_amazonmarketplacepro_category_map` = ' . (int) $idCategoryMap
        );
    }

    private function buildSummary($amazonChecked, $amazonFound)
    {
        $summary = array(
            'total' => 0,
            'ps_only' => 0,
            'amazon_only' => 0,
            'conflict' => 0,
            'in_sync' => 0,
            'amazon_checked' => (int) $amazonChecked,
            'amazon_found' => (int) $amazonFound,
        );

        $rows = Db::getInstance()->executeS(
            'SELECT `sync_direction`, COUNT(*) AS c
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             GROUP BY `sync_direction`'
        );
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $summary['total'] += (int) $r['c'];
                $dir = $r['sync_direction'];
                if (isset($summary[$dir])) {
                    $summary[$dir] = (int) $r['c'];
                }
            }
        }

        return $summary;
    }
}
