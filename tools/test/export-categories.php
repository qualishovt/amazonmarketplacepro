<?php
/**
 * 1.6.18: "Products sent to Amazon: only the products in these categories".
 * With 8000 products and 400 for Amazon, the merchant picks the products by
 * putting them in a category (usually a hidden "Amazon" one) instead of
 * switching the other 7600 off. Any category of a product counts, not only
 * its default one.
 *
 *     php tools/test/export-categories.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$t = $p . 'amazonmarketplacepro_product';
$idShop = (int) AmzproShop::actingId();
$idLang = (int) Configuration::get('PS_LANG_DEFAULT');
$keep = AmzproShop::get('AMZPRO_EXPORT_CATEGORIES', $idShop);

// Two active products of this shop with a reference.
$products = array_map('intval', array_column($db->executeS('SELECT p.`id_product` FROM `' . $p . 'product` p
    INNER JOIN `' . $p . 'product_shop` ps ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ' AND ps.`active` = 1)
    WHERE p.`reference` <> \'\' ORDER BY p.`id_product` ASC LIMIT 2'), 'id_product'));
list($chosen, $other) = $products;
echo 'chosen #' . $chosen . ', other #' . $other . "\n";

// A hidden category that only groups products for Amazon.
$cat = new Category();
$cat->name = [$idLang => 'ZZ Amazon'];
$cat->link_rewrite = [$idLang => 'zz-amazon'];
$cat->id_parent = (int) Configuration::get('PS_HOME_CATEGORY');
$cat->active = 0;
$cat->add();
$db->execute('INSERT IGNORE INTO `' . $p . 'category_shop` (`id_category`, `id_shop`) VALUES (' . (int) $cat->id . ', ' . $idShop . ')');
$db->execute('INSERT INTO `' . $p . 'category_product` (`id_category`, `id_product`, `position`) VALUES (' . (int) $cat->id . ', ' . $chosen . ', 0)');

try {
    // 1. off: every product may go
    AmzproShop::set('AMZPRO_EXPORT_CATEGORIES', '');
    check('no category chosen: no restriction', AmazonListingSettings::exportCategoryProducts(), null);
    check('no category chosen: the other product is sent', AmazonListingSettings::isSyncEnabled($other), true);

    // 2. only the hidden category
    AmzproShop::set('AMZPRO_EXPORT_CATEGORIES', (string) $cat->id);
    check('the product in it (not its default category) is sent', AmazonListingSettings::isSyncEnabled($chosen), true);
    check('a product outside it is not', AmazonListingSettings::isSyncEnabled($other), false);

    // 3. the scan stages only that product
    withStagedSnapshot(function () use ($db, $t, $idShop, $chosen, $other) {
        $sync = new AmazonProductSync(testClient(), (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID'), 'SELLERTEST');
        $sync->syncPrestashopSide();
        $staged = array_map('intval', array_column($db->executeS('SELECT DISTINCT `id_product` FROM `' . $t . '`
            WHERE `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'\' AND `ps_exists` = 1'), 'id_product'));
        check('only the chosen product is staged', $staged, [$chosen]);
    });

    // 4. the settings list shows the hidden category
    $module = Module::getInstanceByName('amazonmarketplacepro');
    $tree = new ReflectionMethod($module, 'exportCategoryTree');
    $tree->setAccessible(true);
    $found = array_values(array_filter($tree->invoke($module), function ($c) use ($cat) {
        return $c['id'] === (int) $cat->id;
    }));
    check('the hidden category can be chosen', $found ? [$found[0]['name'], $found[0]['active']] : null, ['ZZ Amazon', 0]);
} finally {
    AmzproShop::set('AMZPRO_EXPORT_CATEGORIES', (string) $keep);
    // In multistore Category::delete() only drops the shop link, so the rows go by hand.
    foreach (['category_product', 'category_group', 'category_shop', 'category_lang', 'category'] as $table) {
        $db->execute('DELETE FROM `' . $p . $table . '` WHERE `id_category` = ' . (int) $cat->id);
    }
    Category::regenerateEntireNtree();
}
done();
