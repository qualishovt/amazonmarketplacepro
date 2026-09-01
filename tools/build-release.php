<?php
/**
 * Build the module ZIP for PrestaShop Addons.
 *
 *     php tools/build-release.php
 *     php tools/build-release.php --allow-dirty   (build from HEAD anyway)
 *
 * The ZIP is built from committed state via git archive, so what ships is
 * exactly what is in the repository - not whatever happened to be sitting in
 * the working directory. Exclusions come from .gitattributes export-ignore.
 *
 * It refuses to produce a ZIP that would fail on upload or in review, because
 * an Addons rejection costs days of waiting. The checks are the ones that have
 * actually caught something here or are known to fail validation:
 *
 *   - the folder inside the ZIP must be named after the module
 *   - config.xml and $this->version must agree, or the upgrade never runs
 *   - no developer material: relay, docs, tools, diagnostics
 *   - every shipped directory needs index.php
 *   - the security scan must pass
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

$root = dirname(dirname(__FILE__));
$moduleName = basename($root);
$allowDirty = in_array('--allow-dirty', $argv, true);

chdir($root);

$fail = array();
$note = array();

/** Run a command, returning [exitCode, output]. */
function run($cmd)
{
    $out = array();
    $code = 0;
    exec($cmd . ' 2>&1', $out, $code);
    return array($code, implode("\n", $out));
}

echo "Building " . $moduleName . "\n\n";

// ── Version agreement ─────────────────────────────────────────────────────
$main = file_get_contents($root . '/' . $moduleName . '.php');
preg_match('/\$this->version\s*=\s*\'([^\']+)\'/', $main, $m);
$phpVersion = isset($m[1]) ? $m[1] : null;

$xml = file_get_contents($root . '/config.xml');
preg_match('/<version><!\[CDATA\[([^\]]+)\]\]><\/version>/', $xml, $m2);
$xmlVersion = isset($m2[1]) ? $m2[1] : null;

if ($phpVersion === null || $xmlVersion === null) {
    $fail[] = 'Could not read the version from config.xml or the main file.';
} elseif ($phpVersion !== $xmlVersion) {
    $fail[] = "Version mismatch: config.xml says $xmlVersion, the module says $phpVersion. "
        . 'PrestaShop reads both, and a mismatch stops the upgrade script running.';
} else {
    $note[] = "version $phpVersion";
}

// An upgrade script should exist for the version being shipped.
$upgradeFile = $root . '/upgrade/upgrade-' . $phpVersion . '.php';
if ($phpVersion !== null && !file_exists($upgradeFile)) {
    $note[] = "no upgrade/upgrade-$phpVersion.php (fine for a first release of this version)";
}

// ── Working tree ──────────────────────────────────────────────────────────
list(, $status) = run('git status --porcelain');
if (trim($status) !== '' && !$allowDirty) {
    $fail[] = "Working tree is not clean, and the ZIP is built from HEAD - your\n"
        . "  uncommitted changes would NOT be in it:\n"
        . preg_replace('/^/m', '    ', trim($status))
        . "\n  Commit them, or pass --allow-dirty to build from HEAD deliberately.";
}

// ── Security scan ─────────────────────────────────────────────────────────
list($scanCode, $scanOut) = run('php ' . escapeshellarg($root . '/tools/security-scan.php') . ' --quiet');
if ($scanCode !== 0) {
    $fail[] = "Security scan failed:\n" . preg_replace('/^/m', '    ', trim($scanOut));
}

if ($fail) {
    echo "REFUSING TO BUILD\n\n";
    foreach ($fail as $f) {
        echo "  - " . $f . "\n\n";
    }
    exit(1);
}

// ── Build ─────────────────────────────────────────────────────────────────
$zip = $root . '/' . $moduleName . '-' . $phpVersion . '.zip';
if (file_exists($zip)) {
    unlink($zip);
}

list($code, $out) = run(
    'git archive --format=zip --prefix=' . escapeshellarg($moduleName . '/')
    . ' -o ' . escapeshellarg($zip) . ' HEAD'
);
if ($code !== 0 || !file_exists($zip)) {
    echo "git archive failed:\n" . $out . "\n";
    exit(1);
}

// ── Verify what actually ended up inside ──────────────────────────────────
$archive = new ZipArchive();
if ($archive->open($zip) !== true) {
    echo "Could not reopen the built ZIP for verification.\n";
    exit(1);
}

$entries = array();
for ($i = 0; $i < $archive->numFiles; $i++) {
    $entries[] = $archive->getNameIndex($i);
}
$archive->close();

$problems = array();

// Nothing developer-only may ship.
$banned = array('relay-server/', 'docs/', 'tools/', 'website/', 'live-verify.php', '.gitattributes', '.gitignore');
foreach ($entries as $e) {
    $rel = substr($e, strlen($moduleName) + 1);
    foreach ($banned as $b) {
        if ($rel === $b || strpos($rel, $b) === 0) {
            $problems[] = 'developer file shipped: ' . $rel;
        }
    }
}

// Everything must sit under the module folder.
foreach ($entries as $e) {
    if (strpos($e, $moduleName . '/') !== 0) {
        $problems[] = 'entry outside the module folder: ' . $e;
    }
}

// Every shipped directory needs index.php, or the Addons validator objects.
$dirs = array();
foreach ($entries as $e) {
    $d = dirname($e);
    while ($d !== '.' && $d !== '/' && $d !== '') {
        $dirs[$d] = true;
        $d = dirname($d);
    }
}
foreach (array_keys($dirs) as $d) {
    if (!in_array($d . '/index.php', $entries, true)) {
        $problems[] = 'directory without index.php: ' . $d;
    }
}

// The essentials.
foreach (array($moduleName . '.php', 'config.xml', 'logo.png') as $must) {
    if (!in_array($moduleName . '/' . $must, $entries, true)) {
        $problems[] = 'missing from the ZIP: ' . $must;
    }
}

if ($problems) {
    echo "BUILT, BUT THE ZIP IS NOT SHIPPABLE\n\n";
    foreach (array_unique($problems) as $p) {
        echo '  - ' . $p . "\n";
    }
    echo "\n  " . $zip . "\n";
    exit(1);
}

printf(
    "OK  %s\n    %d files, %s\n",
    basename($zip),
    count($entries),
    number_format(filesize($zip) / 1024, 1) . ' KB'
);
foreach ($note as $n) {
    echo '    ' . $n . "\n";
}
