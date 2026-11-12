<?php
/**
 * 1.6.22: a finished Amazon report can be downloaded from Report History.
 * Before, the module stored the file but offered no way to open it, and the
 * poll summary read keys the server never sent ("%1$s reports checked").
 * Only a finished report of the current shop is served, and only from
 * data/reports.
 *
 *     php tools/test/report-download.php <shop root>
 */
require __DIR__ . '/bootstrap.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/amazonmarketplacepro.php';
require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonReportManager.php';

$db = Db::getInstance();
$p = _DB_PREFIX_;
$idShop = (int) AmzproShop::actingId();
$dir = _PS_MODULE_DIR_ . 'amazonmarketplacepro/data/reports';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
$inside = $dir . '/report_ZZR1.tsv';
file_put_contents($inside, "sku\tasin\nA-1\tB000TEST01\n");
$outside = _PS_MODULE_DIR_ . 'amazonmarketplacepro/config.xml';

$add = function ($id, $status, $path, $shop) use ($db, $p) {
    $db->execute('INSERT INTO `' . $p . 'amazonmarketplacepro_report`
        (`report_id`, `report_type`, `status`, `file_path`, `id_shop`, `date_add`, `date_upd`)
        VALUES (\'' . $id . '\', \'GET_MERCHANT_LISTINGS_ALL_DATA\', \'' . $status . '\', \'' . pSQL($path) . '\', ' . (int) $shop . ', NOW(), NOW())');
};

try {
    $add('ZZR1', 'DONE', $inside, $idShop);
    $add('ZZR2', 'IN_QUEUE', '', $idShop);
    $add('ZZR3', 'DONE', $outside, $idShop);
    $add('ZZR4', 'DONE', $inside, $idShop + 1000);

    $f = AmazonReportManager::reportFile('ZZR1');
    check('finished report: its file', [$f['path'] === realpath($inside), $f['name']], [true, 'amazon-merchant-listings-all-ZZR1.tsv']);
    check('still processing: nothing to download', AmazonReportManager::reportFile('ZZR2'), null);
    check('a stored path outside data/reports is refused', AmazonReportManager::reportFile('ZZR3'), null);
    check('another shop\'s report is refused', AmazonReportManager::reportFile('ZZR4'), null);
    check('a malformed id is refused', AmazonReportManager::reportFile("ZZR1' OR '1'='1"), null);
    check('an unknown id', AmazonReportManager::reportFile('ZZR9'), null);

    $tpl = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/views/templates/admin/configure.tpl');
    check('poll summary reads the keys the server sends', strpos($tpl, 'fmt(T.reportsSummary, s.pending, s.completed, s.still_processing)') !== false, true);
    check('Report History has the download link', strpos($tpl, '{$download_report_url|escape:\'htmlall\':\'UTF-8\'}&amp;report_id=') !== false, true);
    // Stored reports are out of reach of the web.
    require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/upgrade/upgrade-1.6.22.php';
    @unlink($dir . '/.htaccess');
    @unlink($dir . '/index.php');
    check('upgrade protects existing report folders', upgrade_module_1_6_22(null)
        && strpos((string) @file_get_contents($dir . '/.htaccess'), 'Require all denied') !== false
        && strpos((string) @file_get_contents($dir . '/.htaccess'), 'Deny from all') !== false
        && is_file($dir . '/index.php') && is_file(dirname($dir) . '/.htaccess'), true);

    $src = file_get_contents(_PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonReportManager.php');
    check('the server sends those keys', (bool) preg_match("/'pending' => 0,\s*'completed' => 0,\s*'failed' => 0,\s*'still_processing' => 0,/", $src), true);
} finally {
    $db->execute('DELETE FROM `' . $p . 'amazonmarketplacepro_report` WHERE `report_id` LIKE \'ZZR%\'');
    @unlink($inside);
}
done();
