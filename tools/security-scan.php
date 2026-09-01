<?php
/**
 * Static security scan for the module and relay.
 *
 * Run before every release:
 *     php tools/security-scan.php
 *     php tools/security-scan.php --quiet    (silent unless findings; for cron)
 *
 * Exits non-zero when anything at ERROR level is found, so it can gate a
 * release or run as a scheduled guard.
 *
 * WHY A CUSTOM SCANNER. The module has no Composer dependencies by design, and
 * has to run on whatever PHP the merchant's host provides - currently 5.6+.
 * Pulling in a scanner toolchain would add the supply chain we deliberately do
 * not have. The rules below instead target the failure modes that actually
 * occur in PrestaShop modules and in this codebase specifically, several of
 * which were real bugs here: unescaped SQL, secrets echoed into forms, and
 * shop-scoped reads of globally-scoped state.
 *
 * This is not a substitute for a penetration test and does not claim to be.
 */

define('LEVEL_ERROR', 'ERROR');
define('LEVEL_WARN', 'WARN');

$root = dirname(dirname(__FILE__));
$quiet = in_array('--quiet', $argv, true) || in_array('-q', $argv, true);

/** Directories never scanned: not shipped, or not ours. */
$skipDirs = array('.git', 'website', 'node_modules', 'vendor', 'docs');

/**
 * Rules. Each: pattern, level, message, and an optional "unless" pattern that
 * suppresses the finding when the same line also matches it.
 */
$rules = array(
    // --- Injection -------------------------------------------------------
    array(
        'name' => 'sql-interpolation',
        'level' => LEVEL_ERROR,
        'pattern' => '/(?:->execute|->executeS|->getValue|->getRow|->insert|->update|->delete)\s*\(\s*[\'"][^\'"]*[\'"]\s*\.\s*\$/i',
        'unless' => '/pSQL|bqSQL|\(int\)|\(float\)|intval|floatval|_DB_PREFIX_/',
        'message' => 'SQL built by concatenating a variable without pSQL/(int) on the same line',
    ),
    array(
        'name' => 'sql-heredoc-var',
        'level' => LEVEL_WARN,
        'pattern' => '/\$(?:sql|query)\s*=\s*[\'"].*\{\$/i',
        'unless' => '/pSQL|bqSQL|\(int\)/',
        'message' => 'Variable interpolated directly into a query string',
    ),

    // --- Dangerous constructs -------------------------------------------
    array(
        'name' => 'dangerous-function',
        'level' => LEVEL_ERROR,
        'pattern' => '/(?<![a-zA-Z_>])(eval|assert|create_function|shell_exec|passthru|proc_open|popen)\s*\(/',
        'unless' => '/security-scan\.php/',
        'message' => 'Dangerous function call',
    ),
    array(
        'name' => 'exec-call',
        'level' => LEVEL_WARN,
        'pattern' => '/(?<![a-zA-Z_>])(exec|system)\s*\(/',
        // A line may be acknowledged with a trailing "scan-ok:" comment
        // giving the reason. Without the reason the marker does not count.
        'unless' => '/scan-ok:\s*\S/',
        'message' => 'Process execution - confirm no user input reaches it',
    ),
    array(
        'name' => 'unserialize',
        'level' => LEVEL_ERROR,
        'pattern' => '/(?<![a-zA-Z_>])unserialize\s*\(/',
        'message' => 'unserialize() can instantiate arbitrary objects; use json_decode',
    ),
    array(
        'name' => 'dynamic-include',
        'level' => LEVEL_ERROR,
        'pattern' => '/(?:include|require)(?:_once)?\s*\(?\s*\$/',
        // $dir/$root in this codebase are always derived from dirname(__FILE__)
        // at the top of the file; they never carry request input.
        'unless' => '/dirname\(__FILE__\)|__DIR__|_PS_MODULE_DIR_|\$iprestaConfig|\$configFile|\$dir\b|\$root\b/',
        'message' => 'File included from a variable path',
    ),

    // --- Transport / crypto ---------------------------------------------
    array(
        'name' => 'ssl-verify-off',
        'level' => LEVEL_ERROR,
        'pattern' => '/CURLOPT_SSL_VERIFY(?:PEER|HOST)\s*,\s*(?:false|0)\b/i',
        'message' => 'TLS certificate verification disabled',
    ),
    array(
        'name' => 'weak-hash-for-secret',
        'level' => LEVEL_WARN,
        'pattern' => '/(?:md5|sha1)\s*\(\s*\$(?:pass|password|secret|token|key)/i',
        'message' => 'Weak hash used on a credential',
    ),

    // --- Secrets ---------------------------------------------------------
    array(
        'name' => 'hardcoded-lwa-token',
        'level' => LEVEL_ERROR,
        'pattern' => '/[\'"]Atzr\|[A-Za-z0-9]/',
        'unless' => '/strpos|!==\s*0|===\s*0|placeholder|example|REPRO|TEST/i',
        'message' => 'Hardcoded LWA refresh token',
    ),
    array(
        'name' => 'hardcoded-client-secret',
        'level' => LEVEL_ERROR,
        'pattern' => '/[\'"]amzn1\.oa2-cs\.v1\.[A-Za-z0-9]{8}/',
        'unless' => '/XXXX|placeholder|example|sample/i',
        'message' => 'Hardcoded LWA client secret',
    ),

    // --- PrestaShop conventions -----------------------------------------
    array(
        'name' => 'echoed-secret',
        'level' => LEVEL_ERROR,
        'pattern' => '/[\'"]mkpro_(?:client_secret|refresh_token|imap_password)[\'"]\s*=>\s*(?!.*\'\')/i',
        'unless' => '/storedRefreshToken|Configuration::get|=> *\'\'/',
        'message' => 'Secret assigned into a template variable - it must never be echoed back into the form',
    ),
);

