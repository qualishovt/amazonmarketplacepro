<?php
/**
 * 1.6.10: the repricer works from Amazon's prices, not the shop's.
 * - Our Price is our own offer on Amazon (item + shipping), the Buy Box is
 *   the one for new items, the lowest price is another seller's offer.
 * - Rules target a landed price and subtract our shipping; holding the Buy
 *   Box already means no change; old suggestions are not pushed again.
 * - The shop price never changes, and the product sync sends the repriced
 *   price instead of the shop's.
 * - A SKU Amazon has only on other marketplaces is not a listing here.
 *
 *     php tools/test/repricing-rules.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonRepricingEngine.php';

/** Answers the pricing calls like Amazon for two ASINs. */
class FakePricingClient extends AmazonSpApiClient
{
    public $calls = [];
    public $throttleOnce = true;

    public function request($method, $path, $query = [], $body = null)
    {
        $this->calls[] = $path;
        if ($path === '/products/pricing/v0/competitivePrice') {
            if ($this->throttleOnce) {
                $this->throttleOnce = false;

                return ['status' => 429, 'body' => ['errors' => [['code' => 'QuotaExceeded']]]];
            }
            $bb = function ($id, $cond, $item, $ship, $mine) {
                return ['CompetitivePriceId' => $id, 'condition' => $cond, 'belongsToRequester' => $mine, 'Price' => [
                    'LandedPrice' => ['Amount' => $item + $ship], 'ListingPrice' => ['Amount' => $item], 'Shipping' => ['Amount' => $ship], ]];
            };

            return ['status' => 200, 'body' => ['payload' => [
                ['ASIN' => 'B0ZZTLOSE', 'Product' => ['CompetitivePricing' => [
                    'CompetitivePrices' => [$bb('1', 'New', 289.99, 6.99, false), $bb('2', 'Used', 150.00, 0, false)],
                    'NumberOfOfferListings' => [['condition' => 'New', 'Count' => 4], ['condition' => 'Used', 'Count' => 2]], ]]],
                ['ASIN' => 'B0ZZTWIN', 'Product' => ['CompetitivePricing' => [
                    'CompetitivePrices' => [$bb('1', 'New', 99.00, 0, true)], ]]],
            ]]];
        }
        if ($path === '/products/pricing/v0/price') {
            return ['status' => 200, 'body' => ['payload' => [
                ['SellerSKU' => 'ZZT-LOSE', 'Product' => ['Offers' => [['BuyingPrice' => [
                    'ListingPrice' => ['Amount' => 317.40], 'Shipping' => ['Amount' => 6.99], ]]]]],
                ['SellerSKU' => 'ZZT-WIN', 'Product' => ['Offers' => [['BuyingPrice' => [
                    'ListingPrice' => ['Amount' => 99.00], 'Shipping' => ['Amount' => 0], ]]]]],
            ]]];
        }
        if ($path === '/batches/products/pricing/v0/itemOffers') {
            return ['status' => 200, 'body' => ['responses' => [
                ['status' => ['statusCode' => 200], 'body' => ['payload' => ['ASIN' => 'B0ZZTLOSE', 'Offers' => [
                    ['MyOffer' => true, 'ListingPrice' => ['Amount' => 200.00], 'Shipping' => ['Amount' => 0]],
                    ['MyOffer' => false, 'ListingPrice' => ['Amount' => 280.00], 'Shipping' => ['Amount' => 5.00]],
                    ['MyOffer' => false, 'ListingPrice' => ['Amount' => 289.99], 'Shipping' => ['Amount' => 6.99]],
                ]]]],
            ]]];
        }
        if (strpos($path, '/listings/2021-08-01/items/') === 0 && $method === 'GET') {
            $sku = rawurldecode(basename($path));
            if ($sku === 'ZZT-ELSEWHERE') {
                return ['status' => 200, 'body' => ['sku' => $sku, 'summaries' => [], 'offers' => []]];
            }
            if ($sku === 'ZZT-GONE') {
                return ['status' => 404, 'body' => ['errors' => [['code' => 'NOT_FOUND']]]];
            }

            return ['status' => 500, 'body' => ['errors' => [['code' => 'InternalFailure']]]];
        }

        return ['status' => 200, 'body' => ['status' => 'ACCEPTED', 'issues' => []]];
    }
}

