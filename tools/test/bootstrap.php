<?php
/**
 * Shared start for the command-line tests in this folder: boots the shop
 * given as the first argument (with its admin folder, as the back office
 * does), loads the module classes, and provides check() and done().
 *
 *     php tools/test/<test>.php <shop root>
 *
 * The tests change data and settings and restore them before they exit;
 * run them on a test shop only.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}

$root = rtrim(str_replace('\\', '/', isset($argv[1]) ? $argv[1] : ''), '/');
if ($root === '' || !is_file($root . '/config/config.inc.php')) {
    fwrite(STDERR, "usage: php " . basename($argv[0]) . " <shop root>\n");
    exit(2);
}
foreach (glob($root . '/admin*', GLOB_ONLYDIR) as $dir) {
    if (is_file($dir . '/init.php') && !defined('_PS_ADMIN_DIR_')) {
        define('_PS_ADMIN_DIR_', $dir);
    }
}
require $root . '/config/config.inc.php';
foreach (glob(_PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/*.php') as $class) {
    // index.php is the folder guard: it redirects and exits.
    if (basename($class) !== 'index.php') {
        require_once $class;
    }
}

$testPass = 0;
$testFail = 0;

/**
 * @param string $name
 * @param mixed $got
 * @param mixed $want
 */
function check($name, $got, $want)
{
    global $testPass, $testFail;
    $ok = ($got === $want);
    $ok ? ++$testPass : ++$testFail;
    printf("%-64s %s  (got %s, want %s)\n", $name, $ok ? 'ok  ' : 'FAIL',
        str_replace("\n", ' ', var_export($got, true)), str_replace("\n", ' ', var_export($want, true)));
}

/** Print the tally and exit non-zero on any failure. */
function done()
{
    global $testPass, $testFail;
    printf("\n%d passed, %d failed\n", $testPass, $testFail);
    exit($testFail ? 1 : 0);
}

/** A client that never reaches Amazon unless a test asks it to. */
function testClient()
{
    return new AmazonSpApiClient('id', 'secret', 'token', 'https://sellingpartnerapi-eu.amazon.com');
}

/**
 * Copy the staged product table, run $fn, put the table back.
 *
 * @param callable $fn
 */
function withStagedSnapshot($fn)
{
    $db = Db::getInstance();
    $t = _DB_PREFIX_ . 'amazonmarketplacepro_product';
    $db->execute('DROP TABLE IF EXISTS `' . $t . '_snap`');
    $db->execute('CREATE TABLE `' . $t . '_snap` AS SELECT * FROM `' . $t . '`');
    $count = (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '`');
    try {
        $fn();
    } finally {
        $db->execute('DELETE FROM `' . $t . '`');
        $db->execute('INSERT INTO `' . $t . '` SELECT * FROM `' . $t . '_snap`');
        $db->execute('DROP TABLE `' . $t . '_snap`');
        check('comparison table restored', (int) $db->getValue('SELECT COUNT(*) FROM `' . $t . '`'), $count);
    }
}
