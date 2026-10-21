<?php
/**
 * 1.6.8: one barcode field holding EAN-13, UPC-12, EAN-8 and GTIN-14 codes.
 * Each is sent with its own type, and "Match ASINs by EAN" looks codes up
 * one type at a time and maps Amazon's answer back even when it comes in
 * another length (a UPC returned as a 13-digit EAN).
 *
 *     php tools/test/barcodes.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

/** Answers catalogue lookups like Amazon, and records what was asked. */
class FakeCatalogClient extends AmazonSpApiClient
{
    public $calls = [];

    public function request($method, $path, $query = [], $body = null)
    {
        $this->calls[] = $query;
        $items = [];
        foreach (explode(',', $query['identifiers']) as $i => $code) {
            // Amazon may answer a UPC with the 13-digit EAN form.
            $answer = ($query['identifiersType'] === 'UPC') ? '0' . $code : $code;
            $items[] = ['asin' => 'B0TEST' . $i . substr($code, -3), 'identifiers' => [[
                'marketplaceId' => $query['marketplaceIds'],
                'identifiers' => [['identifierType' => $query['identifiersType'] === 'UPC' ? 'EAN' : $query['identifiersType'], 'identifier' => $answer]],
            ]]];
        }

        return ['status' => 200, 'body' => ['items' => $items]];
    }
}

$keep = Configuration::getGlobalValue('AMZPRO_EAN_AS');
Configuration::updateGlobalValue('AMZPRO_EAN_AS', 'EAN');
$cases = [
    '4006381333931' => ['EAN', '4006381333931'],
    '012345678905' => ['UPC', '012345678905'],
    '96385074' => ['EAN', '96385074'],
    '00012345678905' => ['GTIN', '00012345678905'],
    '4006 3813 33931' => ['EAN', '4006381333931'],
];
foreach ($cases as $raw => $want) {
    $b = AmazonListingSettings::barcode($raw);
    check('barcode "' . $raw . '" -> ' . $want[0], $b ? [$b['type'], $b['value']] : null, $want);
}
check('barcode "123" is not a barcode', AmazonListingSettings::barcode('123'), null);
check('empty is not a barcode', AmazonListingSettings::barcode(''), null);
Configuration::updateGlobalValue('AMZPRO_EAN_AS', 'UPC');
$b = AmazonListingSettings::barcode('4006381333931');
check('"Always UPC" still sends UPC', $b['type'], 'UPC');
Configuration::updateGlobalValue('AMZPRO_EAN_AS', 'EAN');

// ASIN matching on a mixed field
$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$idShop = (int) AmzproShop::actingId();
withStagedSnapshot(function () use ($db, $t, $idShop) {
    $db->execute('DELETE FROM `' . $t . '` WHERE `id_shop` = ' . $idShop);
    $codes = ['A-EAN' => '4006381333931', 'B-UPC' => '012345678905', 'C-UPC2' => '036000291452',
        'D-EAN8' => '96385074', 'E-BAD' => '12345', ];
    foreach ($codes as $sku => $code) {
        $db->execute('INSERT INTO `' . $t . '` (`id_shop`, `marketplace_id`, `seller_sku`, `id_product`, `ps_exists`,
            `ps_ean13`, `amazon_exists`, `amazon_asin`, `sync_direction`, `date_add`, `date_upd`)
            VALUES (' . $idShop . ', \'\', \'' . $sku . '\', 1, 1, \'' . $code . '\', 0, \'\', \'ps_only\', NOW(), NOW())');
    }
    $client = new FakeCatalogClient('id', 'secret', 'token', 'https://sellingpartnerapi-eu.amazon.com');
    $matcher = new AmazonCatalogMatcher($client, (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID'));
    $summary = $matcher->matchStagedProducts(100);
    $types = [];
    foreach ($client->calls as $c) {
        $types[] = $c['identifiersType'] . ':' . count(explode(',', $c['identifiers']));
    }
    sort($types);
    check('one lookup per barcode type', implode(' ', $types), 'EAN:2 UPC:2');
    check('every valid barcode matched (a 5-digit code is not even a candidate)', [$summary['matched'], $summary['candidates']], [4, 4]);
    check('UPC answered as a 13-digit EAN still lands on its product',
        (string) $db->getValue('SELECT `amazon_asin` FROM `' . $t . '` WHERE `seller_sku` = "B-UPC" AND `id_shop` = ' . $idShop) !== '', true);
});

if ($keep === false || $keep === null) {
    Configuration::deleteByName('AMZPRO_EAN_AS');
} else {
    Configuration::updateGlobalValue('AMZPRO_EAN_AS', $keep);
}
done();
