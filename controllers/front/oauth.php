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
                $this->text($this->module->l('Connection failed', 'oauth')),
                $this->text($this->module->l('This connection link is invalid or has expired. Please go back to your PrestaShop admin and click "Connect to Amazon" again.', 'oauth'))
            );
            return;
        }

        // Nonce is single-use.
        Configuration::updateGlobalValue('AMZPRO_OAUTH_NONCE', '');

        $error = (string) Tools::getValue('mkpro_oauth_error');
        if ($error !== '') {
            $this->htmlPage(
                $this->text($this->module->l('Amazon connection was not completed', 'oauth')),
                sprintf($this->text($this->module->l('Amazon reported: %s — You can retry from the module settings.', 'oauth')), $error)
            );
            return;
        }

        $refreshToken = (string) Tools::getValue('refresh_token');
        if ($refreshToken === '' || strpos($refreshToken, 'Atzr|') !== 0) {
            $this->htmlPage(
                $this->text($this->module->l('Connection failed', 'oauth')),
                $this->text($this->module->l('No valid token was received from Amazon. Please retry from the module settings.', 'oauth'))
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
        if ($sellingPartnerId !== '') {
            $message = sprintf(
                $this->text($this->module->l('Your shop is now connected to Amazon (seller %s). You can close this tab and return to the Amazon Marketplace Pro settings in your shop admin.', 'oauth')),
                $sellingPartnerId
            );
        } else {
            $message = $this->text($this->module->l('Your shop is now connected to Amazon. You can close this tab and return to the Amazon Marketplace Pro settings in your shop admin.', 'oauth'));
        }
        $this->htmlPage($this->text($this->module->l('Connected to Amazon', 'oauth')) . ' ✓', $message);
    }

    /**
     * Plain text from a translation. PrestaShop returns l() HTML-escaped;
     * htmlPage() escapes everything it prints, so the escaping is undone
     * here to keep quotes and accents from showing up twice-escaped.
     */
    private function text($translated)
    {
        return html_entity_decode($translated, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render a minimal standalone HTML page and stop (no theme dependencies).
     * Both arguments are plain text and are escaped here, including anything
     * that came back from Amazon in the query string.
     */
    private function htmlPage($title, $message)
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>body{font-family:sans-serif;max-width:620px;margin:80px auto;color:#333}'
            . 'h1{font-size:20px}div{padding:16px;border:1px solid #ddd;border-radius:6px;background:#fafafa}</style>'
            . '</head><body><div><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
        exit;
    }
}
