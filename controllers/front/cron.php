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

class AmazonMarketplaceProCronModuleFrontController extends ModuleFrontController
{
    /** @var bool Disable rendering (we output JSON directly) */
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        // Validate token
        $token = Tools::getValue('token');
        $expectedToken = Configuration::get('AMZPRO_CRON_TOKEN');

        if (!$expectedToken || $token !== $expectedToken) {
            $this->jsonResponse(array('success' => false, 'error' => 'Invalid or missing cron token.'), 403);
            return;
        }

        $action = Tools::getValue('action');
        $result = array('success' => false, 'error' => 'Unknown action: ' . $action);

        switch ($action) {
            case 'import_orders':
                $result = $this->actionImportOrders();
                break;
            case 'sync_stock':
                $result = $this->actionSyncStock();
                break;
            case 'sync_products':
                $result = $this->actionSyncProducts();
                break;
            case 'create_orders':
                $result = $this->actionCreateOrders();
                break;
            case 'import_returns':
                $result = $this->actionImportReturns();
                break;
            case 'process_returns':
                $result = $this->actionProcessReturns();
                break;
            case 'sync_fba':
                $result = $this->actionSyncFba();
                break;
            case 'reprice':
                $result = $this->actionReprice();
                break;
            case 'fetch_fees':
                $result = $this->actionFetchFees();
                break;
            case 'poll_reports':
                $result = $this->actionPollReports();
                break;
            case 'sync_promotions':
                $result = $this->actionSyncPromotions();
                break;
            case 'multi_import_orders':
                $result = $this->actionMultiImportOrders();
                break;
            case 'multi_sync_products':
                $result = $this->actionMultiSyncProducts();
                break;
            case 'request_reviews':
                $result = $this->actionRequestReviews();
                break;
            case 'process_feeds':
                $result = $this->actionProcessFeeds();
                break;
            case 'remote_cart':
                $result = $this->actionRemoteCart();
                break;
            case 'fetch_messages':
                $result = $this->actionFetchMessages();
                break;
            case 'upload_invoices':
                $result = $this->actionUploadInvoices();
                break;
            case 'purge_pii':
                $result = $this->actionPurgePii();
                break;
        }

