<?php
/**
 * IntelliPresta relay — call every registered shop's schedule.
 *
 *   php schedule-run.php          call the shops that are due a poll
 *   php schedule-run.php status   what is registered, and how it is going
 *
 * Put this in the relay's own crontab, once, to run every five minutes.
 * The line itself is below the docblock, because a crontab five minute
 * pattern contains the characters that end a block comment.
 *
 * That single line is what lets a merchant have exact intervals without
 * touching their own server. The shop still decides what runs and how often -
 * this only knocks on the door; run_due does nothing unless the shop's own
 * table says something is due.
 *
 * BACKOFF. A shop that stops answering is not hammered every five minutes for
 * ever. After a run of failures it is polled hourly instead, and it returns to
 * the normal cycle the moment it answers. Shops go offline, move domain, and
 * let certificates lapse; none of that should cost us a request every five
 * minutes indefinitely, or fill the log with the same error.
 *
 * PHP 5.6+ compatible.
 */

// The crontab line, kept out of the comment above for the reason given there:
//
//   */5 * * * * cd /home/inteylip/spapi && HOME=/home/inteylip php schedule-run.php >> schedule.log 2>&1

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

require_once dirname(__FILE__) . '/schedule-store.php';

/** Failures after which a shop drops to hourly. */
define('BACKOFF_AFTER', 6);

/** Poll interval for shops in backoff, in seconds. */
define('BACKOFF_EVERY', 3600);

/** How long to let one shop's run_due take before moving on. */
define('SHOP_TIMEOUT', 120);

$store = new ScheduleStore();
$shops = $store->all();

if (isset($argv[1]) && $argv[1] === 'status') {
    if (!$shops) {
        echo "No shops registered.\n";
        exit(0);
    }
    printf("%-52s %-20s %-8s %s\n", 'SHOP', 'LAST RUN (UTC)', 'STATUS', 'FAILS');
    foreach ($shops as $url => $s) {
        printf(
            "%-52s %-20s %-8s %d\n",
            substr(preg_replace('#^https?://#', '', $url), 0, 52),
            $s['last_run_at'] ? $s['last_run_at'] : 'never',
            $s['last_status'] ? $s['last_status'] : '-',
            (int) $s['consecutive_failures']
        );
    }
    exit(0);
}

if (!$shops) {
    exit(0);
}

$now = time();
$called = 0;
$ok = 0;

foreach ($shops as $url => $shop) {
    $fails = (int) $shop['consecutive_failures'];

    if ($fails >= BACKOFF_AFTER) {
        $last = $shop['last_run_at'] ? strtotime($shop['last_run_at'] . ' UTC') : 0;
        if ($last && ($now - $last) < BACKOFF_EVERY) {
            continue;
        }
    }

    $target = $url
        . (strpos($url, '?') === false ? '?' : '&')
        . 'token=' . rawurlencode($shop['token'])
        . '&action=run_due';

    $ch = curl_init($target);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, SHOP_TIMEOUT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $called++;
    $answer = $body === false ? null : json_decode($body, true);
    $good = is_array($answer) && !empty($answer['success']);

    $shop['last_run_at'] = gmdate('Y-m-d H:i:s');
    $shop['last_status'] = $good ? 'ok' : 'failed';
    $shop['consecutive_failures'] = $good ? 0 : $fails + 1;
    $store->put($shop);

    if ($good) {
        $ok++;
        $ran = isset($answer['ran']) ? (int) $answer['ran'] : 0;
        if ($ran > 0) {
            printf("[%s] %s ran %d task(s)\n", gmdate('H:i:s'), $url, $ran);
        }
    } else {
        printf(
            "[%s] %s FAILED (%s)\n",
            gmdate('H:i:s'),
            $url,
            $err !== '' ? $err : 'HTTP ' . $code
        );
    }
}

// Only worth a line when there is something to say; this runs 288 times a day
// and a quiet log is one somebody might actually read.
if ($called && $ok !== $called) {
    printf("[%s] polled %d shop(s), %d ok, %d failed\n", gmdate('H:i:s'), $called, $ok, $called - $ok);
}
