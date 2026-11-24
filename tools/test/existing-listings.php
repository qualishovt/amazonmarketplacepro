<?php
/**
 * 1.6.29 (Schwietz, 21 Nov 2026):
 *  - a listing already on Amazon gets a partial price + stock update, not a
 *    full replace (which dropped the Seller Central shipping template and,
 *    with a product type, overwrote the Amazon page);
 *  - the product's "sync price" switch keeps its Amazon price;
 *  - a product with no ASIN and no product type is not sent at all;
 *  - prices are rounded once, after VAT (net 16.764706 + 19 % was 19.94);
 *  - Amazon's own title, description and manufacturer per product.
 *
 *     php tools/test/existing-listings.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

/** Records every call and accepts it. */
class ExistingListingsClient extends AmazonSpApiClient
{
    public $calls = [];

    public function request($method, $path, $query = [], $body = null)
    {
        $this->calls[rawurldecode(basename($path))] = [$method, $body];

        return ['status' => 200, 'body' => ['status' => 'ACCEPTED', 'issues' => []]];
    }
}

$db = Db::getInstance();
$p = _DB_PREFIX_;
$t = $p . 'amazonmarketplacepro_product';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$idShop = (int) AmzproShop::actingId();
$keep = [];
foreach (['AMZPRO_EXISTING_LISTINGS', 'AMZPRO_SYNC_MODE', 'AMZPRO_PRICE_TAX', 'AMZPRO_ROUNDING', 'AMZPRO_ONLY_WITH_ASIN',
    'AMZPRO_DELETE_WHEN_OOS', 'AMZPRO_EXPORT_LIMIT', 'AMZPRO_MARKUP_SOURCES', 'AMZPRO_DEFAULT_MARKUP', ] as $k) {
    $keep[$k] = Configuration::getGlobalValue($k);
}
Configuration::updateGlobalValue('AMZPRO_SYNC_MODE', 'normal');
Configuration::updateGlobalValue('AMZPRO_ONLY_WITH_ASIN', 0);
Configuration::updateGlobalValue('AMZPRO_DELETE_WHEN_OOS', 0);
Configuration::updateGlobalValue('AMZPRO_EXPORT_LIMIT', 0);
Configuration::updateGlobalValue('AMZPRO_ROUNDING', 'cents');
AmazonProductOverride::ensureTable();

