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
 * OAuth return endpoint for the "Connect with Amazon" flow.
 *
 * The IntelliPresta relay redirects the merchant here after exchanging the
 * Amazon authorization code. Query parameters:
 *   refresh_token, selling_partner_id, nonce   (success)
 *   mkpro_oauth_error, nonce                   (failure / cancelled)
 *
 * The nonce must match the one generated when the merchant clicked
 * "Connect to Amazon" — it is single-use and cleared after storing the token.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonMarketplaceProOauthModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonSpApiClient.php';

        // Connection state lives in GLOBAL configuration rows. This front
        // controller runs in shop context, where plain get()/updateValue()
        // would read/create shop-scoped rows that shadow what the admin
        // wrote — the nonce would never match and the token would be
        // invisible to the back office.
        $nonce = (string) Tools::getValue('nonce');
        $expected = (string) Configuration::getGlobalValue('AMZPRO_OAUTH_NONCE');

        if ($expected === '' || $nonce === '' || !hash_equals($expected, $nonce)) {
            $this->htmlPage(
                'Connection failed',
                'This connection link is invalid or has expired. Please go back to your '
                . 'PrestaShop admin and click "Connect to Amazon" again.'
            );
            return;
        }

        // Nonce is single-use.
        Configuration::updateGlobalValue('AMZPRO_OAUTH_NONCE', '');

        $error = (string) Tools::getValue('mkpro_oauth_error');
        if ($error !== '') {
            $this->htmlPage(
                'Amazon connection was not completed',
                'Amazon reported: ' . $error . ' — You can retry from the module settings.'
            );
            return;
        }

        $refreshToken = (string) Tools::getValue('refresh_token');
        if ($refreshToken === '' || strpos($refreshToken, 'Atzr|') !== 0) {
            $this->htmlPage(
                'Connection failed',
                'No valid token was received from Amazon. Please retry from the module settings.'
            );
            return;
        }

        // Sandbox and production tokens are stored apart — see refreshTokenKey().
        Configuration::updateGlobalValue(AmazonSpApiClient::refreshTokenKey(), $refreshToken);
        Configuration::updateGlobalValue('AMZPRO_AUTH_MODE', 'connect');

        // Amazon tells us the seller's id — that's the Merchant Token the
        // Listings API needs, so the merchant never has to look it up.
        $sellingPartnerId = (string) Tools::getValue('selling_partner_id');
        if ($sellingPartnerId !== '') {
            Configuration::updateGlobalValue('AMZPRO_SELLING_PARTNER_ID', $sellingPartnerId);
            Configuration::updateGlobalValue('AMZPRO_SELLER_ID', $sellingPartnerId);
        }

        // A connected customer shop talks to the real API. Dev shops keep
        // their selected environment — the token was just stored in that
        // environment's slot, so forcing production would orphan a sandbox
        // token and break the sandbox connection state.
        if (!Configuration::get('AMZPRO_DEV_MODE')) {
            Configuration::updateGlobalValue('AMZPRO_ENVIRONMENT', 'production');
            Configuration::updateGlobalValue('AMZPRO_USE_MOCK', '0');
        }

        // Send the merchant back to the admin page they clicked Connect on.
        $returnUrl = (string) Configuration::getGlobalValue('AMZPRO_OAUTH_RETURN_URL');
        Configuration::updateGlobalValue('AMZPRO_OAUTH_RETURN_URL', '');
        if ($returnUrl !== '' && preg_match('#^https?://#i', $returnUrl)) {
            $sep = (strpos($returnUrl, '?') !== false) ? '&' : '?';
            Tools::redirect($returnUrl . $sep . 'mkpro_connected=1');
        }

        // Fallback if the return URL is unavailable (e.g. config cleared).
        $this->htmlPage(
            'Connected to Amazon ✓',
            'Your shop is now connected to Amazon'
            . ($sellingPartnerId !== '' ? ' (seller ' . htmlspecialchars($sellingPartnerId) . ')' : '')
            . '. You can close this tab and return to the Marketplaces Pro settings in your shop admin.'
        );
    }

    /**
     * Render a minimal standalone HTML page and stop (no theme dependencies).
     */
    private function htmlPage($title, $message)
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>'
            . '<style>body{font-family:sans-serif;max-width:620px;margin:80px auto;color:#333}'
            . 'h1{font-size:20px}div{padding:16px;border:1px solid #ddd;border-radius:6px;background:#fafafa}</style>'
            . '</head><body><div><h1>' . htmlspecialchars($title) . '</h1><p>' . $message . '</p></div></body></html>';
        exit;
    }
}
