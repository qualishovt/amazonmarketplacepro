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

/*
 * Amazon Report Manager.
 *
 * Handles requesting, polling, and downloading Amazon SP-API reports:
 * - GET_MERCHANT_LISTINGS_ALL_DATA (complete listing catalog)
 * - GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE (settlement/payout reports)
 * - GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA (FBA inventory snapshot)
 * - GET_FLAT_FILE_ORDERS_DATA (order reports)
 *
 * Uses SP-API Reports API 2021-06-30.
 *
 * Reports belong to the shop that requested them, with that shop's seller
 * account; each shop polls and reads only its own.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonReportManager
{
    /** Common report types */
    const REPORT_MERCHANT_LISTINGS = 'GET_MERCHANT_LISTINGS_ALL_DATA';
    const REPORT_SETTLEMENT = 'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE';
    const REPORT_FBA_INVENTORY = 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA';
    const REPORT_ORDERS = 'GET_FLAT_FILE_ORDERS_DATA';

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $lastError;
    private $notices = [];

    public function __construct(AmazonSpApiClient $client, $marketplaceId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Request a new report from Amazon.
     *
     * @param string $reportType One of the REPORT_* constants
     * @param string|null $startDate ISO8601 (optional, for date-ranged reports)
     * @param string|null $endDate ISO8601 (optional)
     *
     * @return array Result with report_id
     */
    public function requestReport($reportType, $startDate = null, $endDate = null)
    {
        $this->lastError = null;

        $body = [
            'reportType' => $reportType,
            'marketplaceIds' => [$this->marketplaceId],
        ];

        if ($startDate !== null) {
            $body['dataStartTime'] = $startDate;
        }
        if ($endDate !== null) {
            $body['dataEndTime'] = $endDate;
        }

        $resp = $this->client->request(
            'POST',
            '/reports/2021-06-30/reports',
            [],
            $body
        );

        if ($resp === false) {
            $this->lastError = $this->client->getLastError();

            return ['success' => false, 'error' => $this->lastError];
        }

        if ($resp['status'] >= 400) {
            $errorBody = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'Reports API HTTP ' . $resp['status'] . ': ' . $errorBody;

            return ['success' => false, 'error' => $this->lastError];
        }

        $reportId = '';
        if (is_array($resp['body']) && isset($resp['body']['reportId'])) {
            $reportId = $resp['body']['reportId'];
        }

        // Track in DB
        $now = date('Y-m-d H:i:s');
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`
             (`report_id`, `report_type`, `status`, `marketplace_id`,
              `data_start_time`, `data_end_time`, `date_add`, `date_upd`, `id_shop`)
             VALUES (
                \'' . pSQL($reportId) . '\',
                \'' . pSQL($reportType) . '\',
                \'IN_QUEUE\',
                \'' . pSQL($this->marketplaceId) . '\',
                ' . ($startDate ? '\'' . pSQL($startDate) . '\'' : 'NULL') . ',
                ' . ($endDate ? '\'' . pSQL($endDate) . '\'' : 'NULL') . ',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\',
                ' . (int) AmzproShop::actingId() . '
             )'
        );

        return [
            'success' => true,
            'report_id' => $reportId,
        ];
    }

    /**
     * Check status of a pending report and download if ready.
     *
     * @param string $reportId
     *
     * @return array Status info
     */
    public function checkAndDownload($reportId)
    {
        $this->lastError = null;

        // Get report status
        $resp = $this->client->request(
            'GET',
            '/reports/2021-06-30/reports/' . rawurlencode($reportId),
            []
        );

        if ($resp === false || $resp['status'] >= 400) {
            $this->lastError = $resp === false
                ? $this->client->getLastError()
                : 'HTTP ' . $resp['status'];

            return ['success' => false, 'status' => 'ERROR', 'error' => $this->lastError];
        }

        $report = is_array($resp['body']) ? $resp['body'] : [];
        $status = isset($report['processingStatus']) ? $report['processingStatus'] : 'UNKNOWN';
        $now = date('Y-m-d H:i:s');

        // Update status in DB
        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_report` SET
                `status` = \'' . pSQL($status) . '\',
                `date_upd` = \'' . pSQL($now) . '\'
             WHERE `report_id` = \'' . pSQL($reportId) . '\'
               AND ' . AmzproShop::sqlWhere()
        );

        if ($status === 'DONE') {
            $documentId = isset($report['reportDocumentId']) ? $report['reportDocumentId'] : '';

            if ($documentId !== '') {
                // Update document ID
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_report` SET
                        `report_document_id` = \'' . pSQL($documentId) . '\'
                     WHERE `report_id` = \'' . pSQL($reportId) . '\'
                       AND ' . AmzproShop::sqlWhere()
                );

                // Download the report
                $content = $this->downloadReportDocument($documentId);
                if ($content !== false) {
                    // Save to file
                    $filePath = $this->saveReportFile($reportId, $content);
                    $rowCount = substr_count($content, "\n");

                    Db::getInstance()->execute(
                        'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_report` SET
                            `file_path` = \'' . pSQL($filePath) . '\',
                            `row_count` = ' . (int) $rowCount . ',
                            `date_upd` = \'' . pSQL($now) . '\'
                         WHERE `report_id` = \'' . pSQL($reportId) . '\'
                           AND ' . AmzproShop::sqlWhere()
                    );

                    return [
                        'success' => true,
                        'status' => 'DONE',
                        'file_path' => $filePath,
                        'row_count' => $rowCount,
                    ];
                }
            }
        } elseif ($status === 'FATAL' || $status === 'CANCELLED') {
            $errorMsg = isset($report['processingEndTime'])
                ? 'Report processing ended: ' . $report['processingEndTime']
                : 'Report processing failed.';

            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_report` SET
                    `error_message` = \'' . pSQL($errorMsg) . '\'
                 WHERE `report_id` = \'' . pSQL($reportId) . '\'
                   AND ' . AmzproShop::sqlWhere()
            );

            return ['success' => false, 'status' => $status, 'error' => $errorMsg];
        }

        return ['success' => true, 'status' => $status];
    }

    /**
     * Download a report document.
     *
     * @param string $documentId
     *
     * @return string|false Report content or false on error
     */
    private function downloadReportDocument($documentId)
    {
        $resp = $this->client->request(
            'GET',
            '/reports/2021-06-30/documents/' . rawurlencode($documentId),
            []
        );

        if ($resp === false || $resp['status'] >= 400) {
            $this->lastError = 'Failed to get report document URL.';

            return false;
        }

        $docInfo = is_array($resp['body']) ? $resp['body'] : [];
        $url = isset($docInfo['url']) ? $docInfo['url'] : '';

        if ($url === '') {
            $this->lastError = 'Report document URL is empty.';

            return false;
        }

        // Download the actual content from the pre-signed URL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content = curl_exec($ch);
        if ($content === false) {
            $this->lastError = 'cURL error downloading report: ' . curl_error($ch);
            curl_close($ch);

            return false;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $this->lastError = 'Report download HTTP ' . $httpCode;

            return false;
        }

        // Handle gzip compression
        $compression = isset($docInfo['compressionAlgorithm']) ? $docInfo['compressionAlgorithm'] : '';
        if ($compression === 'GZIP' && function_exists('gzdecode')) {
            $decoded = gzdecode($content);
            if ($decoded !== false) {
                $content = $decoded;
            }
        }

        return $content;
    }

    /**
     * Save report content to a file.
     *
     * @param string $reportId
     * @param string $content
     *
     * @return string File path
     */
    private function saveReportFile($reportId, $content)
    {
        $dir = dirname(dirname(__FILE__)) . '/data/reports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = 'report_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $reportId) . '.tsv';
        $filePath = $dir . '/' . $filename;

        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Request a merchant listings report.
     *
     * @return array Result
     */
    public function requestMerchantListingsReport()
    {
        return $this->requestReport(self::REPORT_MERCHANT_LISTINGS);
    }

    /**
     * Request a settlement report for a date range.
     *
     * @param int $daysBack Number of days to look back
     *
     * @return array Result
     */
    public function requestSettlementReport($daysBack = 30)
    {
        $endDate = gmdate('Y-m-d\TH:i:s\Z');
        $startDate = gmdate('Y-m-d\TH:i:s\Z', strtotime('-' . (int) $daysBack . ' days'));

        return $this->requestReport(self::REPORT_SETTLEMENT, $startDate, $endDate);
    }

    /**
     * Request an FBA inventory report.
     *
     * @return array Result
     */
    public function requestFbaInventoryReport()
    {
        return $this->requestReport(self::REPORT_FBA_INVENTORY);
    }

    /**
     * Poll all pending reports and download any that are ready.
     *
     * @return array Summary
     */
    public function pollPendingReports()
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'pending' => 0,
            'completed' => 0,
            'failed' => 0,
            'still_processing' => 0,
        ];

        $pending = Db::getInstance()->executeS(
            'SELECT `report_id`, `report_type`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`
             WHERE ' . AmzproShop::sqlWhere() . '
               AND `status` IN (\'IN_QUEUE\', \'IN_PROGRESS\')
             ORDER BY `date_add` ASC
             LIMIT 20'
        );

        if (!is_array($pending)) {
            return $summary;
        }

        $summary['pending'] = count($pending);

        foreach ($pending as $report) {
            $result = $this->checkAndDownload($report['report_id']);

            if (isset($result['status'])) {
                switch ($result['status']) {
                    case 'DONE':
                        $summary['completed']++;
                        $this->notices[] = $report['report_type'] . ' (' . $report['report_id'] . '): completed';
                        break;
                    case 'FATAL':
                    case 'CANCELLED':
                        $summary['failed']++;
                        break;
                    default:
                        $summary['still_processing']++;
                        break;
                }
            }
        }

        return $summary;
    }

    /**
     * Parse a merchant listings report into structured data.
     *
     * @param string $reportId
     *
     * @return array List of listing rows
     */
    public function parseMerchantListingsReport($reportId)
    {
        $filePath = Db::getInstance()->getValue(
            'SELECT `file_path` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`
             WHERE `report_id` = \'' . pSQL($reportId) . '\'
               AND ' . AmzproShop::sqlWhere()
        );

        if (!$filePath || !file_exists($filePath)) {
            $this->lastError = 'Report file not found.';

            return [];
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        if (count($lines) < 2) {
            return [];
        }

        // Parse TSV header
        $header = str_getcsv($lines[0], "\t");
        $rows = [];

        for ($i = 1; $i < count($lines); ++$i) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $fields = str_getcsv($line, "\t");
            $row = [];
            foreach ($header as $idx => $col) {
                $row[trim($col)] = isset($fields[$idx]) ? trim($fields[$idx]) : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Parse a settlement report and return summary data.
     *
     * @param string $reportId
     *
     * @return array Settlement summary
     */
    public function parseSettlementReport($reportId)
    {
        $filePath = Db::getInstance()->getValue(
            'SELECT `file_path` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`
             WHERE `report_id` = \'' . pSQL($reportId) . '\'
               AND ' . AmzproShop::sqlWhere()
        );

        if (!$filePath || !file_exists($filePath)) {
            $this->lastError = 'Report file not found.';

            return [];
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        if (count($lines) < 2) {
            return [];
        }

        $header = str_getcsv($lines[0], "\t");
        $summary = [
            'total_amount' => 0,
            'product_charges' => 0,
            'product_charge_refunds' => 0,
            'amazon_fees' => 0,
            'other_fees' => 0,
            'promotions' => 0,
            'rows' => 0,
        ];

        for ($i = 1; $i < count($lines); ++$i) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $fields = str_getcsv($line, "\t");
            $row = [];
            foreach ($header as $idx => $col) {
                $row[trim($col)] = isset($fields[$idx]) ? trim($fields[$idx]) : '';
            }

            ++$summary['rows'];

            $amount = isset($row['total-amount']) ? (float) $row['total-amount'] : 0;
            $summary['total_amount'] += $amount;

            $type = isset($row['amount-type']) ? $row['amount-type'] : '';
            switch ($type) {
                case 'ItemPrice':
                    $summary['product_charges'] += $amount;
                    break;
                case 'ItemFees':
                    $summary['amazon_fees'] += $amount;
                    break;
                case 'Promotion':
                    $summary['promotions'] += $amount;
                    break;
                case 'RefundedItemPrice':
                case 'ItemPriceAdjustments':
                    $summary['product_charge_refunds'] += $amount;
                    break;
                default:
                    $summary['other_fees'] += $amount;
                    break;
            }
        }

        return $summary;
    }

    /**
     * List the current shop's tracked reports for admin display (every
     * shop's in "All shops"; each row carries its id_shop).
     *
     * @param int $limit
     *
     * @return array
     */
    public function listReports($limit = 50)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_report`
                WHERE ' . AmzproShop::sqlWhere() . '
                ORDER BY `date_add` DESC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }
}
