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
 * Minimal Amazon Selling Partner API client.
 *
 * Deliberately written to run on PHP 5.6+ (no scalar type hints, no enums,
 * no ?? operator, no Throwable) so it works inside any PrestaShop backend,
 * including very old PHP versions. It uses ONLY the cURL and JSON extensions.
 *
 * Amazon dropped the AWS Signature V4 / IAM requirement in 2023, so SP-API
 * calls now only need an LWA (Login With Amazon) access token sent in the
 * "x-amz-access-token" header — which makes a pure-cURL client possible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonSpApiClient
{
    const LWA_TOKEN_URL = 'https://api.amazon.com/auth/o2/token';

    /** SP-API regional hosts (production + sandbox). */
    const ENDPOINT_NA = 'https://sellingpartnerapi-na.amazon.com';
    const ENDPOINT_EU = 'https://sellingpartnerapi-eu.amazon.com';
    const ENDPOINT_FE = 'https://sellingpartnerapi-fe.amazon.com';
    const ENDPOINT_NA_SANDBOX = 'https://sandbox.sellingpartnerapi-na.amazon.com';
    const ENDPOINT_EU_SANDBOX = 'https://sandbox.sellingpartnerapi-eu.amazon.com';
    const ENDPOINT_FE_SANDBOX = 'https://sandbox.sellingpartnerapi-fe.amazon.com';

    /** RDT token endpoint (same base as Tokens API). */
    const RDT_PATH = '/tokens/2021-03-01/restrictedDataToken';

    /** IntelliPresta app defaults for the "Connect with Amazon" flow. */
    const DEFAULT_LWA_APP_ID = 'amzn1.sp.solution.fe7f779a-ff2e-4518-8d1a-fc48c44bdb69';
    const DEFAULT_LWA_APP_ID_SANDBOX = 'amzn1.sp.solution.54283948-5e29-42ce-bfe0-1ef477384b87';
    const DEFAULT_RELAY_URL = 'https://intellipresta.com/spapi';

    /**
     * @return bool Whether the module is configured for the SP-API sandbox.
     */
    public static function isSandboxEnv()
    {
        return self::environment() === 'sandbox';
    }

    /**
     * The active environment, read from global scope.
     *
     * Read globally for the same reason the refresh tokens and the OAuth
     * nonce are: this value decides WHICH token slot and WHICH app client are
     * in play, so it has to give the same answer in every context - the admin
     * in an all-shops context, the front controller in a shop context, and
     * cron with no shop context at all. Configuration::get() is shop-scoped,
     * so a shop-level row silently shadows the global one and the module
     * connects in one environment while believing it is in the other.
     *
     * The fallback to the shop-scoped value keeps installations working that
     * were saved before this became global.
     *
     * @return string 'production' or 'sandbox'
     */
    public static function environment()
    {
        $env = (string) Configuration::getGlobalValue('AMZPRO_ENVIRONMENT');

        if ($env === '') {
            $env = (string) Configuration::get('AMZPRO_ENVIRONMENT');
        }

        return ($env === 'sandbox') ? 'sandbox' : 'production';
    }

    /**
     * The sandbox and production app clients are separate registrations with
     * separate ids, so the consent page must be told which one is asking.
     *
     * @param bool|null $sandbox Null = read the configured environment
     * @return string The configured app id, falling back to the built-in default.
     */
    public static function lwaAppId($sandbox = null)
    {
        if ($sandbox === null) {
            $sandbox = self::isSandboxEnv();
        }
        // The override is per-environment: a production app id must never be
        // sent to the consent page while the relay is redeeming the resulting
        // code with the sandbox app's secret (Amazon returns invalid_grant).
        $key = $sandbox ? 'AMZPRO_LWA_APP_ID_SANDBOX' : 'AMZPRO_LWA_APP_ID';
        $v = trim((string) Configuration::get($key));
        if ($v !== '') {
            return $v;
        }
        return $sandbox ? self::DEFAULT_LWA_APP_ID_SANDBOX : self::DEFAULT_LWA_APP_ID;
    }

    /**
     * Amazon issues refresh tokens per app client, so a sandbox token and a
     * production token cannot share one storage slot — connecting to one
     * would silently disconnect the other.
     *
     * @param bool|null $sandbox Null = read the configured environment
     * @return string Configuration key holding the refresh token
     */
    public static function refreshTokenKey($sandbox = null)
    {
        if ($sandbox === null) {
            $sandbox = self::isSandboxEnv();
        }
        return $sandbox ? 'AMZPRO_REFRESH_TOKEN_SANDBOX' : 'AMZPRO_REFRESH_TOKEN';
    }

    /**
     * @return string The refresh token for the active environment ('' if not connected).
     */
    public static function storedRefreshToken($sandbox = null)
    {
        // Connection state is stored globally: the admin (any shop context)
        // and the front/cron controllers must all see the same token.
        $token = (string) Configuration::getGlobalValue(self::refreshTokenKey($sandbox));

        if ($token !== '') {
            return $token;
        }

        if ($sandbox === null) {
            $sandbox = self::isSandboxEnv();
        }

        // Sandbox with no sandbox token: fall back to the production one.
        //
        // The sandbox HOST and the app client that minted the token are
        // independent. Amazon documents sandbox calls as "identical to making
        // production calls, except you direct calls to the sandbox endpoints",
        // so a production token is expected to work there - and a Sandbox-type
        // app cannot complete the OAuth consent flow at all, which would
        // otherwise leave the sandbox permanently unreachable.
        if ($sandbox) {
            return (string) Configuration::getGlobalValue('AMZPRO_REFRESH_TOKEN');
        }

        return '';
    }

    /**
     * Which app client minted the refresh token now in use.
     *
     * Not the same question as which endpoint we are calling: with the
     * fallback above, a production token can be used against the sandbox
     * host. The relay needs the credential set that matches the TOKEN,
     * because refresh tokens are app-scoped - presenting one to the other
     * app's secret is rejected as invalid_grant.
     *
     * @return bool True when the sandbox app's credentials should be used
     */
    public static function tokenIsSandbox()
    {
        if (!self::isSandboxEnv()) {
            return false;
        }

        // Sandbox environment, but only sandbox-app credentials if a token
        // minted by that app actually exists.
        return (string) Configuration::getGlobalValue('AMZPRO_REFRESH_TOKEN_SANDBOX') !== '';
    }

    /**
     * @return string The configured relay base URL, falling back to the built-in default.
     */
    public static function relayUrl()
    {
        $v = trim((string) Configuration::get('AMZPRO_RELAY_URL'));
        return ($v !== '') ? rtrim($v, '/') : self::DEFAULT_RELAY_URL;
    }

    /**
     * Quantity to publish on Amazon after applying the merchant's stock
     * buffer (security margin kept out of Amazon sales). Never negative.
     *
     * @param int $rawQuantity Real PrestaShop stock
     * @return int
     */
    public static function effectiveQuantity($rawQuantity)
    {
        // Panic switch: publish 0 for everything (e.g. before holidays).
        if (Configuration::get('AMZPRO_FORCE_ZERO_QTY')) {
            return 0;
        }
        $buffer = (int) Configuration::get('AMZPRO_STOCK_BUFFER');
        $qty = (int) $rawQuantity - max(0, $buffer);
        return ($qty > 0) ? $qty : 0;
    }

    /**
     * The condition_type value for listings (merchant-configured; Amazon
     * vocabulary: new_new, used_like_new, used_very_good, used_good,
     * used_acceptable, refurbished_refurbished, collectible_*).
     *
     * @return string
     */
    public static function listingCondition()
    {
        $v = trim((string) Configuration::get('AMZPRO_CONDITION_TYPE'));
        return ($v !== '') ? $v : 'new_new';
    }

    /**
     * Enrich a listing's attributes with merchant-level offer settings:
     * shipping template (merchant_shipping_group) and Amazon Business B2B
     * price (configured as a percentage discount off the B2C price).
     *
     * Call this on every body that carries a purchasable_offer.
     *
     * @param array  $attributes    Listing attributes (by reference semantics via return)
     * @param string $marketplaceId
     * @param float  $priceTaxIncl  The B2C price the B2B discount applies to
     * @param string|null $templateOverride Resolved shipping template name (e.g. from
     *                                      price/weight ranges); null = use the static config
     * @return array
     */
    public static function enrichOfferAttributes($attributes, $marketplaceId, $priceTaxIncl, $templateOverride = null)
    {
        // Shipping template assignment
        $template = ($templateOverride !== null)
            ? trim((string) $templateOverride)
            : trim((string) Configuration::get('AMZPRO_SHIPPING_TEMPLATE'));
        if ($template !== '' && !isset($attributes['merchant_shipping_group'])) {
            $attributes['merchant_shipping_group'] = array(array(
                'value' => $template,
                'marketplace_id' => $marketplaceId,
            ));
        }

        // Amazon Business offer (price + quantity ladder) is built by
        // AmazonListingSettings::applyBusinessPricing(), which needs the
        // product to read its tier prices — the caller applies it.

        return $attributes;
    }

    /** Listing currency per marketplace id (ISO 4217). */
    private static $marketplaceCurrencies = array(
        'ATVPDKIKX0DER'  => 'USD', // US
        'A2EUQ1WTGCTBG2' => 'CAD', // Canada
        'A1AM78C64UM0Y8' => 'MXN', // Mexico
        'A2Q3Y263D00KMC' => 'BRL', // Brazil
        'A1F83G8C2ARO7P' => 'GBP', // UK
        'A1PA6795UKMFR9' => 'EUR', // Germany
        'A13V1IB3VIYZZH' => 'EUR', // France
        'APJ6JRA9NG5V4'  => 'EUR', // Italy
        'A1RKKUPIHCS9HS' => 'EUR', // Spain
        'A1805IZSGTT6HS' => 'EUR', // Netherlands
        'A1C3SOZRARQ6R3' => 'PLN', // Poland
        'A2NODRKZP88ZB9' => 'SEK', // Sweden
        'AMEN7PMS3EDWL'  => 'EUR', // Belgium
        'A28R8C7NBKEWEA' => 'EUR', // Ireland
        'ARBP9OOSHTCHU'  => 'EGP', // Egypt
        'AE08WJ6YKNBMC'  => 'ZAR', // South Africa
        'A33AVAJ2PDY3EV' => 'TRY', // Turkey
        'A21TJRUUN4KGV'  => 'INR', // India
        'A2VIGQ35RCS4UG' => 'AED', // UAE
        'A17E79C6D8DWNP' => 'SAR', // Saudi Arabia
        'A19VAU5U5O7RUS' => 'SGD', // Singapore
        'A39IBJ37TRP1C6' => 'AUD', // Australia
        'A1VC38T7YXB528' => 'JPY', // Japan
    );

    /** Marketplaces served by each Amazon FBA fulfilment network. */
    private static $fbaChannels = array(
        'AMAZON_NA' => array('ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8', 'A2Q3Y263D00KMC'),
        'AMAZON_JP' => array('A1VC38T7YXB528'),
        'AMAZON_AU' => array('A39IBJ37TRP1C6'),
        'AMAZON_SG' => array('A19VAU5U5O7RUS'),
        'AMAZON_IN' => array('A21TJRUUN4KGV'),
    );

    /**
     * The fulfilment channel code that marks a listing as Fulfilled by Amazon.
     *
     * The code names the FBA network, not the marketplace, so it differs by
     * region — sending AMAZON_NA on a European listing is rejected. Amazon
     * publishes the exact values a seller may use in their Product Type
     * Definitions schema, so a merchant whose account differs can pin it.
     *
     * @return string
     */
    public static function fbaChannelCode($marketplaceId)
    {
        $override = trim((string) Configuration::get('AMZPRO_FBA_CHANNEL_CODE'));
        if ($override !== '') {
            return $override;
        }
        foreach (self::$fbaChannels as $code => $marketplaces) {
            if (in_array($marketplaceId, $marketplaces)) {
                return $code;
            }
        }

        // Everything else in the catalogue is a European marketplace.
        return 'AMAZON_EU';
    }

    /**
     * @param string $marketplaceId Amazon marketplace id (e.g. ATVPDKIKX0DER)
     *
     * @return string ISO 4217 currency code for listings on that marketplace
     */
    public static function currencyForMarketplace($marketplaceId)
    {
        if (isset(self::$marketplaceCurrencies[$marketplaceId])) {
            return self::$marketplaceCurrencies[$marketplaceId];
        }

        return 'EUR';
    }

    private $clientId;
    private $clientSecret;
    private $refreshToken;
    private $endpoint;
    private $caBundle;

    /** @var string|null Base URL of the IntelliPresta token relay (no client secret needed locally). */
    private $tokenRelayUrl = null;

    private $accessToken = null;
    private $lastError = null;

    /**
     * Which app client's credentials the relay should use, when the caller
     * knows better than the stored configuration. Null = ask configuration.
     *
     * @var bool|null
     */
    private $credentialSetIsSandbox = null;

    /**
     * @param string $clientId     LWA client id
     * @param string $clientSecret LWA client secret
     * @param string $refreshToken LWA refresh token
     * @param string $endpoint     SP-API base host (use an ENDPOINT_* constant)
     * @param string|null $caBundle Optional absolute path to a cacert.pem for SSL verification
     */
    public function __construct($clientId, $clientSecret, $refreshToken, $endpoint, $caBundle = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
        $this->endpoint = rtrim($endpoint, '/');

        // Fall back to a cacert.pem shipped next to this class, if present.
        if ($caBundle === null) {
            $bundled = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem';
            if (file_exists($bundled)) {
                $caBundle = $bundled;
            }
        }
        $this->caBundle = $caBundle;
    }

    /**
     * Force which app client's credentials the relay uses for the token
     * exchange, independently of the endpoint being called.
     *
     * @param bool $isSandbox True for the sandbox app's credentials
     * @return void
     */
    public function setCredentialSet($isSandbox)
    {
        $this->credentialSetIsSandbox = (bool) $isSandbox;
    }

    /**
     * @return string|null The last error message, or null if the last call succeeded.
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Use a hosted token relay instead of local LWA credentials.
     *
     * In "Connect with Amazon" mode the module has no client id/secret; the
     * relay (run by IntelliPresta) holds them and exchanges this shop's
     * refresh token for access tokens.
     *
     * @param string $baseUrl e.g. https://intellipresta.com/spapi
     */
    public function setTokenRelay($baseUrl)
    {
        $baseUrl = trim((string) $baseUrl);
        $this->tokenRelayUrl = ($baseUrl !== '') ? rtrim($baseUrl, '/') : null;
    }

    /**
     * Exchange the refresh token for an access token (cached for this instance).
     *
     * @return bool true on success; on failure see getLastError()
     */
    public function authenticate()
    {
        if ($this->accessToken !== null) {
            return true;
        }

        if ($this->tokenRelayUrl !== null) {
            return $this->authenticateViaRelay();
        }

        $payload = http_build_query(array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ));

        $res = $this->httpRaw('POST', self::LWA_TOKEN_URL, $payload, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ));

        if ($res === false) {
            return false; // transport error, lastError already set
        }

        $data = json_decode($res['body'], true);

        if ($res['status'] >= 400 || !is_array($data) || !isset($data['access_token'])) {
            $err = isset($data['error']) ? $data['error'] : 'token_error';
            $desc = isset($data['error_description']) ? $data['error_description'] : $res['body'];
            $this->lastError = 'LWA token exchange failed (' . $res['status'] . '): ' . $err . ' - ' . $desc;
            return false;
        }

        $this->accessToken = $data['access_token'];
        return true;
    }

    /**
     * Get an access token from the hosted relay (Connect-with-Amazon mode).
     *
     * @return bool
     */
    private function authenticateViaRelay()
    {
        if (trim((string) $this->refreshToken) === '') {
            $this->lastError = 'Not connected to Amazon yet: no refresh token stored. '
                . 'Use the "Connect to Amazon" button in the module settings.';
            return false;
        }

        // The relay holds one credential pair per app client, and the token
        // decides which - NOT the endpoint. A production token used against
        // the sandbox host still has to be exchanged with the production
        // secret, because refresh tokens are app-scoped and the other app's
        // secret is rejected as invalid_grant.
        //
        // setCredentialSet() overrides this for callers that build the client
        // directly; otherwise ask the configuration.
        if ($this->credentialSetIsSandbox !== null) {
            $sandbox = $this->credentialSetIsSandbox;
        } else {
            $sandbox = self::tokenIsSandbox();
        }

        $res = $this->httpRaw(
            'POST',
            $this->tokenRelayUrl . '/token.php',
            http_build_query(array(
                'refresh_token' => $this->refreshToken,
                'sandbox' => $sandbox ? '1' : '',
            )),
            array(
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            )
        );

        if ($res === false) {
            return false; // transport error, lastError already set
        }

        $data = json_decode($res['body'], true);

        if ($res['status'] >= 400 || !is_array($data) || !isset($data['access_token'])) {
            $err = isset($data['error']) ? $data['error'] : 'relay_error';
            $desc = isset($data['error_description']) ? $data['error_description'] : $res['body'];
            $this->lastError = 'Token relay request failed (' . $res['status'] . '): ' . $err . ' - ' . $desc;
            return false;
        }

        $this->accessToken = $data['access_token'];
        return true;
    }

    /**
     * Make an authenticated SP-API request.
     *
     * @param string     $method HTTP verb (GET, POST, PUT, DELETE...)
     * @param string     $path   Path beginning with "/", e.g. "/orders/v0/orders"
     * @param array      $query  Query-string parameters
     * @param array|null $body   Body to JSON-encode, or null for none
     *
     * @return array|false array('status' => int, 'body' => mixed) or false on transport error
     */
    public function request($method, $path, $query = array(), $body = null)
    {
        if (!$this->authenticate()) {
            return false;
        }

        $url = $this->endpoint . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = array(
            'x-amz-access-token: ' . $this->accessToken,
            'Accept: application/json',
        );

        $payload = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body);
        }

        $res = $this->httpRaw($method, $url, $payload, $headers);
        if ($res === false) {
            return false;
        }

        $decoded = json_decode($res['body'], true);

        return array(
            'status' => $res['status'],
            // Return the decoded array when possible, else the raw string.
            'body' => ($decoded === null && trim($res['body']) !== '') ? $res['body'] : $decoded,
        );
    }

    /**
     * Obtain a Restricted Data Token (RDT) for accessing PII data.
     *
     * Used to fetch buyer shipping address, buyer name, etc. from Orders API.
     * The RDT is a short-lived token that grants access to specific restricted
     * resources.
     *
     * @param array $restrictedResources Array of arrays with 'method', 'path', 'dataElements'
     *   Example: array(array(
     *       'method' => 'GET',
     *       'path' => '/orders/v0/orders/123-456/address',
     *       'dataElements' => array('shippingAddress')
     *   ))
     *
     * @return string|false The RDT token string, or false on failure
     */
    public function getRestrictedDataToken($restrictedResources)
    {
        if (!$this->authenticate()) {
            return false;
        }

        $body = array(
            'restrictedResources' => $restrictedResources,
        );

        $resp = $this->request('POST', self::RDT_PATH, array(), $body);

        if ($resp === false) {
            return false;
        }

        if ($resp['status'] >= 400 || !is_array($resp['body'])) {
            $bodyStr = is_array($resp['body']) ? json_encode($resp['body']) : (string) $resp['body'];
            $this->lastError = 'RDT request failed (HTTP ' . $resp['status'] . '): ' . $bodyStr;
            return false;
        }

        if (isset($resp['body']['restrictedDataToken'])) {
            return $resp['body']['restrictedDataToken'];
        }

        $this->lastError = 'RDT response missing restrictedDataToken field';
        return false;
    }

    /**
     * Make an SP-API request using a Restricted Data Token instead of the
     * standard access token. Used for PII-restricted endpoints like buyer
     * shipping address.
     *
     * @param string      $rdtToken The restricted data token
     * @param string      $method   HTTP verb
     * @param string      $path     API path
     * @param array       $query    Query parameters
     * @param array|null  $body     Body to JSON-encode
     *
     * @return array|false Same as request()
     */
    public function requestWithRDT($rdtToken, $method, $path, $query = array(), $body = null)
    {
        $url = $this->endpoint . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = array(
            'x-amz-access-token: ' . $rdtToken,
            'Accept: application/json',
        );

        $payload = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body);
        }

        $res = $this->httpRaw($method, $url, $payload, $headers);
        if ($res === false) {
            return false;
        }

        $decoded = json_decode($res['body'], true);

        return array(
            'status' => $res['status'],
            'body' => ($decoded === null && trim($res['body']) !== '') ? $res['body'] : $decoded,
        );
    }

    /**
     * Upload raw content to a presigned feed-document URL (no auth headers).
     *
     * @param string $url         Presigned upload URL from createFeedDocument
     * @param string $content     Raw document body
     * @param string $contentType Must match the contentType the document was created with
     * @return bool
     */
    public function uploadDocument($url, $content, $contentType)
    {
        $res = $this->httpRaw('PUT', $url, $content, array('Content-Type: ' . $contentType));
        if ($res === false) {
            return false;
        }
        if ($res['status'] >= 400) {
            $this->lastError = 'Document upload failed (HTTP ' . $res['status'] . '): '
                . Tools::substr($res['body'], 0, 300);
            return false;
        }
        return true;
    }

    /**
     * Download a feed result document from its presigned URL, transparently
     * decompressing GZIP results.
     *
     * @param string $url
     * @param string $compressionAlgorithm '' or 'GZIP'
     * @return string|false
     */
    public function downloadDocument($url, $compressionAlgorithm = '')
    {
        $res = $this->httpRaw('GET', $url, null, array());
        if ($res === false) {
            return false;
        }
        if ($res['status'] >= 400) {
            $this->lastError = 'Document download failed (HTTP ' . $res['status'] . ')';
            return false;
        }

        $body = $res['body'];
        if ($compressionAlgorithm === 'GZIP') {
            $decoded = @gzdecode($body);
            if ($decoded === false) {
                $this->lastError = 'Could not decompress the GZIP result document.';
                return false;
            }
            $body = $decoded;
        }

        return $body;
    }

    /**
     * Low-level cURL transport.
     *
     * @return array|false array('status' => int, 'body' => string) or false on transport error
     */
    private function httpRaw($method, $url, $payload, $headers)
    {
        $this->lastError = null;

        if (!function_exists('curl_init')) {
            $this->lastError = 'The cURL PHP extension is not available on this server.';
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($this->caBundle !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caBundle);
        }

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $body = curl_exec($ch);

        if ($body === false) {
            $this->lastError = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array('status' => $status, 'body' => $body);
    }
}