/**
 * Smarty variables reviewed and confirmed safe to print unescaped.
 *
 * Each carries its reason. An allowlist without reasons rots into a place
 * where real findings get hidden, so anything added here needs a sentence
 * saying why, and should be re-checked if the variable's source changes.
 */
$tplAllowVars = array(
    'confirm_msg' => 'HTML from displayConfirmation() built from translated literals; escaping would break the markup',
    'lim'         => 'loop value from a hardcoded integer array',
    'psCond'      => 'PrestaShop condition key from a fixed map',
    'flag'        => 'loop key from a fixed rule map',
    'i'           => 'numeric loop index',
    'etype'       => 'entity type from a fixed set',
);

/** Template rules, applied to .tpl files. */
$tplRules = array(
    array(
        'name' => 'unescaped-output',
        'level' => LEVEL_WARN,
        'pattern' => '/\{\$[a-zA-Z_][a-zA-Z0-9_\.\[\]\'"$]*\}/',
        'unless' => '/escape|nl2br|intval|\|@?count|\|json_encode/',
        'message' => 'Smarty variable printed without |escape',
    ),
    array(
        'name' => 'secret-value-in-input',
        'level' => LEVEL_ERROR,
        'pattern' => '/name="mkpro_(?:client_secret|refresh_token|imap_password)"[^>]*value="\{\$/',
        'message' => 'Stored secret rendered into an input value attribute',
    ),
);

/**
 * Walk a template's Smarty comment delimiters.
 *
 * An unmatched *} is not a syntax error - Smarty prints it, and whatever
 * comment text precedes it, straight into the merchant's browser. That is
 * how a developer note about credential handling ended up rendered on the
 * settings page: an edit split a multi-line comment and left the closing
 * delimiter orphaned. Compiling the template does not catch it, because
 * there is nothing invalid about the output.
 *
 * @return array Findings as [line, message]
 */
function smartyCommentProblems($src)
{
    $out = array();
    $len = strlen($src);
    $i = 0;
    $line = 1;
    $in = false;
    $openLine = 0;

    while ($i < $len) {
        if ($src[$i] === "
") {
            $line++;
            $i++;
            continue;
        }
        $two = substr($src, $i, 2);
        if (!$in && $two === '{*') {
            $in = true;
            $openLine = $line;
            $i += 2;
            continue;
        }
        if ($in && $two === '*}') {
            $in = false;
            $i += 2;
            continue;
        }
        if (!$in && $two === '*}') {
            $out[] = array($line, 'Orphaned *} - Smarty will print it, and the comment text before it, to the page');
            $i += 2;
            continue;
        }
        $i++;
    }

    if ($in) {
        $out[] = array($openLine, 'Unclosed {* - everything after it is swallowed');
    }

    return $out;
}

/** Files that must carry the PrestaShop direct-access guard. */
function needsPsGuard($rel)
{
    if (strpos($rel, 'relay-server') === 0 || strpos($rel, 'tools') === 0) {
        return false; // relay is standalone; tools are CLI
    }
    return substr($rel, -4) === '.php' && basename($rel) !== 'index.php';
}

