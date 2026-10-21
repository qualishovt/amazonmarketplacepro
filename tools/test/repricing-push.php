<?php
/**
 * 1.6.9: "Push Suggested Prices" changes only the price, under the listing's
 * own product type, and counts a price as sent only when Amazon accepts it
 * (Amazon answers 200 with status INVALID when it refuses).
 *
 *     php tools/test/repricing-push.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonRepricingEngine.php';

/** Accepts ZZT-OK and ZZT-LOOKUP, refuses ZZT-BAD, knows no ZZT-GONE. */
class FakeListingsClient extends AmazonSpApiClient
{
    public $calls = [];

    public function request($method, $path, $query = [], $body = null)
    {
        $this->calls[] = [$method, basename($path), $body];
        $sku = rawurldecode(basename($path));
        if ($method === 'GET') {
            return $sku === 'ZZT-LOOKUP'
                ? ['status' => 200, 'body' => ['sku' => $sku, 'summaries' => [['productType' => 'SHOES']]]]
                : ['status' => 404, 'body' => ['errors' => [['message' => 'Not found']]]];
        }
        if ($sku === 'ZZT-BAD') {
            return ['status' => 200, 'body' => ['sku' => $sku, 'status' => 'INVALID', 'issues' => [
                ['code' => '90220', 'message' => 'The price is below your minimum price.', 'severity' => 'ERROR'],
            ]]];
        }

        return ['status' => 200, 'body' => ['sku' => $sku, 'status' => 'ACCEPTED', 'issues' => []]];
    }
}

$db = Db::getInstance();
$cp = _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price';
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$idShop = (int) AmzproShop::actingId();
$keepSeller = AmzproShop::get('AMZPRO_SELLER_ID');
if (trim((string) $keepSeller) === '') {
    Configuration::updateValue('AMZPRO_SELLER_ID', 'SELLERTEST');
}
$db->execute('DROP TABLE IF EXISTS `' . $cp . '_snap`');
$db->execute('CREATE TABLE `' . $cp . '_snap` AS SELECT * FROM `' . $cp . '`');

withStagedSnapshot(function () use ($db, $cp, $t, $main, $idShop) {
    $db->execute('DELETE FROM `' . $cp . '` WHERE `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'' . pSQL($main) . '\'');
    foreach (['ZZT-OK' => 'LUGGAGE', 'ZZT-BAD' => 'LUGGAGE', 'ZZT-LOOKUP' => '', 'ZZT-GONE' => ''] as $sku => $type) {
        $db->execute('INSERT INTO `' . $cp . '` (`seller_sku`, `our_price`, `suggested_price`, `marketplace_id`, `id_shop`, `date_add`, `date_upd`)
            VALUES (\'' . $sku . '\', 317.40, 296.98, \'' . pSQL($main) . '\', ' . $idShop . ', NOW(), NOW())');
        if ($type !== '') {
            $db->execute('INSERT INTO `' . $t . '` (`id_shop`, `marketplace_id`, `seller_sku`, `amazon_exists`, `amazon_product_type`, `sync_direction`, `date_add`, `date_upd`)
                VALUES (' . $idShop . ', \'\', \'' . $sku . '\', 1, \'' . $type . '\', \'both\', NOW(), NOW())');
        }
    }

    $client = new FakeListingsClient('id', 'secret', 'token', 'https://sellingpartnerapi-eu.amazon.com');
    $engine = new AmazonRepricingEngine($client, $main, 'SELLERTEST');
    $s = $engine->pushSuggestedPrices(25);
    check('candidates / accepted / failed', [$s['candidates'], $s['pushed'], $s['failed']], [4, 2, 2]);

    $sent = [];
    foreach ($client->calls as $c) {
        if ($c[0] !== 'GET') {
            $sent[$c[1]] = $c[0] . ' ' . $c[2]['productType'] . ' ' . $c[2]['patches'][0]['path']
                . ' ' . $c[2]['patches'][0]['value'][0]['our_price'][0]['schedule'][0]['value_with_tax'];
        }
    }
    ksort($sent);
    check('only the price is patched, under the listing\'s product type', $sent, [
        'ZZT-BAD' => 'PATCH LUGGAGE /attributes/purchasable_offer 296.98',
        'ZZT-LOOKUP' => 'PATCH SHOES /attributes/purchasable_offer 296.98',
        'ZZT-OK' => 'PATCH LUGGAGE /attributes/purchasable_offer 296.98',
    ]);

    $rows = [];
    foreach ($db->executeS('SELECT `seller_sku`, `our_price`, `last_repriced` FROM `' . $cp . '` WHERE `id_shop` = ' . $idShop . ' AND `seller_sku` LIKE \'ZZT-%\' ORDER BY `seller_sku`') as $r) {
        $rows[$r['seller_sku']] = (float) $r['our_price'] . ($r['last_repriced'] ? ' repriced' : '');
    }
    check('only accepted prices are marked as repriced', $rows, [
        'ZZT-BAD' => '317.4', 'ZZT-GONE' => '317.4', 'ZZT-LOOKUP' => '296.98 repriced', 'ZZT-OK' => '296.98 repriced',
    ]);

    $notices = implode(' | ', $engine->getNotices());
    check('Amazon\'s refusal is shown', strpos($notices, 'ZZT-BAD: The price is below your minimum price.') !== false, true);
    check('a listing Amazon does not have is reported', strpos($notices, 'ZZT-GONE') !== false, true);
});

$db->execute('DELETE FROM `' . $cp . '`');
$db->execute('INSERT INTO `' . $cp . '` SELECT * FROM `' . $cp . '_snap`');
$db->execute('DROP TABLE `' . $cp . '_snap`');
if (trim((string) $keepSeller) === '') {
    Configuration::updateValue('AMZPRO_SELLER_ID', (string) $keepSeller);
}
done();
