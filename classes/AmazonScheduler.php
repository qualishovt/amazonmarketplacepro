<?php
/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/**
 * The module's own schedule.
 *
 * Automation used to be a wall of nineteen URLs and the instruction "add these
 * to your server's crontab". That asked the merchant to own the part of the
 * job they are least equipped to own, and a shop whose host has no crontab -
 * most shared hosting - simply could not follow it. The schedule now lives in
 * a table the merchant can edit, and there are two ways it runs:
 *
 *   Cron        one URL, called as often as the host allows. Reliable, and
 *               still the recommendation for a busy shop.
 *   From traffic  no setup at all. A page view checks whether anything is due
 *               and, if so, runs it AFTER the visitor's page has been sent.
 *
 * Both funnel into runDue(), so a shop can switch between them, or use both,
 * without the schedule behaving differently.
 *
 * On timekeeping: every time written here comes from PHP, never from MySQL
 * NOW(). The two clocks drift apart on shared hosting, and a schedule that
 * compares a PHP 'now' against a MySQL-written 'next run' fires either
 * constantly or never.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonScheduler
{
    /** A run in progress is considered dead after this long, so a fatal
     *  error during a task cannot wedge the schedule permanently. */
    const LOCK_TIMEOUT = 900;

    /** Traffic-triggered runs are not attempted more often than this. */
    const TRAFFIC_THROTTLE = 60;

    /** How long a single runDue() pass may spend before stopping and leaving
     *  the rest for the next pass. Keeps one slow task from starving others. */
    const DEFAULT_BUDGET = 240;

    /**
     * Every task the module can run, with the interval it ships with.
     *
     * The keys match AmazonTaskRunner::run(), and the intervals are the ones
     * the old documentation told merchants to type into crontab, so an install
     * that accepts the defaults behaves exactly as the manual instructions did.
     */
    public static function catalogue()
    {
        return array(
            'import_orders'       => array('Import orders', 15, 'Orders'),
            'create_orders'       => array('Create PrestaShop orders', 15, 'Orders'),
            'sync_stock'          => array('Sync stock', 30, 'Catalogue'),
            'sync_products'       => array('Full product sync', 1440, 'Catalogue'),
            'import_returns'      => array('Import returns', 60, 'Fulfilment'),
            'process_returns'     => array('Process returns', 60, 'Fulfilment'),
            'sync_fba'            => array('FBA inventory sync', 60, 'Fulfilment'),
            'reprice'             => array('Repricing cycle', 60, 'Pricing'),
            'fetch_fees'          => array('Fetch fees', 360, 'Money'),
            'poll_reports'        => array('Poll reports', 30, 'Money'),
            'sync_promotions'     => array('Sync promotions', 360, 'Pricing'),
            'multi_import_orders' => array('Import orders, all marketplaces', 15, 'Orders'),
            'multi_sync_products' => array('Product sync, all marketplaces', 1440, 'Catalogue'),
            'request_reviews'     => array('Request reviews', 1440, 'Orders'),
            'process_feeds'       => array('Process feed queue', 15, 'Catalogue'),
            'remote_cart'         => array('Settle remote carts', 15, 'Orders'),
            'fetch_messages'      => array('Fetch buyer messages', 60, 'Orders'),
            'upload_invoices'     => array('Upload invoices', 360, 'Money'),
            'purge_pii'           => array('Purge buyer data past retention', 1440, 'System'),
        );
    }

    /** Human label for a task key, falling back to the key itself. */
    public static function label($key)
    {
        $c = self::catalogue();

        return isset($c[$key]) ? $c[$key][0] : $key;
    }

    /* ─────────────────────────── Reading ─────────────────────────── */

    /** @return array every task row for this shop, in run order */
    public static function all()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
                WHERE `id_shop` = ' . (int) self::shopId() . '
                ORDER BY `active` DESC, `next_run_at` ASC, `id_task` ASC';

        $rows = Db::getInstance()->executeS($sql);

        return $rows ? $rows : array();
    }

    /** @return array|false one task row */
    public static function get($idTask)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
                WHERE `id_task` = ' . (int) $idTask . '
                  AND `id_shop` = ' . (int) self::shopId();

        // getRow rather than getValue with a LIMIT: getValue returns false for
        // a legitimately empty column, which is indistinguishable from "no row".
        $row = Db::getInstance()->getRow($sql);

        return $row ? $row : false;
    }

    /* ─────────────────────────── Writing ─────────────────────────── */

    /**
     * Create or update a task.
     *
     * @param array $data task_key, interval_minutes, active, id_task (optional)
     * @return int|false the task id
     */
    public static function save(array $data)
    {
        $key = isset($data['task_key']) ? (string) $data['task_key'] : '';
        if (!array_key_exists($key, self::catalogue())) {
            return false;
        }

        $interval = isset($data['interval_minutes']) ? (int) $data['interval_minutes'] : 0;
        if ($interval < 1) {
            $interval = 1;
        }
        $active = !empty($data['active']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        $db = Db::getInstance();
        $idTask = isset($data['id_task']) ? (int) $data['id_task'] : 0;

        if ($idTask > 0 && self::get($idTask)) {
            $db->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amzpro_scheduled_task` SET
                    `task_key` = \'' . pSQL($key) . '\',
                    `interval_minutes` = ' . (int) $interval . ',
                    `active` = ' . (int) $active . ',
                    `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_task` = ' . (int) $idTask . '
                   AND `id_shop` = ' . (int) self::shopId()
            );

            return $idTask;
        }

        // A brand new task is due one interval from now, not immediately:
        // adding six tasks should not start six runs at once.
        $next = date('Y-m-d H:i:s', time() + ($interval * 60));

        $db->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
             (`id_shop`, `task_key`, `interval_minutes`, `active`, `next_run_at`, `date_add`, `date_upd`)
             VALUES (
                ' . (int) self::shopId() . ',
                \'' . pSQL($key) . '\',
                ' . (int) $interval . ',
                ' . (int) $active . ',
                \'' . pSQL($next) . '\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
             )'
        );

        return (int) $db->Insert_ID();
    }

    public static function delete($idTask)
    {
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
             WHERE `id_task` = ' . (int) $idTask . '
               AND `id_shop` = ' . (int) self::shopId()
        );
    }

    /** Flip a task on or off. @return int|false the new state */
    public static function toggle($idTask)
    {
        $task = self::get($idTask);
        if (!$task) {
            return false;
        }
        $new = empty($task['active']) ? 1 : 0;

        // Re-arming a task starts its interval now, so switching something back
        // on does not immediately fire because it was off for a fortnight.
        $next = date('Y-m-d H:i:s', time() + ((int) $task['interval_minutes'] * 60));

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
             SET `active` = ' . (int) $new . ',
                 `next_run_at` = ' . ($new ? '\'' . pSQL($next) . '\'' : '`next_run_at`') . ',
                 `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `id_task` = ' . (int) $idTask . '
               AND `id_shop` = ' . (int) self::shopId()
        );

        return $new;
    }

    /* ─────────────────────────── Running ─────────────────────────── */

    /** @return array tasks that are active, due, and not already running */
    public static function due()
    {
        $now = date('Y-m-d H:i:s');

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
             WHERE `id_shop` = ' . (int) self::shopId() . '
               AND `active` = 1
               AND `next_run_at` <= \'' . pSQL($now) . '\'
             ORDER BY `next_run_at` ASC'
        );

        return $rows ? $rows : array();
    }

    /**
     * Run everything that is due, within a time budget.
     *
     * Safe to call as often as you like: it takes a lock, so two callers - a
     * crontab and a page view arriving together - cannot run the same task
     * twice.
     */
    public function runDue($budgetSeconds = self::DEFAULT_BUDGET)
    {
        if (!self::acquireLock()) {
            return array('success' => true, 'skipped' => 'another run is in progress');
        }

        require_once dirname(__FILE__) . '/AmazonTaskRunner.php';

        $started = time();
        $runner = new AmazonTaskRunner();
        $ran = array();

        foreach (self::due() as $task) {
            if (time() - $started >= $budgetSeconds) {
                break;      // the rest stay due and go first next time
            }

            $taskStart = microtime(true);
            $result = $runner->run($task['task_key']);
            $ms = (int) round((microtime(true) - $taskStart) * 1000);

            $runner->log($task['task_key'], $result);
            self::recordRun($task, $result, $ms);

            $ran[] = array(
                'task' => $task['task_key'],
                'success' => !empty($result['success']),
                'ms' => $ms,
                'error' => isset($result['error']) ? $result['error'] : null,
            );
        }

        self::releaseLock();

        return array('success' => true, 'ran' => count($ran), 'tasks' => $ran);
    }

    /** Write the outcome of one run and schedule the next. */
    protected static function recordRun(array $task, $result, $ms)
    {
        $ok = !empty($result['success']);
        $message = $ok
            ? (isset($result['summary']) ? Tools::substr(json_encode($result['summary']), 0, 500) : 'OK')
            : (isset($result['error']) ? (string) $result['error'] : 'Failed');

        $now = time();
        $next = date('Y-m-d H:i:s', $now + ((int) $task['interval_minutes'] * 60));

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amzpro_scheduled_task` SET
                `last_run_at` = \'' . pSQL(date('Y-m-d H:i:s', $now)) . '\',
                `next_run_at` = \'' . pSQL($next) . '\',
                `last_status` = \'' . ($ok ? 'success' : 'error') . '\',
                `last_message` = \'' . pSQL(Tools::substr($message, 0, 500)) . '\',
                `last_duration_ms` = ' . (int) $ms . ',
                `run_count` = `run_count` + 1,
                `fail_count` = `fail_count` + ' . ($ok ? 0 : 1) . ',
                `date_upd` = \'' . pSQL(date('Y-m-d H:i:s', $now)) . '\'
             WHERE `id_task` = ' . (int) $task['id_task']
        );
    }

    /**
     * A task run by hand, or by a legacy per-task crontab line, still counts.
     * Without this the schedule would repeat work that was just done.
     */
    public static function noteManualRun($taskKey, $result)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
             WHERE `id_shop` = ' . (int) self::shopId() . '
               AND `task_key` = \'' . pSQL((string) $taskKey) . '\''
        );

        if ($rows) {
            foreach ($rows as $task) {
                self::recordRun($task, $result, 0);
            }
        }
    }

    /**
     * Run one task now, regardless of its schedule. Used by the Run now button.
     */
    public static function runNow($idTask)
    {
        $task = self::get($idTask);
        if (!$task) {
            return array('success' => false, 'error' => 'No such task.');
        }

        require_once dirname(__FILE__) . '/AmazonTaskRunner.php';

        $runner = new AmazonTaskRunner();
        $start = microtime(true);
        $result = $runner->run($task['task_key']);
        $ms = (int) round((microtime(true) - $start) * 1000);

        $runner->log($task['task_key'], $result);
        self::recordRun($task, $result, $ms);

        return $result;
    }

    /* ─────────────────── Running without a crontab ─────────────────── */

    /**
     * Arm the traffic trigger. Called from a page view, and does almost nothing.
     *
     * The work is deferred to shutdown rather than done here, which is the whole
     * point: by then the visitor already has their page, so nobody waits on
     * Amazon. Doing it inline would mean flushing halfway through rendering and
     * sending a broken page.
     *
     * The two checks are ordered by cost. The throttle reads a configuration
     * value PrestaShop has already cached; the due() query then runs at most
     * once a minute, which matters when this sits on every front office request.
     */
    public static function armTrafficTrigger()
    {
        if (!Configuration::get('AMZPRO_CRON_AUTO')) {
            return false;
        }

        $last = (int) Configuration::getGlobalValue('AMZPRO_CRON_AUTO_LAST');
        if ($last && (time() - $last) < self::TRAFFIC_THROTTLE) {
            return false;
        }
        Configuration::updateGlobalValue('AMZPRO_CRON_AUTO_LAST', time());

        if (!self::due()) {
            return false;
        }

        register_shutdown_function(array(__CLASS__, 'runFromTraffic'));

        return true;
    }

    /**
     * Runs once the response has gone out. Given a short budget on purpose:
     * this is still a visitor's request holding a PHP worker, not a batch
     * window, so it takes a bite and leaves the rest for the next trigger.
     */
    public static function runFromTraffic()
    {
        @ignore_user_abort(true);
        if (function_exists('session_write_close')) {
            @session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }

        $scheduler = new self();

        return $scheduler->runDue(60);
    }

    /* ─────────────────────────── Plumbing ─────────────────────────── */

    protected static function shopId()
    {
        return (int) Context::getContext()->shop->id;
    }

    /**
     * A lock that expires. Stored globally rather than per shop because the
     * point is to stop two PHP processes overlapping, whichever shop they
     * think they are serving.
     */
    protected static function acquireLock()
    {
        $held = (int) Configuration::getGlobalValue('AMZPRO_CRON_LOCK');
        if ($held && (time() - $held) < self::LOCK_TIMEOUT) {
            return false;
        }
        Configuration::updateGlobalValue('AMZPRO_CRON_LOCK', time());

        return true;
    }

    protected static function releaseLock()
    {
        Configuration::updateGlobalValue('AMZPRO_CRON_LOCK', 0);
    }

    /** Seed the schedule with the defaults the manual used to describe. */
    public static function seedDefaults($idShop = null)
    {
        $idShop = $idShop === null ? self::shopId() : (int) $idShop;
        $now = date('Y-m-d H:i:s');
        $db = Db::getInstance();

        foreach (self::catalogue() as $key => $meta) {
            $exists = $db->getRow(
                'SELECT `id_task` FROM `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
                 WHERE `id_shop` = ' . (int) $idShop . '
                   AND `task_key` = \'' . pSQL($key) . '\''
            );
            if ($exists) {
                continue;
            }

            // Seeded switched off. Turning automation on is the merchant's
            // decision, and a fresh install should not start calling Amazon
            // before the account is even connected.
            $db->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'amzpro_scheduled_task`
                 (`id_shop`, `task_key`, `interval_minutes`, `active`, `next_run_at`, `date_add`, `date_upd`)
                 VALUES (
                    ' . (int) $idShop . ',
                    \'' . pSQL($key) . '\',
                    ' . (int) $meta[1] . ',
                    0,
                    \'' . pSQL(date('Y-m-d H:i:s', time() + ((int) $meta[1] * 60))) . '\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                 )'
            );
        }
    }
}
