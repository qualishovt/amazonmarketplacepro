<?php
/**
 * Compile configure.tpl with the shop's own Smarty and {l} plugin, then render
 * the new strings in French to prove they translate and that js=1 escapes the
 * apostrophe.
 *
 * The render goes through a real file named configure.tpl: PrestaShop builds
 * the translation key from the template's file name, so a string: template
 * would look up a different key and come back in English.
 *
 *     php tools/test/template-check.php <shop root>
 */

// Boots the shop and writes a temporary file: command line only.
if (PHP_SAPI !== 'cli') {
    exit;
}

$root = rtrim(str_replace('\\', '/', isset($argv[1]) ? $argv[1] : ''), '/');
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';
require $root . '/config/config.inc.php';

$smarty = Context::getContext()->smarty;
$file = $root . '/modules/amazonmarketplacepro/views/templates/admin/configure.tpl';
$status = 0;

error_reporting(E_ERROR | E_PARSE);
try {
    $smarty->force_compile = true;
    $tpl = $smarty->createTemplate($file);
    if (method_exists($tpl, 'compileTemplateSource')) {
        $tpl->compileTemplateSource();                  // Smarty 3.1 (PS 1.6)
    } elseif (isset($tpl->compiled) && method_exists($tpl->compiled, 'compileTemplateSource')) {
        $tpl->compiled->compileTemplateSource($tpl);    // Smarty 4 (PS 8/9)
    } else {
        throw new Exception('no compile entry point on ' . get_class($tpl));
    }
    echo 'compiled OK: ' . basename($file) . ' (' . (defined('Smarty::SMARTY_VERSION') ? Smarty::SMARTY_VERSION : 'Smarty ?') . ")\n";
} catch (Exception $e) {
    echo 'COMPILE ERROR: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
    $status = 1;
}

$lang = new Language();
$lang->iso_code = 'fr';
$lang->locale = 'fr-FR';
$lang->id = 1;
Context::getContext()->language = $lang;

$dir = str_replace('\\', '/', sys_get_temp_dir()) . '/amzpro-tplcheck';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$probe = $dir . '/configure.tpl';
file_put_contents(
    $probe,
    "var a = '{l s='Choose the order report file first.' mod='amazonmarketplacepro' js=1}';\n"
    . "<p>{l s='Upload report' mod='amazonmarketplacepro'}</p>\n"
    . "<p>{l s='In Seller Central: Orders > Order Reports > Unshipped Orders, then Request report and Download.' mod='amazonmarketplacepro'}</p>\n"
);

try {
    $out = $smarty->fetch($probe);
    echo "rendered (fr):\n  " . str_replace("\n", "\n  ", trim($out)) . "\n";
    if (strpos($out, "d\\'abord") === false) {
        echo "  FAIL: the French apostrophe is not escaped inside the JS string\n";
        $status = 1;
    }
    if (strpos($out, 'Importer le rapport') === false) {
        echo "  FAIL: the HTML string did not translate\n";
        $status = 1;
    }
    if (strpos($out, 'Orders &gt; Order Reports') === false) {
        echo "  FAIL: > in the Seller Central path is not HTML-escaped\n";
        $status = 1;
    }
} catch (Exception $e) {
    echo 'RENDER ERROR: ' . $e->getMessage() . "\n";
    $status = 1;
}
unlink($probe);
exit($status);
