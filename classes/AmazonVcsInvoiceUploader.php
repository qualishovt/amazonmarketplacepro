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
 * VAT invoice upload for VCS-enrolled sellers (UPLOAD_VAT_INVOICE feed).
 *
 * For each Amazon order that has a created PrestaShop order with an
 * invoice, renders the PS invoice PDF and uploads it to Amazon with the
 * VCS metadata (order id, invoice number). Requires the merchant to be
 * enrolled in Amazon's VAT Calculation Service.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonVcsInvoiceUploader
{
    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $sellerId;
    private $lastError;
    private $notices = [];
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

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Make sure the tracking column exists (older installs).
     */
    public function ensureSchema()
    {
        $cols = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order` LIKE \'vcs_uploaded\''
        );
        if (empty($cols)) {
            Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
                 ADD COLUMN `vcs_uploaded` TINYINT(1) NOT NULL DEFAULT 0'
            );
        }
    }

    /**
     * Upload invoices for the current shop's created orders that don't have
     * one on Amazon yet.
     *
     * @param int $limit
     *
     * @return array Summary counts
     */
    public function uploadPendingInvoices($limit = 10)
    {
        $this->lastError = null;
        $this->notices = [];
        $this->ensureSchema();

        $summary = ['candidates' => 0, 'uploaded' => 0, 'no_invoice' => 0, 'failed' => 0];

        $rows = Db::getInstance()->executeS(
            'SELECT `amazon_order_id`, `id_order`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             WHERE ' . AmzproShop::sqlWhere() . '
               AND `id_order` > 0
               AND `vcs_uploaded` = 0
               AND `order_status` NOT IN (\'Canceled\', \'Cancelled\')
             ORDER BY `id_amazonmarketplacepro_order` ASC
             LIMIT ' . (int) $limit
        );
        if (!is_array($rows)) {
            return $summary;
        }
        $summary['candidates'] = count($rows);

        foreach ($rows as $row) {
            $result = $this->uploadInvoiceForOrder($row['amazon_order_id'], (int) $row['id_order']);
            if ($result === true) {
                ++$summary['uploaded'];
            } elseif ($result === null) {
                ++$summary['no_invoice'];
            } else {
                ++$summary['failed'];
                $this->notices[] = $row['amazon_order_id'] . ': ' . $this->lastError;
            }
        }

        return $summary;
    }

    /**
     * Render the PS invoice for one order and upload it as a VAT invoice.
     *
     * @param string $amazonOrderId
     * @param int $idOrder PrestaShop order id
     *
     * @return bool|null true = uploaded, null = no invoice yet (skip), false = error
     */
    public function uploadInvoiceForOrder($amazonOrderId, $idOrder)
    {
        $this->lastError = null;

        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order)) {
            $this->lastError = 'PrestaShop order #' . (int) $idOrder . ' not found.';

            return false;
        }
        // The upload goes to this shop's seller account, so an order of
        // another shop is never sent with it.
        if (AmzproShop::isMultistore() && !AmzproShop::isAllShops()
            && (int) $order->id_shop !== AmzproShop::id()) {
            $this->lastError = AmazonI18n::get()->l('This order belongs to another shop. Its invoice is uploaded when that shop runs the task.', 'amazonvcsinvoiceuploader');

            return false;
        }
        $idShop = (int) $order->id_shop;

        $invoices = $order->getInvoicesCollection();
        if (!count($invoices)) {
            return null; // invoice not generated yet — retry on a later run
        }

        $invoice = null;
        foreach ($invoices as $inv) {
            $invoice = $inv; // most recent wins
        }
        if (!$invoice instanceof OrderInvoice) {
            return null;
        }
        $invoiceNumber = $invoice->getInvoiceNumberFormatted(
            (int) Configuration::get('PS_LANG_DEFAULT', null, AmzproShop::groupId($idShop), $idShop),
            $idShop
        );

        if ($this->useMock) {
            $pdfContent = '%PDF-MOCK';
        } else {
            // Rendered as the order's shop: its address, logo and settings.
            $pdfContent = AmzproShop::runInShop($idShop, function () use ($invoices) {
                $pdf = new PDF($invoices, PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);

                return $pdf->render(false);
            });
            if (!$pdfContent) {
                $this->lastError = 'Could not render the invoice PDF.';

                return false;
            }
        }

        require_once dirname(__FILE__) . '/AmazonFeedManager.php';
        $feeds = new AmazonFeedManager($this->client, $this->marketplaceId, $this->sellerId);
        $feeds->setMock($this->useMock);

        $feedId = $feeds->submitFeed(
            'UPLOAD_VAT_INVOICE',
            'application/pdf',
            $pdfContent,
            1,
            [
                'metadata:orderid' => $amazonOrderId,
                'metadata:invoicenumber' => $invoiceNumber,
                'metadata:documenttype' => 'Invoice',
            ]
        );

        if ($feedId === false) {
            $this->lastError = $feeds->getLastError();

            return false;
        }

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_order`
             SET `vcs_uploaded` = 1, `date_upd` = \'' . pSQL(date('Y-m-d H:i:s')) . '\'
             WHERE `amazon_order_id` = \'' . pSQL($amazonOrderId) . '\'
               AND ' . AmzproShop::sqlWhere()
        );

        return true;
    }
}
