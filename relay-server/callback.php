<?php
/**
 * IntelliPresta SP-API OAuth relay — authorization callback.
 *
 * Amazon redirects the merchant here after they approve the app on Seller
 * Central. We exchange the one-time authorization code for the merchant's
 * refresh token (this requires our client secret, which only lives on this
 * server), then send the merchant back to their own shop, which stores it.
 *
 * Registered as the app's OAuth Redirect URI in the Solution Provider Portal.
 */

require_once dirname(__FILE__) . '/lwa.php';

$code = isset($_GET['spapi_oauth_code']) ? (string) $_GET['spapi_oauth_code'] : '';
$state = isset($_GET['state']) ? (string) $_GET['state'] : '';
$sellingPartnerId = isset($_GET['selling_partner_id']) ? (string) $_GET['selling_partner_id'] : '';

$decoded = ipresta_decode_state($state);
if ($decoded === null) {
    ipresta_error_page('Invalid request', 'The connection request is missing or malformed (bad state parameter).');
}

// Merchant denied consent, or Amazon reported an error.
if ($code === '') {
    $reason = isset($_GET['error_description']) ? (string) $_GET['error_description']
        : (isset($_GET['error']) ? (string) $_GET['error'] : 'Authorization was cancelled or no code was returned.');
    $sep = (strpos($decoded['r'], '?') !== false) ? '&' : '?';
    header('Location: ' . $decoded['r'] . $sep . http_build_query(array(
        'mkpro_oauth_error' => $reason,
        'nonce' => $decoded['n'],
    )));
    exit;
}

// Exchange the one-time code for the merchant's refresh token.
// Which app client authorized the merchant is carried in the state we issued.
$res = ipresta_lwa_request(array(
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => IPRESTA_REDIRECT_URI,
), $decoded['s']);

if ($res['status'] !== 200 || !isset($res['data']['refresh_token'])) {
    $detail = isset($res['data']['error_description']) ? $res['data']['error_description'] : $res['raw'];
    ipresta_error_page('Amazon token exchange failed', 'Amazon rejected the authorization code (HTTP '
        . $res['status'] . '): ' . $detail);
}

// Hand the refresh token back to the merchant's shop.
$sep = (strpos($decoded['r'], '?') !== false) ? '&' : '?';
header('Location: ' . $decoded['r'] . $sep . http_build_query(array(
    'refresh_token' => $res['data']['refresh_token'],
    'selling_partner_id' => $sellingPartnerId,
    'nonce' => $decoded['n'],
)));
exit;
