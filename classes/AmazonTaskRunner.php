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
 * Cron front controller for MarketplacesPro.
 *
 * Provides URL endpoints for automated tasks (order import, stock sync,
 * product sync, returns). Protected by a secret token stored in Configuration.
 *
 * Usage (add to server crontab):
 *   curl "https://yourshop.com/module/marketplacespro/cron?token=YOUR_TOKEN&action=import_orders"
 *   curl "https://yourshop.com/module/marketplacespro/cron?token=YOUR_TOKEN&action=sync_stock"
 *   curl "https://yourshop.com/module/marketplacespro/cron?token=YOUR_TOKEN&action=sync_products"
 *   curl "https://yourshop.com/module/marketplacespro/cron?token=YOUR_TOKEN&action=import_returns"
 *   curl "https://yourshop.com/module/marketplacespro/cron?token=YOUR_TOKEN&action=process_returns"
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

/**
 * Runs one scheduled task.
 *
 * These bodies used to live in the cron front controller, which meant a task
 * could only ever be started by an HTTP request someone else had to arrange.
 * They are ordinary methods here, so the scheduler can run them in process and
 * the controller can stay a thin shell over the same code.
 *
 * Nothing here touches the controller: no $this->context, no $this->module.
 * That is what made the move safe, and it is worth keeping true.
 *
 * Every task works for the shop the request acts for: the cron URL's shop, or
 * the shop selected in the back office for Run now. Its settings, its Amazon
 * connection, its log lines and its report e-mail are that shop's. The one
 * exception is the buyer data purge, which covers every shop by itself.
 *
 * PHP 5.6+ compatible.
 */
class AmazonTaskRunner
{
    /**
     * Run a task by key. Always returns an array with a 'success' flag, so a
     * caller never has to guess whether a task worked.
     */
    public function run($action)
    {
        switch ($action) {
            case 'import_orders':
                return $this->actionImportOrders();
            case 'sync_stock':
                return $this->actionSyncStock();
            case 'sync_products':
                return $this->actionSyncProducts();
            case 'create_orders':
                return $this->actionCreateOrders();
            case 'import_returns':
                return $this->actionImportReturns();
            case 'process_returns':
                return $this->actionProcessReturns();
            case 'sync_fba':
                return $this->actionSyncFba();
            case 'reprice':
                return $this->actionReprice();
            case 'fetch_fees':
                return $this->actionFetchFees();
            case 'poll_reports':
                return $this->actionPollReports();
            case 'sync_promotions':
                return $this->actionSyncPromotions();
            case 'multi_import_orders':
                return $this->actionMultiImportOrders();
            case 'multi_sync_products':
                return $this->actionMultiSyncProducts();
            case 'request_reviews':
                return $this->actionRequestReviews();
            case 'process_feeds':
                return $this->actionProcessFeeds();
            case 'remote_cart':
                return $this->actionRemoteCart();
            case 'fetch_messages':
                return $this->actionFetchMessages();
            case 'upload_invoices':
                return $this->actionUploadInvoices();
            case 'purge_pii':
                return $this->actionPurgePii();
        }

        return array('success' => false, 'error' => 'Unknown action: ' . $action);
    }

    /** Record the outcome of a run in the module log. */
    public function log($action, $result)
    {
        $this->logCronRun($action, $result);
    }
    /**
     * Import new Amazon orders into staging.
     */
    private function actionImportOrders()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonOrderImporter.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $importer = new AmazonOrderImporter($client, $this->getMarketplaceId());

        $env = AmazonSpApiClient::environment();
        $createdAfter = ($env === 'production')
            ? AmazonOrderImporter::configuredCreatedAfter()
            : 'TEST_CASE_200';

        $summary = $importer->importNewOrders($createdAfter);
        if ($summary === false) {
            return array('success' => false, 'error' => $importer->getLastError());
        }

        $this->sendReportEmail('Order import', array(
            'Fetched' => $summary['fetched'],
            'Imported new' => $summary['imported_new'],
            'Already staged' => $summary['already'],
            'Items unmatched' => $summary['items_unmatched'],
        ));

        // Retention is a policy requirement, not a convenience, so it is
        // not left to the merchant scheduling a second cron entry: every
        // import also clears what has aged out. A merchant who never adds
        // the purge_pii job is still compliant as long as orders import.
        $summary['pii_purged'] = $this->purgeAgedPii();

