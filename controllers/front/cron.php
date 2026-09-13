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
 * Cron front controller for Amazon Marketplace Pro.
 *
 * This used to be nineteen endpoints and nineteen crontab lines, one per task,
 * which put the whole schedule in the merchant's hands. The schedule now lives
 * in the module, and this controller is a shell over it:
 *
 *   ?token=...&action=run_due     run whatever the schedule says is due
 *   ?token=...&action=<task_key>  run one task immediately, whatever its
 *                                 schedule says - kept because it is what
 *                                 existing installs already have in crontab,
 *                                 and because it is useful when testing
 *
 * A shop with no crontab at all can still be automated: it registers with the
 * IntelliPresta scheduler (see AmazonRelaySchedule), which calls run_due on
 * this same URL every five minutes.
 *
 * With multistore, every shop has its own URL (the shop's own address) and
 * its own token. The request runs in the front office of the shop in the URL,
 * so everything it does - the token check, the schedule, the tasks - is for
 * that shop alone.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonMarketplaceProCronModuleFrontController extends ModuleFrontController
{
    /** @var bool Disable rendering (we output JSON directly) */
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        require_once dirname(__FILE__) . '/../../classes/AmzproShop.php';

        // The shop in the URL, and its own token: another shop's token does
        // not open this shop's schedule.
        $idShop = AmzproShop::id();
        $token = Tools::getValue('token');
        $expectedToken = AmzproShop::get('AMZPRO_CRON_TOKEN', $idShop);

        if (!$expectedToken || !hash_equals((string) $expectedToken, (string) $token)) {
            $this->jsonResponse(array('success' => false, 'error' => 'Invalid or missing cron token.'), 403);
            return;
        }

        $action = (string) Tools::getValue('action');

        // The relay proving a registration. Answered before anything else
        // runs, because its whole purpose is to confirm the token without
        // causing a side effect.
        if ($action === 'verify') {
            $this->jsonResponse(array(
                'success' => true,
                'nonce' => (string) Tools::getValue('nonce'),
                'shop' => Configuration::get('PS_SHOP_NAME', null, AmzproShop::groupId($idShop), $idShop),
                'id_shop' => $idShop,
            ));
            return;
        }

        // A cron call can reach new code before anyone opens the back office,
        // which is where upgrades run. Free once the tables are up to date.
        AmzproShop::ensureSchema();

        require_once dirname(__FILE__) . '/../../classes/AmazonScheduler.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonTaskRunner.php';

        // The whole schedule in one call. This is the only line a merchant
        // needs in crontab, and running it more often than any task's interval
        // costs nothing: tasks that are not due are skipped.
        if ($action === 'run_due' || $action === '') {
            $scheduler = new AmazonScheduler();
            $this->jsonResponse($scheduler->runDue());
            return;
        }

        $runner = new AmazonTaskRunner();
        $result = $runner->run($action);
        $runner->log($action, $result);

        // A task run by hand still counts as a run, so the schedule does not
        // immediately repeat work that has just been done.
        AmazonScheduler::noteManualRun($action, $result);

        $this->jsonResponse($result);
    }

    /**
     * Emit a JSON body and stop. Kept here rather than in the runner because
     * it is the only part of the old controller that was ever about HTTP.
     */
    private function jsonResponse($data, $httpCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8', true, $httpCode);
        echo json_encode($data);
        exit;
    }
}
