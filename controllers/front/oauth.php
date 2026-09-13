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
 * Multistore: this page runs in the front office of the shop in its URL, and
 * that shop is the one being connected. The nonce is "<id_shop>.<random>",
 * stored for that shop, so a link issued for one shop cannot connect another.
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
        require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmzproShop.php';
        require_once _PS_MODULE_DIR_ . 'amazonmarketplacepro/classes/AmazonSpApiClient.php';

        // The shop in the URL is the shop being connected. Its connection
        // state is read and written through AmzproShop for exactly that shop,
        // so the back office of that shop sees what this page stores.
        $idShop = AmzproShop::id();

        $nonce = (string) Tools::getValue('nonce');
        $expected = (string) AmzproShop::get('AMZPRO_OAUTH_NONCE', $idShop);

        if ($expected === '' || $nonce === '' || !hash_equals($expected, $nonce)
            || !$this->nonceIsForShop($nonce, $idShop)) {
            $this->htmlPage(
                $this->text($this->module->l('Connection failed', 'oauth')),
                $this->text($this->module->l('This connection link is invalid or has expired. Please go back to your PrestaShop admin and click "Connect to Amazon" again.', 'oauth'))
            );
            return;
        }

        // Nonce is single-use.
        AmzproShop::set('AMZPRO_OAUTH_NONCE', '', $idShop);

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

        // Amazon tells us the seller's id — that's the Merchant Token the
        // Listings API needs, so the merchant never has to look it up.
        $sellingPartnerId = (string) Tools::getValue('selling_partner_id');

        // One seller account on one marketplace belongs to one shop: two shops
        // importing the same Amazon orders would fight over them. Refuse
        // before anything is stored.
        if ($sellingPartnerId !== '') {
            $otherShop = AmzproShop::shopUsingSeller(
                $sellingPartnerId,
                AmzproShop::get('AMZPRO_MARKETPLACE_ID', $idShop),
                $idShop
            );
            if ($otherShop) {
                $this->htmlPage(
                    $this->text($this->module->l('Connection failed', 'oauth')),
                    sprintf(
                        $this->text($this->module->l('This Amazon seller account is already connected to the shop "%s" for the same marketplace. Disconnect it in that shop first, or choose another marketplace for this shop, then click "Connect to Amazon" again.', 'oauth')),
                        AmzproShop::name($otherShop)
                    )
                );
                return;
            }
        }

        // Sandbox and production tokens are stored apart — see refreshTokenKey().
        AmzproShop::set(AmazonSpApiClient::refreshTokenKey(), $refreshToken, $idShop);
        AmzproShop::set('AMZPRO_AUTH_MODE', 'connect', $idShop);

        if ($sellingPartnerId !== '') {
            AmzproShop::set('AMZPRO_SELLING_PARTNER_ID', $sellingPartnerId, $idShop);
            AmzproShop::set('AMZPRO_SELLER_ID', $sellingPartnerId, $idShop);
        }

        // A connected customer shop talks to the real API. Dev shops keep
        // their selected environment — the token was just stored in that
        // environment's slot, so forcing production would orphan a sandbox
        // token and break the sandbox connection state. The environment is
        // the same for every shop (AmzproShop::$globalKeys).
        if (!AmzproShop::get('AMZPRO_DEV_MODE')) {
            AmzproShop::set('AMZPRO_ENVIRONMENT', 'production');
            AmzproShop::set('AMZPRO_USE_MOCK', '0');
        }

        // Send the merchant back to the admin page they clicked Connect on.
        $returnUrl = (string) AmzproShop::get('AMZPRO_OAUTH_RETURN_URL', $idShop);
        AmzproShop::set('AMZPRO_OAUTH_RETURN_URL', '', $idShop);
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
     * The nonce names the shop it was issued for ("<id_shop>.<random>"). A
     * link issued in one shop's back office must not connect another shop,
     * even if both happen to hold a nonce.
     */
    private function nonceIsForShop($nonce, $idShop)
    {
        return (bool) preg_match('/^([0-9]+)\./', $nonce, $m) && (int) $m[1] === (int) $idShop;
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
