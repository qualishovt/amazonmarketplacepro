<?php
/**
 * Creates real PrestaShop products from staged Amazon-only listings
 * (sync_direction = 'amazon_only'): products the merchant sells on Amazon
 * that don't exist in the shop yet.
 *
 * Imported products are created INACTIVE so the merchant reviews them
 * before they appear in the shop. Title, description, bullet points,
 * brand (manufacturer), price, stock and images are carried over.
 *
 * Multistore: works on one shop (the one the request acts for, or the one
 * passed in). It reads that shop's staged rows, only touches products
 * associated with that shop, and saves products, images and stock for that
 * shop alone (id_shop_list), so the other shops' prices and statuses stay.
 *
 * PHP 5.6+ compatible.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonCatalogImporter
{
    private $idShop;
    private $idLang;
    private $lastError = null;
    private $notices = array();

    public function __construct($idLang = 0, $idShop = 0)
    {
        $this->idShop = $idShop ? (int) $idShop : (int) AmzproShop::actingId();
        if (!$this->idShop) {
            $this->idShop = 1;
        }
        $this->idLang = $idLang ? (int) $idLang : (int) Configuration::get(
            'PS_LANG_DEFAULT', null, AmzproShop::groupId($this->idShop), $this->idShop
        );
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Pull Amazon-side data onto products that already exist in the shop.
     *
     * Reads the staged table, so run an Amazon-side sync first. Each operation
     * is independent, because merchants rarely want all of them: a shop whose
     * descriptions are its own will still want stock, and a shop that prices
     * from Amazon will not want its titles rewritten.
     *
     * @param array $operations Any of: content, price, quantity, hide, features
     * @param int   $limit
     * @return array Per-operation counts
     */
    public function updateFromAmazon($operations, $limit = 500)
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array(
            'candidates' => 0, 'content' => 0, 'price' => 0,
            'quantity' => 0, 'hidden' => 0, 'features' => 0, 'failed' => 0,
        );
        $operations = array_intersect(
            (array) $operations,
            array('content', 'price', 'quantity', 'hide', 'features')
        );
        if (empty($operations)) {
            $this->lastError = AmazonI18n::get()->l('No operation selected.', 'amazoncatalogimporter');
            return $summary;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT ap.*, p.`id_product` AS ps_id
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` ap
             INNER JOIN `' . _DB_PREFIX_ . 'product_shop` p
                 ON (p.`id_product` = ap.`id_product` AND p.`id_shop` = ' . (int) $this->idShop . ')
             WHERE ap.`id_shop` = ' . (int) $this->idShop . '
               AND ap.`amazon_exists` = 1 AND ap.`id_product` > 0
             ORDER BY ap.`seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            $rows = array();
        }
        $summary['candidates'] = count($rows);

        foreach ($rows as $r) {
            $idProduct = (int) $r['id_product'];
            $idPa = (int) $r['id_product_attribute'];

            try {
                $product = new Product($idProduct, false, $this->idLang, $this->idShop);
                if (!Validate::isLoadedObject($product)) {
                    $summary['failed']++;
                    continue;
                }
                // Saves change this shop only, never the others.
                $product->id_shop_list = array($this->idShop);

                if (in_array('content', $operations)) {
                    $summary['content'] += $this->applyContent($product, $r) ? 1 : 0;
                }
                if (in_array('price', $operations) && (float) $r['amazon_price'] > 0) {
                    // Amazon prices include tax; PrestaShop stores them net.
                    $taxRate = (float) $product->getTaxesRate(null);
                    $net = ($taxRate > 0)
                        ? (float) $r['amazon_price'] / (1 + $taxRate / 100)
                        : (float) $r['amazon_price'];
                    $product->price = round($net, 6);
                    if ($product->update()) {
                        $summary['price']++;
                    }
                }
                if (in_array('quantity', $operations)) {
                    StockAvailable::setQuantity(
                        $idProduct, $idPa, (int) $r['amazon_quantity'], $this->idShop
                    );
                    $summary['quantity']++;
                }
                if (in_array('features', $operations)) {
                    $summary['features'] += $this->applyFeatures($product, $r);
                }
            } catch (Exception $e) {
                $summary['failed']++;
                $this->notices[] = $r['seller_sku'] . ': ' . $e->getMessage();
            }
        }

        // Hiding is computed the other way round: products the shop still
        // lists but Amazon no longer carries.
        if (in_array('hide', $operations)) {
            $summary['hidden'] = $this->hideUnavailable($limit);
        }

        if (in_array('price', $operations)) {
            $this->notices[] = AmazonI18n::get()->l('Prices were converted from Amazon\'s tax-inclusive figures using each product\'s tax rule — check a few before relying on them.', 'amazoncatalogimporter');
        }

        return $summary;
    }

    /** Overwrite title and description from the Amazon listing. */
    private function applyContent($product, $row)
    {
        $changed = false;

        $title = trim((string) $row['amazon_title']);
        if ($title !== '') {
            $product->name = array($this->idLang => Tools::substr($title, 0, 128));
            $changed = true;
        }
        $description = trim((string) $row['amazon_description']);
        if ($description !== '') {
            $product->description = array($this->idLang => $description);
            $changed = true;
        }
        $brand = trim((string) $row['amazon_brand']);
        if ($brand !== '') {
            $idManufacturer = (int) Manufacturer::getIdByName($brand);
            if (!$idManufacturer) {
                $manufacturer = new Manufacturer();
                $manufacturer->name = $brand;
                $manufacturer->active = true;
                $manufacturer->id_shop_list = array($this->idShop);
                if ($manufacturer->add()) {
                    $idManufacturer = (int) $manufacturer->id;
                }
            }
            if ($idManufacturer) {
                $product->id_manufacturer = $idManufacturer;
                $changed = true;
            }
        }

        return $changed ? (bool) $product->update() : false;
    }

    /**
     * Turn the listing's bullet points into PrestaShop features, creating the
     * feature and its value when they do not exist yet.
     *
     * @return int Features attached
     */
    private function applyFeatures($product, $row)
    {
        if (empty($row['amazon_bullet_points'])) {
            return 0;
        }
        $bullets = json_decode($row['amazon_bullet_points'], true);
        if (!is_array($bullets)) {
            return 0;
        }

        $added = 0;
        $position = 1;
        foreach (array_slice($bullets, 0, 5) as $bullet) {
            $bullet = trim(strip_tags((string) $bullet));
            if ($bullet === '') {
                continue;
            }
            $featureName = 'Amazon highlight ' . $position;
            $position++;

            $idFeature = (int) Db::getInstance()->getValue(
                'SELECT `id_feature` FROM `' . _DB_PREFIX_ . 'feature_lang`
                 WHERE `name` = \'' . pSQL($featureName) . '\' AND `id_lang` = ' . (int) $this->idLang
            );
            if (!$idFeature) {
                $feature = new Feature();
                $feature->name = array($this->idLang => $featureName);
                $feature->id_shop_list = array($this->idShop);
                if (!$feature->add()) {
                    continue;
                }
                $idFeature = (int) $feature->id;
            }

            $value = Tools::substr($bullet, 0, 255);
            $idValue = (int) Db::getInstance()->getValue(
                'SELECT fv.`id_feature_value` FROM `' . _DB_PREFIX_ . 'feature_value` fv
                 INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
                     ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = ' . (int) $this->idLang . ')
                 WHERE fv.`id_feature` = ' . $idFeature . ' AND fvl.`value` = \'' . pSQL($value) . '\''
            );
            if (!$idValue) {
                $featureValue = new FeatureValue();
                $featureValue->id_feature = $idFeature;
                $featureValue->custom = false;
                $featureValue->value = array($this->idLang => $value);
                if (!$featureValue->add()) {
                    continue;
                }
                $idValue = (int) $featureValue->id;
            }

            Db::getInstance()->execute(
                'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'feature_product`
                    (`id_feature`, `id_product`, `id_feature_value`)
                 VALUES (' . $idFeature . ', ' . (int) $product->id . ', ' . $idValue . ')'
            );
            $added++;
        }

        return $added;
    }

    /**
     * Deactivate shop products whose Amazon listing has gone (or run dry),
     * so the storefront stops offering what the marketplace no longer has.
     *
     * @return int Products deactivated
     */
    private function hideUnavailable($limit = 500)
    {
        // Only products still active in this shop; they are switched off in
        // this shop alone.
        $rows = Db::getInstance()->executeS(
            'SELECT ap.`id_product`, ap.`seller_sku`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` ap
             INNER JOIN `' . _DB_PREFIX_ . 'product_shop` p
                 ON (p.`id_product` = ap.`id_product` AND p.`id_shop` = ' . (int) $this->idShop . ')
             WHERE ap.`id_shop` = ' . (int) $this->idShop . '
               AND ap.`id_product` > 0 AND p.`active` = 1
               AND (ap.`amazon_exists` = 0 OR ap.`amazon_quantity` <= 0)
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows) || empty($rows)) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $r) {
            $product = new Product((int) $r['id_product'], false, null, $this->idShop);
            if (!Validate::isLoadedObject($product)) {
                continue;
            }
            $product->id_shop_list = array($this->idShop);
            $product->active = false;
            if ($product->update()) {
                $count++;
            }
        }
        if ($count > 0) {
            $this->notices[] = sprintf(
                AmazonI18n::get()->l('%d product(s) deactivated because Amazon no longer carries them or shows zero stock. They were not deleted — re-enable them from the catalogue.', 'amazoncatalogimporter'),
                $count
            );
        }

        return $count;
    }

    /**
     * Import all staged Amazon-only listings as PrestaShop products.
     *
     * @param int $idCategory Target category (0 = shop's Home category)
     * @param int $limit
     * @return array Summary counts
     */
    public function importAmazonOnlyProducts($idCategory = 0, $limit = 25)
    {
        $this->lastError = null;
        $this->notices = array();

        $summary = array('candidates' => 0, 'created' => 0, 'skipped' => 0, 'failed' => 0, 'images_failed' => 0);

        if (!$idCategory) {
            $idCategory = (int) Configuration::get(
                'PS_HOME_CATEGORY', null, AmzproShop::groupId($this->idShop), $this->idShop
            );
            if (!$idCategory) {
                $idCategory = 2;
            }
        }

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             WHERE `id_shop` = ' . (int) $this->idShop . '
               AND `sync_direction` = \'amazon_only\'
               AND `ps_exists` = 0 AND `id_product` = 0
             ORDER BY `seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            $rows = array();
        }
        $summary['candidates'] = count($rows);

        foreach ($rows as $row) {
            // The SKU may exist in this shop already (reference match not yet staged)
            $existing = (int) Db::getInstance()->getValue(
                'SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p
                 INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                     ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . (int) $this->idShop . ')
                 WHERE p.`reference` = \'' . pSQL($row['seller_sku']) . '\''
            );
            if (!$existing) {
                // A product of another shop carries the reference: creating a
                // second one would give the catalogue a duplicate reference.
                $elsewhere = (int) Db::getInstance()->getValue(
                    'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
                     WHERE `reference` = \'' . pSQL($row['seller_sku']) . '\''
                );
                if ($elsewhere) {
                    $summary['skipped']++;
                    $this->notices[] = sprintf(
                        AmazonI18n::get()->l('SKU %1$s: product #%2$d already has this reference but is not in this shop. Add it to this shop, then sync again.', 'amazoncatalogimporter'),
                        $row['seller_sku'],
                        $elsewhere
                    );
                    continue;
                }
            }
            if ($existing) {
                $this->linkStagedRow($row['seller_sku'], $existing);
                $summary['skipped']++;
                $this->notices[] = sprintf(
                    AmazonI18n::get()->l('SKU %1$s: already exists as product #%2$d — linked.', 'amazoncatalogimporter'),
                    $row['seller_sku'],
                    $existing
                );
                continue;
            }

            $idProduct = $this->createProduct($row, $idCategory, $summary);
            if ($idProduct) {
                $summary['created']++;
            } else {
                $summary['failed']++;
                $this->notices[] = sprintf(
                    AmazonI18n::get()->l('SKU %1$s: %2$s', 'amazoncatalogimporter'),
                    $row['seller_sku'],
                    $this->lastError
                );
            }
        }

        return $summary;
    }

    /**
     * @return int|false New id_product, or false (lastError set)
     */
    private function createProduct($row, $idCategory, &$summary)
    {
        $this->lastError = null;

        $name = trim((string) $row['amazon_title']);
        if ($name === '') {
            $this->lastError = AmazonI18n::get()->l('Amazon listing has no title.', 'amazoncatalogimporter');
            return false;
        }
        // PS product name limit is 128 chars and forbids some characters.
        $name = Tools::substr(preg_replace('/[<>;=#{}]/', '', $name), 0, 128);

        $product = new Product();
        foreach (Language::getLanguages(false) as $lang) {
            $product->name[$lang['id_lang']] = $name;
            $product->link_rewrite[$lang['id_lang']] = Tools::str2url($name);
        }

        $product->reference = $row['seller_sku'];
        $product->id_category_default = (int) $idCategory;
        $product->id_shop_default = $this->idShop;
        // Created in this shop only.
        $product->id_shop_list = array($this->idShop);
        // Amazon prices are tax-inclusive; PS stores tax-exclusive. We import
        // the amount as-is with no tax group and flag it for review.
        $product->price = (float) $row['amazon_price'];
        $product->id_tax_rules_group = 0;
        $product->active = false; // merchant reviews before publishing
        $product->visibility = 'both';
        $product->condition = 'new';

        $desc = trim((string) $row['amazon_description']);
        $bullets = trim((string) $row['amazon_bullet_points']);
        $shortDesc = '';
        if ($bullets !== '') {
            $shortDesc = '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', explode("\n", $bullets))) . '</li></ul>';
            $shortDesc = Tools::substr($shortDesc, 0, 800);
        }
        foreach (Language::getLanguages(false) as $lang) {
            if ($desc !== '') {
                $product->description[$lang['id_lang']] = Tools::substr(htmlspecialchars($desc), 0, 21844);
            }
            if ($shortDesc !== '') {
                $product->description_short[$lang['id_lang']] = $shortDesc;
            }
        }

        // Brand -> manufacturer (created on demand)
        $brand = trim((string) $row['amazon_brand']);
        if ($brand !== '') {
            $idManufacturer = (int) Manufacturer::getIdByName($brand);
            if (!$idManufacturer) {
                $manufacturer = new Manufacturer();
                $manufacturer->name = Tools::substr($brand, 0, 64);
                $manufacturer->active = true;
                $manufacturer->id_shop_list = array($this->idShop);
                if ($manufacturer->add()) {
                    $idManufacturer = (int) $manufacturer->id;
                }
            }
            if ($idManufacturer) {
                $product->id_manufacturer = $idManufacturer;
            }
        }

        if (!$product->add()) {
            $this->lastError = AmazonI18n::get()->l('Could not create the product.', 'amazoncatalogimporter');
            return false;
        }

        $product->updateCategories(array_unique(array((int) $idCategory)));

        // Stock
        StockAvailable::setQuantity((int) $product->id, 0, (int) $row['amazon_quantity'], $this->idShop);

        // Images (best effort — a failed download never fails the import)
        $images = json_decode((string) $row['amazon_images'], true);
        if (is_array($images)) {
            $first = true;
            foreach (array_slice($images, 0, 6) as $url) {
                if (!$this->importImage((int) $product->id, $url, $first)) {
                    $summary['images_failed']++;
                } else {
                    $first = false;
                }
            }
        }

        $this->linkStagedRow($row['seller_sku'], (int) $product->id);

        return (int) $product->id;
    }

    /**
     * Download one image URL and attach it to the product.
     *
     * @return bool
     */
    private function importImage($idProduct, $url, $isCover)
    {
        $url = trim((string) $url);
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $tmpFile = tempnam(_PS_TMP_IMG_DIR_, 'mkpro');
        if ($tmpFile === false || !Tools::copy($url, $tmpFile)) {
            if ($tmpFile !== false) {
                @unlink($tmpFile);
            }
            return false;
        }
        if (!filesize($tmpFile) || !ImageManager::isRealImage($tmpFile, null)) {
            @unlink($tmpFile);
            return false;
        }

        $image = new Image();
        $image->id_product = $idProduct;
        $image->position = Image::getHighestPosition($idProduct) + 1;
        $image->cover = $isCover;
        $image->id_shop_list = array($this->idShop);
        if (!$image->add()) {
            @unlink($tmpFile);
            return false;
        }

        $path = $image->getPathForCreation();
        $ok = ImageManager::resize($tmpFile, $path . '.jpg');
        if ($ok) {
            foreach (ImageType::getImagesTypes('products') as $imageType) {
                ImageManager::resize(
                    $tmpFile,
                    $path . '-' . stripslashes($imageType['name']) . '.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );
            }
        }
        @unlink($tmpFile);

        if (!$ok) {
            $image->delete();
            return false;
        }

        return true;
    }

    /**
     * Point the staged row at the (new or found) PS product.
     */
    private function linkStagedRow($sku, $idProduct)
    {
        $idLang = $this->idLang;
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product` pr ON (pr.`id_product` = ' . (int) $idProduct . ')
             LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                 ON (ps.`id_product` = pr.`id_product` AND ps.`id_shop` = ' . (int) $this->idShop . ')
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = pr.`id_product` AND pl.`id_lang` = ' . (int) $idLang . '
                     AND pl.`id_shop` = ' . (int) $this->idShop . ')
             SET p.`id_product` = ' . (int) $idProduct . ',
                 p.`ps_exists` = 1,
                 p.`ps_name` = IFNULL(pl.`name`, \'\'),
                 p.`ps_price` = IFNULL(ps.`price`, pr.`price`),
                 p.`sync_direction` = \'in_sync\',
                 p.`date_upd` = \'' . pSQL($now) . '\'
             WHERE p.`seller_sku` = \'' . pSQL($sku) . '\'
               AND p.`id_shop` = ' . (int) $this->idShop
        );
    }
}
