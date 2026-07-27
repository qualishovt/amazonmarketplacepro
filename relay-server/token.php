<?php
/**
 * IntelliPresta SP-API OAuth relay — access token endpoint.
 *
 * The merchant's module POSTs its refresh token here (server-to-server) and
 * receives a short-lived access token. This is the only ongoing call that
 * needs our client secret; all SP-API data traffic goes directly from the
 * merchant's shop to Amazon.
 *
 * POST refresh_token=<token>[&sandbox=1]
 * -> 200 {"access_token": "...", "expires_in": 3600}
 * -> 4xx {"error": "...", "error_description": "..."}
 */

require_once dirname(__FILE__) . '/lwa.php';

header('Content-Type: application/json');

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(array('error' => 'method_not_allowed', 'error_description' => 'POST only.')));
}

$refreshToken = isset($_POST['refresh_token']) ? (string) $_POST['refresh_token'] : '';
if ($refreshToken === '' || strpos($refreshToken, 'Atzr|') !== 0) {
    http_response_code(400);
    exit(json_encode(array('error' => 'invalid_request', 'error_description' => 'Missing or malformed refresh_token.')));
}

$sandbox = !empty($_POST['sandbox']);

$res = ipresta_lwa_request(array(
    'grant_type' => 'refresh_token',
    'refresh_token' => $refreshToken,
), $sandbox);

if ($res['status'] !== 200 || !isset($res['data']['access_token'])) {
    http_response_code($res['status'] >= 400 ? $res['status'] : 502);
    $err = isset($res['data']['error']) ? $res['data']['error'] : 'lwa_error';
    $desc = isset($res['data']['error_description']) ? $res['data']['error_description'] : $res['raw'];
    exit(json_encode(array('error' => $err, 'error_description' => $desc)));
}

exit(json_encode(array(
    'access_token' => $res['data']['access_token'],
    'expires_in' => isset($res['data']['expires_in']) ? (int) $res['data']['expires_in'] : 3600,
)));