        $this->logCronRun($action, $result);
        $this->jsonResponse($result);
    }

    /**
     * Import new Amazon orders into staging.
     */
    private function actionImportOrders()
    {
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonOrderImporter.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonProductSync.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), $sellerId);

        $env = AmazonSpApiClient::environment();
        $useMock = Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production';
        $sync->setMock($useMock);

        // Expired change-queue entries age out before each sync.
        require_once dirname(__FILE__) . '/../../classes/AmazonListingSettings.php';
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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonProductSync.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), $sellerId);

        $env = AmazonSpApiClient::environment();
        $useMock = Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production';
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
        require_once dirname(__FILE__) . '/../../classes/AmazonOrderCreator.php';

        $idCarrier = (int) Configuration::get('AMZPRO_DEFAULT_CARRIER');
        $idOrderState = (int) Configuration::get('AMZPRO_DEFAULT_ORDER_STATE');

        if (!$idOrderState) {
            $idOrderState = (int) Configuration::get('PS_OS_PAYMENT');
        }

        $creator = new AmazonOrderCreator($idCarrier, $idOrderState);
        $summary = $creator->createAllPending();

        return array('success' => true, 'action' => 'create_orders', 'summary' => $summary);
    }

    /**
     * Import returns/cancellations from Amazon.
     */
    private function actionImportReturns()
    {
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonReturnManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        $manager = new AmazonReturnManager($client, $this->getMarketplaceId(), $sellerId);

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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonReturnManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        $manager = new AmazonReturnManager($client, $this->getMarketplaceId(), $sellerId);

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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonFbaManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        $fba = new AmazonFbaManager($client, $this->getMarketplaceId(), $sellerId);

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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonRepricingEngine.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonFeesTracker.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $tracker = new AmazonFeesTracker($client, $this->getMarketplaceId());
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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonReportManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonPromotionSync.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonMultiMarketplace.php';

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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonMultiMarketplace.php';

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
        if (!Configuration::get('AMZPRO_AUTO_REVIEW_REQUEST')) {
            return array('success' => true, 'action' => 'request_reviews',
                'summary' => 'Skipped: automatic review requests are disabled in module settings.');
        }

        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonReviewRequester.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $requester = new AmazonReviewRequester($client, $this->getMarketplaceId());

        $env = AmazonSpApiClient::environment();
        $requester->setMock(Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production');

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
        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonProductSync.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonFeedManager.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        $env = AmazonSpApiClient::environment();
        $useMock = Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production';

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
     * Upload PS invoices to Amazon for VCS-enrolled sellers.
     */
    private function actionUploadInvoices()
    {
        if (!Configuration::get('AMZPRO_VCS_ENABLED')) {
            return array('success' => true, 'action' => 'upload_invoices',
                'summary' => 'Skipped: VCS invoice upload is disabled in module settings.');
        }

        require_once dirname(__FILE__) . '/../../classes/AmazonSpApiClient.php';
        require_once dirname(__FILE__) . '/../../classes/AmazonVcsInvoiceUploader.php';

        $client = $this->buildClient();
        if (!$client) {
            return array('success' => false, 'error' => 'Amazon API not configured.');
        }

        $uploader = new AmazonVcsInvoiceUploader(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );
        $env = AmazonSpApiClient::environment();
        $uploader->setMock(Configuration::get('AMZPRO_USE_MOCK') && $env !== 'production');

        return array(
            'success' => true,
            'action' => 'upload_invoices',
            'summary' => $uploader->uploadPendingInvoices(10),
            'notices' => $uploader->getNotices(),
        );
    }


    /**
     * Build the SP-API client from stored configuration.
     *
     * @return AmazonSpApiClient|null
     */
    private function buildClient()
    {
        $clientId = Configuration::get('AMZPRO_CLIENT_ID');
        $clientSecret = Configuration::get('AMZPRO_CLIENT_SECRET');
        $refreshToken = AmazonSpApiClient::storedRefreshToken();
        $env = AmazonSpApiClient::environment();

        $relayMode = (Configuration::get('AMZPRO_AUTH_MODE') !== 'manual');

        if (!$refreshToken || (!$relayMode && (!$clientId || !$clientSecret))) {
            return null;
        }

        // Resolve endpoint by marketplace + region
        $endpoint = AmazonSpApiClient::ENDPOINT_NA_SANDBOX;
        if ($env === 'production') {
            $mp = Configuration::get('AMZPRO_MARKETPLACE_ID');
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

    private function getMarketplaceId()
    {
        $env = AmazonSpApiClient::environment();
        if ($env === 'production') {
            $mp = Configuration::get('AMZPRO_MARKETPLACE_ID');
            return $mp ? $mp : 'A1PA6795UKMFR9';
        }
        return 'ATVPDKIKX0DER';
    }

    private function logCronRun($action, $result)
    {
        $success = isset($result['success']) && $result['success'] ? 1 : 0;
        $message = isset($result['error']) ? (string) $result['error'] : 'OK';
        $now = date('Y-m-d H:i:s');

        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`level`, `source`, `message`, `date_add`)
             VALUES (
                \'' . ($success ? 'info' : 'error') . '\',
                \'cron:' . pSQL($action) . '\',
                \'' . pSQL(Tools::substr($message, 0, 1000)) . '\',
                \'' . pSQL($now) . '\'
             )'
        );
    }

    /**
     * Settle Remote Cart reservations: hand over the ones whose order is now
     * payable, return the stock of the ones that expired.
     */
    private function actionRemoteCart()
    {
        require_once dirname(__FILE__) . '/../../classes/AmazonRemoteCart.php';

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

    /**
     * Delete buyer personal data from orders past the retention window.
     *
     * Amazon's Data Protection Policy makes this a requirement rather than
     * a convenience, so it runs even when the merchant has not scheduled it:
     * see the same call in the order import.
     */
    private function actionPurgePii()
    {
        require_once dirname(__FILE__) . '/../../classes/AmazonPiiPurger.php';

        if (!AmazonPiiPurger::isEnabled()) {
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
     * Clear aged-out buyer data. Never fails the caller: a retention
     * problem should be visible in the module log, not turn a successful
     * order import into a failed cron run.
     *
     * @return int Orders cleared
     */
    private function purgeAgedPii()
    {
        require_once dirname(__FILE__) . '/../../classes/AmazonPiiPurger.php';

        if (!AmazonPiiPurger::isEnabled()) {
            return 0;
        }

        $purger = new AmazonPiiPurger();
        $done = $purger->purge();

        return ($done === false) ? 0 : (int) $done['orders'];
    }

    /** Read buyer replies from the configured mailbox into Customer Service. */
    private function actionFetchMessages()
    {
        require_once dirname(__FILE__) . '/../../classes/AmazonBuyerInbox.php';

        $inbox = new AmazonBuyerInbox();
        $summary = $inbox->fetchNewMessages(50);
        if ($summary === false) {
            return array('success' => false, 'error' => $inbox->getLastError());
        }

        return array('success' => true, 'action' => 'fetch_messages', 'summary' => $summary);
    }

    private function jsonResponse($data, $httpCode = 200)
    {
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Plain-text summary of a cron run, sent to the configured report
     * address. Silently disabled when no address is set.
     */
    private function sendReportEmail($subject, $stats)
    {
        $to = trim((string) Configuration::get('AMZPRO_REPORT_EMAIL'));
        if ($to === '' || !Validate::isEmail($to)) {
            return;
        }

        $lines = array();
        foreach ($stats as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        $body = 'Amazon Marketplace Pro — ' . $subject . ' report' . "\n"
            . date('Y-m-d H:i:s') . "\n\n" . implode("\n", $lines);

        try {
            Mail::send(
                (int) Configuration::get('PS_LANG_DEFAULT'),
                'contact', // stock PS template: {message} in a plain wrapper
                'Amazon Marketplace Pro: ' . $subject,
                array('{message}' => $body,
                      '{email}' => (string) Configuration::get('PS_SHOP_EMAIL'),
                      '{attached_file}' => ''),
                $to
            );
        } catch (Exception $e) {
            // Reporting must never fail the cron run itself.
        }
    }
}