        return array('success' => true, 'action' => 'import_orders', 'summary' => $summary);
    }
    /**
     * Push current PrestaShop stock levels to Amazon for all synced products.
     */
    private function actionSyncStock()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonProductSync.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $sellerId = AmzproShop::get('AMZPRO_SELLER_ID');
        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), $sellerId);

        $env = AmazonSpApiClient::environment();
        $useMock = AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production';
        $sync->setMock($useMock);

        // Expired change-queue entries age out before each sync.
        require_once dirname(__FILE__) . '/AmazonListingSettings.php';
        AmazonListingSettings::purgeQueue();

        // Refresh PS side, then push
        $sync->syncPrestashopSide();
        $pushResult = $sync->pushToAmazon(100);

        $this->sendReportEmail('Stock sync', array(
            'Candidates' => isset($pushResult['candidates']) ? $pushResult['candidates'] : 0,
            'Pushed' => isset($pushResult['pushed']) ? $pushResult['pushed'] : 0,
            'Failed' => isset($pushResult['failed']) ? $pushResult['failed'] : 0,
        ));

        return array('success' => true, 'action' => 'sync_stock', 'summary' => $pushResult);
    }
    /**
     * Full bidirectional product sync.
     */
    private function actionSyncProducts()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonProductSync.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $sellerId = AmzproShop::get('AMZPRO_SELLER_ID');
        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), $sellerId);

        $env = AmazonSpApiClient::environment();
        $useMock = AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production';
        $sync->setMock($useMock);

        $psSummary = $sync->syncPrestashopSide();
        $azSummary = $sync->syncAmazonSide();

        return array(
            'success' => true,
            'action' => 'sync_products',
            'ps_summary' => $psSummary,
            'amazon_summary' => $azSummary,
        );
    }
    /**
     * Create real PS orders from staged Amazon orders.
     */
    private function actionCreateOrders()
    {
        require_once dirname(__FILE__) . '/AmazonOrderCreator.php';

        $idShop = $this->shopId();
        $idCarrier = (int) AmzproShop::get('AMZPRO_DEFAULT_CARRIER');
        $idOrderState = (int) AmzproShop::get('AMZPRO_DEFAULT_ORDER_STATE');

        if (!$idOrderState) {
            $idOrderState = (int) $this->shopConfig('PS_OS_PAYMENT');
        }

        $creator = new AmazonOrderCreator($idCarrier, $idOrderState, (int) $this->shopConfig('PS_LANG_DEFAULT'), $idShop);
        $summary = $creator->createAllPending();

        return array('success' => true, 'action' => 'create_orders', 'summary' => $summary);
    }
    /**
     * Import returns/cancellations from Amazon.
     */
    private function actionImportReturns()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonReturnManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $manager = new AmazonReturnManager($client, $this->getMarketplaceId());

        $env = AmazonSpApiClient::environment();
        $createdAfter = ($env === 'production')
            ? gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days'))
            : 'TEST_CASE_200';

        $summary = $manager->importReturns($createdAfter);

        return array(
            'success' => ($manager->getLastError() === null),
            'action' => 'import_returns',
            'summary' => $summary,
            'error' => $manager->getLastError(),
        );
    }
    /**
     * Process imported returns (create credit slips, cancel orders).
     */
    private function actionProcessReturns()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonReturnManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $manager = new AmazonReturnManager($client, $this->getMarketplaceId());

        $summary = $manager->processReturns();

        return array(
            'success' => true,
            'action' => 'process_returns',
            'summary' => $summary,
        );
    }
    /**
     * Sync FBA inventory and optionally update PS stock.
     */
    private function actionSyncFba()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonFbaManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $fba = new AmazonFbaManager($client, $this->getMarketplaceId());

        $invSummary = $fba->syncFbaInventory();
        $stockSummary = $fba->syncFbaStockToPs();

        return array(
            'success' => ($fba->getLastError() === null),
            'action' => 'sync_fba',
            'inventory' => $invSummary,
            'stock_sync' => $stockSummary,
            'error' => $fba->getLastError(),
        );
    }
    /**
     * Fetch competitive pricing, apply rules, and push new prices.
     */
    private function actionReprice()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonRepricingEngine.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $sellerId = AmzproShop::get('AMZPRO_SELLER_ID');
        $engine = new AmazonRepricingEngine($client, $this->getMarketplaceId(), $sellerId);

        $fetchSummary = $engine->fetchCompetitivePricing();
        $ruleSummary = $engine->applyPricingRules();
        $pushSummary = $engine->pushSuggestedPrices(50);

        return array(
            'success' => true,
            'action' => 'reprice',
            'fetch' => $fetchSummary,
            'rules' => $ruleSummary,
            'push' => $pushSummary,
        );
    }
    /**
     * Fetch Amazon fees/commissions for recent orders.
     */
    private function actionFetchFees()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonFeesTracker.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $tracker = new AmazonFeesTracker($client);
        $summary = $tracker->fetchOrderFees(50);

        return array(
            'success' => ($tracker->getLastError() === null),
            'action' => 'fetch_fees',
            'summary' => $summary,
            'error' => $tracker->getLastError(),
        );
    }
    /**
     * Poll and download pending Amazon reports.
     */
    private function actionPollReports()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonReportManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $manager = new AmazonReportManager($client, $this->getMarketplaceId());
        $summary = $manager->pollPendingReports();

        return array(
            'success' => true,
            'action' => 'poll_reports',
            'summary' => $summary,
        );
    }
    /**
     * Sync promotions from Amazon orders.
     */
    private function actionSyncPromotions()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonPromotionSync.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $sync = new AmazonPromotionSync($client, $this->getMarketplaceId());

        $importSummary = $sync->importPromotionsFromOrders();
        $exportSummary = $sync->exportPsCartRules();
        $cartRuleSummary = $sync->createPsCartRules();

        return array(
            'success' => true,
            'action' => 'sync_promotions',
            'import' => $importSummary,
            'export' => $exportSummary,
            'cart_rules' => $cartRuleSummary,
        );
    }
    /**
     * Import orders across all active marketplaces.
     */
    private function actionMultiImportOrders()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonMultiMarketplace.php';

        $mm = new AmazonMultiMarketplace();
        $results = $mm->importOrdersAllMarketplaces();

        return array(
            'success' => true,
            'action' => 'multi_import_orders',
            'results' => $results,
        );
    }
    /**
     * Sync products across all active marketplaces.
     */
    private function actionMultiSyncProducts()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonMultiMarketplace.php';

        $mm = new AmazonMultiMarketplace();
        $results = $mm->syncProductsAllMarketplaces();

        return array(
            'success' => true,
            'action' => 'multi_sync_products',
            'results' => $results,
        );
    }
    /**
     * Send Amazon's standard review request for eligible delivered orders.
     * Only runs when the merchant enabled it in the module settings.
     */
    private function actionRequestReviews()
    {
        if (!AmzproShop::get('AMZPRO_AUTO_REVIEW_REQUEST')) {
            return array('success' => true, 'action' => 'request_reviews',
                'summary' => AmazonI18n::get()->l('Skipped: automatic review requests are disabled in module settings.', 'amazontaskrunner'));
        }

        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonReviewRequester.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $requester = new AmazonReviewRequester($client, $this->getMarketplaceId());

        $env = AmazonSpApiClient::environment();
        $requester->setMock(AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production');

        return array(
            'success' => true,
            'action' => 'request_reviews',
            'summary' => $requester->requestAllEligible(25),
        );
    }
    /**
     * Bulk feed cycle: submit pending listing changes as one feed, then
     * poll earlier feeds for results.
     */
    private function actionProcessFeeds()
    {
        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonProductSync.php';
        require_once dirname(__FILE__) . '/AmazonFeedManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $sellerId = AmzproShop::get('AMZPRO_SELLER_ID');
        $env = AmazonSpApiClient::environment();
        $useMock = AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production';

        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), $sellerId);
        $sync->setMock($useMock);
        $sync->syncPrestashopSide();
        $collected = $sync->collectFeedMessages(500);

        $feeds = new AmazonFeedManager($client, $this->getMarketplaceId(), $sellerId);
        $feeds->setMock($useMock);

        $submitted = null;
        if (!empty($collected['messages'])) {
            $submitted = $feeds->submitListingsFeed($collected['messages']);
        }

        return array(
            'success' => true,
            'action' => 'process_feeds',
            'submitted_feed' => $submitted,
            'messages' => count($collected['messages']),
            'skipped' => count($collected['skipped']),
            'poll' => $feeds->pollPendingFeeds(),
        );
    }
    /**
     * Settle Remote Cart reservations: hand over the ones whose order is now
     * payable, return the stock of the ones that expired.
     */
    private function actionRemoteCart()
    {
        require_once dirname(__FILE__) . '/AmazonRemoteCart.php';

        if (!AmazonRemoteCart::isEnabled()) {
            return array('success' => true, 'action' => 'remote_cart', 'summary' => array('disabled' => true));
        }

        $converted = AmazonRemoteCart::convertConfirmed();
        $expired = AmazonRemoteCart::releaseExpired();

        return array(
            'success' => true,
            'action' => 'remote_cart',
            'summary' => array(
                'converted_orders' => $converted,
                'expired_orders' => $expired['orders'],
                'released_items' => $expired['items'],
            ),
        );
    }
    /** Read buyer replies from the configured mailbox into Customer Service. */
    private function actionFetchMessages()
    {
        require_once dirname(__FILE__) . '/AmazonBuyerInbox.php';

        $inbox = new AmazonBuyerInbox();
        $summary = $inbox->fetchNewMessages(50);
        if ($summary === false) {
            return array('success' => false, 'error' => $inbox->getLastError());
        }

        return array('success' => true, 'action' => 'fetch_messages', 'summary' => $summary);
    }
    /**
     * Upload PS invoices to Amazon for VCS-enrolled sellers.
     */
    private function actionUploadInvoices()
    {
        if (!AmzproShop::get('AMZPRO_VCS_ENABLED')) {
            return array('success' => true, 'action' => 'upload_invoices',
                'summary' => AmazonI18n::get()->l('Skipped: VCS invoice upload is disabled in module settings.', 'amazontaskrunner'));
        }

        require_once dirname(__FILE__) . '/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/AmazonVcsInvoiceUploader.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => $this->notConnected());
        }

        $uploader = new AmazonVcsInvoiceUploader(
            $client,
            $this->getMarketplaceId(),
            AmzproShop::get('AMZPRO_SELLER_ID')
        );
        $env = AmazonSpApiClient::environment();
        $uploader->setMock(AmzproShop::get('AMZPRO_USE_MOCK') && $env !== 'production');

        return array(
            'success' => true,
            'action' => 'upload_invoices',
            'summary' => $uploader->uploadPendingInvoices(10),
            'notices' => $uploader->getNotices(),
        );
    }
    /**
     * Delete buyer personal data from orders past the retention window.
     *
     * Amazon's Data Protection Policy makes this a requirement rather than
     * a convenience, so it runs even when the merchant has not scheduled it:
     * see the same call in the order import.
     */
    private function actionPurgePii()
    {
        require_once dirname(__FILE__) . '/AmazonPiiPurger.php';

        if (!AmazonPiiPurger::isEnabledInAnyShop()) {
            return array('success' => true, 'action' => 'purge_pii', 'summary' => array('disabled' => true));
        }

        $purger = new AmazonPiiPurger();
        $summary = $purger->purge();
        if ($summary === false) {
            return array('success' => false, 'error' => $purger->getLastError());
        }

        return array('success' => true, 'action' => 'purge_pii', 'summary' => $summary);
    }

    /**
     * Build the SP-API client from stored configuration.
     *
     * @return AmazonSpApiClient|null
     */
    private function buildClient()
    {
        $clientId = AmzproShop::get('AMZPRO_CLIENT_ID');
        $clientSecret = AmzproShop::get('AMZPRO_CLIENT_SECRET');
        $refreshToken = AmazonSpApiClient::storedRefreshToken();
        $env = AmazonSpApiClient::environment();

        $relayMode = (AmazonSpApiClient::authMode() !== 'manual');

        if (!$refreshToken || (!$relayMode && (!$clientId || !$clientSecret))) {
            return null;
        }

        // Resolve endpoint by marketplace + region
        $endpoint = AmazonSpApiClient::ENDPOINT_NA_SANDBOX;
        if ($env === 'production') {
            $mp = AmzproShop::get('AMZPRO_MARKETPLACE_ID');
            $regionMap = array(
                'ATVPDKIKX0DER' => 'NA', 'A2EUQ1WTGCTBG2' => 'NA', 'A1AM78C64UM0Y8' => 'NA',
                'A2Q3Y263D00KMC' => 'NA',
                'A1F83G8C2ARO7P' => 'EU', 'A1PA6795UKMFR9' => 'EU', 'A13V1IB3VIYZZH' => 'EU',
                'APJ6JRA9NG5V4' => 'EU', 'A1RKKUPIHCS9HS' => 'EU', 'A1805IZSGTT6HS' => 'EU',
                'A1C3SOZRARQ6R3' => 'EU', 'A2NODRKZP88ZB9' => 'EU', 'AMEN7PMS3EDWL' => 'EU',
                'A33AVAJ2PDY3EV' => 'EU', 'A21TJRUUN4KGV' => 'EU', 'A2VIGQ35RCS4UG' => 'EU',
                'A17E79C6D8DWNP' => 'EU', 'A28R8C7NBKEWEA' => 'EU', 'ARBP9OOSHTCHU'  => 'EU',
                'AE08WJ6YKNBMC'  => 'EU', 'A19VAU5U5O7RUS' => 'FE',
                'A39IBJ37TRP1C6' => 'FE', 'A1VC38T7YXB528' => 'FE',
            );
            $region = isset($regionMap[$mp]) ? $regionMap[$mp] : 'EU';
            switch ($region) {
                case 'NA':
                    $endpoint = AmazonSpApiClient::ENDPOINT_NA;
                    break;
                case 'FE':
                    $endpoint = AmazonSpApiClient::ENDPOINT_FE;
                    break;
                default:
                    $endpoint = AmazonSpApiClient::ENDPOINT_EU;
                    break;
            }
        }

        $client = new AmazonSpApiClient($clientId, $clientSecret, $refreshToken, $endpoint);
        if ($relayMode) {
            $client->setTokenRelay(AmazonSpApiClient::relayUrl());
        }

        return $client;
    }

    /** The error a task returns when buildClient() finds no connection. */
    private function notConnected()
    {
        return AmazonI18n::get()->l('This shop is not connected to Amazon yet. Use the "Connect to Amazon" button in the module settings.', 'amazontaskrunner');
    }

    private function getMarketplaceId()
    {
        $env = AmazonSpApiClient::environment();
        if ($env === 'production') {
            $mp = AmzproShop::get('AMZPRO_MARKETPLACE_ID');
            return $mp ? $mp : 'A1PA6795UKMFR9';
        }
        return 'ATVPDKIKX0DER';
    }

    /** The shop this run works for. Never 0. */
    private function shopId()
    {
        return (int) AmzproShop::actingId();
    }

    /** A PrestaShop setting (PS_*) as this run's shop sees it. */
    private function shopConfig($key)
    {
        $idShop = $this->shopId();

        return Configuration::get($key, null, AmzproShop::groupId($idShop), $idShop);
    }

    private function logCronRun($action, $result)
    {
        $success = isset($result['success']) && $result['success'] ? 1 : 0;
        $message = isset($result['error']) ? (string) $result['error'] : 'OK';
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`id_shop`, `level`, `source`, `message`, `date_add`)
             VALUES (
                ' . (int) AmzproShop::id() . ',
                \'' . ($success ? 'info' : 'error') . '\',
                \'cron:' . pSQL($action) . '\',
                \'' . pSQL(Tools::substr($message, 0, 1000)) . '\',
                \'' . pSQL($now) . '\'
             )'
        );
    }
    /**
     * Clear aged-out buyer data. Never fails the caller: a retention
     * problem should be visible in the module log, not turn a successful
     * order import into a failed cron run.
     *
     * @return int Orders cleared
     */
    private function purgeAgedPii()
    {
        require_once dirname(__FILE__) . '/AmazonPiiPurger.php';

        if (!AmazonPiiPurger::isEnabledInAnyShop()) {
            return 0;
        }

        $purger = new AmazonPiiPurger();
        $done = $purger->purge();

        return ($done === false) ? 0 : (int) $done['orders'];
    }

    /**
     * Plain-text summary of a cron run, sent to the configured report
     * address. Silently disabled when no address is set.
     */
    private function sendReportEmail($subject, $stats)
    {
        $to = trim((string) AmzproShop::get('AMZPRO_REPORT_EMAIL'));
        if ($to === '' || !Validate::isEmail($to)) {
            return;
        }

        $idShop = $this->shopId();
        $lines = array();
        foreach ($stats as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        $title = 'Amazon Marketplace Pro — ' . $subject . ' report';
        if (AmzproShop::isMultistore()) {
            // Several shops can send the same report to one address, and
            // their store names (PS_SHOP_NAME) are often the same: use the
            // name the shop has in the shop list.
            $title .= ' — ' . AmzproShop::name($idShop);
        }
        $body = $title . "\n"
            . date('Y-m-d H:i:s') . "\n\n" . implode("\n", $lines);

        try {
            // The shop id (13th argument, PrestaShop 1.6 to 9) gives the
            // e-mail that shop's name, sender, logo and theme templates.
            Mail::send(
                (int) $this->shopConfig('PS_LANG_DEFAULT'),
                'contact', // stock PS template: {message} in a plain wrapper
                'Amazon Marketplace Pro: ' . $subject,
                array('{message}' => $body,
                      '{email}' => (string) $this->shopConfig('PS_SHOP_EMAIL'),
                      '{attached_file}' => ''),
                $to,
                null,
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                false,
                $idShop
            );
        } catch (Exception $e) {
            // Reporting must never fail the cron run itself.
        }
    }
}