AmzproShop::ensureSchema();
$db = Db::getInstance();
$cpT = _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price';
$ruleT = _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule';
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$idShop = (int) AmzproShop::actingId();
foreach ([$cpT, $ruleT] as $table) {
    $db->execute('DROP TABLE IF EXISTS `' . $table . '_snap`');
    $db->execute('CREATE TABLE `' . $table . '_snap` AS SELECT * FROM `' . $table . '`');
}

/** Run a private method. */
function invoke($object, $method, array $args = [])
{
    $m = new ReflectionMethod($object, $method);
    $m->setAccessible(true);

    return $m->invokeArgs($object, $args);
}

function setRule($type, $adjustment)
{
    $db = Db::getInstance();
    $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`');
    $db->execute('INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
        (`id_shop`, `name`, `rule_type`, `price_adjustment`, `adjustment_type`, `min_price`, `max_price`,
         `target_buybox`, `id_category`, `marketplace_id`, `active`, `date_add`, `date_upd`)
        VALUES (0, \'t\', \'' . pSQL($type) . '\', ' . (float) $adjustment . ', \'percentage\', 0, 0, 1, 0, \'\', 1, NOW(), NOW())');
}

function suggested($sku)
{
    return (float) Db::getInstance()->getValue('SELECT `suggested_price` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price`
        WHERE `seller_sku` = \'' . pSQL($sku) . '\' AND `id_shop` = ' . (int) AmzproShop::actingId());
}

