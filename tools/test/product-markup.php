<?php
/**
 * 1.6.7: a markup set on the product itself, ahead of the category,
 * manufacturer and supplier markups; category markups follow the product's
 * default category only; typed markups are normalised.
 *
 *     php tools/test/product-markup.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$keep = [];
foreach (['AMZPRO_MARKUP_SOURCES', 'AMZPRO_DEFAULT_MARKUP', 'AMZPRO_PRICE_TAX', 'AMZPRO_ROUNDING'] as $k) {
    $keep[$k] = Configuration::getGlobalValue($k);
}

// normalising what a merchant types
Module::getInstanceByName('amazonmarketplacepro');
$rm = new ReflectionMethod('AmazonMarketPlacePro', 'normalizeMarkup');
$rm->setAccessible(true);
foreach (['10 %' => '10%', '2,50' => '2.50', '+3%' => '3%', '-5%' => '-5%', ' 7 ' => '7', 'abc' => '', '10%%' => '', '' => ''] as $in => $want) {
    check('typed "' . $in . '" is kept as "' . $want . '"', $rm->invoke(null, $in), $want);
}

// a product with a price and a default category, and a second category it is in
$p = $db->getRow('SELECT ps.`id_product`, ps.`id_category_default` AS cat, ps.`price`,
        (SELECT cp.`id_category` FROM `' . _DB_PREFIX_ . 'category_product` cp
         WHERE cp.`id_product` = ps.`id_product` AND cp.`id_category` <> ps.`id_category_default` LIMIT 1) AS other
    FROM `' . _DB_PREFIX_ . 'product_shop` ps
    WHERE ps.`id_shop` = ' . (int) AmzproShop::actingId() . ' AND ps.`active` = 1 AND ps.`price` > 1
    ORDER BY (other IS NULL) ASC, ps.`id_product` ASC');
$idp = (int) $p['id_product'];
echo '   product #' . $idp . ', price ' . $p['price'] . ', default category ' . $p['cat'] . ', other category ' . var_export($p['other'], true) . "\n";

Configuration::updateGlobalValue('AMZPRO_MARKUP_SOURCES', 'category,manufacturer,supplier');
Configuration::updateGlobalValue('AMZPRO_DEFAULT_MARKUP', '');
Configuration::updateGlobalValue('AMZPRO_PRICE_TAX', 'excl');
Configuration::updateGlobalValue('AMZPRO_ROUNDING', 'cents');

$entityTable = _DB_PREFIX_ . 'amazonmarketplacepro_entity_setting';
$client = testClient();

/** The staged price of the product after a scan. */
function stagedPrice($idp, $client, $main)
{
    $s = new AmazonProductSync($client, $main, 'SELLERTEST');
    $s->syncPrestashopSide();

    return (float) Db::getInstance()->getValue('SELECT `ps_price` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
        WHERE `marketplace_id` = "" AND `id_product` = ' . (int) $idp . ' AND `id_product_attribute` = 0 AND `ps_exists` = 1');
}
function expected($idp, $base, $markup)
{
    return AmazonListingSettings::applyRounding(
        AmazonListingSettings::applyMarkupString(AmazonListingSettings::applySpecificPrice((float) $base, $idp, 0), $markup)
    );
}

withStagedSnapshot(function () use ($p, $idp, $client, $main) {
    AmazonListingSettings::saveEntitySetting('category', (int) $p['cat'], '10%', -1, '', '', 1);
    check('default category 10%: the product gets 10%', stagedPrice($idp, $client, $main), expected($idp, $p['price'], '10%'));

    AmazonProductOverride::save($idp, ['price_markup' => '5%']);
    check('its own 5% replaces the category\'s 10%', stagedPrice($idp, $client, $main), expected($idp, $p['price'], '5%'));

    AmazonProductOverride::save($idp, ['price_markup' => '2.50']);
    check('an amount works too (+2.50)', stagedPrice($idp, $client, $main), expected($idp, $p['price'], '2.50'));

    AmazonProductOverride::save($idp, ['price_markup' => '']);
    check('emptied: back to the category\'s 10%', stagedPrice($idp, $client, $main), expected($idp, $p['price'], '10%'));

    if ($p['other']) {
        AmazonListingSettings::saveEntitySetting('category', (int) $p['cat'], '', -1, '', '', 1);
        AmazonListingSettings::saveEntitySetting('category', (int) $p['other'], '30%', -1, '', '', 1);
        check('a markup on another (non-default) category does not apply', stagedPrice($idp, $client, $main), expected($idp, $p['price'], ''));
        AmazonListingSettings::saveEntitySetting('category', (int) $p['other'], '', -1, '', '', 1);
    }
});

// restore
Db::getInstance()->execute('DELETE FROM `' . $entityTable . '` WHERE `entity_type` = "category" AND `id_entity` IN (' . (int) $p['cat'] . ',' . (int) $p['other'] . ')
    AND `price_markup` = "" AND `shipping_delay` = -1 AND `gpsr_contact` = "" AND `sync` = 1');
foreach ($keep as $k => $v) {
    if ($v === false || $v === null) {
        Configuration::deleteByName($k);
    } else {
        Configuration::updateGlobalValue($k, $v);
    }
}
done();
