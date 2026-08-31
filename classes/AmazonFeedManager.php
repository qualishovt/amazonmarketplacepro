<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Bulk listing updates via the SP-API Feeds API (2021-06-30).
 *
 * Instead of one HTTP call per SKU, thousands of listing changes travel in
 * a single JSON_LISTINGS_FEED document that Amazon processes asynchronously:
 *   submit:  create document -> upload JSON -> create feed
 *   poll:    check processingStatus until DONE -> download result -> parse
 * Every submitted feed is tracked in amazonmarketplacepro_feed.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonFeedManager
{
    const FEED_TYPE = 'JSON_LISTINGS_FEED';
    const CONTENT_TYPE = 'application/json; charset=UTF-8';

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $sellerId;
    private $lastError = null;
    private $useMock = false;

    public function __construct(AmazonSpApiClient $client, $marketplaceId, $sellerId)
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
        $this->sellerId = (string) $sellerId;
    }

    public function setMock($enabled)
    {
        $this->useMock = (bool) $enabled;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Create the feed tracking table (idempotent).
     */
    public function ensureTable()
    {
        $engine = defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB';
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed` (
                `id_amazonmarketplacepro_feed` INT(11) NOT NULL AUTO_INCREMENT,
                `feed_id` VARCHAR(64) NOT NULL DEFAULT \'\',
                `feed_type` VARCHAR(64) NOT NULL DEFAULT \'\',
                `marketplace_id` VARCHAR(32) NOT NULL DEFAULT \'\',
                `processing_status` VARCHAR(32) NOT NULL DEFAULT \'SUBMITTED\',
                `messages_count` INT(11) NOT NULL DEFAULT 0,
                `accepted` INT(11) NOT NULL DEFAULT 0,
                `errors` INT(11) NOT NULL DEFAULT 0,
                `warnings` INT(11) NOT NULL DEFAULT 0,
                `issues_json` TEXT NULL,
                `error_message` TEXT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_amazonmarketplacepro_feed`),
                KEY `processing_status` (`processing_status`)
            ) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8;'
        );

        // Retaining the submitted payload is what makes an Amazon support
        // ticket answerable; added after the original table shipped.
        $hasPayload = false;
        $columns = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`'
        );
        if (is_array($columns)) {
            foreach ($columns as $c) {
                if ($c['Field'] === 'feed_content') {
                    $hasPayload = true;
                    break;
                }
            }
        }
        if (!$hasPayload) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`
                 ADD `feed_content` LONGTEXT NULL'
            );
        }
    }

    /**
     * Submit one JSON_LISTINGS_FEED containing the given messages.
     *
     * @param array $messages Messages from AmazonProductSync::collectFeedMessages()
     * @return string|false The Amazon feedId, or false (see getLastError())
     */
    public function submitListingsFeed($messages)
    {
        if (empty($messages)) {
            $this->lastError = 'Nothing to submit: no pending listing changes.';
            return false;
        }

        $document = json_encode(array(
            'header' => array(
                'sellerId' => $this->sellerId,
                'version' => '2.0',
                'issueLocale' => 'en_US',
            ),
            'messages' => $messages,
        ));

        return $this->submitFeed(self::FEED_TYPE, self::CONTENT_TYPE, $document, count($messages));
    }

    /**
     * Submit an arbitrary feed (document create -> upload -> create feed).
     *
     * @param string     $feedType      e.g. JSON_LISTINGS_FEED, UPLOAD_VAT_INVOICE
     * @param string     $contentType   Content type of the document
     * @param string     $content       Raw document body
     * @param int        $messagesCount For the tracking table
     * @param array|null $feedOptions   Extra feed options (e.g. VCS metadata)
     * @return string|false The Amazon feedId, or false (see getLastError())
     */
    public function submitFeed($feedType, $contentType, $content, $messagesCount, $feedOptions = null)
    {
        $this->lastError = null;
        $this->ensureTable();

        if ($this->sellerId === '') {
            $this->lastError = 'No seller id configured.';
            return false;
        }

        if ($this->useMock) {
            $feedId = 'MOCKFEED' . rand(1000, 9999);
            $this->storeFeed($feedId, $feedType, $messagesCount, 'DONE', $messagesCount, 0, 0, '[]', $content);
            return $feedId;
        }

        // 1. Create the feed document slot
        $resp = $this->client->request('POST', '/feeds/2021-06-30/documents', array(), array(
            'contentType' => $contentType,
        ));
        if ($resp === false || $resp['status'] >= 400 || !isset($resp['body']['feedDocumentId'])) {
            $this->lastError = 'createFeedDocument failed: ' . $this->respError($resp);
            return false;
        }
        $documentId = $resp['body']['feedDocumentId'];
        $uploadUrl = $resp['body']['url'];

        // 2. Upload the document
        if (!$this->client->uploadDocument($uploadUrl, $content, $contentType)) {
            $this->lastError = 'Feed upload failed: ' . $this->client->getLastError();
            return false;
        }

        // 3. Create the feed itself
        $feedSpec = array(
            'feedType' => $feedType,
            'marketplaceIds' => array($this->marketplaceId),
            'inputFeedDocumentId' => $documentId,
        );
        if (is_array($feedOptions) && !empty($feedOptions)) {
            $feedSpec['feedOptions'] = $feedOptions;
        }
        $resp = $this->client->request('POST', '/feeds/2021-06-30/feeds', array(), $feedSpec);
        if ($resp === false || $resp['status'] >= 400 || !isset($resp['body']['feedId'])) {
            $this->lastError = 'createFeed failed: ' . $this->respError($resp);
            return false;
        }

        $feedId = $resp['body']['feedId'];
        $this->storeFeed($feedId, $feedType, $messagesCount, 'SUBMITTED', 0, 0, 0, null, $content);

        return $feedId;
    }

    /**
     * Poll all unfinished feeds and parse results for the completed ones.
     *
     * @return array Summary of what changed this run
     */
    public function pollPendingFeeds()
    {
        $this->lastError = null;
        $this->ensureTable();

        $summary = array('checked' => 0, 'done' => 0, 'failed' => 0, 'still_processing' => 0);

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`
             WHERE `processing_status` NOT IN (\'DONE\', \'CANCELLED\', \'FATAL\')
             ORDER BY `id_amazonmarketplacepro_feed` ASC
             LIMIT 20'
        );
        if (!is_array($rows)) {
            return $summary;
        }

        foreach ($rows as $row) {
            $summary['checked']++;

            $resp = $this->client->request('GET', '/feeds/2021-06-30/feeds/' . rawurlencode($row['feed_id']));
            if ($resp === false || $resp['status'] >= 400 || !isset($resp['body']['processingStatus'])) {
                $summary['failed']++;
                $this->updateFeed($row['feed_id'], array('error_message' => $this->respError($resp)));
                continue;
            }

            $status = $resp['body']['processingStatus'];
            if ($status === 'IN_QUEUE' || $status === 'IN_PROGRESS') {
                $summary['still_processing']++;
                $this->updateFeed($row['feed_id'], array('processing_status' => $status));
                continue;
            }

            if ($status !== 'DONE') { // CANCELLED / FATAL
                $summary['failed']++;
                $this->updateFeed($row['feed_id'], array(
                    'processing_status' => $status,
                    'error_message' => 'Feed ended with status ' . $status,
                ));
                continue;
            }

            // DONE: fetch and parse the processing report
            $counts = array('accepted' => 0, 'errors' => 0, 'warnings' => 0, 'issues' => array());
            if (isset($resp['body']['resultFeedDocumentId'])) {
                $parsed = $this->fetchResult($resp['body']['resultFeedDocumentId']);
                if ($parsed !== false) {
                    $counts = $parsed;
                }
            }

            $this->updateFeed($row['feed_id'], array(
                'processing_status' => 'DONE',
                'accepted' => $counts['accepted'],
                'errors' => $counts['errors'],
                'warnings' => $counts['warnings'],
                'issues_json' => Tools::substr(json_encode($counts['issues']), 0, 60000),
            ));
            $summary['done']++;
        }

        return $summary;
    }

    /**
     * Recent feeds for the admin UI.
     */
    public function listFeeds($limit = 20)
    {
        $this->ensureTable();
        $rows = Db::getInstance()->executeS(
            'SELECT `feed_id`, `processing_status`, `messages_count`, `accepted`,
                    `errors`, `warnings`, `error_message`, `date_add`, `date_upd`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`
             ORDER BY `id_amazonmarketplacepro_feed` DESC
             LIMIT ' . (int) $limit
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * Download and parse a JSON_LISTINGS_FEED processing report.
     *
     * @return array|false array(accepted, errors, warnings, issues[])
     */
    private function fetchResult($resultDocumentId)
    {
        $resp = $this->client->request('GET', '/feeds/2021-06-30/documents/' . rawurlencode($resultDocumentId));
        if ($resp === false || $resp['status'] >= 400 || !isset($resp['body']['url'])) {
            return false;
        }

        $compression = isset($resp['body']['compressionAlgorithm']) ? $resp['body']['compressionAlgorithm'] : '';
        $raw = $this->client->downloadDocument($resp['body']['url'], $compression);
        if ($raw === false) {
            return false;
        }

        $report = json_decode($raw, true);
        if (!is_array($report)) {
            return false;
        }

        $out = array('accepted' => 0, 'errors' => 0, 'warnings' => 0, 'issues' => array());
        if (isset($report['summary'])) {
            $s = $report['summary'];
            $out['accepted'] = isset($s['messagesAccepted']) ? (int) $s['messagesAccepted'] : 0;
            $out['errors'] = isset($s['errors']) ? (int) $s['errors'] : 0;
            $out['warnings'] = isset($s['warnings']) ? (int) $s['warnings'] : 0;
        }
        if (isset($report['issues']) && is_array($report['issues'])) {
            foreach (array_slice($report['issues'], 0, 200) as $issue) {
                $out['issues'][] = array(
                    'sku' => isset($issue['sku']) ? $issue['sku'] : '',
                    'severity' => isset($issue['severity']) ? $issue['severity'] : '',
                    'code' => isset($issue['code']) ? $issue['code'] : '',
                    'message' => isset($issue['message']) ? $issue['message'] : '',
                );
            }
        }

        return $out;
    }

    private function storeFeed($feedId, $feedType, $messagesCount, $status, $accepted, $errors, $warnings, $issuesJson, $payload = null)
    {
        $now = date('Y-m-d H:i:s');
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`
             (`feed_id`, `feed_type`, `marketplace_id`, `processing_status`, `messages_count`,
              `accepted`, `errors`, `warnings`, `issues_json`, `feed_content`, `date_add`, `date_upd`)
             VALUES (
                \'' . pSQL($feedId) . '\',
                \'' . pSQL($feedType) . '\',
                \'' . pSQL($this->marketplaceId) . '\',
                \'' . pSQL($status) . '\',
                ' . (int) $messagesCount . ',
                ' . (int) $accepted . ',
                ' . (int) $errors . ',
                ' . (int) $warnings . ',
                ' . ($issuesJson === null ? 'NULL' : '\'' . pSQL($issuesJson, true) . '\'') . ',
                ' . ($payload === null ? 'NULL' : '\'' . pSQL(Tools::substr($payload, 0, 4000000), true) . '\'') . ',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
             )'
        );
    }

    /** The exact JSON submitted for a feed, for support tickets. */
    public function getFeedPayload($feedId)
    {
        $this->ensureTable();

        return (string) Db::getInstance()->getValue(
            'SELECT `feed_content` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`
             WHERE `feed_id` = \'' . pSQL($feedId) . '\''
        );
    }

    private function updateFeed($feedId, $fields)
    {
        $sets = array();
        foreach ($fields as $col => $val) {
            $sets[] = '`' . bqSQL($col) . '` = ' . (is_int($val) ? (int) $val : '\'' . pSQL((string) $val, true) . '\'');
        }
        $sets[] = '`date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'';

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_feed`
             SET ' . implode(', ', $sets) . '
             WHERE `feed_id` = \'' . pSQL($feedId) . '\''
        );
    }

    private function respError($resp)
    {
        if ($resp === false) {
            return (string) $this->client->getLastError();
        }
        $body = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
        return 'HTTP ' . $resp['status'] . ': ' . Tools::substr($body, 0, 300);
    }
}
