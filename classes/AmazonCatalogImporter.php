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
 * PHP 5.6+ compatible.
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonCatalogImporter
{
    private $idShop;
    private $idLang;
    private $lastError = null;
    private $notices = array();

    public function __construct($idLang = 0, $idShop = 0)
    {
        $this->idLang = $idLang ? (int) $idLang : (int) Configuration::get('PS_LANG_DEFAULT');
        $this->idShop = $idShop ? (int) $idShop : (int) Context::getContext()->shop->id;
        if (!$this->idShop) {
            $this->idShop = 1;
        }
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
            $idCategory = (int) Configuration::get('PS_HOME_CATEGORY');
            if (!$idCategory) {
                $idCategory = 2;
            }
        }

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             WHERE `sync_direction` = \'amazon_only\'
               AND `ps_exists` = 0 AND `id_product` = 0
             ORDER BY `seller_sku` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            $rows = array();
        }
        $summary['candidates'] = count($rows);

        foreach ($rows as $row) {
            // The SKU may exist in PS already (reference match not yet staged)
            $existing = (int) Db::getInstance()->getValue(
                'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product`
                 WHERE `reference` = \'' . pSQL($row['seller_sku']) . '\''
            );
            if ($existing) {
                $this->linkStagedRow($row['seller_sku'], $existing);
                $summary['skipped']++;
                $this->notices[] = 'SKU ' . $row['seller_sku'] . ': already exists as product #' . $existing . ' — linked.';
                continue;
            }

            $idProduct = $this->createProduct($row, $idCategory, $summary);
            if ($idProduct) {
                $summary['created']++;
            } else {
                $summary['failed']++;
                $this->notices[] = 'SKU ' . $row['seller_sku'] . ': ' . $this->lastError;
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
            $this->lastError = 'Amazon listing has no title.';
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
        // Amazon prices are tax-inclusive; PS stores tax-exclusive. We import
        // the amount as-is with no tax group and flag it for review.
        $product->price = (float) $row['amazon_price'];
        $product->id_tax_rules_group = 0;
        $product->active = 0; // merchant reviews before publishing
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
                $manufacturer->active = 1;
                if ($manufacturer->add()) {
                    $idManufacturer = (int) $manufacturer->id;
                }
            }
            if ($idManufacturer) {
                $product->id_manufacturer = $idManufacturer;
            }
        }

        if (!$product->add()) {
            $this->lastError = 'Could not create the product.';
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
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = pr.`id_product` AND pl.`id_lang` = ' . (int) $idLang . '
                     AND pl.`id_shop` = ' . (int) $this->idShop . ')
             SET p.`id_product` = ' . (int) $idProduct . ',
                 p.`ps_exists` = 1,
                 p.`ps_name` = IFNULL(pl.`name`, \'\'),
                 p.`ps_price` = pr.`price`,
                 p.`sync_direction` = \'in_sync\',
                 p.`date_upd` = \'' . pSQL($now) . '\'
             WHERE p.`seller_sku` = \'' . pSQL($sku) . '\''
        );
    }
}
