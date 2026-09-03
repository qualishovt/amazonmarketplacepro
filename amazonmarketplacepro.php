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

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonMarketplacePro extends Module
{
    /** Amazon marketplace directory (id => label). */
    private static $marketplaces = array(
        'ATVPDKIKX0DER'  => 'Amazon.com (US)',
        'A2EUQ1WTGCTBG2' => 'Amazon.ca (Canada)',
        'A1AM78C64UM0Y8'  => 'Amazon.com.mx (Mexico)',
        'A2Q3Y263D00KMC'  => 'Amazon.com.br (Brazil)',
        'A1F83G8C2ARO7P'  => 'Amazon.co.uk (UK)',
        'A1PA6795UKMFR9'  => 'Amazon.de (Germany)',
        'A13V1IB3VIYZZH'  => 'Amazon.fr (France)',
        'APJ6JRA9NG5V4'   => 'Amazon.it (Italy)',
        'A1RKKUPIHCS9HS'  => 'Amazon.es (Spain)',
        'A1805IZSGTT6HS'  => 'Amazon.nl (Netherlands)',
        'A1C3SOZRARQ6R3'  => 'Amazon.pl (Poland)',
        'A2NODRKZP88ZB9'  => 'Amazon.se (Sweden)',
        'AMEN7PMS3EDWL'   => 'Amazon.com.be (Belgium)',
        'A28R8C7NBKEWEA'  => 'Amazon.ie (Ireland)',
        'ARBP9OOSHTCHU'   => 'Amazon.eg (Egypt)',
        'AE08WJ6YKNBMC'   => 'Amazon.co.za (South Africa)',
        'A33AVAJ2PDY3EV'  => 'Amazon.com.tr (Turkey)',
        'A21TJRUUN4KGV'   => 'Amazon.in (India)',
        'A2VIGQ35RCS4UG'  => 'Amazon.ae (UAE)',
        'A17E79C6D8DWNP'  => 'Amazon.sa (Saudi Arabia)',
        'A19VAU5U5O7RUS'  => 'Amazon.sg (Singapore)',
        'A39IBJ37TRP1C6'  => 'Amazon.com.au (Australia)',
        'A1VC38T7YXB528'  => 'Amazon.co.jp (Japan)',
    );

    /** Seller Central domain per marketplace (for the OAuth consent page). */
    private static $sellerCentralDomains = array(
        'ATVPDKIKX0DER'  => 'sellercentral.amazon.com',
        'A2EUQ1WTGCTBG2' => 'sellercentral.amazon.ca',
        'A1AM78C64UM0Y8' => 'sellercentral.amazon.com.mx',
        'A2Q3Y263D00KMC' => 'sellercentral.amazon.com.br',
        'A1F83G8C2ARO7P' => 'sellercentral-europe.amazon.com',
        'A1PA6795UKMFR9' => 'sellercentral-europe.amazon.com',
        'A13V1IB3VIYZZH' => 'sellercentral-europe.amazon.com',
        'APJ6JRA9NG5V4'  => 'sellercentral-europe.amazon.com',
        'A1RKKUPIHCS9HS' => 'sellercentral-europe.amazon.com',
        'A1805IZSGTT6HS' => 'sellercentral.amazon.nl',
        'A1C3SOZRARQ6R3' => 'sellercentral.amazon.pl',
        'A2NODRKZP88ZB9' => 'sellercentral.amazon.se',
        'AMEN7PMS3EDWL'  => 'sellercentral.amazon.com.be',
        'A28R8C7NBKEWEA' => 'sellercentral-europe.amazon.com',
        'ARBP9OOSHTCHU'  => 'sellercentral.amazon.eg',
        'AE08WJ6YKNBMC'  => 'sellercentral.amazon.co.za',
        'A33AVAJ2PDY3EV' => 'sellercentral.amazon.com.tr',
        'A21TJRUUN4KGV'  => 'sellercentral.amazon.in',
        'A2VIGQ35RCS4UG' => 'sellercentral.amazon.ae',
        'A17E79C6D8DWNP' => 'sellercentral.amazon.sa',
        'A19VAU5U5O7RUS' => 'sellercentral.amazon.sg',
        'A39IBJ37TRP1C6' => 'sellercentral.amazon.com.au',
        'A1VC38T7YXB528' => 'sellercentral.amazon.co.jp',
    );

    /** Map marketplace to SP-API regional endpoint. */
    private static $marketplaceEndpoints = array(
        'ATVPDKIKX0DER'  => 'NA', 'A2EUQ1WTGCTBG2' => 'NA', 'A1AM78C64UM0Y8' => 'NA',
        'A2Q3Y263D00KMC' => 'NA',
        'A1F83G8C2ARO7P' => 'EU', 'A1PA6795UKMFR9' => 'EU', 'A13V1IB3VIYZZH' => 'EU',
        'APJ6JRA9NG5V4'  => 'EU', 'A1RKKUPIHCS9HS' => 'EU', 'A1805IZSGTT6HS' => 'EU',
        'A1C3SOZRARQ6R3' => 'EU', 'A2NODRKZP88ZB9' => 'EU', 'AMEN7PMS3EDWL'  => 'EU',
        'A33AVAJ2PDY3EV' => 'EU', 'A21TJRUUN4KGV'  => 'EU', 'A2VIGQ35RCS4UG' => 'EU',
        'A17E79C6D8DWNP' => 'EU', 'A28R8C7NBKEWEA' => 'EU', 'ARBP9OOSHTCHU'  => 'EU',
        'AE08WJ6YKNBMC'  => 'EU', 'A19VAU5U5O7RUS' => 'FE',
        'A39IBJ37TRP1C6' => 'FE', 'A1VC38T7YXB528' => 'FE',
    );

    /** @var string feedback from the schedule screen, shown on the next render */
    protected $scheduleNotice = '';

    public function __construct()
    {
        $this->name = 'amazonmarketplacepro';
        $this->tab = 'market_place';
        $this->version = '1.5.0';
        $this->author = 'IntelliPresta';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Amazon Marketplace Pro');
        $this->description = $this->l('Full Amazon integration: sync products, import orders, update stock & prices, push shipments, handle returns — all automated.');
        $this->confirmUninstall = $this->l('Are you sure? All Amazon sync data will be removed.');
        // The upper bound is the running version, so the module never refuses
        // to install on a PrestaShop newer than the one this was written
        // against. A literal ceiling gets this wrong in a way that is easy to
        // miss: since PrestaShop 8, Module::__construct only pads a max whose
        // first number is below 8, so 9.0 stays 9.0, and checkCompliancy then
        // asks version_compare('9.0.0', '9.0', '>') - which is true. The
        // module was blocked from installing on the very version it named.
        // Padding would not save us either, because this property is assigned
        // after parent::__construct() and so is compared exactly as written.
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /* ───────────────────── Install / Uninstall ───────────────────── */

    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');

        // Generate cron token
        $cronToken = Tools::substr(md5(uniqid((string) rand(), true)), 0, 24);

        // Set default configuration values
        Configuration::updateValue('AMZPRO_CLIENT_ID', '');
        Configuration::updateValue('AMZPRO_CLIENT_SECRET', '');
        Configuration::updateValue('AMZPRO_REFRESH_TOKEN', '');
        Configuration::updateValue('AMZPRO_REFRESH_TOKEN_SANDBOX', '');
        Configuration::updateValue('AMZPRO_SELLER_ID', '');
        Configuration::updateValue('AMZPRO_MARKETPLACE_ID', 'A1PA6795UKMFR9');
        // Customer installs run live; sandbox/mock are developer-mode tools.
        Configuration::updateGlobalValue('AMZPRO_ENVIRONMENT', 'production');
        Configuration::updateValue('AMZPRO_USE_MOCK', '0');
        Configuration::updateValue('AMZPRO_DEFAULT_CARRIER', (string) Configuration::get('PS_CARRIER_DEFAULT'));
        Configuration::updateValue('AMZPRO_DEFAULT_ORDER_STATE', (string) Configuration::get('PS_OS_PAYMENT'));
        Configuration::updateValue('AMZPRO_CRON_TOKEN', $cronToken);
        Configuration::updateValue('AMZPRO_SYNC_STOCK_HOOK', '0');
        Configuration::updateValue('AMZPRO_SYNC_ORDER_STATUS_HOOK', '0');
        Configuration::updateValue('AMZPRO_SYNC_CANCEL_HOOK', '0');

        Configuration::updateValue('AMZPRO_AUTO_REVIEW_REQUEST', '0');
        Configuration::updateValue('AMZPRO_STOCK_BUFFER', '0');
        Configuration::updateValue('AMZPRO_DELETE_WHEN_OOS', '0');
        Configuration::updateValue('AMZPRO_CONDITION_TYPE', 'new_new');
        Configuration::updateValue('AMZPRO_TAX_MODE', 'amazon');
        Configuration::updateValue('AMZPRO_LISTING_LANG', '');
        Configuration::updateValue('AMZPRO_CARRIER_MAP', '');
        Configuration::updateValue('AMZPRO_SHIPPING_TEMPLATE', '');
        Configuration::updateValue('AMZPRO_B2B_DISCOUNT', '0');
        Configuration::updateValue('AMZPRO_VCS_ENABLED', '0');
        // Developer-only controls (environment/mock/beta selectors) stay
        // hidden for customers; IntelliPresta enables this flag on dev shops.
        Configuration::updateValue('AMZPRO_DEV_MODE', '0');

        // SKU & export filters
        Configuration::updateValue('AMZPRO_SKU_PREFIX', '');
        Configuration::updateValue('AMZPRO_SKU_SOURCE', 'reference');
        Configuration::updateValue('AMZPRO_PRICE_MIN', '0');
        Configuration::updateValue('AMZPRO_PRICE_MAX', '0');
        Configuration::updateValue('AMZPRO_QTY_MIN', '0');
        Configuration::updateValue('AMZPRO_USE_SPECIFIC_PRICES', '0');
        Configuration::updateValue('AMZPRO_SPECIFIC_PRICE_GROUP', '0');
        Configuration::updateValue('AMZPRO_REPORT_EMAIL', '');
        // Markup / handling delay cascades
        Configuration::updateValue('AMZPRO_DEFAULT_MARKUP', '');
        Configuration::updateValue('AMZPRO_MARKUP_SOURCES', 'category,manufacturer,supplier');
        Configuration::updateValue('AMZPRO_DEFAULT_DELAY', '0');
        Configuration::updateValue('AMZPRO_DELAY_SOURCES', 'category,manufacturer,supplier');
        Configuration::updateValue('AMZPRO_GPSR_PRIORITY', 'manufacturer');
        // Sync modes & options
        Configuration::updateValue('AMZPRO_SYNC_MODE', 'normal');
        Configuration::updateValue('AMZPRO_FORCE_ZERO_QTY', '0');
        Configuration::updateValue('AMZPRO_ONLY_WITH_ASIN', '0');
        Configuration::updateValue('AMZPRO_EXPORT_LIMIT', '0');
        Configuration::updateValue('AMZPRO_EAN_AS', 'EAN');
        Configuration::updateValue('AMZPRO_DELTA_HOURS', '0');
        Configuration::updateValue('AMZPRO_COND_NOTE_USED', '');
        Configuration::updateValue('AMZPRO_COND_NOTE_REFURB', '');
        Configuration::updateValue('AMZPRO_QUEUE_TTL_DAYS', '7');
        Configuration::updateValue('AMZPRO_TITLE_FORMAT', 'name');
        Configuration::updateValue('AMZPRO_ORDER_MATCH', 'reference');
        Configuration::updateValue('AMZPRO_ROUNDING', 'cents');
        Configuration::updateValue('AMZPRO_SEND_IMAGES', '1');
        Configuration::updateValue('AMZPRO_EXTENDED_DATA', '1');
        Configuration::updateValue('AMZPRO_FULL_CATALOG', '0');
        Configuration::updateValue('AMZPRO_BUSINESS_GROUP', '0');
        Configuration::updateValue('AMZPRO_FBA_CHANNEL_CODE', '');
        Configuration::updateValue('AMZPRO_IMAP_ENABLED', '0');
        Configuration::updateValue('AMZPRO_IMAP_HOST', '');
        Configuration::updateValue('AMZPRO_IMAP_PORT', '993');
        Configuration::updateValue('AMZPRO_IMAP_USER', '');
        Configuration::updateValue('AMZPRO_IMAP_PASSWORD', '');
        Configuration::updateValue('AMZPRO_IMAP_FOLDER', 'INBOX');
        Configuration::updateValue('AMZPRO_IMAP_SSL', '1');
        Configuration::updateValue('AMZPRO_CARRIER_MAP_IN', '');
        Configuration::updateValue('AMZPRO_REMOTE_CART', '0');
        Configuration::updateValue('AMZPRO_REMOTE_CART_TTL', '4');
        Configuration::updateValue('AMZPRO_STATUS_RULES', '');
        Configuration::updateValue('AMZPRO_INVOICE_EMAIL', '0');
        Configuration::updateValue('AMZPRO_INVOICE_EMAIL_STATE', '0');
        Configuration::updateValue('AMZPRO_INVOICE_ATTACHMENT', '');
        Configuration::updateValue('AMZPRO_SEND_SALE_PRICE', '0');
        Configuration::updateValue('AMZPRO_SEND_LIST_PRICE', '0');
        Configuration::updateValue('AMZPRO_PREORDER', '0');
        Configuration::updateValue('AMZPRO_CONDITION_MAP', json_encode(array(
            'new' => 'new_new',
            'used' => 'used_good',
            'refurbished' => 'refurbished_refurbished',
        )));
        // Shipping template ranges
        Configuration::updateValue('AMZPRO_SHIP_TPL_ENABLED', '0');
        Configuration::updateValue('AMZPRO_SHIP_TPL_BASIS', 'price');
        // Extended order import
        Configuration::updateValue('AMZPRO_ORDER_LOOKBACK_VALUE', '7');
        Configuration::updateValue('AMZPRO_ORDER_LOOKBACK_UNIT', 'days');
        Configuration::updateValue('AMZPRO_IMPORT_FBA_ORDERS', '1');
        Configuration::updateValue('AMZPRO_FBA_ORDER_STATE', '0');
        Configuration::updateValue('AMZPRO_ORDER_STATE_SHIPPED', '0');
        Configuration::updateValue('AMZPRO_PRIORITIZE_ASIN', '0');
        Configuration::updateValue('AMZPRO_FAKE_EMAIL', '0');
        Configuration::updateValue('AMZPRO_CUSTOMER_GROUP', '0');
        Configuration::updateValue('AMZPRO_SKIP_NO_STOCK', '0');

        // Buyer data retention. On by default at Amazon's own limit: the
        // compliant setting is the one a merchant should have to opt out of,
        // not the one they have to discover. The PrestaShop-side pass stays
        // off, because it rewrites addresses that invoices are rendered from.
        Configuration::updateValue('AMZPRO_PII_PURGE', '1');
        Configuration::updateValue('AMZPRO_PII_RETENTION_DAYS', '30');
        Configuration::updateValue('AMZPRO_PII_PURGE_PS', '0');

        // "Connect with Amazon" (IntelliPresta relay) settings
        Configuration::updateValue('AMZPRO_AUTH_MODE', 'connect');
        // Left empty on purpose: the app ids are baked into AmazonSpApiClient
        // and chosen per environment. Storing one here would pin both
        // environments to the same app.
        Configuration::updateValue('AMZPRO_LWA_APP_ID', '');
        Configuration::updateValue('AMZPRO_LWA_APP_ID_SANDBOX', '');
        Configuration::updateValue('AMZPRO_RELAY_URL', 'https://intellipresta.com/spapi');
        // Deliberately not seeded: AmazonSpApiClient::PRODUCTION_APP_PUBLISHED
        // decides this, and a stored value would only shadow it.
        Configuration::updateValue('AMZPRO_OAUTH_NONCE', '');
        Configuration::updateValue('AMZPRO_OAUTH_RETURN_URL', '');
        Configuration::updateValue('AMZPRO_SELLING_PARTNER_ID', '');

        $installed = parent::install()
            && $this->registerHook('actionProductSave')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionUpdateQuantity')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('displayHeader');

        if ($installed) {
            // The schedule exists from the first minute, so Automation is
            // never an empty screen. Every task is seeded switched off.
            require_once dirname(__FILE__) . '/classes/AmazonScheduler.php';
            AmazonScheduler::seedDefaults();

            // Automatic by default. A new merchant should not have to
            // arrange anything on the server to get automation: traffic
            // drives the schedule until they choose otherwise. Existing
            // installs are left alone by the upgrade, since they may
            // already have crontab entries.
            Configuration::updateValue('AMZPRO_CRON_AUTO', 1);
        }

        return $installed;
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        $keys = array(
            'AMZPRO_CLIENT_ID', 'AMZPRO_CLIENT_SECRET', 'AMZPRO_REFRESH_TOKEN',
            'AMZPRO_REFRESH_TOKEN_SANDBOX',
            'AMZPRO_SELLER_ID', 'AMZPRO_MARKETPLACE_ID', 'AMZPRO_ENVIRONMENT',
            'AMZPRO_USE_MOCK', 'AMZPRO_DEFAULT_CARRIER', 'AMZPRO_DEFAULT_ORDER_STATE',
            'AMZPRO_CRON_TOKEN', 'AMZPRO_SYNC_STOCK_HOOK', 'AMZPRO_SYNC_ORDER_STATUS_HOOK',
            'AMZPRO_SYNC_CANCEL_HOOK',
            'AMZPRO_AUTH_MODE', 'AMZPRO_LWA_APP_ID', 'AMZPRO_LWA_APP_ID_SANDBOX', 'AMZPRO_RELAY_URL',
            'AMZPRO_OAUTH_BETA', 'AMZPRO_OAUTH_NONCE', 'AMZPRO_OAUTH_RETURN_URL',
            'AMZPRO_SELLING_PARTNER_ID', 'AMZPRO_AUTO_REVIEW_REQUEST',
            'AMZPRO_STOCK_BUFFER', 'AMZPRO_DELETE_WHEN_OOS', 'AMZPRO_CONDITION_TYPE',
            'AMZPRO_TAX_MODE', 'AMZPRO_LISTING_LANG',
            'AMZPRO_CARRIER_MAP', 'AMZPRO_SHIPPING_TEMPLATE', 'AMZPRO_B2B_DISCOUNT',
            'AMZPRO_VCS_ENABLED', 'AMZPRO_DEV_MODE',
            'AMZPRO_SKU_PREFIX', 'AMZPRO_SKU_SOURCE', 'AMZPRO_PRICE_MIN', 'AMZPRO_PRICE_MAX',
            'AMZPRO_QTY_MIN', 'AMZPRO_USE_SPECIFIC_PRICES', 'AMZPRO_SPECIFIC_PRICE_GROUP',
            'AMZPRO_REPORT_EMAIL', 'AMZPRO_DEFAULT_MARKUP', 'AMZPRO_MARKUP_SOURCES',
            'AMZPRO_DEFAULT_DELAY', 'AMZPRO_DELAY_SOURCES', 'AMZPRO_GPSR_PRIORITY',
            'AMZPRO_SYNC_MODE', 'AMZPRO_FORCE_ZERO_QTY', 'AMZPRO_ONLY_WITH_ASIN',
            'AMZPRO_EXPORT_LIMIT', 'AMZPRO_EAN_AS', 'AMZPRO_DELTA_HOURS',
            'AMZPRO_COND_NOTE_USED', 'AMZPRO_COND_NOTE_REFURB', 'AMZPRO_QUEUE_TTL_DAYS',
            'AMZPRO_SHIP_TPL_ENABLED', 'AMZPRO_SHIP_TPL_BASIS',
            'AMZPRO_ORDER_LOOKBACK_VALUE', 'AMZPRO_ORDER_LOOKBACK_UNIT',
            'AMZPRO_IMPORT_FBA_ORDERS', 'AMZPRO_FBA_ORDER_STATE', 'AMZPRO_ORDER_STATE_SHIPPED',
            'AMZPRO_PRIORITIZE_ASIN', 'AMZPRO_FAKE_EMAIL', 'AMZPRO_CUSTOMER_GROUP',
            'AMZPRO_SKIP_NO_STOCK', 'AMZPRO_TITLE_FORMAT', 'AMZPRO_ORDER_MATCH',
            'AMZPRO_PII_PURGE', 'AMZPRO_PII_RETENTION_DAYS', 'AMZPRO_PII_PURGE_PS',
            'AMZPRO_ROUNDING', 'AMZPRO_SEND_SALE_PRICE', 'AMZPRO_SEND_LIST_PRICE',
            'AMZPRO_PREORDER', 'AMZPRO_CONDITION_MAP',
            'AMZPRO_CARRIER_MAP_IN', 'AMZPRO_STATUS_RULES', 'AMZPRO_INVOICE_EMAIL',
            'AMZPRO_INVOICE_EMAIL_STATE', 'AMZPRO_INVOICE_ATTACHMENT',
            'AMZPRO_REMOTE_CART', 'AMZPRO_REMOTE_CART_TTL',
            'AMZPRO_SEND_IMAGES', 'AMZPRO_EXTENDED_DATA', 'AMZPRO_FULL_CATALOG',
            'AMZPRO_BUSINESS_GROUP', 'AMZPRO_FBA_CHANNEL_CODE',
            'AMZPRO_IMAP_ENABLED', 'AMZPRO_IMAP_HOST', 'AMZPRO_IMAP_PORT',
            'AMZPRO_IMAP_USER', 'AMZPRO_IMAP_PASSWORD', 'AMZPRO_IMAP_FOLDER', 'AMZPRO_IMAP_SSL',
        );
        foreach ($keys as $k) {
            Configuration::deleteByName($k);
        }

        return parent::uninstall();
    }

    /* ─────────────────── Configuration page (getContent) ─────────────────── */

    public function getContent()
    {
        require_once dirname(__FILE__) . '/classes/AmazonSpApiClient.php';

        // ----------- Schedule -----------
        if (Tools::isSubmit('mkproScheduleSave')
            || Tools::isSubmit('mkproScheduleToggle')
            || Tools::isSubmit('mkproScheduleDelete')
            || Tools::isSubmit('mkproScheduleRunNow')
            || Tools::isSubmit('mkproScheduleAuto')
        ) {
            require_once dirname(__FILE__) . '/classes/AmazonScheduler.php';
        }

        if (Tools::isSubmit('mkproScheduleSave')) {
            $saved = AmazonScheduler::save(array(
                'id_task' => (int) Tools::getValue('id_task'),
                'task_key' => Tools::getValue('task_key'),
                'interval_minutes' => (int) Tools::getValue('interval_minutes'),
                'active' => (int) Tools::getValue('active'),
            ));
            $this->scheduleNotice = $saved
                ? $this->l('Task saved.')
                : $this->l('That task could not be saved: unknown task type.');
        }

        if (Tools::isSubmit('mkproScheduleToggle')) {
            $state = AmazonScheduler::toggle((int) Tools::getValue('id_task'));
            $this->scheduleNotice = ($state === 1)
                ? $this->l('Task switched on.')
                : $this->l('Task switched off.');
        }

        if (Tools::isSubmit('mkproScheduleDelete')) {
            AmazonScheduler::delete((int) Tools::getValue('id_task'));
            $this->scheduleNotice = $this->l('Task removed.');
        }

        if (Tools::isSubmit('mkproScheduleRunNow')) {
            $result = AmazonScheduler::runNow((int) Tools::getValue('id_task'));
            $this->scheduleNotice = !empty($result['success'])
                ? $this->l('Task ran.')
                : $this->l('Task failed: ') . (isset($result['error']) ? $result['error'] : '');
        }

        if (Tools::isSubmit('mkproScheduleAuto')) {
            Configuration::updateValue('AMZPRO_CRON_AUTO', (int) Tools::getValue('auto') ? 1 : 0);
            $this->scheduleNotice = $this->l('Automation mode updated.');
        }


        // ── "Connect with Amazon" OAuth flow ──
        if (Tools::isSubmit('mkproConnectAmazon')) {
            $this->saveSettings(); // persist marketplace choice etc. before leaving
            $this->redirectToAmazonConsent();
            // (redirectToAmazonConsent exits; if it returns, config is incomplete)
        }
        if (Tools::isSubmit('mkproDisconnectAmazon')) {
            // Only the active environment's token — the other stays connected.
            // Global scope, matching where the oauth controller stores them.
            Configuration::updateGlobalValue(AmazonSpApiClient::refreshTokenKey(), '');
            Configuration::updateGlobalValue('AMZPRO_SELLING_PARTNER_ID', '');
            Configuration::updateGlobalValue('AMZPRO_OAUTH_NONCE', '');
        }

        // ── File downloads (CSV / feed payload): plain output, not JSON ──
        if (Tools::isSubmit('mkproExportReferences')) {
            require_once dirname(__FILE__) . '/classes/AmazonReferenceTool.php';
            $csv = AmazonReferenceTool::exportCsv();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="amazon-references-' . date('Ymd-His') . '.csv"');
            header('Content-Length: ' . strlen($csv));
            echo $csv;
            exit;
        }
        if (Tools::isSubmit('mkproDownloadFeed')) {
            require_once dirname(__FILE__) . '/classes/AmazonFeedManager.php';
            $feedId = trim((string) Tools::getValue('feed_id'));
            $feeds = new AmazonFeedManager($this->buildAmazonClient(), $this->getMarketplaceId(),
                Configuration::get('AMZPRO_SELLER_ID'));
            $payload = $feeds->getFeedPayload($feedId);
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="feed-' . preg_replace('/[^A-Za-z0-9_.-]/', '', $feedId) . '.json"');
            echo ($payload !== '') ? $payload : '{"error":"This feed was submitted before payload retention was added."}';
            exit;
        }

        // ── AJAX handlers (exit early with JSON) ──
        $ajaxActions = array(
            'ajaxTestAmazon'         => 'runAmazonConnectionTest',
            'ajaxImportAmazonOrders'  => 'runAmazonOrderImport',
            'ajaxCreatePsOrders'     => 'runCreatePsOrders',
            'ajaxSyncProductsPs'     => 'runProductSync',
            'ajaxSyncProductsAmazon' => 'runProductSync',
            'ajaxListAmazonProducts' => 'runListAmazonProducts',
            'ajaxPushProducts'       => 'runPushProducts',
            'ajaxImportReturns'      => 'runImportReturns',
            'ajaxProcessReturns'     => 'runProcessReturns',
            'ajaxSaveCategoryMap'    => 'runSaveCategoryMap',
            'ajaxDeleteCategoryMap'  => 'runDeleteCategoryMap',
            'ajaxSyncFbaInventory'   => 'runSyncFbaInventory',
            'ajaxCreateMcfOrder'     => 'runCreateMcfOrder',
            'ajaxSyncFbaStockToPs'   => 'runSyncFbaStockToPs',
            'ajaxFetchPricing'       => 'runFetchPricing',
            'ajaxApplyPricingRules'  => 'runApplyPricingRules',
            'ajaxPushPrices'         => 'runPushPrices',
            'ajaxSavePricingRule'    => 'runSavePricingRule',
            'ajaxDeletePricingRule'  => 'runDeletePricingRule',
            'ajaxFetchFees'          => 'runFetchFees',
            'ajaxRequestReport'      => 'runRequestReport',
            'ajaxPollReports'        => 'runPollReports',
            'ajaxSaveMarketplace'    => 'runSaveMarketplace',
            'ajaxDeleteMarketplace'  => 'runDeleteMarketplace',
            'ajaxImportPromotions'   => 'runImportPromotions',
            'ajaxExportPromotions'   => 'runExportPromotions',
            'ajaxCreatePromoCartRules' => 'runCreatePromoCartRules',
            'ajaxGetMessagingActions' => 'runGetMessagingActions',
            'ajaxSendBuyerMessage'   => 'runSendBuyerMessage',
            'ajaxRequestReview'      => 'runRequestReview',
            'ajaxMatchCatalog'       => 'runMatchCatalog',
            'ajaxImportCatalog'      => 'runImportCatalog',
            'ajaxSubmitFeed'         => 'runSubmitFeed',
            'ajaxPollFeeds'          => 'runPollFeeds',
            'ajaxSaveEntitySettings' => 'runSaveEntitySettings',
            'ajaxSaveProductRules'   => 'runSaveProductRules',
            'ajaxQueueAction'        => 'runQueueAction',
            'ajaxRefreshOrphans'     => 'runRefreshOrphans',
            'ajaxPendingOrderAction' => 'runPendingOrderAction',
            'ajaxSaveShippingTemplate'   => 'runSaveShippingTemplate',
            'ajaxDeleteShippingTemplate' => 'runDeleteShippingTemplate',
            'ajaxSearchProductTypes'  => 'runSearchProductTypes',
            'ajaxLoadProductTypeSchema' => 'runLoadProductTypeSchema',
            'ajaxSaveProfile'         => 'runSaveProfile',
            'ajaxGetProfile'          => 'runGetProfile',
            'ajaxDeleteProfile'       => 'runDeleteProfile',
            'ajaxUpdateFromAmazon'    => 'runUpdateFromAmazon',
            'ajaxFetchBuyerMessages'  => 'runFetchBuyerMessages',
            'ajaxAuditCatalogue'      => 'runAuditCatalogue',
            'ajaxImportReferences'    => 'runImportReferences',
            'ajaxListDeletions'       => 'runListDeletions',
            'ajaxDeleteListings'      => 'runDeleteListings',
        );
        foreach ($ajaxActions as $submit => $method) {
            if (Tools::isSubmit($submit)) {
                header('Content-Type: application/json');
                if ($submit === 'ajaxSyncProductsPs') {
                    echo json_encode($this->runProductSync('ps'));
                } elseif ($submit === 'ajaxSyncProductsAmazon') {
                    echo json_encode($this->runProductSync('amazon'));
                } else {
                    echo json_encode($this->$method());
                }
                exit;
            }
        }

        // ── Save settings ──
        $confirmMsg = '';
        if (Tools::isSubmit('submitMkproSettings')) {
            $this->saveSettings();
            $confirmMsg = $this->displayConfirmation($this->l('Settings saved.'));

            // Runs after the save so it uses the window just entered.
            if (Tools::getValue('submitMkproSettings') === 'purge_pii') {
                require_once dirname(__FILE__) . '/classes/AmazonPiiPurger.php';
                $purger = new AmazonPiiPurger();
                $done = $purger->purge();
                if ($done === false) {
                    $confirmMsg .= $this->displayError($purger->getLastError());
                } else {
                    $confirmMsg .= $this->displayConfirmation(sprintf(
                        $this->l('Buyer data cleared from %1$d order(s). %2$d still waiting.'),
                        (int) $done['orders'],
                        (int) $done['remaining']
                    ));
                }
            }
        }
        if (Tools::getValue('mkpro_connected')) {
            $confirmMsg .= $this->displayConfirmation($this->l('Your shop is now connected to Amazon. You can start syncing.'));
        }

        // ── Build AJAX URLs ──
        $baseUrl = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');

        // ── Cron URLs ──
        $cronBase = $this->getCronBaseUrl();

        // ── Get carriers and order states for settings form ──
        $carriers = Carrier::getCarriers($this->context->language->id, true);
        $orderStates = OrderState::getOrderStates($this->context->language->id);

        // ── Get PS categories for category mapping ──
        $categories = $this->getPsCategories();

        // ── Get existing category mappings ──
        $categoryMappings = $this->getCategoryMappings();

        // ── Get staged returns ──
        $returns = $this->getRecentReturns(50);

        // ── Get FBA inventory ──
        $fbaInventory = $this->getFbaInventory();

        // ── Get competitive pricing data ──
        $competitivePrices = $this->getCompetitivePrices();
        $pricingRules = $this->getPricingRules();

        // ── Get fee summary ──
        $feeSummary = $this->getFeeSummary();

        // ── Get reports ──
        $reports = $this->getReports();

        // ── Get marketplace configs ──
        $marketplaceConfigs = $this->getMarketplaceConfigs();

        // ── Get promotions ──
        $promotions = $this->getPromotions();
        $promotionStats = $this->getPromotionStats();

        // ── Markup / rules / queue / orphans / pending data ──
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';
        $entityCategories = $this->getEntityRows('category');
        $entityManufacturers = $this->getEntityRows('manufacturer');
        $entitySuppliers = $this->getEntityRows('supplier');
        $productRules = $this->getProductRules(500);
        $queueRows = AmazonListingSettings::getQueue(200);
        $orphanRows = AmazonListingSettings::getOrphanedProducts(200);
        $pendingOrders = $this->getPendingStockOrders();
        require_once dirname(__FILE__) . '/classes/AmazonRemoteCart.php';
        $reservations = AmazonRemoteCart::listActive(200);
        $shippingTemplates = AmazonListingSettings::getShippingTemplates();
        $customerGroups = Group::getGroups($this->context->language->id);

        // ── Buyer data retention ──
        require_once dirname(__FILE__) . '/classes/AmazonPiiPurger.php';
        $piiPurger = new AmazonPiiPurger();
        $piiDueCount = $piiPurger->dueCount();
        $piiPurgedCount = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `pii_purged_at` IS NOT NULL'
        );

        // ── Listing profiles ──
        require_once dirname(__FILE__) . '/classes/AmazonProfile.php';
        $profiles = AmazonProfile::getAll($this->getMarketplaceId());
        $attributeGroups = Db::getInstance()->executeS(
            'SELECT DISTINCT agl.`name`
             FROM `' . _DB_PREFIX_ . 'attribute_group_lang` agl
             WHERE agl.`id_lang` = ' . (int) $this->context->language->id . '
             ORDER BY agl.`name` ASC'
        );

        require_once dirname(__FILE__) . '/classes/AmazonScheduler.php';
        $this->context->smarty->assign(array(
            'module_dir'  => $this->_path,
            'confirm_msg' => $confirmMsg,

            // Current settings
            'mkpro_client_id'       => Configuration::get('AMZPRO_CLIENT_ID'),
            'mkpro_client_secret'   => Configuration::get('AMZPRO_CLIENT_SECRET'),
            'mkpro_refresh_token'   => AmazonSpApiClient::storedRefreshToken(),
            'mkpro_seller_id'       => Configuration::get('AMZPRO_SELLER_ID'),
            'mkpro_marketplace_id'  => Configuration::get('AMZPRO_MARKETPLACE_ID'),
            'mkpro_environment'     => AmazonSpApiClient::environment(),
            'mkpro_use_mock'        => Configuration::get('AMZPRO_USE_MOCK'),
            'mkpro_default_carrier' => Configuration::get('AMZPRO_DEFAULT_CARRIER'),
            'mkpro_default_order_state' => Configuration::get('AMZPRO_DEFAULT_ORDER_STATE'),
            'mkpro_sync_stock_hook' => Configuration::get('AMZPRO_SYNC_STOCK_HOOK'),
            'mkpro_sync_order_hook' => Configuration::get('AMZPRO_SYNC_ORDER_STATUS_HOOK'),
            'mkpro_sync_cancel_hook' => Configuration::get('AMZPRO_SYNC_CANCEL_HOOK'),
            'mkpro_cron_token'      => Configuration::get('AMZPRO_CRON_TOKEN'),
            'mkpro_auto_review_request' => Configuration::get('AMZPRO_AUTO_REVIEW_REQUEST'),
            'mkpro_stock_buffer'    => (int) Configuration::get('AMZPRO_STOCK_BUFFER'),
            'mkpro_delete_when_oos' => Configuration::get('AMZPRO_DELETE_WHEN_OOS'),
            'mkpro_condition_type'  => Configuration::get('AMZPRO_CONDITION_TYPE'),
            'mkpro_tax_mode'        => Configuration::get('AMZPRO_TAX_MODE'),
            'mkpro_listing_lang'    => Configuration::get('AMZPRO_LISTING_LANG'),
            'ps_languages'          => Language::getLanguages(true),
            'mkpro_shipping_template' => Configuration::get('AMZPRO_SHIPPING_TEMPLATE'),
            'mkpro_b2b_discount'    => (float) Configuration::get('AMZPRO_B2B_DISCOUNT'),
            'mkpro_vcs_enabled'     => Configuration::get('AMZPRO_VCS_ENABLED'),
            'mkpro_dev_mode'        => (bool) Configuration::get('AMZPRO_DEV_MODE'),

            'mkpro_carrier_map'     => (array) json_decode((string) Configuration::get('AMZPRO_CARRIER_MAP'), true),
            'amazon_carrier_codes'  => self::$amazonCarrierCodes,

            // Connect with Amazon
            'mkpro_auth_mode'       => Configuration::get('AMZPRO_AUTH_MODE'),
            'mkpro_lwa_app_id'      => AmazonSpApiClient::lwaAppId(),
            'mkpro_relay_url'       => Configuration::get('AMZPRO_RELAY_URL'),
            'mkpro_oauth_beta'      => AmazonSpApiClient::oauthBeta(),
            'mkpro_connected'       => (AmazonSpApiClient::storedRefreshToken() != ''),
            // Manual mode is "connected" as soon as a token is stored for the
            // active environment — the OAuth Connect flow is never used there.
            'mkpro_manual_connected' => (Configuration::get('AMZPRO_AUTH_MODE') === 'manual'
                && AmazonSpApiClient::storedRefreshToken() != ''),
            // Sandbox and production hold separate tokens; knowing the inactive
            // one is connected lets the UI promise that switching back is free.
            'mkpro_other_env_connected' => (AmazonSpApiClient::storedRefreshToken(
                !AmazonSpApiClient::isSandboxEnv()
            ) != ''),
            'mkpro_selling_partner_id' => Configuration::get('AMZPRO_SELLING_PARTNER_ID'),
            'mkpro_oauth_error'     => Tools::getValue('mkpro_oauth_error', ''),

            // Select options
            'marketplaces'   => self::$marketplaces,
            'carriers'       => $carriers,
            'order_states'   => $orderStates,
            'ps_categories'  => $categories,

            // Category mappings
            'category_mappings' => $categoryMappings,

            // Returns
            'returns' => $returns,

            // FBA
            'fba_inventory' => $fbaInventory,

            // Repricing
            'competitive_prices' => $competitivePrices,
            'pricing_rules' => $pricingRules,

            // Fees
            'fee_summary' => $feeSummary,

            // Reports
            'reports' => $reports,

            // Multi-marketplace
            'marketplace_configs' => $marketplaceConfigs,

            // Promotions
            'promotions' => $promotions,
            'promotion_stats' => $promotionStats,

            // SKU & export filters
            'mkpro_sku_prefix'   => Configuration::get('AMZPRO_SKU_PREFIX'),
            'mkpro_sku_source'   => Configuration::get('AMZPRO_SKU_SOURCE'),
            'mkpro_price_min'    => (float) Configuration::get('AMZPRO_PRICE_MIN'),
            'mkpro_price_max'    => (float) Configuration::get('AMZPRO_PRICE_MAX'),
            'mkpro_qty_min'      => (int) Configuration::get('AMZPRO_QTY_MIN'),
            'mkpro_use_specific_prices' => Configuration::get('AMZPRO_USE_SPECIFIC_PRICES'),
            'mkpro_specific_price_group' => (int) Configuration::get('AMZPRO_SPECIFIC_PRICE_GROUP'),
            'mkpro_report_email' => Configuration::get('AMZPRO_REPORT_EMAIL'),

            // Markup / delay / GPSR
            'mkpro_default_markup' => Configuration::get('AMZPRO_DEFAULT_MARKUP'),
            'mkpro_default_delay'  => (int) Configuration::get('AMZPRO_DEFAULT_DELAY'),
            'mkpro_markup_sources' => explode(',', (string) Configuration::get('AMZPRO_MARKUP_SOURCES')),
            'mkpro_delay_sources'  => explode(',', (string) Configuration::get('AMZPRO_DELAY_SOURCES')),
            'mkpro_gpsr_priority'  => Configuration::get('AMZPRO_GPSR_PRIORITY'),

            // Sync options
            'mkpro_sync_mode'      => Configuration::get('AMZPRO_SYNC_MODE'),
            'mkpro_force_zero_qty' => Configuration::get('AMZPRO_FORCE_ZERO_QTY'),
            'mkpro_only_with_asin' => Configuration::get('AMZPRO_ONLY_WITH_ASIN'),
            'mkpro_export_limit'   => (int) Configuration::get('AMZPRO_EXPORT_LIMIT'),
            'mkpro_ean_as'         => Configuration::get('AMZPRO_EAN_AS'),
            'mkpro_delta_hours'    => (int) Configuration::get('AMZPRO_DELTA_HOURS'),
            'mkpro_cond_note_used'   => Configuration::get('AMZPRO_COND_NOTE_USED'),
            'mkpro_cond_note_refurb' => Configuration::get('AMZPRO_COND_NOTE_REFURB'),
            'mkpro_queue_ttl_days' => (int) Configuration::get('AMZPRO_QUEUE_TTL_DAYS'),
            'mkpro_title_format'   => Configuration::get('AMZPRO_TITLE_FORMAT'),

            // Shipping template ranges
            'mkpro_ship_tpl_enabled' => Configuration::get('AMZPRO_SHIP_TPL_ENABLED'),
            'mkpro_ship_tpl_basis'   => Configuration::get('AMZPRO_SHIP_TPL_BASIS'),
            'shipping_templates'     => $shippingTemplates,

            // Extended order import
            'mkpro_order_lookback_value' => (int) Configuration::get('AMZPRO_ORDER_LOOKBACK_VALUE'),
            'mkpro_order_lookback_unit'  => Configuration::get('AMZPRO_ORDER_LOOKBACK_UNIT'),
            'mkpro_import_fba_orders'    => Configuration::get('AMZPRO_IMPORT_FBA_ORDERS'),
            'mkpro_fba_order_state'      => (int) Configuration::get('AMZPRO_FBA_ORDER_STATE'),
            'mkpro_order_state_shipped'  => (int) Configuration::get('AMZPRO_ORDER_STATE_SHIPPED'),
            'mkpro_prioritize_asin'      => Configuration::get('AMZPRO_PRIORITIZE_ASIN'),
            'mkpro_fake_email'           => Configuration::get('AMZPRO_FAKE_EMAIL'),
            'mkpro_customer_group'       => (int) Configuration::get('AMZPRO_CUSTOMER_GROUP'),
            'mkpro_skip_no_stock'        => Configuration::get('AMZPRO_SKIP_NO_STOCK'),
            'mkpro_pii_purge'            => AmazonPiiPurger::isEnabled(),
            'mkpro_pii_retention_days'   => AmazonPiiPurger::retentionDays(),
            'mkpro_pii_purge_ps'         => Configuration::get('AMZPRO_PII_PURGE_PS'),
            'pii_due_count'              => $piiDueCount,
            'pii_purged_count'           => $piiPurgedCount,
            'mkpro_order_match'          => Configuration::get('AMZPRO_ORDER_MATCH'),
            'mkpro_rounding'             => Configuration::get('AMZPRO_ROUNDING'),
            'mkpro_send_images'          => Configuration::get('AMZPRO_SEND_IMAGES'),
            'mkpro_extended_data'        => Configuration::get('AMZPRO_EXTENDED_DATA'),
            'mkpro_full_catalog'         => Configuration::get('AMZPRO_FULL_CATALOG'),
            'mkpro_business_group'       => (int) Configuration::get('AMZPRO_BUSINESS_GROUP'),
            'mkpro_imap_enabled'         => Configuration::get('AMZPRO_IMAP_ENABLED'),
            'mkpro_imap_host'            => Configuration::get('AMZPRO_IMAP_HOST'),
            'mkpro_imap_port'            => (int) Configuration::get('AMZPRO_IMAP_PORT'),
            'mkpro_imap_user'            => Configuration::get('AMZPRO_IMAP_USER'),
            'mkpro_imap_password_set'    => (Configuration::get('AMZPRO_IMAP_PASSWORD') != ''),
            'mkpro_imap_folder'          => Configuration::get('AMZPRO_IMAP_FOLDER'),
            'mkpro_imap_ssl'             => Configuration::get('AMZPRO_IMAP_SSL'),
            'mkpro_imap_available'       => function_exists('imap_open'),
            'mkpro_carrier_map_in'       => (array) json_decode((string) Configuration::get('AMZPRO_CARRIER_MAP_IN'), true),
            'amazon_ship_levels'         => array('Standard', 'Expedited', 'NextDay', 'SecondDay', 'Priority', 'SameDay', 'Scheduled'),
            'mkpro_status_rules'         => (array) json_decode((string) Configuration::get('AMZPRO_STATUS_RULES'), true),
            'mkpro_invoice_email'        => Configuration::get('AMZPRO_INVOICE_EMAIL'),
            'mkpro_invoice_email_state'  => (int) Configuration::get('AMZPRO_INVOICE_EMAIL_STATE'),
            'mkpro_invoice_attachment'   => Configuration::get('AMZPRO_INVOICE_ATTACHMENT'),
            'mkpro_send_sale_price'      => Configuration::get('AMZPRO_SEND_SALE_PRICE'),
            'mkpro_send_list_price'      => Configuration::get('AMZPRO_SEND_LIST_PRICE'),
            'mkpro_preorder'             => Configuration::get('AMZPRO_PREORDER'),
            'mkpro_condition_map'        => array_merge(
                array('new' => 'new_new', 'used' => 'used_good', 'refurbished' => 'refurbished_refurbished'),
                (array) json_decode((string) Configuration::get('AMZPRO_CONDITION_MAP'), true)
            ),
            'amazon_conditions'          => array(
                'new_new' => 'New',
                'used_like_new' => 'Used - Like New',
                'used_very_good' => 'Used - Very Good',
                'used_good' => 'Used - Good',
                'used_acceptable' => 'Used - Acceptable',
                'collectible_like_new' => 'Collectible - Like New',
                'refurbished_refurbished' => 'Refurbished',
            ),
            'customer_groups'            => $customerGroups,

            // Listing profiles
            'profiles'          => $profiles,
            'profile_ps_fields' => AmazonProfile::$psFields,
            'attribute_groups'  => is_array($attributeGroups) ? $attributeGroups : array(),

            // Rules / queue / orphans / pending
            'entity_categories'    => $entityCategories,
            'entity_manufacturers' => $entityManufacturers,
            'entity_suppliers'     => $entitySuppliers,
            'product_rules'        => $productRules,
            'queue_rows'           => $queueRows,
            'orphan_rows'          => $orphanRows,
            'pending_orders'       => $pendingOrders,
            'reservations'         => $reservations,
            'mkpro_remote_cart'    => Configuration::get('AMZPRO_REMOTE_CART'),
            'mkpro_remote_cart_ttl' => (int) Configuration::get('AMZPRO_REMOTE_CART_TTL'),

            // AJAX URLs
            'ajax_test_amazon_url'          => $baseUrl . '&ajaxTestAmazon=1',
            'ajax_import_orders_url'        => $baseUrl . '&ajaxImportAmazonOrders=1',
            'ajax_create_ps_orders_url'     => $baseUrl . '&ajaxCreatePsOrders=1',
            'ajax_sync_products_ps_url'     => $baseUrl . '&ajaxSyncProductsPs=1',
            'ajax_sync_products_amazon_url' => $baseUrl . '&ajaxSyncProductsAmazon=1',
            'ajax_list_amazon_products_url' => $baseUrl . '&ajaxListAmazonProducts=1',
            'ajax_push_products_url'        => $baseUrl . '&ajaxPushProducts=1',
            'ajax_import_returns_url'       => $baseUrl . '&ajaxImportReturns=1',
            'ajax_process_returns_url'      => $baseUrl . '&ajaxProcessReturns=1',
            'ajax_save_category_map_url'    => $baseUrl . '&ajaxSaveCategoryMap=1',
            'ajax_delete_category_map_url'  => $baseUrl . '&ajaxDeleteCategoryMap=1',
            'ajax_sync_fba_inventory_url'   => $baseUrl . '&ajaxSyncFbaInventory=1',
            'ajax_create_mcf_order_url'     => $baseUrl . '&ajaxCreateMcfOrder=1',
            'ajax_sync_fba_stock_ps_url'    => $baseUrl . '&ajaxSyncFbaStockToPs=1',
            'ajax_fetch_pricing_url'        => $baseUrl . '&ajaxFetchPricing=1',
            'ajax_apply_pricing_rules_url'  => $baseUrl . '&ajaxApplyPricingRules=1',
            'ajax_push_prices_url'          => $baseUrl . '&ajaxPushPrices=1',
            'ajax_save_pricing_rule_url'    => $baseUrl . '&ajaxSavePricingRule=1',
            'ajax_delete_pricing_rule_url'  => $baseUrl . '&ajaxDeletePricingRule=1',
            'ajax_fetch_fees_url'           => $baseUrl . '&ajaxFetchFees=1',
            'ajax_request_report_url'       => $baseUrl . '&ajaxRequestReport=1',
            'ajax_poll_reports_url'         => $baseUrl . '&ajaxPollReports=1',
            'ajax_save_marketplace_url'     => $baseUrl . '&ajaxSaveMarketplace=1',
            'ajax_delete_marketplace_url'   => $baseUrl . '&ajaxDeleteMarketplace=1',
            'ajax_import_promotions_url'    => $baseUrl . '&ajaxImportPromotions=1',
            'ajax_export_promotions_url'    => $baseUrl . '&ajaxExportPromotions=1',
            'ajax_create_promo_cart_rules_url' => $baseUrl . '&ajaxCreatePromoCartRules=1',
            'ajax_get_messaging_actions_url' => $baseUrl . '&ajaxGetMessagingActions=1',
            'ajax_send_buyer_message_url'   => $baseUrl . '&ajaxSendBuyerMessage=1',
            'ajax_request_review_url'       => $baseUrl . '&ajaxRequestReview=1',
            'ajax_match_catalog_url'        => $baseUrl . '&ajaxMatchCatalog=1',
            'ajax_import_catalog_url'       => $baseUrl . '&ajaxImportCatalog=1',
            'ajax_submit_feed_url'          => $baseUrl . '&ajaxSubmitFeed=1',
            'ajax_poll_feeds_url'           => $baseUrl . '&ajaxPollFeeds=1',
            'ajax_save_entity_settings_url' => $baseUrl . '&ajaxSaveEntitySettings=1',
            'ajax_save_product_rules_url'   => $baseUrl . '&ajaxSaveProductRules=1',
            'ajax_queue_action_url'         => $baseUrl . '&ajaxQueueAction=1',
            'ajax_refresh_orphans_url'      => $baseUrl . '&ajaxRefreshOrphans=1',
            'ajax_pending_order_action_url' => $baseUrl . '&ajaxPendingOrderAction=1',
            'ajax_save_shipping_template_url'   => $baseUrl . '&ajaxSaveShippingTemplate=1',
            'ajax_delete_shipping_template_url' => $baseUrl . '&ajaxDeleteShippingTemplate=1',
            'ajax_search_product_types_url'  => $baseUrl . '&ajaxSearchProductTypes=1',
            'ajax_load_pt_schema_url'       => $baseUrl . '&ajaxLoadProductTypeSchema=1',
            'ajax_save_profile_url'         => $baseUrl . '&ajaxSaveProfile=1',
            'ajax_get_profile_url'          => $baseUrl . '&ajaxGetProfile=1',
            'ajax_delete_profile_url'       => $baseUrl . '&ajaxDeleteProfile=1',
            'ajax_update_from_amazon_url'   => $baseUrl . '&ajaxUpdateFromAmazon=1',
            'ajax_fetch_buyer_messages_url' => $baseUrl . '&ajaxFetchBuyerMessages=1',
            'ajax_audit_catalogue_url'      => $baseUrl . '&ajaxAuditCatalogue=1',
            'ajax_import_references_url'    => $baseUrl . '&ajaxImportReferences=1',
            'ajax_list_deletions_url'       => $baseUrl . '&ajaxListDeletions=1',
            'ajax_delete_listings_url'      => $baseUrl . '&ajaxDeleteListings=1',
            'export_references_url'         => $baseUrl . '&mkproExportReferences=1',
            'download_feed_url'             => $baseUrl . '&mkproDownloadFeed=1',

            // Cron URLs
            'cron_import_orders_url'   => $cronBase . '&action=import_orders',
            'cron_create_orders_url'   => $cronBase . '&action=create_orders',
            'cron_sync_stock_url'      => $cronBase . '&action=sync_stock',
            'cron_sync_products_url'   => $cronBase . '&action=sync_products',
            'cron_import_returns_url'  => $cronBase . '&action=import_returns',
            'cron_process_returns_url' => $cronBase . '&action=process_returns',
            'cron_sync_fba_url'        => $cronBase . '&action=sync_fba',
            'cron_reprice_url'         => $cronBase . '&action=reprice',
            'cron_fetch_fees_url'      => $cronBase . '&action=fetch_fees',
            'cron_poll_reports_url'    => $cronBase . '&action=poll_reports',
            'cron_sync_promotions_url' => $cronBase . '&action=sync_promotions',
            'cron_multi_import_url'    => $cronBase . '&action=multi_import_orders',
            'cron_multi_sync_url'      => $cronBase . '&action=multi_sync_products',
            'cron_request_reviews_url' => $cronBase . '&action=request_reviews',
            'cron_process_feeds_url'   => $cronBase . '&action=process_feeds',
            'cron_purge_pii_url'       => $cronBase . '&action=purge_pii',
            'cron_upload_invoices_url' => $cronBase . '&action=upload_invoices',
            'cron_remote_cart_url'     => $cronBase . '&action=remote_cart',

            // The schedule. One URL replaces the nineteen above; the old ones
            // stay assigned so an install that already uses them keeps working.
            'cron_run_due_url'   => $cronBase . '&action=run_due',
            'schedule_tasks'     => AmazonScheduler::all(),
            'schedule_catalogue' => AmazonScheduler::catalogue(),
            'schedule_auto'      => (bool) Configuration::get('AMZPRO_CRON_AUTO'),
            'schedule_notice'    => $this->scheduleNotice,
            'cron_fetch_messages_url'  => $cronBase . '&action=fetch_messages',

            // Log entries
            'log_entries' => $this->getRecentLogs(50),
        ));

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    /* ─────────────────── Settings persistence ─────────────────── */

    private function saveSettings()
    {
        $fields = array(
            'AMZPRO_CLIENT_ID'       => 'mkpro_client_id',
            'AMZPRO_CLIENT_SECRET'    => 'mkpro_client_secret',
            // Env-aware, matching what the form displayed. Resolved before the
            // new environment is written below, so it targets the slot the
            // merchant was actually looking at.
            AmazonSpApiClient::refreshTokenKey() => 'mkpro_refresh_token',
            'AMZPRO_SELLER_ID'        => 'mkpro_seller_id',
            'AMZPRO_MARKETPLACE_ID'   => 'mkpro_marketplace_id',
            'AMZPRO_ENVIRONMENT'      => 'mkpro_environment',
            'AMZPRO_USE_MOCK'         => 'mkpro_use_mock',
            'AMZPRO_DEFAULT_CARRIER'         => 'mkpro_default_carrier',
            'AMZPRO_DEFAULT_ORDER_STATE'     => 'mkpro_default_order_state',
            'AMZPRO_SYNC_STOCK_HOOK'         => 'mkpro_sync_stock_hook',
            'AMZPRO_SYNC_ORDER_STATUS_HOOK'  => 'mkpro_sync_order_hook',
            'AMZPRO_SYNC_CANCEL_HOOK'        => 'mkpro_sync_cancel_hook',
            'AMZPRO_AUTH_MODE'               => 'mkpro_auth_mode',
            'AMZPRO_OAUTH_BETA'              => 'mkpro_oauth_beta',
            'AMZPRO_AUTO_REVIEW_REQUEST'     => 'mkpro_auto_review_request',
            'AMZPRO_STOCK_BUFFER'            => 'mkpro_stock_buffer',
            'AMZPRO_DELETE_WHEN_OOS'         => 'mkpro_delete_when_oos',
            'AMZPRO_CONDITION_TYPE'          => 'mkpro_condition_type',
            'AMZPRO_TAX_MODE'                => 'mkpro_tax_mode',
            'AMZPRO_LISTING_LANG'            => 'mkpro_listing_lang',
            'AMZPRO_SHIPPING_TEMPLATE'       => 'mkpro_shipping_template',
            'AMZPRO_B2B_DISCOUNT'            => 'mkpro_b2b_discount',
            'AMZPRO_VCS_ENABLED'             => 'mkpro_vcs_enabled',
            'AMZPRO_SKU_PREFIX'              => 'mkpro_sku_prefix',
            'AMZPRO_SKU_SOURCE'              => 'mkpro_sku_source',
            'AMZPRO_PRICE_MIN'               => 'mkpro_price_min',
            'AMZPRO_PRICE_MAX'               => 'mkpro_price_max',
            'AMZPRO_QTY_MIN'                 => 'mkpro_qty_min',
            'AMZPRO_USE_SPECIFIC_PRICES'     => 'mkpro_use_specific_prices',
            'AMZPRO_SPECIFIC_PRICE_GROUP'    => 'mkpro_specific_price_group',
            'AMZPRO_REPORT_EMAIL'            => 'mkpro_report_email',
            'AMZPRO_DEFAULT_MARKUP'          => 'mkpro_default_markup',
            'AMZPRO_DEFAULT_DELAY'           => 'mkpro_default_delay',
            'AMZPRO_GPSR_PRIORITY'           => 'mkpro_gpsr_priority',
            'AMZPRO_SYNC_MODE'               => 'mkpro_sync_mode',
            'AMZPRO_FORCE_ZERO_QTY'          => 'mkpro_force_zero_qty',
            'AMZPRO_ONLY_WITH_ASIN'          => 'mkpro_only_with_asin',
            'AMZPRO_EXPORT_LIMIT'            => 'mkpro_export_limit',
            'AMZPRO_EAN_AS'                  => 'mkpro_ean_as',
            'AMZPRO_DELTA_HOURS'             => 'mkpro_delta_hours',
            'AMZPRO_COND_NOTE_USED'          => 'mkpro_cond_note_used',
            'AMZPRO_COND_NOTE_REFURB'        => 'mkpro_cond_note_refurb',
            'AMZPRO_QUEUE_TTL_DAYS'          => 'mkpro_queue_ttl_days',
            'AMZPRO_TITLE_FORMAT'            => 'mkpro_title_format',
            'AMZPRO_SHIP_TPL_ENABLED'        => 'mkpro_ship_tpl_enabled',
            'AMZPRO_SHIP_TPL_BASIS'          => 'mkpro_ship_tpl_basis',
            'AMZPRO_ORDER_LOOKBACK_VALUE'    => 'mkpro_order_lookback_value',
            'AMZPRO_ORDER_LOOKBACK_UNIT'     => 'mkpro_order_lookback_unit',
            'AMZPRO_IMPORT_FBA_ORDERS'       => 'mkpro_import_fba_orders',
            'AMZPRO_FBA_ORDER_STATE'         => 'mkpro_fba_order_state',
            'AMZPRO_ORDER_STATE_SHIPPED'     => 'mkpro_order_state_shipped',
            'AMZPRO_PRIORITIZE_ASIN'         => 'mkpro_prioritize_asin',
            'AMZPRO_FAKE_EMAIL'              => 'mkpro_fake_email',
            'AMZPRO_CUSTOMER_GROUP'          => 'mkpro_customer_group',
            'AMZPRO_SKIP_NO_STOCK'           => 'mkpro_skip_no_stock',
            'AMZPRO_PII_PURGE'               => 'mkpro_pii_purge',
            'AMZPRO_PII_RETENTION_DAYS'      => 'mkpro_pii_retention_days',
            'AMZPRO_PII_PURGE_PS'            => 'mkpro_pii_purge_ps',
            'AMZPRO_ORDER_MATCH'             => 'mkpro_order_match',
            'AMZPRO_ROUNDING'                => 'mkpro_rounding',
            'AMZPRO_SEND_IMAGES'             => 'mkpro_send_images',
            'AMZPRO_EXTENDED_DATA'           => 'mkpro_extended_data',
            'AMZPRO_FULL_CATALOG'            => 'mkpro_full_catalog',
            'AMZPRO_BUSINESS_GROUP'          => 'mkpro_business_group',
            'AMZPRO_IMAP_ENABLED'            => 'mkpro_imap_enabled',
            'AMZPRO_IMAP_HOST'               => 'mkpro_imap_host',
            'AMZPRO_IMAP_PORT'               => 'mkpro_imap_port',
            'AMZPRO_IMAP_USER'               => 'mkpro_imap_user',
            'AMZPRO_IMAP_FOLDER'             => 'mkpro_imap_folder',
            'AMZPRO_IMAP_SSL'                => 'mkpro_imap_ssl',
            'AMZPRO_IMAP_PASSWORD'           => 'mkpro_imap_password',
            'AMZPRO_SEND_SALE_PRICE'         => 'mkpro_send_sale_price',
            'AMZPRO_SEND_LIST_PRICE'         => 'mkpro_send_list_price',
            'AMZPRO_PREORDER'                => 'mkpro_preorder',
            'AMZPRO_INVOICE_EMAIL'           => 'mkpro_invoice_email',
            'AMZPRO_INVOICE_EMAIL_STATE'     => 'mkpro_invoice_email_state',
            'AMZPRO_INVOICE_ATTACHMENT'      => 'mkpro_invoice_attachment',
            'AMZPRO_REMOTE_CART'             => 'mkpro_remote_cart',
            'AMZPRO_REMOTE_CART_TTL'         => 'mkpro_remote_cart_ttl',
        );

        // Developer-only fields are hidden from the customer form; without
        // this, their absent POST values would wipe the stored settings.
        if (!Configuration::get('AMZPRO_DEV_MODE')) {
            unset(
                $fields['AMZPRO_ENVIRONMENT'],
                $fields['AMZPRO_USE_MOCK'],
                $fields['AMZPRO_OAUTH_BETA'],
                $fields['AMZPRO_AUTH_MODE']
            );
        }

        // Several forms on the page post the same submit name, so only write
        // back what the submitted form actually contained — otherwise saving
        // one panel blanks the settings that live in another.
        foreach ($fields as $configKey => $formName) {
            if (!Tools::getIsset($formName)) {
                continue;
            }
            $value = Tools::getValue($formName, '');

            // Secrets are never echoed back into the form (and browser
            // autofill loves to blank or overwrite password fields), so an
            // empty submit means "keep the stored value" — clearing a token
            // is what the Disconnect button is for.
            if (($configKey === AmazonSpApiClient::refreshTokenKey()
                    || $configKey === 'AMZPRO_CLIENT_SECRET'
                    || $configKey === 'AMZPRO_IMAP_PASSWORD')
                && trim($value) === '') {
                continue;
            }

            // Connection state is global — same rows the oauth controller
            // and the connection status checks use, whatever the shop context.
            if ($configKey === AmazonSpApiClient::refreshTokenKey()
                || $configKey === 'AMZPRO_SELLER_ID'
                // The environment decides which token slot and which app
                // client are in play, so it has to be readable from every
                // context for the same reason the tokens are.
                || $configKey === 'AMZPRO_ENVIRONMENT') {
                Configuration::updateGlobalValue($configKey, $value);
            } else {
                Configuration::updateValue($configKey, $value);
            }
        }

        // Carrier map arrives as an array: mkpro_carrier_map[id_carrier] = code
        $carrierMap = Tools::getValue('mkpro_carrier_map');
        if (is_array($carrierMap)) {
            $clean = array();
            foreach ($carrierMap as $idCarrier => $code) {
                $code = trim((string) $code);
                if ($code !== '' && in_array($code, self::$amazonCarrierCodes)) {
                    $clean[(int) $idCarrier] = $code;
                }
            }
            Configuration::updateValue('AMZPRO_CARRIER_MAP', json_encode($clean));
        }

        // Incoming carrier map: Amazon shipping speed -> PrestaShop carrier.
        $carrierMapIn = Tools::getValue('mkpro_carrier_map_in');
        if (is_array($carrierMapIn)) {
            $clean = array();
            foreach ($carrierMapIn as $level => $idCarrier) {
                if ((int) $idCarrier > 0) {
                    $clean[(string) $level] = (int) $idCarrier;
                }
            }
            Configuration::updateValue('AMZPRO_CARRIER_MAP_IN', json_encode($clean));
        }

        // Advanced status rules: parallel arrays of conditions + target state.
        $ruleStates = Tools::getValue('mkpro_rule_state');
        if (is_array($ruleStates)) {
            $rules = array();
            $primes = (array) Tools::getValue('mkpro_rule_prime');
            $fbas = (array) Tools::getValue('mkpro_rule_fba');
            $businesses = (array) Tools::getValue('mkpro_rule_business');
            foreach ($ruleStates as $i => $state) {
                if ((int) $state <= 0) {
                    continue; // a rule with no target state is not a rule
                }
                $rules[] = array(
                    'prime' => isset($primes[$i]) ? (int) $primes[$i] : -1,
                    'fba' => isset($fbas[$i]) ? (int) $fbas[$i] : -1,
                    'business' => isset($businesses[$i]) ? (int) $businesses[$i] : -1,
                    'state' => (int) $state,
                );
            }
            Configuration::updateValue('AMZPRO_STATUS_RULES', json_encode($rules));
        }

        // PrestaShop condition -> Amazon condition map (three selects).
        if (Tools::getIsset('mkpro_cond_map_new')) {
            Configuration::updateValue('AMZPRO_CONDITION_MAP', json_encode(array(
                'new' => (string) Tools::getValue('mkpro_cond_map_new'),
                'used' => (string) Tools::getValue('mkpro_cond_map_used'),
                'refurbished' => (string) Tools::getValue('mkpro_cond_map_refurbished'),
            )));
        }

        // Markup / delay cascade sources arrive as checkbox arrays.
        foreach (array('AMZPRO_MARKUP_SOURCES' => 'mkpro_markup_sources',
                       'AMZPRO_DELAY_SOURCES' => 'mkpro_delay_sources') as $configKey => $formName) {
            if (!Tools::getIsset($formName . '_present')) {
                continue; // form section not on the submitted page
            }
            $sources = Tools::getValue($formName);
            $clean = array();
            if (is_array($sources)) {
                foreach ($sources as $s) {
                    if (in_array($s, array('category', 'manufacturer', 'supplier'))) {
                        $clean[] = $s;
                    }
                }
            }
            Configuration::updateValue($configKey, implode(',', $clean));
        }

        // Allow regenerating cron token
        if (Tools::isSubmit('mkpro_regenerate_cron_token')) {
            $newToken = Tools::substr(md5(uniqid((string) rand(), true)), 0, 24);
            Configuration::updateValue('AMZPRO_CRON_TOKEN', $newToken);
        }

    }

    /* ─────────────────── "Connect with Amazon" OAuth ─────────────────── */

    /**
     * Send the merchant to the Seller Central consent page for our app.
     * The relay (intellipresta.com) receives the code, exchanges it, and
     * redirects back to this shop's oauth front controller with the token.
     */
    private function redirectToAmazonConsent()
    {
        require_once dirname(__FILE__) . '/classes/AmazonSpApiClient.php';

        $appId = AmazonSpApiClient::lwaAppId();
        $relayUrl = AmazonSpApiClient::relayUrl();

        // One-time nonce so only this connect attempt can store a token.
        // Global scope: the front oauth controller (shop context) must read
        // the exact row this admin request (any shop context) writes.
        $nonce = Tools::substr(md5(uniqid((string) rand(), true)), 0, 32);
        Configuration::updateGlobalValue('AMZPRO_OAUTH_NONCE', $nonce);

        // Remember the admin page the merchant clicked Connect on, so the
        // oauth controller can send them straight back after success. Stored
        // locally only — never sent to Amazon or the relay.
        if (isset($_SERVER['REQUEST_URI'])) {
            Configuration::updateGlobalValue(
                'AMZPRO_OAUTH_RETURN_URL',
                Tools::getShopDomainSsl(true) . $_SERVER['REQUEST_URI']
            );
        }

        // Where the relay should send the merchant (and token) back to.
        $returnUrl = $this->context->link->getModuleLink($this->name, 'oauth', array(), true);

        // 's' tells the relay which app client's secret to exchange the code with.
        $state = rtrim(strtr(base64_encode(json_encode(array(
            'r' => $returnUrl,
            'n' => $nonce,
            's' => AmazonSpApiClient::isSandboxEnv() ? 1 : 0,
        ))), '+/', '-_'), '=');

        $mp = Configuration::get('AMZPRO_MARKETPLACE_ID');
        $domain = isset(self::$sellerCentralDomains[$mp])
            ? self::$sellerCentralDomains[$mp]
            : 'sellercentral-europe.amazon.com';

        $params = array(
            'application_id' => $appId,
            'state' => $state,
            'redirect_uri' => $relayUrl . '/callback.php',
        );
        // Draft (unpublished) apps can only be authorized with version=beta.
        // Decided by the shipped constant rather than a per-shop setting, so
        // publishing the app is a release rather than a support request to
        // every merchant. See AmazonSpApiClient::oauthBeta().
        if (AmazonSpApiClient::oauthBeta()) {
            $params['version'] = 'beta';
        }

        Tools::redirect('https://' . $domain . '/apps/authorize/consent?' . http_build_query($params));
    }

    /* ─────────────────── Config helpers ─────────────────── */

    protected function isProduction()
    {
        return AmazonSpApiClient::environment() === 'production';
    }

    protected function useMock()
    {
        return Configuration::get('AMZPRO_USE_MOCK') && !$this->isProduction();
    }

    protected function getMarketplaceId()
    {
        if ($this->isProduction()) {
            $mp = Configuration::get('AMZPRO_MARKETPLACE_ID');
            return $mp ? $mp : 'A1PA6795UKMFR9';
        }
        return 'ATVPDKIKX0DER'; // US sandbox
    }

    /**
     * Build a configured SP-API client from DB-stored credentials.
     *
     * @return AmazonSpApiClient
     */
    protected function buildAmazonClient()
    {
        $clientId     = Configuration::get('AMZPRO_CLIENT_ID');
        $clientSecret = Configuration::get('AMZPRO_CLIENT_SECRET');
        require_once dirname(__FILE__) . '/classes/AmazonSpApiClient.php';

        $refreshToken = AmazonSpApiClient::storedRefreshToken();

        $endpoint = $this->resolveEndpoint();

        $client = new AmazonSpApiClient($clientId, $clientSecret, $refreshToken, $endpoint);

        // "Connect with Amazon" mode: access tokens come from the IntelliPresta
        // relay instead of local LWA credentials.
        if (Configuration::get('AMZPRO_AUTH_MODE') !== 'manual') {
            $client->setTokenRelay(AmazonSpApiClient::relayUrl());
        }

        return $client;
    }

    /**
     * Determine the correct SP-API endpoint from environment + marketplace.
     */
    private function resolveEndpoint()
    {
        if (!$this->isProduction()) {
            return AmazonSpApiClient::ENDPOINT_NA_SANDBOX;
        }

        $mp = Configuration::get('AMZPRO_MARKETPLACE_ID');
        $region = isset(self::$marketplaceEndpoints[$mp]) ? self::$marketplaceEndpoints[$mp] : 'EU';

        switch ($region) {
            case 'NA':
                return AmazonSpApiClient::ENDPOINT_NA;
            case 'FE':
                return AmazonSpApiClient::ENDPOINT_FE;
            default:
                return AmazonSpApiClient::ENDPOINT_EU;
        }
    }

    private function getCronBaseUrl()
    {
        $token = Configuration::get('AMZPRO_CRON_TOKEN');
        $link = $this->context->link;
        if (method_exists($link, 'getModuleLink')) {
            $base = $link->getModuleLink($this->name, 'cron', array(), true);
            $separator = (strpos($base, '?') !== false) ? '&' : '?';
            return $base . $separator . 'token=' . urlencode($token);
        }
        $shopUrl = Tools::getShopDomainSsl(true);
        return $shopUrl . '/index.php?fc=module&module=' . $this->name . '&controller=cron&token=' . urlencode($token);
    }

    /**
     * Get PS categories for the category mapping dropdown.
     */
    private function getPsCategories()
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        if (!$idShop) {
            $idShop = 1;
        }

        $sql = 'SELECT c.`id_category`, cl.`name`
                FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON (cl.`id_category` = c.`id_category` AND cl.`id_lang` = ' . $idLang . ' AND cl.`id_shop` = ' . $idShop . ')
                WHERE c.`id_category` > 1 AND c.`active` = 1
                ORDER BY cl.`name` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Get category mappings for admin UI.
     */
    private function getCategoryMappings()
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        if (!$idShop) {
            $idShop = 1;
        }

        $sql = 'SELECT cm.*, cl.`name` AS category_name
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_category_map` cm
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON (cl.`id_category` = cm.`id_category` AND cl.`id_lang` = ' . $idLang . ' AND cl.`id_shop` = ' . $idShop . ')
                ORDER BY cl.`name` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Get recent returns for admin UI.
     */
    private function getRecentReturns($limit = 50)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_return`
                ORDER BY `date_add` DESC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    /* ─────────────────── Markup / rules / queue helpers ─────────────────── */

    /**
     * Categories, manufacturers or suppliers joined with their module rules
     * (markup, delay, GPSR contact, country of origin, sync switch).
     */
    private function getEntityRows($type)
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        if (!$idShop) {
            $idShop = 1;
        }

        if ($type === 'category') {
            $sql = 'SELECT c.`id_category` AS id_entity, cl.`name`
                    FROM `' . _DB_PREFIX_ . 'category` c
                    INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                        ON (cl.`id_category` = c.`id_category` AND cl.`id_lang` = ' . $idLang . '
                            AND cl.`id_shop` = ' . $idShop . ')
                    WHERE c.`id_category` > 1 AND c.`active` = 1
                    ORDER BY cl.`name` ASC';
        } elseif ($type === 'manufacturer') {
            $sql = 'SELECT `id_manufacturer` AS id_entity, `name`
                    FROM `' . _DB_PREFIX_ . 'manufacturer` ORDER BY `name` ASC';
        } else {
            $sql = 'SELECT `id_supplier` AS id_entity, `name`
                    FROM `' . _DB_PREFIX_ . 'supplier` ORDER BY `name` ASC';
        }

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = array();
        }

        $settings = AmazonListingSettings::getEntitySettings($type);
        foreach ($rows as &$row) {
            $id = (int) $row['id_entity'];
            $s = isset($settings[$id]) ? $settings[$id] : null;
            $row['price_markup'] = $s ? $s['price_markup'] : '';
            $row['shipping_delay'] = ($s && (int) $s['shipping_delay'] >= 0) ? (int) $s['shipping_delay'] : '';
            $row['gpsr_contact'] = $s ? $s['gpsr_contact'] : '';
            $row['country_of_origin'] = $s ? $s['country_of_origin'] : '';
            $row['sync'] = $s ? (int) $s['sync'] : 1;
        }
        unset($row);

        return $rows;
    }

    /** Products joined with their per-product module rules (sync, GPSR). */
    private function getProductRules($limit = 500)
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $idLang = (int) $this->context->language->id;
        $rows = Db::getInstance()->executeS(
            'SELECT p.`id_product`, p.`reference`, pl.`name`
             FROM `' . _DB_PREFIX_ . 'product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ')
             WHERE p.`active` = 1
             GROUP BY p.`id_product`
             ORDER BY p.`id_product` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            $rows = array();
        }

        $settings = AmazonListingSettings::getProductSettings();
        foreach ($rows as &$row) {
            $id = (int) $row['id_product'];
            $s = isset($settings[$id]) ? $settings[$id] : null;
            $row['sync'] = $s ? (int) $s['sync'] : 1;
            $row['gpsr_contact'] = $s ? $s['gpsr_contact'] : '';
        }
        unset($row);

        return $rows;
    }

    /** Staged orders parked because of insufficient stock. */
    private function getPendingStockOrders()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT o.`id_amazonmarketplacepro_order`, o.`amazon_order_id`, o.`purchase_date`,
                    o.`order_total`, o.`currency`, o.`date_upd`,
                    GROUP_CONCAT(CONCAT(i.`seller_sku`, \' x\', i.`quantity`) SEPARATOR \', \') AS item_list
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` o
             LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_item` i
                 ON (i.`id_amazonmarketplacepro_order` = o.`id_amazonmarketplacepro_order`)
             WHERE o.`import_status` = \'pending_stock\'
             GROUP BY o.`id_amazonmarketplacepro_order`
             ORDER BY o.`purchase_date` ASC'
        );

        return is_array($rows) ? $rows : array();
    }

    /**
     * Save a batch of entity rules. Rows whose values changed queue their
     * products for the next delta sync.
     */
    protected function runSaveEntitySettings()
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $type = trim((string) Tools::getValue('entity_type'));
        if (!in_array($type, array('category', 'manufacturer', 'supplier'))) {
            return array('success' => false, 'error' => 'Unknown entity type.');
        }
        $rows = json_decode((string) Tools::getValue('rows'), true);
        if (!is_array($rows)) {
            return array('success' => false, 'error' => 'Invalid rows payload.');
        }

        $before = AmazonListingSettings::getEntitySettings($type);
        $saved = 0;
        $queued = 0;
        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if (!$id) {
                continue;
            }
            $markup = isset($row['markup']) ? trim((string) $row['markup']) : '';
            $delay = (isset($row['delay']) && $row['delay'] !== '') ? (int) $row['delay'] : -1;
            $gpsr = isset($row['gpsr']) ? trim((string) $row['gpsr']) : '';
            $coo = isset($row['coo']) ? trim((string) $row['coo']) : '';
            $sync = isset($row['sync']) ? (int) $row['sync'] : 1;

            $old = isset($before[$id]) ? $before[$id] : null;
            $changed = !$old
                || $old['price_markup'] !== $markup
                || (int) $old['shipping_delay'] !== $delay
                || $old['gpsr_contact'] !== $gpsr
                || $old['country_of_origin'] !== Tools::strtoupper($coo)
                || (int) $old['sync'] !== $sync;
            // Untouched rows with no stored setting and no values don't need a row.
            if (!$old && $markup === '' && $delay < 0 && $gpsr === '' && $coo === '' && $sync == 1) {
                continue;
            }

            AmazonListingSettings::saveEntitySetting($type, $id, $markup, $delay, $gpsr, $coo, $sync);
            $saved++;
            if ($changed) {
                $queued += AmazonListingSettings::enqueueEntityProducts(
                    $type, $id, $type . ' rules changed'
                );
            }
        }

        $this->logActivity('info', 'rules', 'Saved ' . $saved . ' ' . $type . ' rule(s), queued ' . $queued . ' product(s).');

        return array('success' => true, 'saved' => $saved, 'queued' => $queued);
    }

    protected function runSaveProductRules()
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $rows = json_decode((string) Tools::getValue('rows'), true);
        if (!is_array($rows)) {
            return array('success' => false, 'error' => 'Invalid rows payload.');
        }

        $before = AmazonListingSettings::getProductSettings();
        $saved = 0;
        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if (!$id) {
                continue;
            }
            $sync = isset($row['sync']) ? (int) $row['sync'] : 1;
            $gpsr = isset($row['gpsr']) ? trim((string) $row['gpsr']) : '';

            $old = isset($before[$id]) ? $before[$id] : null;
            if (!$old && $sync == 1 && $gpsr === '') {
                continue;
            }
            $changed = !$old || (int) $old['sync'] !== $sync || $old['gpsr_contact'] !== $gpsr;

            AmazonListingSettings::saveProductSetting($id, $sync, $gpsr);
            $saved++;
            if ($changed) {
                AmazonListingSettings::enqueueProduct($id, 'product rules changed');
            }
        }

        return array('success' => true, 'saved' => $saved);
    }

    protected function runQueueAction()
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $op = trim((string) Tools::getValue('queue_op'));
        if ($op === 'purge') {
            AmazonListingSettings::purgeQueue();
        } elseif (in_array($op, array('enable', 'disable', 'clear'))) {
            AmazonListingSettings::queueAction($op);
        } else {
            return array('success' => false, 'error' => 'Unknown queue action.');
        }

        return array('success' => true, 'queue' => AmazonListingSettings::getQueue(200));
    }

    protected function runRefreshOrphans()
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $orphans = AmazonListingSettings::getOrphanedProducts(500);

        return array(
            'success' => true,
            'orphans' => $orphans,
            'notice' => 'Orphans are computed from the last Amazon-side sync. '
                . 'Run "Sync Amazon to PS" (Products tab) first for an up-to-date list.',
        );
    }

    protected function runPendingOrderAction()
    {
        require_once dirname(__FILE__) . '/classes/AmazonOrderCreator.php';

        $idStaged = (int) Tools::getValue('id_staged');
        $op = trim((string) Tools::getValue('pending_op'));

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `id_amazonmarketplacepro_order` = ' . $idStaged . '
               AND `import_status` = \'pending_stock\''
        );
        if (!$row) {
            return array('success' => false, 'error' => 'Pending order not found.');
        }

        if ($op === 'delete') {
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 SET `import_status` = \'cancelled\', `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
                 WHERE `id_amazonmarketplacepro_order` = ' . $idStaged
            );
            $this->logActivity('info', 'pending_orders', $row['amazon_order_id'] . ' removed from pending orders.');

            return array('success' => true, 'message' => 'Pending order removed.');
        }

        if ($op === 'create') {
            $idCarrier = (int) Configuration::get('AMZPRO_DEFAULT_CARRIER');
            $idOrderState = (int) Configuration::get('AMZPRO_DEFAULT_ORDER_STATE');
            if (!$idOrderState) {
                $idOrderState = (int) Configuration::get('PS_OS_PAYMENT');
            }
            $creator = new AmazonOrderCreator($idCarrier, $idOrderState);
            $result = $creator->createOneOrder($row, true);
            if ($result === false || $result === 'pending_stock') {
                return array('success' => false, 'error' => (string) $creator->getLastError());
            }
            $this->logActivity('info', 'pending_orders',
                $row['amazon_order_id'] . ' force-created as PS order #' . (int) $result . ' despite missing stock.');

            return array('success' => true, 'message' => 'PS order #' . (int) $result . ' created.', 'id_order' => (int) $result);
        }

        return array('success' => false, 'error' => 'Unknown pending order action.');
    }

    protected function runSaveShippingTemplate()
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $name = trim((string) Tools::getValue('template_name'));
        if ($name === '') {
            return array('success' => false, 'error' => 'Template name is required.');
        }
        AmazonListingSettings::saveShippingTemplate(
            Tools::getValue('basis'),
            (float) Tools::getValue('min_value'),
            (float) Tools::getValue('max_value'),
            $name,
            (int) Tools::getValue('id_template')
        );

        return array('success' => true, 'templates' => AmazonListingSettings::getShippingTemplates());
    }

    protected function runDeleteShippingTemplate()
    {
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        AmazonListingSettings::deleteShippingTemplate((int) Tools::getValue('id_template'));

        return array('success' => true, 'templates' => AmazonListingSettings::getShippingTemplates());
    }

    /* ─────────────────── Listing profiles (product type schemas) ─────────────────── */

    protected function runSearchProductTypes()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProductTypeDefinitions.php';

        $defs = new AmazonProductTypeDefinitions($this->buildAmazonClient(), $this->getMarketplaceId());
        $types = $defs->searchProductTypes(Tools::getValue('keywords', ''));
        if ($types === false) {
            return array('success' => false, 'error' => $defs->getLastError());
        }

        return array('success' => true, 'product_types' => $types);
    }

    protected function runLoadProductTypeSchema()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProductTypeDefinitions.php';
        require_once dirname(__FILE__) . '/classes/AmazonProfile.php';

        $productType = trim((string) Tools::getValue('product_type'));
        $defs = new AmazonProductTypeDefinitions($this->buildAmazonClient(), $this->getMarketplaceId());
        $definition = $defs->getDefinition($productType, (bool) Tools::getValue('refresh'));
        if ($definition === false) {
            return array('success' => false, 'error' => $defs->getLastError());
        }

        $required = 0;
        foreach ($definition['attributes'] as $attr) {
            if (!empty($attr['required'])) {
                $required++;
            }
        }

        return array(
            'success' => true,
            'product_type' => $productType,
            'display_name' => $definition['display_name'],
            'attributes' => $definition['attributes'],
            'required_count' => $required,
            'ps_fields' => AmazonProfile::$psFields,
        );
    }

    protected function runSaveProfile()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProfile.php';
        require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';

        $name = trim((string) Tools::getValue('name'));
        $productType = trim((string) Tools::getValue('product_type'));
        if ($name === '' || $productType === '') {
            return array('success' => false, 'error' => $this->l('A profile needs a name and an Amazon product type.'));
        }

        $attributes = json_decode((string) Tools::getValue('attributes'), true);
        if (!is_array($attributes)) {
            $attributes = array();
        }
        $categories = json_decode((string) Tools::getValue('categories'), true);
        if (!is_array($categories)) {
            $categories = array();
        }

        $raw = trim((string) Tools::getValue('raw_attributes_json'));
        if ($raw !== '') {
            json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return array('success' => false, 'error' => $this->l('Extra attributes must be valid JSON.'));
            }
        }

        $id = AmazonProfile::save(array(
            'id' => (int) Tools::getValue('id_profile'),
            'name' => $name,
            'product_type' => $productType,
            'marketplace_id' => $this->getMarketplaceId(),
            'browse_nodes' => trim((string) Tools::getValue('browse_nodes')),
            'is_variation' => (int) Tools::getValue('is_variation'),
            'variation_attributes' => trim((string) Tools::getValue('variation_attributes')),
            'attributes' => $attributes,
            'raw_attributes_json' => $raw,
            'latency' => Tools::getValue('latency'),
            'shipping_template' => trim((string) Tools::getValue('shipping_template')),
            'price_markup' => trim((string) Tools::getValue('price_markup')),
            'gtin_exemption' => (int) Tools::getValue('gtin_exemption'),
            'categories' => $categories,
        ));
        if (!$id) {
            return array('success' => false, 'error' => $this->l('Could not save the profile.'));
        }

        // Products in the bound categories need resending with the new shape.
        $queued = 0;
        foreach ($categories as $idCategory) {
            $queued += AmazonListingSettings::enqueueEntityProducts(
                AmazonListingSettings::ENTITY_CATEGORY, (int) $idCategory, 'profile changed'
            );
        }

        $this->logActivity('info', 'profiles', 'Saved profile "' . $name . '" (' . $productType . '), queued ' . $queued . ' product(s).');

        return array(
            'success' => true,
            'id_profile' => $id,
            'queued' => $queued,
            'profiles' => AmazonProfile::getAll($this->getMarketplaceId()),
        );
    }

    protected function runGetProfile()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProfile.php';

        $profile = AmazonProfile::get((int) Tools::getValue('id_profile'));
        if ($profile === false) {
            return array('success' => false, 'error' => $this->l('Profile not found.'));
        }

        return array('success' => true, 'profile' => $profile);
    }

    protected function runDeleteProfile()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProfile.php';

        AmazonProfile::delete((int) Tools::getValue('id_profile'));

        return array('success' => true, 'profiles' => AmazonProfile::getAll($this->getMarketplaceId()));
    }

    /* ─────────────────── Tools: references, deletions ─────────────────── */

    protected function runFetchBuyerMessages()
    {
        require_once dirname(__FILE__) . '/classes/AmazonBuyerInbox.php';

        $inbox = new AmazonBuyerInbox();
        $summary = $inbox->fetchNewMessages(50);
        if ($summary === false) {
            return array('success' => false, 'error' => $inbox->getLastError());
        }

        $this->logActivity('info', 'buyer_inbox',
            $summary['scanned'] . ' message(s) scanned, ' . $summary['filed'] . ' filed into Customer Service.');

        return array('success' => true, 'summary' => $summary, 'notices' => $inbox->getNotices());
    }

    protected function runUpdateFromAmazon()
    {
        require_once dirname(__FILE__) . '/classes/AmazonCatalogImporter.php';

        $operations = json_decode((string) Tools::getValue('operations'), true);
        if (!is_array($operations) || empty($operations)) {
            return array('success' => false, 'error' => $this->l('Pick at least one thing to update.'));
        }

        $importer = new AmazonCatalogImporter();
        $summary = $importer->updateFromAmazon($operations, 500);
        if ($importer->getLastError()) {
            return array('success' => false, 'error' => $importer->getLastError());
        }

        $this->logActivity('info', 'catalog_update',
            'Updated from Amazon: ' . $summary['content'] . ' content, ' . $summary['price'] . ' price, '
            . $summary['quantity'] . ' stock, ' . $summary['hidden'] . ' hidden, '
            . $summary['features'] . ' feature(s).');

        return array('success' => true, 'summary' => $summary, 'notices' => $importer->getNotices());
    }

    protected function runAuditCatalogue()
    {
        require_once dirname(__FILE__) . '/classes/AmazonReferenceTool.php';

        return array('success' => true, 'audit' => AmazonReferenceTool::auditCatalogue());
    }

    protected function runImportReferences()
    {
        require_once dirname(__FILE__) . '/classes/AmazonReferenceTool.php';

        if (!isset($_FILES['reference_file']) || !is_uploaded_file($_FILES['reference_file']['tmp_name'])) {
            return array('success' => false, 'error' => $this->l('No file was uploaded.'));
        }
        if ($_FILES['reference_file']['size'] > 10485760) {
            return array('success' => false, 'error' => $this->l('The file is larger than 10 MB.'));
        }

        $content = file_get_contents($_FILES['reference_file']['tmp_name']);
        if ($content === false) {
            return array('success' => false, 'error' => $this->l('The uploaded file could not be read.'));
        }

        $summary = AmazonReferenceTool::importCsv($content);
        $this->logActivity('info', 'reference_tool',
            'Reference import: ' . $summary['updated'] . ' updated, ' . $summary['skipped'] . ' skipped, '
            . count($summary['errors']) . ' error(s).');

        return array('success' => true, 'summary' => $summary);
    }

    protected function runListDeletions()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $sync = new AmazonProductSync(
            $this->buildAmazonClient(), $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID')
        );

        return array('success' => true, 'candidates' => $sync->listDeletionCandidates(500));
    }

    protected function runDeleteListings()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $skus = json_decode((string) Tools::getValue('skus'), true);
        if (!is_array($skus) || empty($skus)) {
            return array('success' => false, 'error' => $this->l('Select at least one listing to delete.'));
        }

        $sync = new AmazonProductSync(
            $this->buildAmazonClient(), $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID')
        );
        $sync->setMock($this->useMock());
        $summary = $sync->deleteFromAmazon($skus);

        $this->logActivity(
            $summary['failed'] > 0 ? 'warning' : 'info',
            'listing_deletion',
            $summary['deleted'] . ' listing(s) deleted from Amazon, ' . $summary['failed'] . ' failed.'
        );

        return array('success' => true, 'summary' => $summary, 'error' => $sync->getLastError());
    }

    /* ─────────────────── Feature: Connection Test ─────────────────── */

    protected function runAmazonConnectionTest()
    {
        $result = array(
            'success'    => false,
            'lines'      => array(),
            'error'      => null,
            'error_type' => null,
            'hint'       => null,
        );

        $clientId = Configuration::get('AMZPRO_CLIENT_ID');
        if (!$clientId) {
            $result['error'] = 'Amazon credentials not configured. Go to the Settings tab and enter your SP-API credentials.';
            return $result;
        }

        $client = $this->buildAmazonClient();

        $result['lines'][] = 'Requesting LWA access token...';
        if (!$client->authenticate()) {
            return $this->amazonTestError($result, $client->getLastError());
        }
        $result['lines'][] = 'Access token obtained.';

        $result['lines'][] = 'Calling getOrders (' . ($this->isProduction() ? 'production' : 'sandbox') . ')...';
        $createdAfter = $this->isProduction()
            ? gmdate('Y-m-d\TH:i:s\Z', strtotime('-7 days'))
            : 'TEST_CASE_200';

        $resp = $client->request('GET', '/orders/v0/orders', array(
            'MarketplaceIds' => $this->getMarketplaceId(),
            'CreatedAfter'   => $createdAfter,
        ));

        if ($resp === false) {
            return $this->amazonTestError($result, $client->getLastError());
        }
        if ($resp['status'] >= 400) {
            $body = is_array($resp['body']) ? json_encode($resp['body']) : $resp['body'];
            return $this->amazonTestError($result, 'HTTP ' . $resp['status'] . ': ' . $body);
        }

        $orders = array();
        if (is_array($resp['body']) && isset($resp['body']['payload']['Orders'])) {
            $orders = $resp['body']['payload']['Orders'];
        }

        $result['lines'][] = 'API call succeeded.';
        $result['lines'][] = 'Orders returned: ' . count($orders);

        if (!empty($orders)) {
            $first = $orders[0];
            $result['lines'][] = 'Sample order ID: ' . (isset($first['AmazonOrderId']) ? $first['AmazonOrderId'] : 'n/a');
            $result['lines'][] = 'Status: ' . (isset($first['OrderStatus']) ? $first['OrderStatus'] : 'n/a');
            $result['lines'][] = 'Channel: ' . (isset($first['FulfillmentChannel']) ? $first['FulfillmentChannel'] : 'n/a');
        }

        $result['lines'][] = 'SUCCESS - credentials and connection are working.';
        $result['success'] = true;

        $this->logActivity('info', 'connection_test', 'Connection test passed.');
        return $result;
    }

    private function amazonTestError($result, $message)
    {
        $message = (string) $message;
        $result['success'] = false;
        $result['error'] = $message;
        $result['error_type'] = 'AmazonSpApiError';

        if (strpos($message, 'invalid_grant') !== false) {
            $result['hint'] = 'Refresh token is wrong/expired, or doesn\'t match this client.';
        } elseif (strpos($message, 'invalid_client') !== false) {
            $result['hint'] = 'Client ID or Client Secret is incorrect.';
        } elseif (strpos($message, '403') !== false || stripos($message, 'Unauthorized') !== false) {
            // A 403 on EVERY endpoint (rather than one) usually means the
            // seller account itself cannot serve API data — that outranks
            // app roles as an explanation, and is the easiest thing to check.
            $result['hint'] = 'The token is valid but Amazon denied access. Check, in this order: '
                . '1) the seller account is active and on a Professional selling plan '
                . '(Seller Central > Settings > Account Info); '
                . '2) the LWA client id, secret and refresh token all belong to the SAME registered app; '
                . '3) the app has the required roles AND the seller re-authorized after they were added.';
        } elseif (stripos($message, 'SSL') !== false || stripos($message, 'certificate') !== false) {
            $result['hint'] = 'cURL cannot verify SSL. Drop a cacert.pem into the module\'s classes/ folder.';
        }

        $this->logActivity('error', 'connection_test', $message);
        return $result;
    }

    /* ─────────────────── Feature: Order Import ─────────────────── */

    protected function runAmazonOrderImport()
    {
        $result = array(
            'success' => false,
            'error'   => null,
            'summary' => null,
            'orders'  => array(),
        );

        require_once dirname(__FILE__) . '/classes/AmazonOrderImporter.php';

        $client = $this->buildAmazonClient();
        $importer = new AmazonOrderImporter($client, $this->getMarketplaceId());

        $createdAfter = $this->isProduction()
            ? AmazonOrderImporter::configuredCreatedAfter()
            : 'TEST_CASE_200';
        $summary = $importer->importNewOrders($createdAfter);

        if ($summary === false) {
            $result['error'] = $importer->getLastError();
            $this->logActivity('error', 'order_import', $result['error']);
        } else {
            $result['success'] = true;
            $result['summary'] = $summary;
            $this->logActivity('info', 'order_import',
                'Fetched ' . $summary['fetched'] . ', imported ' . $summary['imported_new'] . ' new.'
            );
        }

        $result['orders'] = $importer->listStagedOrders();
        return $result;
    }

    /* ─────────────────── Feature: Create PS Orders ─────────────────── */

    protected function runCreatePsOrders()
    {
        $result = array(
            'success' => false,
            'error'   => null,
            'summary' => null,
            'notices' => array(),
        );

        require_once dirname(__FILE__) . '/classes/AmazonOrderCreator.php';

        $idCarrier = (int) Configuration::get('AMZPRO_DEFAULT_CARRIER');
        $idOrderState = (int) Configuration::get('AMZPRO_DEFAULT_ORDER_STATE');
        if (!$idOrderState) {
            $idOrderState = (int) Configuration::get('PS_OS_PAYMENT');
        }

        $creator = new AmazonOrderCreator($idCarrier, $idOrderState);
        $summary = $creator->createAllPending();

        $result['success'] = true;
        $result['summary'] = $summary;
        $result['notices'] = $creator->getNotices();

        if ($summary['created'] > 0) {
            $this->logActivity('info', 'create_orders',
                'Created ' . $summary['created'] . ' PS order(s).'
            );
        }
        if ($summary['failed'] > 0) {
            $this->logActivity('error', 'create_orders',
                $summary['failed'] . ' order(s) failed: ' . implode('; ', $summary['errors'])
            );
        }

        return $result;
    }

    /* ─────────────────── Feature: Buyer Messaging ─────────────────── */

    protected function runGetMessagingActions()
    {
        $result = array('success' => false, 'error' => null, 'actions' => array());

        $orderId = trim((string) Tools::getValue('amazon_order_id'));
        if ($orderId === '') {
            $result['error'] = $this->l('Amazon order id is required.');
            return $result;
        }

        require_once dirname(__FILE__) . '/classes/AmazonBuyerMessaging.php';

        $messaging = new AmazonBuyerMessaging($this->buildAmazonClient(), $this->getMarketplaceId());
        $messaging->setMock($this->useMock());

        $actions = $messaging->getAllowedActions($orderId);
        if ($actions === false) {
            $result['error'] = $messaging->getLastError();
            return $result;
        }

        $result['success'] = true;
        $result['actions'] = $actions;
        if ($this->useMock()) {
            $result['notice'] = 'MOCK MODE: sample message types — no real Amazon call was made.';
        }

        return $result;
    }

    protected function runSendBuyerMessage()
    {
        $result = array('success' => false, 'error' => null);

        $orderId = trim((string) Tools::getValue('amazon_order_id'));
        $actionName = trim((string) Tools::getValue('action_name'));
        $text = trim((string) Tools::getValue('message_text'));

        require_once dirname(__FILE__) . '/classes/AmazonBuyerMessaging.php';

        $messaging = new AmazonBuyerMessaging($this->buildAmazonClient(), $this->getMarketplaceId());
        $messaging->setMock($this->useMock());

        if (!$messaging->sendMessage($orderId, $actionName, $text)) {
            $result['error'] = $messaging->getLastError();
            return $result;
        }

        $result['success'] = true;
        if ($this->useMock()) {
            $result['notice'] = 'MOCK MODE: message simulated — no real Amazon call was made.';
        }

        return $result;
    }

    protected function runRequestReview()
    {
        $result = array('success' => false, 'error' => null);

        $orderId = trim((string) Tools::getValue('amazon_order_id'));

        require_once dirname(__FILE__) . '/classes/AmazonReviewRequester.php';

        $requester = new AmazonReviewRequester($this->buildAmazonClient(), $this->getMarketplaceId());
        $requester->setMock($this->useMock());

        if (!$requester->requestReview($orderId)) {
            $result['error'] = $requester->getLastError();
            return $result;
        }

        $result['success'] = true;
        if ($this->useMock()) {
            $result['notice'] = 'MOCK MODE: review request simulated — no real Amazon call was made.';
        }

        return $result;
    }

    /* ─────────────────── Feature: Catalog (ASIN) Matching ─────────────────── */

    protected function runMatchCatalog()
    {
        $result = array('success' => false, 'error' => null, 'summary' => null);

        require_once dirname(__FILE__) . '/classes/AmazonCatalogMatcher.php';

        $matcher = new AmazonCatalogMatcher($this->buildAmazonClient(), $this->getMarketplaceId());
        $matcher->setMock($this->useMock());

        $summary = $matcher->matchStagedProducts(100);
        if ($summary === false) {
            $result['error'] = $matcher->getLastError();
            $this->logActivity('error', 'catalog_match', $result['error']);
            return $result;
        }

        $result['success'] = true;
        $result['summary'] = $summary;
        $this->logActivity('info', 'catalog_match',
            'Checked ' . $summary['candidates'] . ' product(s), matched ' . $summary['matched'] . ' ASIN(s).'
        );
        if ($this->useMock()) {
            $result['notice'] = 'MOCK MODE: sample ASINs — no real Amazon call was made.';
        }

        return $result;
    }

    /* ─────────────────── Feature: Catalog Import (Amazon -> PS) ─────────────────── */

    protected function runImportCatalog()
    {
        $result = array('success' => false, 'error' => null, 'summary' => null, 'notices' => array());

        require_once dirname(__FILE__) . '/classes/AmazonCatalogImporter.php';

        $importer = new AmazonCatalogImporter();
        $summary = $importer->importAmazonOnlyProducts((int) Tools::getValue('id_category', 0), 25);

        $result['success'] = true;
        $result['summary'] = $summary;
        $result['notices'] = $importer->getNotices();

        $this->logActivity('info', 'catalog_import',
            'Created ' . $summary['created'] . ' PS product(s) from ' . $summary['candidates'] . ' Amazon-only listing(s).'
        );

        return $result;
    }

    /* ─────────────────── Feature: Bulk Feeds ─────────────────── */

    protected function runSubmitFeed()
    {
        $result = array('success' => false, 'error' => null);

        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';
        require_once dirname(__FILE__) . '/classes/AmazonFeedManager.php';

        $client = $this->buildAmazonClient();
        $sellerId = Configuration::get('AMZPRO_SELLER_ID');

        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), $sellerId);
        $sync->setMock($this->useMock());
        $collected = $sync->collectFeedMessages(500);

        $feeds = new AmazonFeedManager($client, $this->getMarketplaceId(), $sellerId);
        $feeds->setMock($this->useMock());

        $feedId = $feeds->submitListingsFeed($collected['messages']);
        if ($feedId === false) {
            $result['error'] = $feeds->getLastError();
            $result['skipped'] = $collected['skipped'];
            return $result;
        }

        $this->logActivity('info', 'feed_submit',
            'Feed ' . $feedId . ' submitted with ' . count($collected['messages']) . ' message(s).'
        );

        // Products included in the feed leave the change queue.
        if (!empty($collected['id_products'])) {
            require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';
            AmazonListingSettings::deactivateQueued($collected['id_products']);
        }

        $result['success'] = true;
        $result['feed_id'] = $feedId;
        $result['messages'] = count($collected['messages']);
        $result['skipped'] = $collected['skipped'];
        $result['feeds'] = $feeds->listFeeds();
        if ($this->useMock()) {
            $result['notice'] = 'MOCK MODE: feed simulated — no real Amazon call was made.';
        }

        return $result;
    }

    protected function runPollFeeds()
    {
        $result = array('success' => false, 'error' => null);

        require_once dirname(__FILE__) . '/classes/AmazonFeedManager.php';

        $feeds = new AmazonFeedManager(
            $this->buildAmazonClient(),
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );
        $feeds->setMock($this->useMock());

        $result['success'] = true;
        $result['summary'] = $feeds->pollPendingFeeds();
        $result['feeds'] = $feeds->listFeeds();

        return $result;
    }
    /* ─────────────────── Feature: Product Sync ─────────────────── */

    protected function runProductSync($direction)
    {
        $result = array(
            'success'  => false,
            'error'    => null,
            'summary'  => null,
            'notices'  => array(),
            'products' => array(),
        );

        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $client = $this->buildAmazonClient();
        $sync = new AmazonProductSync(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );
        $sync->setMock($this->useMock());

        if ($direction === 'amazon') {
            $summary = $sync->syncAmazonSide();
        } else {
            $summary = $sync->syncPrestashopSide();
        }

        $result['success'] = true;
        $result['summary'] = $summary;
        $result['notices'] = $sync->getNotices();
        $result['products'] = $sync->listStaged();

        $this->logActivity('info', 'product_sync_' . $direction,
            'Total: ' . (isset($summary['total']) ? $summary['total'] : 0)
        );

        return $result;
    }

    /* ─────────────────── Feature: List Amazon Products ─────────────────── */

    protected function runListAmazonProducts()
    {
        $result = array(
            'success'  => false,
            'error'    => null,
            'notices'  => array(),
            'products' => array(),
        );

        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $client = $this->buildAmazonClient();
        $sync = new AmazonProductSync(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );
        $sync->setMock($this->useMock());

        $products = $sync->listAmazonProducts(20);
        $error = $sync->getLastError();

        $result['success'] = ($error === null);
        $result['error'] = $error;
        $result['notices'] = $sync->getNotices();
        $result['products'] = $products;

        return $result;
    }

    /* ─────────────────── Feature: Push to Amazon ─────────────────── */

    protected function runPushProducts()
    {
        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $client = $this->buildAmazonClient();
        $sync = new AmazonProductSync(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );
        $sync->setMock($this->useMock());

        $summary = $sync->pushToAmazon(25);

        $this->logActivity('info', 'push_products',
            'Pushed ' . $summary['pushed'] . ', failed ' . $summary['failed']
        );

        return array(
            'success' => true,
            'summary' => $summary,
            'notices' => $sync->getNotices(),
        );
    }

    /* ─────────────────── Feature: Returns/Refunds ─────────────────── */

    protected function runImportReturns()
    {
        $result = array(
            'success' => false,
            'error'   => null,
            'summary' => null,
            'notices' => array(),
            'returns' => array(),
        );

        require_once dirname(__FILE__) . '/classes/AmazonReturnManager.php';

        $client = $this->buildAmazonClient();
        $manager = new AmazonReturnManager(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );

        $createdAfter = $this->isProduction()
            ? gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days'))
            : 'TEST_CASE_200';

        $summary = $manager->importReturns($createdAfter);

        $result['success'] = ($manager->getLastError() === null);
        $result['error'] = $manager->getLastError();
        $result['summary'] = $summary;
        $result['notices'] = $manager->getNotices();
        $result['returns'] = $manager->listReturns(50);

        if ($summary['new_returns'] > 0 || $summary['new_cancellations'] > 0) {
            $this->logActivity('info', 'import_returns',
                'Returns: ' . $summary['new_returns'] . ', cancellations: ' . $summary['new_cancellations']
            );
        }

        return $result;
    }

    protected function runProcessReturns()
    {
        $result = array(
            'success' => false,
            'error'   => null,
            'summary' => null,
            'notices' => array(),
        );

        require_once dirname(__FILE__) . '/classes/AmazonReturnManager.php';

        $client = $this->buildAmazonClient();
        $manager = new AmazonReturnManager(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );

        $summary = $manager->processReturns();

        $result['success'] = true;
        $result['summary'] = $summary;
        $result['notices'] = $manager->getNotices();

        if ($summary['processed'] > 0) {
            $this->logActivity('info', 'process_returns',
                'Processed ' . $summary['processed'] . ' return(s).'
            );
        }

        return $result;
    }

    /* ─────────────────── Feature: Category Mapping ─────────────────── */

    protected function runSaveCategoryMap()
    {
        $idCategory = (int) Tools::getValue('id_category');
        $productType = Tools::getValue('amazon_product_type', '');
        $browseNode = Tools::getValue('amazon_browse_node', '');
        $marketplaceId = $this->getMarketplaceId();

        if (!$idCategory || !$productType) {
            return array('success' => false, 'error' => 'Category and Amazon Product Type are required.');
        }

        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $client = $this->buildAmazonClient();
        $sync = new AmazonProductSync($client, $marketplaceId, Configuration::get('AMZPRO_SELLER_ID'));
        $attributesJson = trim((string) Tools::getValue('attributes_json', ''));

        if (!$sync->saveCategoryMapping($idCategory, $productType, $browseNode, $marketplaceId, $attributesJson)) {
            return array('success' => false, 'error' => $sync->getLastError()
                ? $sync->getLastError() : 'Failed to save mapping.');
        }

        return array(
            'success' => true,
            'mappings' => $sync->getCategoryMappings(),
        );
    }

    protected function runDeleteCategoryMap()
    {
        $idMap = (int) Tools::getValue('id_category_map');
        if (!$idMap) {
            return array('success' => false, 'error' => 'No mapping ID provided.');
        }

        require_once dirname(__FILE__) . '/classes/AmazonProductSync.php';

        $client = $this->buildAmazonClient();
        $sync = new AmazonProductSync($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $sync->deleteCategoryMapping($idMap);

        return array(
            'success' => true,
            'mappings' => $sync->getCategoryMappings(),
        );
    }

    /* ─────────────────── Feature: FBA ─────────────────── */

    protected function runSyncFbaInventory()
    {
        require_once dirname(__FILE__) . '/classes/AmazonFbaManager.php';
        $client = $this->buildAmazonClient();
        $fba = new AmazonFbaManager($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $summary = $fba->syncFbaInventory();
        $this->logActivity('info', 'fba_inventory_sync',
            'Fetched: ' . $summary['fetched'] . ', updated: ' . $summary['updated'] . ', new: ' . $summary['new']
        );
        return array('success' => ($fba->getLastError() === null), 'summary' => $summary,
            'error' => $fba->getLastError(), 'notices' => $fba->getNotices(),
            'inventory' => $fba->listFbaInventory());
    }

    protected function runCreateMcfOrder()
    {
        require_once dirname(__FILE__) . '/classes/AmazonFbaManager.php';
        $client = $this->buildAmazonClient();
        $fba = new AmazonFbaManager($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $idOrder = (int) Tools::getValue('id_order');
        $result = $fba->createMcfOrder($idOrder);
        $this->logActivity($result['success'] ? 'info' : 'error', 'mcf_order',
            'PS order #' . $idOrder . ': ' . ($result['success'] ? 'MCF created' : $result['error'])
        );
        return $result;
    }

    protected function runSyncFbaStockToPs()
    {
        require_once dirname(__FILE__) . '/classes/AmazonFbaManager.php';
        $client = $this->buildAmazonClient();
        $fba = new AmazonFbaManager($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $summary = $fba->syncFbaStockToPs();
        $this->logActivity('info', 'fba_stock_to_ps', 'Updated: ' . $summary['updated']);
        return array('success' => true, 'summary' => $summary);
    }

    /* ─────────────────── Feature: Repricing ─────────────────── */

    protected function runFetchPricing()
    {
        require_once dirname(__FILE__) . '/classes/AmazonRepricingEngine.php';
        $client = $this->buildAmazonClient();
        $engine = new AmazonRepricingEngine($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $summary = $engine->fetchCompetitivePricing();
        $this->logActivity('info', 'fetch_pricing',
            'Checked: ' . $summary['checked'] . ', BuyBox wins: ' . $summary['buybox_wins']
        );
        return array('success' => ($engine->getLastError() === null), 'summary' => $summary,
            'error' => $engine->getLastError(), 'notices' => $engine->getNotices(),
            'prices' => $engine->listCompetitivePrices());
    }

    protected function runApplyPricingRules()
    {
        require_once dirname(__FILE__) . '/classes/AmazonRepricingEngine.php';
        $client = $this->buildAmazonClient();
        $engine = new AmazonRepricingEngine($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $summary = $engine->applyPricingRules();
        return array('success' => true, 'summary' => $summary, 'notices' => $engine->getNotices(),
            'prices' => $engine->listCompetitivePrices());
    }

    protected function runPushPrices()
    {
        require_once dirname(__FILE__) . '/classes/AmazonRepricingEngine.php';
        $client = $this->buildAmazonClient();
        $engine = new AmazonRepricingEngine($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $summary = $engine->pushSuggestedPrices(25);
        $this->logActivity('info', 'push_prices', 'Pushed: ' . $summary['pushed'] . ', failed: ' . $summary['failed']);
        return array('success' => true, 'summary' => $summary, 'notices' => $engine->getNotices());
    }

    protected function runSavePricingRule()
    {
        require_once dirname(__FILE__) . '/classes/AmazonRepricingEngine.php';
        $client = $this->buildAmazonClient();
        $engine = new AmazonRepricingEngine($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $data = array(
            'id' => (int) Tools::getValue('rule_id', 0),
            'name' => Tools::getValue('rule_name', ''),
            'rule_type' => Tools::getValue('rule_type', 'match_lowest'),
            'price_adjustment' => (float) Tools::getValue('price_adjustment', 0),
            'adjustment_type' => Tools::getValue('adjustment_type', 'percentage'),
            'min_price' => (float) Tools::getValue('min_price', 0),
            'max_price' => (float) Tools::getValue('max_price', 0),
            'target_buybox' => (int) Tools::getValue('target_buybox', 0),
            'id_category' => (int) Tools::getValue('rule_category', 0),
            'marketplace_id' => $this->getMarketplaceId(),
            'active' => (int) Tools::getValue('rule_active', 1),
        );
        if (!$data['name']) {
            return array('success' => false, 'error' => 'Rule name is required.');
        }
        $engine->savePricingRule($data);
        return array('success' => true, 'rules' => $engine->listPricingRules());
    }

    protected function runDeletePricingRule()
    {
        require_once dirname(__FILE__) . '/classes/AmazonRepricingEngine.php';
        $client = $this->buildAmazonClient();
        $engine = new AmazonRepricingEngine($client, $this->getMarketplaceId(), Configuration::get('AMZPRO_SELLER_ID'));
        $id = (int) Tools::getValue('rule_id');
        $engine->deletePricingRule($id);
        return array('success' => true, 'rules' => $engine->listPricingRules());
    }

    /* ─────────────────── Feature: Fees ─────────────────── */

    protected function runFetchFees()
    {
        require_once dirname(__FILE__) . '/classes/AmazonFeesTracker.php';
        $client = $this->buildAmazonClient();
        $tracker = new AmazonFeesTracker($client, $this->getMarketplaceId());
        $summary = $tracker->fetchOrderFees(50);
        $this->logActivity('info', 'fetch_fees',
            'Checked: ' . $summary['checked'] . ', updated: ' . $summary['updated']
        );
        return array('success' => ($tracker->getLastError() === null), 'summary' => $summary,
            'error' => $tracker->getLastError(), 'notices' => $tracker->getNotices(),
            'fee_summary' => $tracker->getFeeSummary());
    }

    /* ─────────────────── Feature: Reports ─────────────────── */

    protected function runRequestReport()
    {
        require_once dirname(__FILE__) . '/classes/AmazonReportManager.php';
        $client = $this->buildAmazonClient();
        $manager = new AmazonReportManager($client, $this->getMarketplaceId());
        $reportType = Tools::getValue('report_type', 'GET_MERCHANT_LISTINGS_ALL_DATA');
        $result = $manager->requestReport($reportType);
        $this->logActivity('info', 'request_report', 'Type: ' . $reportType . ', ID: '
            . (isset($result['report_id']) ? $result['report_id'] : 'n/a'));
        $result['reports'] = $manager->listReports();
        return $result;
    }

    protected function runPollReports()
    {
        require_once dirname(__FILE__) . '/classes/AmazonReportManager.php';
        $client = $this->buildAmazonClient();
        $manager = new AmazonReportManager($client, $this->getMarketplaceId());
        $summary = $manager->pollPendingReports();
        return array('success' => true, 'summary' => $summary, 'notices' => $manager->getNotices(),
            'reports' => $manager->listReports());
    }

    /* ─────────────────── Feature: Multi-Marketplace ─────────────────── */

    protected function runSaveMarketplace()
    {
        require_once dirname(__FILE__) . '/classes/AmazonMultiMarketplace.php';
        $mm = new AmazonMultiMarketplace();
        $data = array(
            'marketplace_id' => Tools::getValue('mp_marketplace_id', ''),
            'client_id' => Tools::getValue('mp_client_id', ''),
            'client_secret' => Tools::getValue('mp_client_secret', ''),
            'refresh_token' => Tools::getValue('mp_refresh_token', ''),
            'seller_id' => Tools::getValue('mp_seller_id', ''),
            'active' => (int) Tools::getValue('mp_active', 1),
            'default_carrier' => (int) Tools::getValue('mp_carrier', 0),
            'default_order_state' => (int) Tools::getValue('mp_order_state', 0),
            'sync_orders' => (int) Tools::getValue('mp_sync_orders', 1),
            'sync_products' => (int) Tools::getValue('mp_sync_products', 1),
            'sync_stock' => (int) Tools::getValue('mp_sync_stock', 1),
        );
        $success = $mm->saveMarketplaceConfig($data);
        return array('success' => $success, 'error' => $mm->getLastError(),
            'configs' => $mm->getMarketplaceConfigs());
    }

    protected function runDeleteMarketplace()
    {
        require_once dirname(__FILE__) . '/classes/AmazonMultiMarketplace.php';
        $mm = new AmazonMultiMarketplace();
        $mpId = Tools::getValue('mp_marketplace_id', '');
        $mm->deleteMarketplaceConfig($mpId);
        return array('success' => true, 'configs' => $mm->getMarketplaceConfigs());
    }

    /* ─────────────────── Feature: Promotions ─────────────────── */

    protected function runImportPromotions()
    {
        require_once dirname(__FILE__) . '/classes/AmazonPromotionSync.php';
        $client = $this->buildAmazonClient();
        $sync = new AmazonPromotionSync($client, $this->getMarketplaceId());
        $summary = $sync->importPromotionsFromOrders();
        $this->logActivity('info', 'import_promotions',
            'Found: ' . $summary['promotions_found'] . ', new: ' . $summary['promotions_new']
        );
        return array('success' => true, 'summary' => $summary, 'notices' => $sync->getNotices(),
            'promotions' => $sync->listPromotions(), 'stats' => $sync->getPromotionStats());
    }

    protected function runExportPromotions()
    {
        require_once dirname(__FILE__) . '/classes/AmazonPromotionSync.php';
        $client = $this->buildAmazonClient();
        $sync = new AmazonPromotionSync($client, $this->getMarketplaceId());
        $summary = $sync->exportPsCartRules();
        return array('success' => true, 'summary' => $summary, 'notices' => $sync->getNotices(),
            'promotions' => $sync->listPromotions(), 'stats' => $sync->getPromotionStats());
    }

    protected function runCreatePromoCartRules()
    {
        require_once dirname(__FILE__) . '/classes/AmazonPromotionSync.php';
        $client = $this->buildAmazonClient();
        $sync = new AmazonPromotionSync($client, $this->getMarketplaceId());
        $summary = $sync->createPsCartRules();
        return array('success' => true, 'summary' => $summary, 'notices' => $sync->getNotices(),
            'promotions' => $sync->listPromotions());
    }

    /* ─────────────────── Data getters for template ─────────────────── */

    private function getFbaInventory()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_fba_inventory`
                ORDER BY `seller_sku` ASC LIMIT 100';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getCompetitivePrices()
    {
        $sql = 'SELECT cp.*, p.`ps_name`
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
                LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p ON (p.`seller_sku` = cp.`seller_sku`)
                ORDER BY cp.`is_buybox_winner` ASC, cp.`seller_sku` ASC LIMIT 100';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getPricingRules()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule` ORDER BY `name` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getFeeSummary()
    {
        $sql = 'SELECT `fee_type`, SUM(ABS(`fee_amount`)) AS total_amount, COUNT(*) AS count, `currency`
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order_fee`
                GROUP BY `fee_type`, `currency` ORDER BY total_amount DESC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getReports()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`
                ORDER BY `date_add` DESC LIMIT 50';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getMarketplaceConfigs()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_marketplace_config`
                ORDER BY `marketplace_name` ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getPromotions()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`
                ORDER BY `date_add` DESC LIMIT 100';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function getPromotionStats()
    {
        $row = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN `sync_direction` = \'amazon_to_ps\' THEN 1 ELSE 0 END) AS from_amazon,
                    SUM(CASE WHEN `sync_direction` = \'ps_to_amazon\' THEN 1 ELSE 0 END) AS from_ps,
                    SUM(`discount_value`) AS total_discount,
                    SUM(CASE WHEN `id_cart_rule` > 0 THEN 1 ELSE 0 END) AS with_cart_rule
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_promotion`'
        );
        if (!$row) {
            return array('total' => 0, 'from_amazon' => 0, 'from_ps' => 0, 'total_discount' => 0, 'with_cart_rule' => 0);
        }
        return $row;
    }

    /* ─────────────────── Hooks ─────────────────── */

    /**
     * Front office page view: give the schedule a chance to run.
     *
     * This is what lets a shop with no crontab still automate. Nothing slow
     * happens here - armTrafficTrigger() only decides whether to register a
     * shutdown function, so the visitor is never kept waiting on Amazon.
     */
    public function hookDisplayHeader($params)
    {
        require_once dirname(__FILE__) . '/classes/AmazonScheduler.php';
        AmazonScheduler::armTrafficTrigger();

        return '';
    }

    /*
     * There is deliberately no displayBackOfficeHeader hook.
     *
     * It used to add views/css/back.css and views/js/back.js, neither of
     * which has ever existed, and it could not have loaded them anyway: it
     * gated on Tools::getValue('configure'), which only exists on 1.6. From
     * 1.7 the module name is a path segment - .../action/configure/<module> -
     * so the condition was false on every back office after 1.6.
     *
     * The configuration page keeps its CSS in configure.tpl instead, which is
     * not merely convenient. That stylesheet has to win against the host
     * theme, and a template that ships its own styles cannot be defeated by a
     * hook that never fires, a stale Media cache, or a version that routes
     * differently. If assets are ever moved to files, the loading has to be
     * verified on 1.6 AND 1.7+, because those two disagree about how this
     * page is even addressed.
     */

    /**
     * When a product is saved in PrestaShop, push updated price/stock to Amazon.
     */
    /**
     * Amazon panel on the PrestaShop product page (1.6 and 1.7+).
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        require_once dirname(__FILE__) . '/classes/AmazonProductOverride.php';

        $idProduct = 0;
        if (isset($params['id_product'])) {
            $idProduct = (int) $params['id_product'];
        } elseif (isset($params['product']) && isset($params['product']->id)) {
            $idProduct = (int) $params['product']->id;
        }
        if (!$idProduct) {
            $idProduct = (int) Tools::getValue('id_product');
        }
        if (!$idProduct) {
            return '';
        }

        $this->context->smarty->assign(array(
            'amzpro' => AmazonProductOverride::get($idProduct),
            'amzpro_propagatable' => AmazonProductOverride::$propagatable,
        ));

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    /**
     * Persist the Amazon panel's fields when the product is saved.
     *
     * Guarded by a marker input so programmatic product saves (imports, other
     * modules) never blank the overrides.
     */
    private function saveProductOverrides($idProduct)
    {
        if (!Tools::getIsset('amzpro_tab_present')) {
            return;
        }
        require_once dirname(__FILE__) . '/classes/AmazonProductOverride.php';

        $stockForce = Tools::getValue('amzpro_stock_force', '');
        $data = array(
            'sync' => (int) Tools::getValue('amzpro_sync', 1),
            'sync_price' => Tools::getIsset('amzpro_sync_price') ? 1 : 0,
            'sync_quantity' => Tools::getIsset('amzpro_sync_quantity') ? 1 : 0,
            'force_in_stock' => ($stockForce === 'in') ? 1 : 0,
            'force_out_of_stock' => ($stockForce === 'out') ? 1 : 0,
            'override_price' => (float) Tools::getValue('amzpro_override_price', 0),
            'override_sku' => trim((string) Tools::getValue('amzpro_override_sku', '')),
            'asin' => trim((string) Tools::getValue('amzpro_asin', '')),
            'is_fba' => (int) Tools::getValue('amzpro_is_fba', 0),
            'lead_time' => Tools::getValue('amzpro_lead_time', ''),
            'shipping_template' => trim((string) Tools::getValue('amzpro_shipping_template', '')),
            'browse_node' => trim((string) Tools::getValue('amzpro_browse_node', '')),
            'brand' => trim((string) Tools::getValue('amzpro_brand', '')),
            'condition_type' => trim((string) Tools::getValue('amzpro_condition_type', '')),
            'condition_note' => trim((string) Tools::getValue('amzpro_condition_note', '')),
            'bullet_points' => trim((string) Tools::getValue('amzpro_bullet_points', '')),
            'gpsr_contact' => trim((string) Tools::getValue('amzpro_gpsr_contact', '')),
            'gift_option' => Tools::getIsset('amzpro_gift_option') ? 1 : 0,
            'transparency_code' => trim((string) Tools::getValue('amzpro_transparency_code', '')),
        );

        AmazonProductOverride::save($idProduct, $data);

        $scope = trim((string) Tools::getValue('amzpro_propagate_scope', ''));
        if ($scope !== '') {
            $fields = Tools::getValue('amzpro_propagate_fields');
            $count = AmazonProductOverride::propagate($idProduct, $scope, is_array($fields) ? $fields : array());
            $this->logActivity('info', 'product_overrides',
                'Propagated Amazon settings from product #' . (int) $idProduct . ' to ' . $count . ' product(s) (' . $scope . ').');
        }
    }

    public function hookActionProductSave($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if (!$idProduct) {
            return;
        }

        $this->saveProductOverrides($idProduct);
        $this->enqueueForDeltaSync($idProduct, 'product saved');

        if (!Configuration::get('AMZPRO_SYNC_STOCK_HOOK') || !$this->isProduction()) {
            return;
        }

        $this->pushProductStockToAmazon($idProduct);
    }

    public function hookActionProductUpdate($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if (!$idProduct) {
            return;
        }

        $this->enqueueForDeltaSync($idProduct, 'product updated');

        if (!Configuration::get('AMZPRO_SYNC_STOCK_HOOK') || !$this->isProduction()) {
            return;
        }

        $this->pushProductStockToAmazon($idProduct);
    }

    /**
     * E-mail the PrestaShop invoice PDF to the buyer of an Amazon order.
     *
     * Amazon expects sellers to make an invoice available; this is the
     * "invoice by e-mail" route (VCS upload is the other, see the settings).
     */
    private function sendBuyerInvoice($order)
    {
        if (!Configuration::get('AMZPRO_INVOICE_EMAIL')) {
            return;
        }
        $amazonId = Db::getInstance()->getValue(
            'SELECT `amazon_order_id` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `id_order` = ' . (int) $order->id
        );
        if (!$amazonId) {
            return; // not one of ours
        }

        $customer = new Customer((int) $order->id_customer);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            return;
        }
        // Skip only the placeholder addresses this module invents itself.
        // Amazon's own relay addresses (something@marketplace.amazon.co.uk
        // and friends) look similar but ARE deliverable — Amazon forwards
        // them to the buyer — so match the domain exactly, not as a substring.
        $domain = Tools::strtolower(substr(strrchr($customer->email, '@'), 1));
        if ($domain === 'marketplace.amazon' || $domain === 'marketplace.local') {
            $this->logActivity('warning', 'invoice_email',
                $amazonId . ': invoice not e-mailed — this buyer has a placeholder address '
                . '(the "anonymised e-mail" setting is on, or Amazon supplied no address).');
            return;
        }

        $invoices = $order->getInvoicesCollection();
        if (!count($invoices)) {
            $order->setInvoice(true);
            $invoices = $order->getInvoicesCollection();
        }
        if (!count($invoices)) {
            return;
        }

        try {
            $pdf = new PDF($invoices, PDF::TEMPLATE_INVOICE, $this->context->smarty);
            $content = $pdf->render(false);

            $attachments = array(array(
                'content' => $content,
                'name' => 'invoice-' . $order->reference . '.pdf',
                'mime' => 'application/pdf',
            ));

            // Optional extra document (terms, returns policy) shipped by the merchant.
            $extra = trim((string) Configuration::get('AMZPRO_INVOICE_ATTACHMENT'));
            if ($extra !== '') {
                $path = _PS_MODULE_DIR_ . $this->name . '/docs/' . basename($extra);
                if (file_exists($path)) {
                    $attachments[] = array(
                        'content' => file_get_contents($path),
                        'name' => basename($extra),
                        'mime' => 'application/pdf',
                    );
                }
            }

            Mail::send(
                (int) $order->id_lang,
                'contact',
                $this->l('Your invoice for order') . ' ' . $amazonId,
                array(
                    '{message}' => $this->l('Please find your invoice attached.') . "\n"
                        . $this->l('Amazon order') . ': ' . $amazonId,
                    '{email}' => (string) Configuration::get('PS_SHOP_EMAIL'),
                    '{attached_file}' => '',
                ),
                $customer->email,
                null, null, null,
                $attachments
            );
            $this->logActivity('info', 'invoice_email', $amazonId . ': invoice e-mailed to the buyer.');
        } catch (Exception $e) {
            $this->logActivity('error', 'invoice_email', $amazonId . ': ' . $e->getMessage());
        }
    }

    /**
     * Track a modified product in the change queue so delta exports
     * ("only products updated in the last N hours") pick it up.
     */
    private function enqueueForDeltaSync($idProduct, $reason)
    {
        // Only worth tracking when delta exports are in use.
        if ((int) Configuration::get('AMZPRO_DELTA_HOURS') <= 0) {
            return;
        }
        try {
            require_once dirname(__FILE__) . '/classes/AmazonListingSettings.php';
            AmazonListingSettings::enqueueProduct($idProduct, $reason);
        } catch (Exception $e) {
            // Queueing must never break product saves.
        }
    }

    /**
     * When stock quantity is updated (BO, import, or other module), push to Amazon.
     */
    public function hookActionUpdateQuantity($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if (!$idProduct) {
            return;
        }

        $this->enqueueForDeltaSync($idProduct, 'quantity changed');

        if (!Configuration::get('AMZPRO_SYNC_STOCK_HOOK') || !$this->isProduction()) {
            return;
        }

        $this->pushProductStockToAmazon($idProduct);
    }

    /**
     * When an order status changes:
     * - "Shipped" → send shipment confirmation to Amazon
     * - "Cancelled" → notify Amazon of cancellation
     */
    public function hookActionOrderStatusUpdate($params)
    {
        $newState = isset($params['newOrderStatus']) ? $params['newOrderStatus'] : null;
        $idOrder = isset($params['id_order']) ? (int) $params['id_order'] : 0;

        if (!$newState || !$idOrder) {
            return;
        }

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // Only act on orders this module imported. The staged table is the
        // authority: checking $order->module would also catch orders created
        // by the all-in-one Marketplaces Pro module on the same shop.
        $isOurs = (bool) Db::getInstance()->getValue(
            'SELECT `id_amazonmarketplacepro_order` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `id_order` = ' . $idOrder
        );
        if (!$isOurs) {
            return;
        }

        $shippedStateId = (int) Configuration::get('PS_OS_SHIPPING');
        $cancelledStateId = (int) Configuration::get('PS_OS_CANCELED');

        // Shipment confirmation
        if ((int) $newState->id === $shippedStateId && Configuration::get('AMZPRO_SYNC_ORDER_STATUS_HOOK')) {
            if ($this->isProduction()) {
                $this->confirmShipmentOnAmazon($order);
            }
        }

        // Cancellation
        if ((int) $newState->id === $cancelledStateId && Configuration::get('AMZPRO_SYNC_CANCEL_HOOK')
            && $this->isProduction()) {
            $this->cancelOrderOnAmazon($order);
        }

        // Buyer invoice e-mail on the configured status
        $invoiceState = (int) Configuration::get('AMZPRO_INVOICE_EMAIL_STATE');
        if ($invoiceState > 0 && (int) $newState->id === $invoiceState) {
            $this->sendBuyerInvoice($order);
        }
    }

    /* ─────────────────── Hook helpers ─────────────────── */

    /**
     * Push a single product's current stock/price to Amazon.
     */
    private function pushProductStockToAmazon($idProduct)
    {
        $sku = (string) Db::getInstance()->getValue(
            'SELECT `reference` FROM `' . _DB_PREFIX_ . 'product`
             WHERE `id_product` = ' . (int) $idProduct
        );
        if ($sku === '') {
            return;
        }

        $sellerId = Configuration::get('AMZPRO_SELLER_ID');
        if (!$sellerId) {
            return;
        }

        require_once dirname(__FILE__) . '/classes/AmazonSpApiClient.php';

        $client = $this->buildAmazonClient();
        $marketplaceId = $this->getMarketplaceId();

        $idShop = (int) Context::getContext()->shop->id;
        if (!$idShop) {
            $idShop = 1;
        }
        $qty = (int) StockAvailable::getQuantityAvailableByProduct($idProduct, 0, $idShop);

        $price = (float) Db::getInstance()->getValue(
            'SELECT `price` FROM `' . _DB_PREFIX_ . 'product`
             WHERE `id_product` = ' . (int) $idProduct
        );

        $effectiveQty = AmazonSpApiClient::effectiveQuantity($qty);

        // Out-of-stock delisting (optional): remove the listing instead of
        // publishing zero stock.
        if (Configuration::get('AMZPRO_DELETE_WHEN_OOS') && $effectiveQty <= 0) {
            $resp = $client->request(
                'DELETE',
                '/listings/2021-08-01/items/' . rawurlencode($sellerId) . '/' . rawurlencode($sku),
                array('marketplaceIds' => $marketplaceId)
            );
            $status = ($resp !== false && is_array($resp['body']) && isset($resp['body']['status']))
                ? $resp['body']['status'] : 'unknown';
            $this->logActivity('info', 'hook_stock_push',
                'SKU ' . $sku . ': out of stock, deletion requested, status=' . $status
            );
            return;
        }

        $attributes = array(
            'condition_type' => array(array('value' => AmazonSpApiClient::listingCondition())),
            'purchasable_offer' => array(array(
                'currency' => AmazonSpApiClient::currencyForMarketplace($marketplaceId),
                'marketplace_id' => $marketplaceId,
                'our_price' => array(array(
                    'schedule' => array(array('value_with_tax' => $price)),
                )),
            )),
            'fulfillment_availability' => array(array(
                'fulfillment_channel_code' => 'DEFAULT',
                'quantity' => $effectiveQty,
            )),
        );
        $attributes = AmazonSpApiClient::enrichOfferAttributes($attributes, $marketplaceId, $price);

        $body = array(
            'productType' => 'PRODUCT',
            'requirements' => 'LISTING_OFFER_ONLY',
            'attributes' => $attributes,
        );

        $resp = $client->request(
            'PUT',
            '/listings/2021-08-01/items/' . rawurlencode($sellerId) . '/' . rawurlencode($sku),
            array('marketplaceIds' => $marketplaceId),
            $body
        );

        $status = 'unknown';
        if ($resp !== false && is_array($resp['body']) && isset($resp['body']['status'])) {
            $status = $resp['body']['status'];
        }

        $this->logActivity('info', 'hook_stock_push',
            'SKU ' . $sku . ': qty=' . $qty . ', price=' . $price . ', status=' . $status
        );
    }

    /**
     * Send shipment confirmation to Amazon for an order.
     */
    private function confirmShipmentOnAmazon($order)
    {
        $amazonOrderId = Db::getInstance()->getValue(
            'SELECT `amazon_order_id` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `id_order` = ' . (int) $order->id
        );

        if (!$amazonOrderId) {
            return;
        }

        $trackingNumber = '';
        $carrierName = '';
        $idCarrier = 0;
        $orderCarrier = Db::getInstance()->getRow(
            'SELECT oc.`tracking_number`, oc.`id_carrier`, c.`name` AS carrier_name
             FROM `' . _DB_PREFIX_ . 'order_carrier` oc
             LEFT JOIN `' . _DB_PREFIX_ . 'carrier` c ON (c.`id_carrier` = oc.`id_carrier`)
             WHERE oc.`id_order` = ' . (int) $order->id . '
             ORDER BY oc.`id_order_carrier` DESC
             LIMIT 1'
        );
        if ($orderCarrier) {
            $trackingNumber = (string) $orderCarrier['tracking_number'];
            $carrierName = (string) $orderCarrier['carrier_name'];
            $idCarrier = (int) $orderCarrier['id_carrier'];
        }

        require_once dirname(__FILE__) . '/classes/AmazonSpApiClient.php';
        $client = $this->buildAmazonClient();

        $resp = $client->request(
            'POST',
            '/orders/v0/orders/' . rawurlencode($amazonOrderId) . '/shipmentConfirmation',
            array(),
            array(
                'marketplaceId' => $this->getMarketplaceId(),
                'packageDetail' => array(
                    'trackingNumber' => $trackingNumber,
                    'carrierCode' => $this->mapCarrierToAmazon($carrierName, $idCarrier),
                ),
            )
        );

        $status = ($resp !== false && $resp['status'] < 400) ? 'OK' : 'FAILED';
        $this->logActivity('info', 'hook_shipment',
            'Order #' . $order->id . ' (Amazon: ' . $amazonOrderId . '): ' . $status
        );
    }

    /**
     * Cancel an Amazon order when PS order is cancelled.
     */
    private function cancelOrderOnAmazon($order)
    {
        $amazonOrderId = Db::getInstance()->getValue(
            'SELECT `amazon_order_id` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE `id_order` = ' . (int) $order->id
        );

        if (!$amazonOrderId) {
            return;
        }

        require_once dirname(__FILE__) . '/classes/AmazonReturnManager.php';

        $client = $this->buildAmazonClient();
        $manager = new AmazonReturnManager(
            $client,
            $this->getMarketplaceId(),
            Configuration::get('AMZPRO_SELLER_ID')
        );

        $success = $manager->cancelOrderOnAmazon($amazonOrderId);

        $status = $success ? 'OK' : 'FAILED';
        $error = $manager->getLastError();

        $this->logActivity($success ? 'info' : 'warning', 'hook_cancel',
            'Order #' . $order->id . ' (Amazon: ' . $amazonOrderId . '): ' . $status
            . ($error ? ' — ' . $error : '')
        );

        // Update staging table
        $now = date('Y-m-d H:i:s');
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` SET
                `order_status` = \'Canceled\',
                `import_status` = \'cancelled\',
                `date_upd` = \'' . pSQL($now) . '\'
             WHERE `id_order` = ' . (int) $order->id
        );
    }

    /**
     * Map a PrestaShop carrier name to an Amazon carrier code.
     */
    /** Amazon carrier codes offered in the mapping UI. */
    public static $amazonCarrierCodes = array(
        'DHL', 'DPD', 'GLS', 'Hermes', 'UPS', 'USPS', 'FedEx', 'Royal Mail',
        'TNT', 'Chronopost', 'Colissimo', 'La Poste', 'Colis Prive',
        'Mondial Relay', 'Deutsche Post', 'PostNL', 'bpost', 'Correos',
        'Poste Italiane', 'Aramex', 'Amazon Shipping', 'Other',
    );

    /**
     * Map a PS carrier to an Amazon carrier code: explicit merchant mapping
     * first, name heuristics as fallback.
     */
    private function mapCarrierToAmazon($carrierName, $idCarrier = 0)
    {
        if ($idCarrier) {
            $map = json_decode((string) Configuration::get('AMZPRO_CARRIER_MAP'), true);
            if (is_array($map) && !empty($map[$idCarrier])) {
                return $map[$idCarrier];
            }
        }

        $name = strtolower(trim($carrierName));
        $map = array(
            'dhl' => 'DHL', 'fedex' => 'FedEx', 'ups' => 'UPS',
            'usps' => 'USPS', 'royal mail' => 'Royal Mail',
            'dpd' => 'DPD', 'gls' => 'GLS', 'hermes' => 'Hermes',
            'tnt' => 'TNT', 'chronopost' => 'Chronopost',
            'colissimo' => 'Colissimo', 'la poste' => 'La Poste',
            'mondial relay' => 'Mondial Relay', 'postnl' => 'PostNL',
            'bpost' => 'bpost', 'correos' => 'Correos',
            'aramex' => 'Aramex', 'post' => 'Other',
        );
        foreach ($map as $key => $code) {
            if (strpos($name, $key) !== false) {
                return $code;
            }
        }
        return 'Other';
    }

    /* ─────────────────── Logging ─────────────────── */

    protected function logActivity($level, $source, $message)
    {
        $now = date('Y-m-d H:i:s');
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
             (`level`, `source`, `message`, `date_add`)
             VALUES (
                \'' . pSQL($level) . '\',
                \'' . pSQL($source) . '\',
                \'' . pSQL(Tools::substr((string) $message, 0, 2000)) . '\',
                \'' . pSQL($now) . '\'
             )'
        );
    }

    protected function getRecentLogs($limit = 50)
    {
        $sql = 'SELECT `level`, `source`, `message`, `date_add`
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_log`
                ORDER BY `id_amazonmarketplacepro_log` DESC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }
}