$catLive = 999901;
$catNew = 999902;
$pidNoPrice = 999990;
$pidTitle = 999991;
$pidProfile = 999992;
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonProfile.php';
AmazonProfile::ensureTables();
$cleanup = function () use ($db, $p, $catLive, $catNew, $pidNoPrice, $pidTitle, $pidProfile) {
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_profile` WHERE `name` = \'ZZE Endkappen\'');
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_category_map` WHERE `id_category` IN (' . $catLive . ', ' . $catNew . ')');
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_product_setting` WHERE `id_product` IN (' . $pidNoPrice . ', ' . $pidTitle . ', ' . $pidProfile . ')');
};

withStagedSnapshot(function () use ($db, $p, $t, $main, $idShop, $catLive, $catNew, $pidNoPrice, $pidTitle, $pidProfile, $cleanup) {
    $cleanup();
    try {
        $db->execute('DELETE FROM `' . $t . '` WHERE `id_shop` = ' . $idShop);
        $row = function ($sku, $exists, $dir, $asin, $cat, $idProduct) use ($db, $t, $idShop) {
            $db->insert('amazonmarketplacepro_product', [
                'id_shop' => $idShop, 'marketplace_id' => '', 'seller_sku' => $sku, 'id_product' => $idProduct,
                'ps_exists' => 1, 'ps_name' => 'Shop name ' . $sku, 'ps_price' => 19.95, 'ps_quantity' => 30,
                'ps_description' => 'Shop description', 'ps_manufacturer' => 'LIMLESSPRO', 'ps_ean13' => '4251907700661',
                'ps_id_category_default' => $cat, 'amazon_exists' => $exists, 'amazon_asin' => $asin,
                'amazon_product_type' => $exists ? 'AUTO_ACCESSORY' : '', 'amazon_price' => 19.95, 'amazon_quantity' => 25,
                'sync_direction' => $dir, 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s'),
            ]);
        };
        $row('ZZE-LIVE', 1, 'conflict', 'B0LIVE0001', 0, 0);
        $row('ZZE-NOPRICE', 1, 'conflict', 'B0LIVE0002', 0, $pidNoPrice);
        $row('ZZE-LIVEMAP', 1, 'conflict', 'B0LIVE0003', $catLive, 0);
        $row('ZZE-NOID', 0, 'ps_only', '', 0, 0);
        $row('ZZE-ASIN', 0, 'ps_only', 'B0OTHER001', 0, 0);
        $row('ZZE-NEW', 0, 'ps_only', '', $catNew, $pidTitle);
        // In the shop's home category with everything else: only its own
        // profile makes it a new Amazon product.
        $row('ZZE-PROF', 0, 'ps_only', '', 2, $pidProfile);
        $row('ZZE-SIBLING', 0, 'ps_only', '', 2, 0);
        $idProfile = AmazonProfile::save(['name' => 'ZZE Endkappen', 'product_type' => 'SPORTING_GOODS', 'marketplace_id' => $main,
            'browse_nodes' => '2509246031', 'is_variation' => 0, 'variation_attributes' => '', 'attributes' => [],
            'raw_attributes_json' => json_encode(['supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable', 'marketplace_id' => $main]]]),
            'latency' => '', 'shipping_template' => '', 'price_markup' => '', 'gtin_exemption' => 0, 'categories' => [], ]);
        $db->insert('amazonmarketplacepro_product_setting', ['id_shop' => 0, 'id_product' => $pidProfile, 'sync' => 1,
            'id_profile' => (int) $idProfile, 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);
        $db->insert('amazonmarketplacepro_product_setting', ['id_shop' => 0, 'id_product' => $pidNoPrice, 'sync' => 1,
            'sync_price' => 0, 'sync_quantity' => 1, 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);
        $db->insert('amazonmarketplacepro_product_setting', ['id_shop' => 0, 'id_product' => $pidTitle, 'sync' => 1,
            'amazon_title' => 'LIMLESSPRO Endkappe Amazon-Titel', 'amazon_description' => 'Amazon-Beschreibung',
            'manufacturer' => 'Schwietz', 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);

        $client = new ExistingListingsClient('id', 'secret', 'token', 'https://sellingpartnerapi-eu.amazon.com');
        $sync = new AmazonProductSync($client, $main, 'SELLERTEST');
        $sync->saveCategoryMapping($catLive, 'AUTO_ACCESSORY', '', $main);
        $sync->saveCategoryMapping($catNew, 'AUTO_ACCESSORY', '', $main);

        Configuration::updateGlobalValue('AMZPRO_EXISTING_LISTINGS', 'offer');
        $s = $sync->pushToAmazon(100);
        $c = $client->calls;
        check('a product with no ASIN and no product type is not sent', isset($c['ZZE-NOID']), false);
        check('six sent, all accepted', [$s['candidates'], $s['pushed'], $s['failed']], [6, 6, 0]);
        check('a sibling in the same category without a profile is not sent', isset($c['ZZE-SIBLING']), false);
        $prof = $c['ZZE-PROF'][1];
        check('product profile: full listing with the profile type', [$c['ZZE-PROF'][0], $prof['requirements'], $prof['productType']], ['PUT', 'LISTING', 'SPORTING_GOODS']);
        check('product profile: browse node and raw attribute', [$prof['attributes']['recommended_browse_nodes'][0]['value'],
            $prof['attributes']['supplier_declared_dg_hz_regulation'][0]['value']], ['2509246031', 'not_applicable']);

        $paths = function ($call) {
            return array_map(function ($x) {
                return $x['path'];
            }, $call[1]['patches']);
        };
        check('live listing: PATCH, not PUT', $c['ZZE-LIVE'][0], 'PATCH');
        check('live listing: price and stock only', $paths($c['ZZE-LIVE']), ['/attributes/purchasable_offer', '/attributes/fulfillment_availability']);
        check('live listing: under its own product type', $c['ZZE-LIVE'][1]['productType'], 'AUTO_ACCESSORY');
        check('live listing: price incl. VAT', $c['ZZE-LIVE'][1]['patches'][0]['value'][0]['our_price'][0]['schedule'][0]['value_with_tax'], 19.95);
        check('price sync off: only the stock is sent', $paths($c['ZZE-NOPRICE']), ['/attributes/fulfillment_availability']);
        check('live + product type, "price and stock only": PATCH', $c['ZZE-LIVEMAP'][0], 'PATCH');
        check('ASIN, not live yet: offer-only PUT', [$c['ZZE-ASIN'][0], $c['ZZE-ASIN'][1]['requirements']], ['PUT', 'LISTING_OFFER_ONLY']);

        $new = $c['ZZE-NEW'][1];
        check('new product with a product type: full listing', [$c['ZZE-NEW'][0], $new['requirements'], $new['productType']], ['PUT', 'LISTING', 'AUTO_ACCESSORY']);
        check('new product: Amazon title', $new['attributes']['item_name'][0]['value'], 'LIMLESSPRO Endkappe Amazon-Titel');
        check('new product: Amazon description', $new['attributes']['product_description'][0]['value'], 'Amazon-Beschreibung');
        check('new product: manufacturer', $new['attributes']['manufacturer'][0]['value'], 'Schwietz');
        check('new product: brand from the shop', $new['attributes']['brand'][0]['value'], 'LIMLESSPRO');

        // "Also send title, description, brand and images": a live listing
        // with a product type gets the full data again, as before 1.6.29.
        Configuration::updateGlobalValue('AMZPRO_EXISTING_LISTINGS', 'full');
        $client->calls = [];
        $sync->pushToAmazon(100);
        $c = $client->calls;
        check('"full": live + product type is a full PUT', [$c['ZZE-LIVEMAP'][0], $c['ZZE-LIVEMAP'][1]['requirements']], ['PUT', 'LISTING']);
        check('"full": live without product type is still a PATCH', $c['ZZE-LIVE'][0], 'PATCH');
        check('"full": the shop title when no Amazon title is set', $c['ZZE-LIVEMAP'][1]['attributes']['item_name'][0]['value'], 'Shop name ZZE-LIVEMAP');
    } finally {
        $cleanup();
    }
});

// A live listing as the start of a category mapping.
class TemplateListingClient extends AmazonSpApiClient
{
    public function request($method, $path, $query = [], $body = null)
    {
        $m = $query['marketplaceIds'];
        // A listing matched to an existing ASIN: its offer, nothing else.
        if (rawurldecode(basename($path)) === '000017') {
            return ['status' => 200, 'body' => ['sku' => '000017',
                'summaries' => [['productType' => 'SPORTING_GOODS', 'asin' => 'B0B6GR2GLD']],
                // matched to an existing ASIN: the offer only
                'attributes' => [
                    'condition_type' => [['value' => 'new_new', 'marketplace_id' => $m]],
                    'purchasable_offer' => [['marketplace_id' => $m, 'currency' => 'EUR']],
                ]]];
        }
        if (strpos($path, '/catalog/2022-04-01/items/B0B6GR2GLD') === 0) {
            return ['status' => 200, 'body' => [
                'asin' => 'B0B6GR2GLD',
                'attributes' => [
                    'item_name' => [['value' => 'LIMLESSPRO Endkappe', 'marketplace_id' => $m]],
                    'material' => [['value' => 'Kunststoff', 'language_tag' => 'de_DE', 'marketplace_id' => $m]],
                    'color' => [['value' => 'Weiß', 'language_tag' => 'de_DE', 'marketplace_id' => $m]],
                ],
                'classifications' => [['marketplaceId' => $m, 'classifications' => [
                    ['displayName' => 'Abdeckungen', 'classificationId' => '2301009031'],
                ]]],
            ]];
        }
        if (rawurldecode(basename($path)) !== '000063') {
            return ['status' => 404, 'body' => ['errors' => [['message' => 'NOT_FOUND']]]];
        }
        $m = $query['marketplaceIds'];

        return ['status' => 200, 'body' => [
            'sku' => '000063',
            'summaries' => [['productType' => 'SPORTING_GOODS', 'asin' => 'B0BXSY3BWN']],
            'attributes' => [
                'item_name' => [['value' => 'Endkappe klein', 'marketplace_id' => $m]],
                'brand' => [['value' => 'LIMLESSPRO', 'marketplace_id' => $m]],
                'externally_assigned_product_identifier' => [['type' => 'ean', 'value' => '4251907700999', 'marketplace_id' => $m]],
                'main_product_image_locator' => [['media_location' => 'https://m.media-amazon.com/x.jpg', 'marketplace_id' => $m]],
                'purchasable_offer' => [['marketplace_id' => $m, 'currency' => 'EUR']],
                'recommended_browse_nodes' => [['value' => '2301009031', 'marketplace_id' => $m]],
                'material' => [['value' => 'Kunststoff', 'language_tag' => 'de_DE', 'marketplace_id' => $m],
                    ['value' => 'Plastique', 'language_tag' => 'fr_FR', 'marketplace_id' => 'A13V1IB3VIYZZH']],
                'color' => [['value' => 'Weiß', 'language_tag' => 'de_DE', 'marketplace_id' => $m]],
                'country_of_origin' => [['value' => 'DE', 'marketplace_id' => $m]],
            ],
        ]];
    }
}
$tsync = new AmazonProductSync(new TemplateListingClient('id', 'secret', 'token', 'https://sellingpartnerapi-eu.amazon.com'), $main, 'SELLERTEST');
$tpl = $tsync->listingTemplate('000063');
check('template: product type and browse node', [$tpl['product_type'], $tpl['browse_nodes']], ['SPORTING_GOODS', '2301009031']);
check('template: only shared attributes, for this marketplace', array_keys($tpl['attributes']), ['color', 'country_of_origin', 'material']);
check('template: one material value (this marketplace)', count($tpl['attributes']['material']), 1);
$tpl = $tsync->listingTemplate('000017');
check('older listing: catalogue attributes and browse node', [$tpl['product_type'], $tpl['browse_nodes'], array_keys($tpl['attributes'])],
    ['SPORTING_GOODS', '2301009031', ['color', 'material']]);
check('template: unknown SKU is reported', [$tsync->listingTemplate('NOPE'), strpos((string) $tsync->getLastError(), 'NOPE') !== false], [false, true]);

// Rounding: once, after VAT.
// A product under a temporary 19 % rule for the shop's country, so the net
// price really has to be rounded (16.764706 + 19 %).
$prod = $db->getRow('SELECT ps.`id_product`, ps.`price`, ps.`id_tax_rules_group` FROM `' . $p . 'product_shop` ps
    INNER JOIN `' . $p . 'product` pr ON pr.`id_product` = ps.`id_product`
    WHERE ps.`id_shop` = ' . $idShop . ' AND ps.`active` = 1 AND pr.`reference` <> \'\'
    ORDER BY ps.`id_product`');
$tax = new Tax();
$tax->name = [(int) Configuration::get('PS_LANG_DEFAULT') => 'ZZ test 19'];
$tax->rate = 19;
$tax->active = 1;
$tax->add();
$group = new TaxRulesGroup();
$group->name = 'ZZ test 19';
$group->active = 1;
$group->add();
$rule = new TaxRule();
$rule->id_tax_rules_group = (int) $group->id;
$rule->id_country = (int) AmzproShop::context()->country->id;
$rule->id_tax = (int) $tax->id;
$rule->add();
foreach (['product', 'product_shop'] as $tbl) {
    $db->execute('UPDATE `' . $p . $tbl . '` SET `id_tax_rules_group` = ' . (int) $group->id . ' WHERE `id_product` = ' . (int) $prod['id_product']);
}
Cache::clean('*');
$rate = (float) Tax::getProductTaxRate((int) $prod['id_product'], null, AmzproShop::context());
Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', 'incl');
Configuration::updateGlobalValue('AMZPRO_MARKUP_SOURCES', '');
Configuration::updateGlobalValue('AMZPRO_DEFAULT_MARKUP', '');
check('the test product carries 19 %', $rate, 19.0);
$net = round(19.95 / (1 + $rate / 100), 6);
echo '   product #' . $prod['id_product'] . ', tax ' . $rate . " %, net $net; the net rounded first gives "
    . round(round($net, 2) * (1 + $rate / 100), 2) . "\n";
withStagedSnapshot(function () use ($db, $p, $t, $prod, $net, $main) {
    $db->execute('UPDATE `' . $p . 'product_shop` SET `price` = ' . $net . ' WHERE `id_product` = ' . (int) $prod['id_product']);
    try {
        $s = new AmazonProductSync(testClient(), $main, 'SELLERTEST');
        $s->syncPrestashopSide();
        $got = (float) $db->getValue('SELECT `ps_price` FROM `' . $t . '` WHERE `marketplace_id` = \'\'
            AND `id_product` = ' . (int) $prod['id_product'] . ' AND `id_product_attribute` = 0 AND `ps_exists` = 1');
        check('gross 19.95 stays 19.95 (rounded after VAT)', $got, 19.95);
    } finally {
        $db->execute('UPDATE `' . $p . 'product_shop` SET `price` = ' . (float) $prod['price'] . ' WHERE `id_product` = ' . (int) $prod['id_product']);
    }
});
foreach (['product', 'product_shop'] as $tbl) {
    $db->execute('UPDATE `' . $p . $tbl . '` SET `id_tax_rules_group` = ' . (int) $prod['id_tax_rules_group'] . ' WHERE `id_product` = ' . (int) $prod['id_product']);
}
$rule->delete();
$group->delete();
$tax->delete();

foreach ($keep as $k => $v) {
    if ($v === false) {
        Configuration::deleteByName($k);
    } else {
        Configuration::updateGlobalValue($k, $v);
    }
}
done();
