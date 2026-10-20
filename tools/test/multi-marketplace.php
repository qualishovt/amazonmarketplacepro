<?php
/**
 * 1.6.3: each marketplace keeps its own comparison rows; extra marketplaces
 * reuse the main one's ASINs without its browse nodes, get converted prices
 * (or nothing, when a currency is missing), and are sent to by the
 * all-marketplaces product sync.
 *
 *     php tools/test/multi-marketplace.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$FR = 'A13V1IB3VIYZZH';
$PL = 'A1C3SOZRARQ6R3';
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
$client = testClient();

/** The factor from the main marketplace's currency to $mp's, from PrestaShop's rates. */
function expectedRate($mainMp, $mp)
{
    $from = AmazonSpApiClient::currencyForMarketplace($mainMp);
    $to = AmazonSpApiClient::currencyForMarketplace($mp);
    if ($from === $to) {
        return 1.0;
    }

    return (float) (new Currency((int) Currency::getIdByIsoCode($to)))->conversion_rate
        / (float) (new Currency((int) Currency::getIdByIsoCode($from)))->conversion_rate;
}

/** Rows of $mp whose price is not the main row's price times $rate. */
function priceMismatches($t, $mp, $rate)
{
    $bad = 0;
    foreach (Db::getInstance()->executeS('SELECT a.ps_price m, b.ps_price x FROM `' . $t . '` a
        JOIN `' . $t . '` b ON b.seller_sku = a.seller_sku AND b.id_shop = a.id_shop AND b.marketplace_id = "' . pSQL($mp) . '"
        WHERE a.marketplace_id = "" AND a.ps_exists = 1 AND a.ps_price > 0') as $p) {
        $want = ($rate === 1.0) ? (float) $p['m'] : AmazonListingSettings::applyRounding((float) $p['m'] * $rate);
        if (abs((float) $p['x'] - $want) > 0.001) {
            ++$bad;
        }
    }

    return $bad;
}

withStagedSnapshot(function () use ($db, $t, $FR, $PL, $main, $client) {
    $m = new AmazonProductSync($client, $main, 'SELLERTEST');
    $m->syncPrestashopSide();
    $n = (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '` WHERE ps_exists = 1 AND marketplace_id = ""');
    check('main scan staged rows', $n > 0, true);
    $db->execute('UPDATE `' . $t . '` SET amazon_exists = 1, amazon_asin = CONCAT("B0T", id_product), amazon_price = ps_price,
        amazon_quantity = ps_quantity WHERE marketplace_id = "" AND ps_exists = 1');
    $m->syncPrestashopSide();
    check('main: nothing pending once live', $m->countPending(), 0);

    $fr = new AmazonProductSync($client, $FR, 'SELLERTEST');
    $fr->syncPrestashopSide();
    check('FR has its own rows, one per exported product',
        (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '` WHERE marketplace_id = "' . $FR . '" AND ps_exists = 1'), $n);
    check('FR: products live on the main marketplace are pending for FR', $fr->countPending() > 0, true);
    check('main still has nothing pending', $m->countPending(), 0);
    $frRate = expectedRate($main, $FR);
    check('FR prices = main prices x ' . round($frRate, 4), priceMismatches($t, $FR, $frRate), 0);

    $rm = new ReflectionMethod('AmazonProductSync', 'adaptRowsForMarketplace');
    $rm->setAccessible(true);
    $sku = $db->getValue('SELECT seller_sku FROM `' . $t . '` WHERE marketplace_id = "" AND ps_exists = 1 AND id_product > 0');
    $idp = (int) $db->getValue('SELECT id_product FROM `' . $t . '` WHERE marketplace_id = "" AND seller_sku = "' . pSQL($sku) . '"');
    $rows = $rm->invoke($fr, [['seller_sku' => $sku, 'amazon_asin' => '', 'amazon_browse_node' => '123',
        'profile_browse_nodes' => '1;2', 'ov_browse_node' => '9', ]]);
    check('FR row suggests the ASIN of the main marketplace', $rows[0]['amazon_asin'], 'B0T' . $idp);
    check('FR row drops the browse nodes of the main marketplace',
        $rows[0]['amazon_browse_node'] . $rows[0]['profile_browse_nodes'] . $rows[0]['ov_browse_node'], '');

    if ((int) Currency::getIdByIsoCode('PLN') > 0) {
        $pl = new AmazonProductSync($client, $PL, 'SELLERTEST');
        $pl->syncPrestashopSide();
        $plRate = expectedRate($main, $PL);
        check('PL prices = main prices x ' . round($plRate, 4), priceMismatches($t, $PL, $plRate), 0);
    }
    foreach (['A1F83G8C2ARO7P', 'A2NODRKZP88ZB9'] as $mp) {
        $iso = AmazonSpApiClient::currencyForMarketplace($mp);
        if ($mp !== $main && (int) Currency::getIdByIsoCode($iso) === 0) {
            $x = new AmazonProductSync($client, $mp, 'SELLERTEST');
            $x->syncPrestashopSide();
            check($iso . ' not in the shop: nothing staged',
                (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '` WHERE marketplace_id = "' . $mp . '"'), 0);
            check($iso . ' not in the shop: the merchant is told why', strpos(implode(' ', $x->getNotices()), $iso) !== false, true);
            break;
        }
    }

    $fr->resetStaged();
    check('FR reset removes FR rows', (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '` WHERE marketplace_id = "' . $FR . '"'), 0);
    check('FR reset keeps the main rows', (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '` WHERE marketplace_id = "" AND amazon_exists = 1'), $n);
    check('main comparison lists only main rows', count($m->listStaged(5000)), $m->countStaged());

    $mm = new AmazonMultiMarketplace();
    check('Add Marketplace saves FR', (bool) $mm->saveMarketplaceConfig(['marketplace_id' => $FR, 'client_id' => '',
        'client_secret' => '', 'refresh_token' => '', 'seller_id' => '', 'active' => 1, 'default_carrier' => 0,
        'default_order_state' => 0, 'sync_orders' => 0, 'sync_products' => 1, 'sync_stock' => 1, ]), true);
    $mock = Configuration::getGlobalValue('AMZPRO_USE_MOCK');
    Configuration::updateGlobalValue('AMZPRO_USE_MOCK', 1);
    $res = $mm->syncProductsAllMarketplaces();
    Configuration::updateGlobalValue('AMZPRO_USE_MOCK', $mock);
    $fr1 = null;
    foreach ((array) $res as $r) {
        if ($r['marketplace_id'] === $FR) {
            $fr1 = $r;
        }
    }
    check('all-marketplaces sync ran for FR', $fr1 !== null, true);
    // Without a connection the push is refused: then nothing counts as sent.
    check('what counts as sent is what Amazon took', $fr1 !== null && $fr1['sent']['sent'] <= $fr1['sent']['pending'], true);
    $mm->deleteMarketplaceConfig($FR);
});
done();
