<?php
/**
 * IntelliPresta relay — scheduler registration.
 *
 * Shops that cannot, or would rather not, keep a crontab line register here.
 * schedule-run.php then calls their run_due endpoint every five minutes.
 *
 *   POST action=register    cron_url, token, shop_name, seller_id, module_version
 *   POST action=unregister  cron_url, token
 *
 * WHAT THIS STORES, AND WHY IT MATTERS. A registration is a URL and that
 * shop's cron token. The token is enough to trigger a sync on that shop, so
 * the registry is a credential store: it lives outside the web root, 0600,
 * and this script refuses to run rather than write it anywhere servable. No
 * Amazon Information passes through here - the relay only asks the shop to run
 * its own schedule; the shop talks to Amazon itself.
 *
 * WHY A CALLBACK. Registration is not taken on trust. Before storing anything
 * this calls the URL back with a nonce and requires the shop to echo it using
 * the same token. That proves three things at once: the address runs our
 * module, the token is genuine for it, and whoever posted here controls that
 * shop. Without it, anyone could register any address and this scheduler would
 * become a machine for making requests to hosts of their choosing.
 *
 * PHP 5.6+ compatible.
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__) . '/schedule-store.php';

/** Answer and stop. */
function reply(array $data, $code = 200)
{
    header('HTTP/1.1 ' . $code . ' ' . ($code === 200 ? 'OK' : 'Error'), true, $code);
    echo json_encode($data);
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(array('success' => false, 'error' => 'POST only.'), 405);
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$cronUrl = isset($_POST['cron_url']) ? trim((string) $_POST['cron_url']) : '';
$token = isset($_POST['token']) ? trim((string) $_POST['token']) : '';

if ($cronUrl === '' || $token === '') {
    reply(array('success' => false, 'error' => 'cron_url and token are required.'), 400);
}

$store = new ScheduleStore();

if ($action === 'unregister') {
    $store->remove($cronUrl);
    reply(array('success' => true, 'message' => 'Removed from the scheduler.'));
}

if ($action !== 'register') {
    reply(array('success' => false, 'error' => 'Unknown action.'), 400);
}

/* ── The address has to be one we are willing to call ──────────────────── */

$why = ScheduleStore::rejectUrl($cronUrl);
if ($why !== null) {
    reply(array('success' => false, 'error' => $why), 400);
}

/* ── Prove the shop asked for this ─────────────────────────────────────── */

$nonce = bin2hex(openssl_random_pseudo_bytes(16));
$probe = $cronUrl
    . (strpos($cronUrl, '?') === false ? '?' : '&')
    . 'token=' . rawurlencode($token)
    . '&action=verify&nonce=' . rawurlencode($nonce);

$ch = curl_init($probe);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);   // a redirect could leave the checks behind
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
$body = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($body === false) {
    reply(array(
        'success' => false,
        'error' => 'The scheduler could not reach this shop: ' . $curlError,
    ), 400);
}

$answer = json_decode($body, true);
if (!is_array($answer) || empty($answer['success']) || !isset($answer['nonce'])
    || !hash_equals($nonce, (string) $answer['nonce'])) {
    reply(array(
        'success' => false,
        'error' => 'The shop did not confirm the registration. Check that the cron token is correct.',
    ), 400);
}

/* ── Store it ──────────────────────────────────────────────────────────── */

$store->put(array(
    'cron_url' => $cronUrl,
    'token' => $token,
    'shop_name' => isset($_POST['shop_name']) ? (string) $_POST['shop_name'] : '',
    'seller_id' => isset($_POST['seller_id']) ? (string) $_POST['seller_id'] : '',
    'module_version' => isset($_POST['module_version']) ? (string) $_POST['module_version'] : '',
    'registered_at' => gmdate('Y-m-d H:i:s'),
    'last_run_at' => null,
    'last_status' => null,
    'consecutive_failures' => 0,
));

reply(array(
    'success' => true,
    'message' => 'This shop is now on the IntelliPresta schedule, checked every five minutes.',
));