withStagedSnapshot(function () use ($db, $cpT, $t, $main, $idShop) {
    $db->execute('DELETE FROM `' . $cpT . '` WHERE `id_shop` = ' . $idShop);
    $db->execute('DELETE FROM `' . $t . '` WHERE `id_shop` = ' . $idShop);
    $rows = [
        // sku, asin, product type, shop price as prepared for Amazon, Amazon price seen by the scan
        ['ZZT-LOSE', 'B0ZZTLOSE', 'LUGGAGE', 263.80, 317.40],
        ['ZZT-WIN', 'B0ZZTWIN', 'LUGGAGE', 80.00, 99.00],
        ['ZZT-NOTYPE', 'B0ZZTNOTYPE', '', 10.00, 0],
    ];
    foreach ($rows as $r) {
        $db->execute('INSERT INTO `' . $t . '` (`id_shop`, `marketplace_id`, `seller_sku`, `id_product`, `ps_exists`, `ps_price`, `ps_quantity`,
            `amazon_exists`, `amazon_asin`, `amazon_product_type`, `amazon_price`, `amazon_quantity`, `sync_direction`, `date_add`, `date_upd`)
            VALUES (' . $idShop . ', \'\', \'' . $r[0] . '\', 0, 1, ' . $r[3] . ', 5, 1, \'' . $r[1] . '\', \'' . $r[2] . '\', ' . $r[4] . ', 5, \'conflict\', NOW(), NOW())');
    }

    // 1. fetch
    setRule('beat_lowest', 1);
    $client = new FakePricingClient('id', 'secret', 'token', 'https://sellingpartnerapi-eu.amazon.com');
    $engine = new AmazonRepricingEngine($client, $main, 'SELLERTEST');
    $s = $engine->fetchCompetitivePricing();
    check('a throttled call is retried; listings without a product type are skipped', [$s['checked'], $s['errors']], [2, 0]);
    $cp = $db->getRow('SELECT * FROM `' . $cpT . '` WHERE `seller_sku` = \'ZZT-LOSE\' AND `id_shop` = ' . $idShop);
    check('Our Price is our Amazon offer, with its shipping', [(float) $cp['our_price'], (float) $cp['our_shipping']], [317.40, 6.99]);
    check('Buy Box is the new-item one', [(float) $cp['buybox_landed'], (int) $cp['is_buybox_winner']], [296.98, 0]);
    check('lowest is another seller\'s new offer, not the used Buy Box or ours', (float) $cp['lowest_landed'], 285.0);
    check('offers counted for new items', (int) $cp['number_of_offers'], 4);

    // 2. rules
    setRule('match_buybox', 0);
    $engine->applyPricingRules();
    check('match Buy Box: landed 296.98 minus our 6.99 shipping', suggested('ZZT-LOSE'), 289.99);
    check('holding the Buy Box: no change', suggested('ZZT-WIN'), 0.0);
    setRule('beat_buybox', 1);
    $engine->applyPricingRules();
    check('beat Buy Box by 1%', suggested('ZZT-LOSE'), round(296.98 * 0.99 - 6.99, 2));
    setRule('beat_lowest', 1);
    $engine->applyPricingRules();
    check('beat lowest by 1%', suggested('ZZT-LOSE'), round(285.0 * 0.99 - 6.99, 2));
    $db->execute('UPDATE `' . $cpT . '` SET `suggested_price` = 50 WHERE `seller_sku` = \'ZZT-WIN\' AND `id_shop` = ' . $idShop);
    setRule('match_buybox', 0);
    $engine->applyPricingRules();
    check('an older suggestion is cleared, not pushed again', suggested('ZZT-WIN'), 0.0);

    // 3. push: Amazon only; the shop price stays
    $psBefore = $db->getValue('SELECT SUM(`price`) FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = ' . $idShop);
    $p = $engine->pushSuggestedPrices(25);
    check('one price pushed', [$p['candidates'], $p['pushed']], [1, 1]);
    check('shop prices untouched', $db->getValue('SELECT SUM(`price`) FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = ' . $idShop), $psBefore);
    check('the repricer now manages that price', AmazonRepricingEngine::managedPrices($idShop, $main), ['ZZT-LOSE' => 289.99]);

    // 4. the product sync keeps the repriced price
    $sync = new AmazonProductSync($client, $main, 'SELLERTEST');
    invoke($sync, 'recomputeDirections');
    $dirs = [];
    foreach ($db->executeS('SELECT `seller_sku`, `sync_direction` FROM `' . $t . '` WHERE `id_shop` = ' . $idShop) as $r) {
        $dirs[$r['seller_sku']] = $r['sync_direction'];
    }
    check('a repriced price is not a conflict; others still are', [$dirs['ZZT-LOSE'], $dirs['ZZT-WIN']], ['in_sync', 'conflict']);
    $held = invoke($sync, 'holdRepricedPrices', [[['seller_sku' => 'ZZT-LOSE', 'ps_price' => 263.80, 'sale_price' => 200],
        ['seller_sku' => 'ZZT-WIN', 'ps_price' => 80.00, 'sale_price' => 0], ]]);
    check('push and feed send the repriced price, no sale price', [$held[0]['ps_price'], $held[0]['sale_price'], $held[1]['ps_price']], [289.99, 0, 80.00]);
    $db->execute('UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule` SET `active` = 0');
    check('with no active rule the shop price is sent again', AmazonRepricingEngine::managedPrices($idShop, $main), []);

    // 5. a SKU Amazon has only elsewhere is not a listing here; a failed call changes nothing
    check('200 without a summary for this marketplace = no listing', invoke($sync, 'fetchAmazonListing', ['ZZT-ELSEWHERE']), null);
    check('404 = no listing', invoke($sync, 'fetchAmazonListing', ['ZZT-GONE']), null);
    check('a server error is not "no listing"', invoke($sync, 'fetchAmazonListing', ['ZZT-LOSE']), false);
    invoke($sync, 'clearAmazonSide', ['ZZT-WIN', date('Y-m-d H:i:s')]);
    check('a listing gone from Amazon is no longer compared', $db->getValue('SELECT CONCAT(`amazon_exists`, \'/\', `amazon_asin`) FROM `' . $t . '` WHERE `seller_sku` = \'ZZT-WIN\' AND `id_shop` = ' . $idShop), '0/B0ZZTWIN');
});

foreach ([$cpT, $ruleT] as $table) {
    $db->execute('DELETE FROM `' . $table . '`');
    $db->execute('INSERT INTO `' . $table . '` SELECT * FROM `' . $table . '_snap`');
    $db->execute('DROP TABLE `' . $table . '_snap`');
}
done();
