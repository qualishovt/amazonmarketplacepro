<?php
/**
 * IntelliPresta relay — access log archiving and review.
 *
 * Amazon's Data Protection Policy asks for audit logs with at least
 * bi-weekly review and a minimum of 12 months retention. The hosting
 * provider rotates its logs on a much shorter cycle, so this keeps our own
 * copy of the lines that concern the relay, and makes the review a job of
 * minutes rather than reading raw Apache logs.
 *
 *   php log-archive.php archive          append new relay lines, prune >12 months
 *   php log-archive.php review           summarise the last 14 days
 *   php log-archive.php review --days=30 summarise a different window
 *   php log-archive.php status           what is archived, and how far back
 *
 * Run "archive" daily from cron. Run "review" fortnightly and read it.
 *
 * SCOPE. These are web server access lines for the relay endpoints. They
 * contain request paths, status codes and client addresses. They do not
 * contain Amazon Information, because the relay never receives any - it
 * exchanges a token and returns it. Refresh tokens travel in POST bodies,
 * which Apache does not log.
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

define('RETENTION_MONTHS', 12);

/** Where our archives live. Outside the web root. */
$archiveDir = getenv('IPRESTA_LOG_DIR');
if (!$archiveDir) {
    $home = getenv('HOME');
    $archiveDir = ($home ? $home : dirname(dirname(__FILE__))) . '/spapi-logs';
}

/** Paths that identify a request as ours. */
$relayPaths = array('/spapi/', '/ebay/');

/**
 * Candidate locations for the live access log on cPanel hosting.
 * The first readable match wins; IPRESTA_ACCESS_LOG overrides everything.
 *
 * @return array
 */
function candidateLogs()
{
    $override = getenv('IPRESTA_ACCESS_LOG');
    if ($override) {
        return array($override);
    }

    $home = getenv('HOME');
    if (!$home) {
        return array();
    }

    $found = array();
    foreach (array($home . '/access-logs', $home . '/logs') as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach ((array) scandir($dir) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            // Skip already-rotated archives; we read the live logs.
            if (substr($f, -3) === '.gz') {
                continue;
            }
            $path = $dir . '/' . $f;
            if (is_file($path) && is_readable($path)) {
                $found[] = $path;
            }
        }
    }

    return $found;
}

