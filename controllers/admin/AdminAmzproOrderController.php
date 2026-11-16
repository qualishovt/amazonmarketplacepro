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
 * Hidden back-office page behind the Amazon box on the order page. It only
 * answers the box's refund request (JSON); it has no screen of its own.
 * Access follows the employee's rights on this tab, like any admin page.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonRefundManager.php';

class AdminAmzproOrderController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        if (!Tools::getValue('ajax')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
        }
        parent::initContent();
    }

    public function displayAjaxRefund()
    {
        header('Content-Type: application/json');
        $allowed = method_exists($this, 'access') ? $this->access('edit')
            : (isset($this->tabAccess['edit']) && (string) $this->tabAccess['edit'] === '1');   // PS 1.6
        if (!$allowed) {
            die(json_encode(['success' => false, 'error' => $this->module->l('You do not have permission to refund orders.', 'adminamzproordercontroller')]));
        }
        if (strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
            die(json_encode(['success' => false, 'error' => 'POST only']));
        }
        $idOrder = (int) Tools::getValue('id_order');
        $input = [
            'items' => (array) Tools::getValue('items', []),
            'shipping' => Tools::getValue('shipping', 0),
            'extra' => Tools::getValue('extra', 0),
            'reason' => (string) Tools::getValue('reason'),
        ];
        die(json_encode($this->module->refundOnAmazon($idOrder, $input, (int) $this->context->employee->id)));
    }
}
