<?php
/**
 * 1.6.12: own-brand products under a GTIN exemption. The product's EAN is
 * the manufacturer's, so it is not sent, not used to match an ASIN, and the
 * listing declares the exemption instead. Copying the switch to "all products
 * of the same manufacturer" sets it for a whole brand.
 *
 *     php tools/test/gtin-exempt.php <shop root>
 */
require __DIR__ . '/bootstrap.php';

$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
$ovT = _DB_PREFIX_ . 'amazonmarketplacepro_product_setting';
$idShop = (int) AmzproShop::actingId();
$main = (string) AmzproShop::get('AMZPRO_MARKETPLACE_ID');
AmazonProductOverride::ensureTable();

$product = $db->getRow('SELECT p.`id_product`, p.`id_manufacturer`, p.`ean13` FROM `' . _DB_PREFIX_ . 'product` p
    INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop . ' AND ps.`active` = 1)
    WHERE p.`id_manufacturer` > 0 AND p.`reference` <> \'\'
      AND (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` x WHERE x.`id_manufacturer` = p.`id_manufacturer`) > 1
    ORDER BY p.`id_product` ASC');
$id = (int) $product['id_product'];
echo 'product #' . $id . ', manufacturer #' . $product['id_manufacturer'] . "\n";

$db->execute('DROP TABLE IF EXISTS `' . $ovT . '_snap`');
$db->execute('CREATE TABLE `' . $ovT . '_snap` AS SELECT * FROM `' . $ovT . '`');
$db->execute('UPDATE `' . _DB_PREFIX_ . 'product` SET `ean13` = \'4006381333931\' WHERE `id_product` = ' . $id);
$db->execute('UPDATE `' . _DB_PREFIX_ . 'product_attribute` SET `ean13` = \'4006381333931\' WHERE `id_product` = ' . $id);

/** Run a private method. */
function invokePrivate($object, $method, array $args = [])
{
    $m = new ReflectionMethod($object, $method);
    $m->setAccessible(true);

    return $m->invokeArgs($object, $args);
}

try {
    withStagedSnapshot(function () use ($db, $t, $id, $idShop, $main, $product) {
        $sync = new AmazonProductSync(testClient(), $main, 'SELLERTEST');

        // 1. without the switch the EAN is staged as usual
        AmazonProductOverride::save($id, ['gtin_exempt' => 0]);
        $sync->syncPrestashopSide();
        $eans = $db->executeS('SELECT DISTINCT `ps_ean13` FROM `' . $t . '` WHERE `id_product` = ' . $id . ' AND `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'\' AND `is_parent` = 0');
        check('without the switch the EAN is staged', array_column($eans, 'ps_ean13'), ['4006381333931']);

        // an ASIN found by EAN matching earlier
        $db->execute('UPDATE `' . $t . '` SET `amazon_asin` = \'B0SUPPLIER\', `amazon_exists` = 0 WHERE `id_product` = ' . $id . ' AND `id_shop` = ' . $idShop);

        // 2. with the switch: no EAN, the matched ASIN is dropped
        AmazonProductOverride::save($id, ['gtin_exempt' => 1]);
        $sync->syncPrestashopSide();
        $row = $db->getRow('SELECT GROUP_CONCAT(DISTINCT `ps_ean13`) AS e, GROUP_CONCAT(DISTINCT `amazon_asin`) AS a FROM `' . $t . '`
            WHERE `id_product` = ' . $id . ' AND `id_shop` = ' . $idShop . ' AND `marketplace_id` = \'\'');
        check('exempt: no EAN staged, no supplier ASIN kept', [$row['e'], $row['a']], ['', '']);

        // 3. the catalogue matcher has nothing to match it with
        $candidates = (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '` WHERE `id_product` = ' . $id . ' AND `id_shop` = ' . $idShop . ' AND `ps_ean13` != \'\'');
        check('not a candidate for Match ASINs by EAN', $candidates, 0);

        // 4. the listing declares the exemption and carries no identifier
        $body = invokePrivate($sync, 'buildFullListingBody', [[
            'seller_sku' => 'ZZT-OWN', 'id_product' => $id, 'ps_name' => 'Own brand', 'ps_price' => 10, 'ps_quantity' => 1,
            'ps_description' => '', 'ps_manufacturer' => 'Own', 'ps_ean13' => '4006381333931', 'ps_images' => '[]',
            'ps_condition' => 'new', 'amazon_product_type' => 'PRODUCT', 'ov_gtin_exempt' => '1',
        ], '', []]);
        $a = $body['attributes'];
        check('no externally_assigned_product_identifier', isset($a['externally_assigned_product_identifier']), false);
        check('exemption declared', isset($a['supplier_declared_has_product_identifier_exemption'][0]['value'])
            ? $a['supplier_declared_has_product_identifier_exemption'][0]['value'] : null, true);
        $plain = invokePrivate($sync, 'buildFullListingBody', [[
            'seller_sku' => 'ZZT-OTHER', 'id_product' => $id, 'ps_name' => 'Other', 'ps_price' => 10, 'ps_quantity' => 1,
            'ps_description' => '', 'ps_manufacturer' => 'Other', 'ps_ean13' => '4006381333931', 'ps_images' => '[]',
            'ps_condition' => 'new', 'amazon_product_type' => 'PRODUCT', 'ov_gtin_exempt' => '0',
        ], '', []]);
        check('a normal product still sends its EAN', $plain['attributes']['externally_assigned_product_identifier'][0]['value'], '4006381333931');

        // 5. one click for the whole brand
        $n = AmazonProductOverride::propagate($id, 'manufacturer', ['gtin_exempt']);
        $brand = $db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` WHERE `id_manufacturer` = ' . (int) $product['id_manufacturer']);
        $all = AmazonProductOverride::all();
        $set = 0;
        foreach ($db->executeS('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_manufacturer` = ' . (int) $product['id_manufacturer']) as $p) {
            $set += !empty($all[(int) $p['id_product']]['gtin_exempt']) ? 1 : 0;
        }
        check('copied to every product of the manufacturer', $set >= $n + 1 && $n > 0, true);
        echo '   ' . $n . ' other product(s) of ' . $brand . " set\n";
    });
} finally {
    $db->execute('UPDATE `' . _DB_PREFIX_ . 'product` SET `ean13` = \'' . pSQL($product['ean13']) . '\' WHERE `id_product` = ' . $id);
    $db->execute('UPDATE `' . _DB_PREFIX_ . 'product_attribute` SET `ean13` = \'\' WHERE `id_product` = ' . $id . ' AND `ean13` = \'4006381333931\'');
    $db->execute('DELETE FROM `' . $ovT . '`');
    $db->execute('INSERT INTO `' . $ovT . '` SELECT * FROM `' . $ovT . '_snap`');
    $db->execute('DROP TABLE `' . $ovT . '_snap`');
}
done();