/** @return bool whether the line is a request to a relay endpoint */
function isRelayLine($line, $relayPaths)
{
    foreach ($relayPaths as $p) {
        if (strpos($line, $p) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Is this request asking for something that must never be served?
 *
 * A plain substring list is what this started as, and it missed a real probe:
 * a scanner asked for /spapi/.ipresta-key - the actual name of the key file -
 * and the pattern only knew about ".key", not "-key". The deny rules refused
 * it correctly, but the review that exists to surface such probes stayed
 * silent. These rules are deliberately broader than the deny list: a false
 * positive costs a glance, a false negative costs the thing this is for.
 *
 * @param string $path Request path, query string already stripped
 * @return bool
 */
function isProtectedTarget($path)
{
    $name = strtolower(basename($path));

    // Anything dot-prefixed: .env, .git, .htaccess, .ipresta-key.
    if ($name !== '' && $name[0] === '.') {
        return true;
    }

    // The files the deny rules name explicitly.
    $exact = array(
        'config.php', 'config.sample.php', 'secrets.php',
        'secret-tool.php', 'log-archive.php',
    );
    if (in_array($name, $exact, true)) {
        return true;
    }

    // Key material by extension, wherever it ends up.
    if (preg_match('/\.(key|pem|env|p12|pfx|crt)$/', $name)) {
        return true;
    }

    // Anything that merely looks like a credential. Catches ipresta.key,
    // .ipresta-key, backup-secrets.php, id_rsa, wp-config.php and the rest
    // of what scanners routinely walk through.
    return (bool) preg_match('/(key|secret|passwd|password|credential|id_rsa|\benv\b)/', $name);
}

/**
 * Parse an Apache combined log line.
 *
 * @return array|null
 */
function parseLine($line)
{
    if (!preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+)[^"]*" (\d{3}) (\S+)/', $line, $m)) {
        return null;
    }
    return array(
        'ip' => $m[1],
        'time' => $m[2],
        'method' => $m[3],
        'path' => $m[4],
        'status' => (int) $m[5],
        'bytes' => $m[6],
    );
}

$command = isset($argv[1]) ? $argv[1] : '';

switch ($command) {
    case 'archive':
        if (!is_dir($archiveDir) && !@mkdir($archiveDir, 0700, true)) {
            fwrite(STDERR, "Cannot create archive directory: $archiveDir\n");
            exit(1);
        }
        @chmod($archiveDir, 0700);

        $logs = candidateLogs();
        if (!$logs) {
            fwrite(STDERR, "No readable access log found. Set IPRESTA_ACCESS_LOG to its path.\n");
            exit(1);
        }

        // Remember how far we read each log, so a daily run appends rather
        // than duplicating. Logs are rotated in place, so a file that shrank
        // has been rotated and is read from the start.
        $stateFile = $archiveDir . '/.offsets.json';
        $offsets = array();
        if (is_readable($stateFile)) {
            $decoded = json_decode((string) file_get_contents($stateFile), true);
            if (is_array($decoded)) {
                $offsets = $decoded;
            }
        }

        $added = 0;
        $buckets = array();
        foreach ($logs as $log) {
            $size = filesize($log);
            $from = isset($offsets[$log]) ? (int) $offsets[$log] : 0;
            if ($from > $size) {
                $from = 0; // rotated
            }
            $fh = @fopen($log, 'rb');
            if (!$fh) {
                continue;
            }
            fseek($fh, $from);
            while (($line = fgets($fh)) !== false) {
                if (!isRelayLine($line, $relayPaths)) {
                    continue;
                }
                $p = parseLine($line);
                $month = $p ? date('Y-m', strtotime($p['time'])) : date('Y-m');
                if (!isset($buckets[$month])) {
                    $buckets[$month] = '';
                }
                $buckets[$month] .= $line;
                $added++;
            }
            $offsets[$log] = ftell($fh);
            fclose($fh);
        }

        foreach ($buckets as $month => $data) {
            $file = $archiveDir . '/' . $month . '.log';
            file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
            @chmod($file, 0600);
        }

        file_put_contents($stateFile, json_encode($offsets));
        @chmod($stateFile, 0600);

        // Prune anything past the retention window.
        $cutoff = date('Y-m', strtotime('-' . RETENTION_MONTHS . ' months'));
        $pruned = 0;
        foreach ((array) glob($archiveDir . '/*.log') as $f) {
            if (basename($f, '.log') < $cutoff) {
                unlink($f);
                $pruned++;
            }
        }

        printf("archived %d relay request(s) from %d log file(s); pruned %d month(s) past %d-month retention\n",
            $added, count($logs), $pruned, RETENTION_MONTHS);
        break;

    case 'review':
        $days = 14;
        foreach ($argv as $a) {
            if (preg_match('/^--days=(\d+)$/', $a, $m)) {
                $days = (int) $m[1];
            }
        }
        $since = strtotime('-' . $days . ' days');

        $rows = array();
        foreach ((array) glob($archiveDir . '/*.log') as $f) {
            foreach (file($f) as $line) {
                $p = parseLine($line);
                if (!$p || strtotime($p['time']) < $since) {
                    continue;
                }
                $rows[] = $p;
            }
        }

        printf("Relay access review - last %d days (%d requests)\n\n", $days, count($rows));
        if (!$rows) {
            echo "No relay requests in the window.\n";
            echo "For a relay with no merchants connected yet, this is expected.\n";
            break;
        }

        $byEndpoint = array();
        $byStatus = array();
        $byIp = array();
        $suspicious = array();
        foreach ($rows as $p) {
            $endpoint = preg_replace('/\?.*$/', '', $p['path']);
            $byEndpoint[$endpoint] = isset($byEndpoint[$endpoint]) ? $byEndpoint[$endpoint] + 1 : 1;
            $byStatus[$p['status']] = isset($byStatus[$p['status']]) ? $byStatus[$p['status']] + 1 : 1;
            $byIp[$p['ip']] = isset($byIp[$p['ip']]) ? $byIp[$p['ip']] + 1 : 1;

            // Anything probing the files that must never be served.
            if (isProtectedTarget($endpoint)) {
                $suspicious[] = $p;
            }
        }

        echo "By endpoint:\n";
        arsort($byEndpoint);
        foreach ($byEndpoint as $k => $v) {
            printf("  %-45s %d\n", substr($k, 0, 45), $v);
        }

        echo "\nBy status:\n";
        ksort($byStatus);
        foreach ($byStatus as $k => $v) {
            printf("  %-5s %d\n", $k, $v);
        }

        echo "\nTop clients:\n";
        arsort($byIp);
        $n = 0;
        foreach ($byIp as $k => $v) {
            printf("  %-40s %d\n", $k, $v);
            if (++$n >= 10) {
                break;
            }
        }

        $served = array();
        foreach ($suspicious as $p) {
            // 403 refused, 404 not there at all - both are correct outcomes.
            // Anything else means the file was handed over, in whole or part.
            if ($p['status'] !== 403 && $p['status'] !== 404) {
                $served[] = $p;
            }
        }

        if ($suspicious) {
            echo "\nRequests for protected files (" . count($suspicious) . "):\n";
            foreach (array_slice($suspicious, 0, 30) as $p) {
                printf("  %-16s %s %s -> %d%s\n", $p['ip'], $p['method'], $p['path'],
                    $p['status'], ($p['status'] === 403 || $p['status'] === 404) ? '' : '   <<< SERVED');
            }
            if ($served) {
                echo "\n  INCIDENT: " . count($served) . " protected file(s) were served rather\n";
                echo "  than refused. Start incident-response.md at containment now.\n";
            } else {
                echo "\n  All refused (403) or absent (404). This is the expected result:\n";
                echo "  scanners probe for these constantly. Nothing to act on.\n";
            }
        } else {
            $served = array();
            echo "\nNo requests for protected files.\n";
        }

        echo "\nReviewed on " . date('Y-m-d') . ". Record anything acted on in the repository.\n";

        // Non-zero so this can also run unattended as a guard.
        if ($served) {
            exit(2);
        }
        break;

    case 'status':
        $files = (array) glob($archiveDir . '/*.log');
        sort($files);
        printf("Archive directory: %s\n", $archiveDir);
        printf("Retention: %d months\n\n", RETENTION_MONTHS);
        if (!$files) {
            echo "No archives yet. Run: php log-archive.php archive\n";
            break;
        }
        $total = 0;
        foreach ($files as $f) {
            $lines = count(file($f));
            $total += $lines;
            printf("  %-12s %6d requests  %8d bytes\n", basename($f, '.log'), $lines, filesize($f));
        }
        printf("\n%d month(s) archived, %d requests total\n", count($files), $total);
        printf("Oldest: %s   Newest: %s\n", basename($files[0], '.log'), basename(end($files), '.log'));
        break;

    default:
        fwrite(STDERR, "Usage: php log-archive.php archive|review|status\n\n");
        fwrite(STDERR, "  archive           append new relay requests, prune past 12 months (run daily)\n");
        fwrite(STDERR, "  review [--days=N] summarise the window and flag probes for protected files\n");
        fwrite(STDERR, "  status            what is archived and how far back it goes\n");
        exit(1);
}
