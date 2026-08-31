<?php
/**
 * IntelliPresta SP-API OAuth relay — shared LWA helpers.
 * PHP 5.6+ compatible, cURL only.
 */

if (!file_exists(dirname(__FILE__) . '/config.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(array('error' => 'relay_not_configured', 'error_description' => 'config.php is missing on the relay server.')));
}
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/secrets.php';

define('IPRESTA_LWA_TOKEN_URL', 'https://api.amazon.com/auth/o2/token');

/**
 * POST to Amazon's LWA token endpoint.
 *
 * Amazon issues refresh tokens per app client, so the sandbox app and the
 * production app each need their own credential pair here — a token minted
 * for one is rejected by the other.
 *
 * @param array $params  form fields (grant_type etc.)
 * @param bool  $sandbox use the sandbox app client's credentials
 * @return array array('status' => int, 'data' => array|null, 'raw' => string)
 */
function ipresta_lwa_request($params, $sandbox = false)
{
    if ($sandbox) {
        if (!defined('IPRESTA_LWA_SANDBOX_CLIENT_ID') || IPRESTA_LWA_SANDBOX_CLIENT_ID === '') {
            return array(
                'status' => 500,
                'data' => array(
                    'error' => 'sandbox_not_configured',
                    'error_description' => 'No sandbox app credentials are configured on the relay.',
                ),
                'raw' => '',
            );
        }
        $params['client_id'] = IPRESTA_LWA_SANDBOX_CLIENT_ID;
        $params['client_secret'] = ipresta_secret('IPRESTA_LWA_SANDBOX_CLIENT_SECRET');
    } else {
        $params['client_id'] = IPRESTA_LWA_CLIENT_ID;
        $params['client_secret'] = ipresta_secret('IPRESTA_LWA_CLIENT_SECRET');
    }

    // A decryption failure must not fall through as an empty secret - LWA
    // would answer invalid_client and send us hunting the wrong problem.
    if ($params['client_secret'] === '') {
        return array(
            'status' => 500,
            'data' => array(
                'error' => 'secret_unavailable',
                'error_description' => 'The relay could not read its LWA client secret. '
                    . 'Check the master key configuration.',
            ),
            'raw' => '',
        );
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, IPRESTA_LWA_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return array('status' => 0, 'data' => null, 'raw' => 'cURL error: ' . $err);
    }
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);
    return array('status' => $status, 'data' => is_array($data) ? $data : null, 'raw' => $raw);
}

/**
 * Decode the base64url "state" passed through the consent flow.
 *
 * @return array|null array('r' => return url, 'n' => nonce, 's' => sandbox bool) or null
 */
function ipresta_decode_state($state)
{
    $json = base64_decode(strtr((string) $state, '-_', '+/'), true);
    if ($json === false) {
        return null;
    }
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['r']) || empty($data['n'])) {
        return null;
    }
    // Only redirect back to plain http(s) URLs.
    $url = (string) $data['r'];
    if (!preg_match('#^https?://#i', $url) || strpos($url, '#') !== false) {
        return null;
    }
    return array(
        'r' => $url,
        'n' => (string) $data['n'],
        's' => !empty($data['s']),
    );
}

/** Minimal HTML error page (shown to the merchant mid-flow). */
function ipresta_error_page($title, $message)
{
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>'
        . '<style>body{font-family:sans-serif;max-width:600px;margin:80px auto;color:#333}h1{font-size:20px}</style>'
        . '</head><body><h1>' . htmlspecialchars($title) . '</h1><p>' . htmlspecialchars($message) . '</p>'
        . '<p>Please return to your PrestaShop admin and try again, or contact support@intellipresta.com.</p>'
        . '</body></html>';
    exit;
}
