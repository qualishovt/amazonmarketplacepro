<?php
/**
 * 1.6.11: the activity log never stores an e-mail address, even when an
 * error from the mailer or an API quotes one.
 *
 *     php tools/test/log-pii.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';

$cases = [
    '402-1234567-1234567: Address in mailbox given [abc123xyz@marketplace.amazon.de] does not comply with RFC 2822'
        => '402-1234567-1234567: Address in mailbox given [[e-mail removed]] does not comply with RFC 2822',
    'Could not send to <John.Doe+shop@example.co.uk>, retrying' => 'Could not send to <[e-mail removed]>, retrying',
    '"buyer":"jane_doe@gmail.com","city":"Berlin"' => '"buyer":"[e-mail removed]","city":"Berlin"',
    'Order #12 (Amazon: 402-1234567-1234567): shipped' => 'Order #12 (Amazon: 402-1234567-1234567): shipped',
    'Pushed: 3, failed: 0' => 'Pushed: 3, failed: 0',
];
foreach ($cases as $in => $want) {
    check(Tools::substr($in, 0, 60), AmazonMarketplacePro::maskEmails($in), $want);
}

// through the real log
$module = Module::getInstanceByName('amazonmarketplacepro');
$m = new ReflectionMethod($module, 'logActivity');
$m->setAccessible(true);
$m->invoke($module, 'error', 'zz_test', 'X: failed for abc123xyz@marketplace.amazon.de');
$db = Db::getInstance();
$t = _DB_PREFIX_ . 'amazonmarketplacepro_log';
check('stored without the address', $db->getValue('SELECT `message` FROM `' . $t . '` WHERE `source` = \'zz_test\' ORDER BY `id_amazonmarketplacepro_log` DESC'), 'X: failed for [e-mail removed]');
$db->execute('DELETE FROM `' . $t . '` WHERE `source` = \'zz_test\'');
done();