/**
 * Either guard closes the same hole: the file must not answer over HTTP.
 * A CLI-only script cannot carry the _PS_VERSION_ guard, because nothing has
 * defined it yet at the point the check has to run.
 *
 * @return bool
 */
function hasDirectAccessGuard($src)
{
    return strpos($src, '_PS_VERSION_') !== false
        || preg_match('/PHP_SAPI\s*!==?\s*[\'"]cli[\'"]/', $src);
}

function walk($dir, $skipDirs, &$files)
{
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            if (!in_array($entry, $skipDirs, true)) {
                walk($path, $skipDirs, $files);
            }
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'php' || $ext === 'tpl') {
            $files[] = $path;
        }
    }
}

$files = array();
walk($root, $skipDirs, $files);

$findings = array();
foreach ($files as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));

    // The scanner's own rule strings contain the patterns it searches for.
    if ($rel === 'tools/security-scan.php') {
        continue;
    }

    $src = file_get_contents($path);
    $lines = preg_split('/\r\n|\r|\n/', $src);
    $isTpl = substr($path, -4) === '.tpl';
    $active = $isTpl ? $tplRules : $rules;

    foreach ($lines as $i => $line) {
        // Skip comment-only lines: rules describe code, not prose about code.
        $trimmed = ltrim($line);
        if ($trimmed === '' || strpos($trimmed, '*') === 0 || strpos($trimmed, '//') === 0
            || strpos($trimmed, '#') === 0 || strpos($trimmed, '{*') === 0) {
            continue;
        }
        foreach ($active as $rule) {
            if (!preg_match($rule['pattern'], $line, $hit)) {
                continue;
            }
            if (isset($rule['unless']) && preg_match($rule['unless'], $line)) {
                continue;
            }
            // Every unescaped variable on the line must be reviewed-safe for
            // the line to pass; one unknown variable is still a finding.
            if ($rule['name'] === 'unescaped-output') {
                preg_match_all('/\{\$([a-zA-Z_][a-zA-Z0-9_]*)/', $line, $vars);
                $unknown = array_diff(array_unique($vars[1]), array_keys($tplAllowVars));
                if (!$unknown) {
                    continue;
                }
            }
            $findings[] = array(
                'level' => $rule['level'],
                'rule' => $rule['name'],
                'file' => $rel,
                'line' => $i + 1,
                'message' => $rule['message'],
                'code' => trim(substr($line, 0, 110)),
            );
        }
    }

    if ($isTpl) {
        foreach (smartyCommentProblems($src) as $p) {
            $findings[] = array(
                'level' => LEVEL_ERROR,
                'rule' => 'smarty-comment',
                'file' => $rel,
                'line' => $p[0],
                'message' => $p[1],
                'code' => '',
            );
        }
    }

    if (!$isTpl && needsPsGuard($rel) && !hasDirectAccessGuard($src)) {
        $findings[] = array(
            'level' => LEVEL_WARN,
            'rule' => 'missing-ps-guard',
            'file' => $rel,
            'line' => 1,
            'message' => 'No direct-access guard (_PS_VERSION_ or CLI) - file can be requested over HTTP',
            'code' => '',
        );
    }
}

$errors = 0;
$warnings = 0;
foreach ($findings as $f) {
    if ($f['level'] === LEVEL_ERROR) {
        $errors++;
    } else {
        $warnings++;
    }
}

if ($quiet && $errors === 0) {
    exit(0);
}

$out = '';
$out .= "Security scan - " . count($files) . " files\n\n";

if (!$findings) {
    $out .= "No findings.\n";
} else {
    usort($findings, function ($a, $b) {
        if ($a['level'] !== $b['level']) {
            return $a['level'] === LEVEL_ERROR ? -1 : 1;
        }
        return strcmp($a['file'], $b['file']);
    });
    foreach ($findings as $f) {
        $out .= sprintf("[%s] %s\n  %s:%d  %s\n", $f['level'], $f['message'], $f['file'], $f['line'], $f['rule']);
        if ($f['code'] !== '') {
            $out .= '    ' . $f['code'] . "\n";
        }
        $out .= "\n";
    }
}

$out .= sprintf("%d error(s), %d warning(s)\n", $errors, $warnings);

fwrite($errors > 0 ? STDERR : STDOUT, $out);
exit($errors > 0 ? 1 : 0);
