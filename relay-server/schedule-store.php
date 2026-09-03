<?php
/**
 * IntelliPresta relay — the scheduler registry.
 *
 * A registration is a shop's cron URL and its cron token. The token can
 * trigger a sync on that shop, so this file is a credential store and is
 * treated as one: it lives outside the web root, is written 0600, and this
 * class refuses to operate rather than fall back to a location that might be
 * served. log-archive.php learned that lesson the hard way - its first version
 * fell back to a path that resolved to public_html - and the same rule applies
 * here with more at stake.
 *
 * The file is small (one line of JSON per shop is not needed; the whole map is
 * one document) and written atomically, because schedule-run.php reads it on a
 * five minute cycle while registrations can arrive at any moment.
 *
 * PHP 5.6+ compatible.
 */

class ScheduleStore
{
    /** @var string */
    protected $path;

    public function __construct()
    {
        $dir = getenv('IPRESTA_SCHEDULE_DIR');
        if (!$dir) {
            $home = getenv('HOME');
            if (!$home) {
                // Cron does not always set HOME. Refusing is the only safe
                // answer: guessing produced a web-served path once already.
                $this->fail('Neither IPRESTA_SCHEDULE_DIR nor HOME is set, so the registry location is unknown.');
            }
            $dir = rtrim($home, '/\\') . '/ipresta-schedule';
        }

        if (preg_match('#(^|/)(public_html|www|htdocs|public)(/|$)#i', str_replace('\\', '/', $dir))) {
            $this->fail('The registry directory is inside a web-served path: ' . $dir);
        }

        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->fail('Could not create the registry directory: ' . $dir);
        }

        $this->path = $dir . '/shops.json';
    }

    /** @return array cron_url => registration */
    public function all()
    {
        if (!is_file($this->path)) {
            return array();
        }
        $raw = file_get_contents($this->path);
        $data = json_decode($raw, true);

        return is_array($data) ? $data : array();
    }

    public function put(array $row)
    {
        $all = $this->all();
        $all[$row['cron_url']] = $row;
        $this->save($all);
    }

    public function remove($cronUrl)
    {
        $all = $this->all();
        unset($all[$cronUrl]);
        $this->save($all);
    }

    protected function save(array $all)
    {
        // Write beside the target and rename, so a reader never sees a
        // half-written registry.
        $tmp = $this->path . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmp, json_encode($all)) === false) {
            $this->fail('Could not write the registry.');
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $this->path)) {
            @unlink($tmp);
            $this->fail('Could not replace the registry.');
        }
        @chmod($this->path, 0600);
    }

    /**
     * Is this an address we are willing to call every five minutes?
     *
     * @return string|null the reason to refuse, or null to accept
     */
    public static function rejectUrl($url)
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return 'That does not look like a URL.';
        }
        if (Tools_strtolower($parts['scheme']) !== 'https') {
            // The token travels in the query string on every call.
            return 'The shop must be reachable over HTTPS.';
        }

        $host = $parts['host'];
        $ips = array();
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $resolved = @gethostbynamel($host);
            if (!$resolved) {
                return 'That host name does not resolve.';
            }
            $ips = $resolved;
        }

        foreach ($ips as $ip) {
            // Private, loopback and link-local addresses are refused: without
            // this the scheduler would happily make requests into whatever
            // network it happens to sit in, on someone else's instruction.
            if (!filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                return 'That address is not reachable from the public internet.';
            }
        }

        return null;
    }

    protected function fail($message)
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'schedule store: ' . $message . "\n");
            exit(1);
        }
        header('Content-Type: application/json; charset=utf-8', true, 500);
        echo json_encode(array('success' => false, 'error' => 'Scheduler storage error.'));
        exit;
    }
}

/** Small helper so this file needs nothing from PrestaShop. */
function Tools_strtolower($s)
{
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}
