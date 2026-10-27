<?php
/**
 * 1.6.13: carriers.
 * - Carriers created by a carrier module (Colissimo, Chronopost, Mondial
 *   Relay...) are offered in the carrier settings, not only PrestaShop's own.
 * - PrestaShop gives a carrier a new id each time it is edited; the default
 *   carrier, the shipping-speed map and the shipment-confirmation map follow
 *   it instead of silently falling back.
 *
 *     php tools/test/carriers.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonOrderCreator.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idShop = (int) AmzproShop::actingId();
$idLang = (int) Configuration::get('PS_LANG_DEFAULT');
$created = [];

/** A carrier row as a carrier module would create it. */
function makeCarrier($name, $isModule, array &$created)
{
    $c = new Carrier();
    $c->name = $name;
    $c->active = 1;
    $c->is_module = $isModule ? 1 : 0;
    $c->external_module_name = $isModule ? 'zztestcarrier' : '';
    $c->shipping_external = $isModule ? 1 : 0;
    $c->need_range = 1;
    $c->delay = [(int) Configuration::get('PS_LANG_DEFAULT') => 'Test'];
    $c->add();
    Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'carrier_shop` (`id_carrier`, `id_shop`) VALUES (' . (int) $c->id . ', ' . (int) AmzproShop::actingId() . ')');
    $created[] = (int) $c->id;

    return (int) $c->id;
}

/** What PrestaShop does when a carrier is edited: a copy with a new id, the old one deleted. */
function editCarrier($idCarrier, array &$created)
{
    $old = new Carrier($idCarrier);
    $new = $old->duplicateObject();
    $new->id_reference = $old->id_reference;
    $new->update();
    Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'carrier_shop` (`id_carrier`, `id_shop`) VALUES (' . (int) $new->id . ', ' . (int) AmzproShop::actingId() . ')');
    $old->deleted = 1;
    $old->update();
    $created[] = (int) $new->id;

    return (int) $new->id;
}

$keep = [];
foreach (['AMZPRO_CARRIER_MAP', 'AMZPRO_CARRIER_MAP_IN', 'AMZPRO_DEFAULT_CARRIER'] as $k) {
    $keep[$k] = Configuration::getGlobalValue($k);
}

try {
    // 1. module carriers are listed
    $own = makeCarrier('ZZ Own carrier', false, $created);
    $mod = makeCarrier('ZZ Colissimo (module)', true, $created);
    $ids = function ($list) {
        return array_map('intval', array_column($list, 'id_carrier'));
    };
    $listed = $ids(Carrier::getCarriers($idLang, true, false, false, null, Carrier::ALL_CARRIERS));
    check('a carrier module\'s carrier is offered', in_array($mod, $listed, true), true);
    check('PrestaShop\'s own carriers still are', in_array($own, $listed, true), true);
    check('(the default call leaves module carriers out)', in_array($mod, $ids(Carrier::getCarriers($idLang, true)), true), false);
    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php');
    check('the settings form asks for every carrier', strpos($src, 'Carrier::getCarriers($this->context->language->id, true, false, false, null, Carrier::ALL_CARRIERS)') !== false, true);

    // 2. a mapped carrier is edited in PrestaShop
    $edited = editCarrier($mod, $created);
    check('the old id leads to the edited carrier', AmazonOrderCreator::currentCarrier($mod), $edited);
    check('also within the shop', AmazonOrderCreator::currentCarrier($mod, $idShop), $edited);
    check('the current id stays itself', AmazonOrderCreator::currentCarrier($edited, $idShop), $edited);

    Configuration::updateGlobalValue('AMZPRO_CARRIER_MAP_IN', json_encode(['Expedited' => $mod]));
    check('shipping-speed map: the order gets the edited carrier',
        AmazonOrderCreator::resolveCarrier(['ship_service_level' => 'Expedited'], $own, $idShop), $edited);
    check('a carrier deleted for good falls back to the default', (function () use ($own, $idShop, $edited) {
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'carrier` SET `deleted` = 1 WHERE `id_carrier` = ' . (int) $edited);
        $r = AmazonOrderCreator::resolveCarrier(['ship_service_level' => 'Expedited'], $own, $idShop);
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'carrier` SET `deleted` = 0 WHERE `id_carrier` = ' . (int) $edited);

        return $r;
    })(), $own);

    // shipment confirmation: map saved under the old id, order carries the new one
    Configuration::updateGlobalValue('AMZPRO_CARRIER_MAP', json_encode([$mod => 'Colissimo', $own => 'DPD']));
    $module = Module::getInstanceByName('amazonmarketplacepro');
    $m = new ReflectionMethod($module, 'mapCarrierToAmazon');
    $m->setAccessible(true);
    check('shipment confirmation uses the mapping of the edited carrier', $m->invoke($module, 'Renamed carrier', $edited), 'Colissimo');
    check('an unedited carrier keeps its mapping', $m->invoke($module, 'ZZ Own carrier', $own), 'DPD');

    // the settings form shows the saved choice under the new id (else saving would wipe it)
    check('the form shows the mapping on the edited carrier', AmazonMarketplacePro::currentCarrierKeys([$mod => 'Colissimo']), [$edited => 'Colissimo']);
} finally {
    foreach ($keep as $k => $v) {
        if ($v === false || $v === null) {
            Configuration::deleteByName($k);
        } else {
            Configuration::updateGlobalValue($k, $v);
        }
    }
    if ($created) {
        $in = implode(',', $created);
        foreach (['carrier', 'carrier_lang', 'carrier_shop', 'carrier_zone', 'carrier_group', 'carrier_tax_rules_group_shop', 'delivery', 'range_price', 'range_weight'] as $table) {
            $db->execute('DELETE FROM `' . $p . $table . '` WHERE `id_carrier` IN (' . $in . ')');
        }
    }
}
done();
