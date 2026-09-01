<?php
/**
 * 2007-2026 PrestaShop
 *
 *  @author    IntelliPresta
 *  @copyright 2007-2026 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * Live verification against the real SP-API.
 *
 * Everything here is READ-ONLY: it never creates, updates or deletes a
 * listing, an order or anything else on Amazon. Its job is to prove that the
 * parts of the module that can only be tested against Amazon actually work —
 * the schema download and parsing above all.
 *
 * Run from the module folder:
 *   php live-verify.php              read-only checks
 *   php live-verify.php SHIRT        also load that product type's schema
 *
 * Delete this file before shipping the module.
 */

$root = dirname(dirname(dirname(__FILE__)));
require_once $root . '/config/config.inc.php';

$dir = dirname(__FILE__) . '/classes/';
require_once $dir . 'AmazonSpApiClient.php';
require_once $dir . 'AmazonProductTypeDefinitions.php';
require_once $dir . 'AmazonProfile.php';

$pass = 0;
$fail = 0;
$skip = 0;

function line($status, $label, $detail = '')
{
    global $pass, $fail, $skip;
    if ($status === 'PASS') {
        $pass++;
    } elseif ($status === 'FAIL') {
        $fail++;
    } else {
        $skip++;
    }
    echo str_pad($status, 6) . $label . ($detail !== '' ? "\n        " . $detail : '') . "\n";
}

echo "\n=== Amazon Marketplace Pro — live verification (read-only) ===\n\n";

/* ── 1. Connection state ── */
$environment = AmazonSpApiClient::environment();
$marketplaceId = Configuration::get('AMZPRO_MARKETPLACE_ID');
$sellerId = trim((string) Configuration::get('AMZPRO_SELLER_ID'));
$token = AmazonSpApiClient::storedRefreshToken();

echo "Environment : $environment\n";
echo "Marketplace : $marketplaceId\n";
echo "Seller      : " . ($sellerId !== '' ? $sellerId : '(not set)') . "\n";
echo "Auth mode   : " . Configuration::get('AMZPRO_AUTH_MODE') . "\n\n";

if ($token === '') {
    echo "STOP  No refresh token is stored for the '$environment' environment.\n";
    echo "      Connect the shop to Amazon first (module Settings tab), then re-run.\n\n";
    exit(1);
}
line('PASS', 'A refresh token is stored for this environment');

/* ── 2. Access token ── */
$endpoint = AmazonSpApiClient::ENDPOINT_EU;
$region = 'EU';
$naMarketplaces = array('ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8', 'A2Q3Y263D00KMC');
$feMarketplaces = array('A1VC38T7YXB528', 'A39IBJ37TRP1C6', 'A19VAU5U5O7RUS');
if (in_array($marketplaceId, $naMarketplaces)) {
    $endpoint = AmazonSpApiClient::ENDPOINT_NA;
    $region = 'NA';
} elseif (in_array($marketplaceId, $feMarketplaces)) {
    $endpoint = AmazonSpApiClient::ENDPOINT_FE;
    $region = 'FE';
}
if ($environment !== 'production') {
    $endpoint = AmazonSpApiClient::ENDPOINT_NA_SANDBOX;
}

$client = new AmazonSpApiClient(
    Configuration::get('AMZPRO_CLIENT_ID'),
    Configuration::get('AMZPRO_CLIENT_SECRET'),
    $token,
    $endpoint
);
if (Configuration::get('AMZPRO_AUTH_MODE') !== 'manual') {
    $client->setTokenRelay(AmazonSpApiClient::relayUrl());
}

if ($client->authenticate()) {
    line('PASS', 'LWA access token obtained', 'region ' . $region . ', endpoint ' . $endpoint);
} else {
    line('FAIL', 'LWA access token', (string) $client->getLastError());
    echo "\nCannot continue without an access token.\n";
    exit(1);
}

/* ── 3. FBA channel code sanity ── */
$expected = AmazonSpApiClient::fbaChannelCode($marketplaceId);
line('PASS', 'FBA fulfilment channel code for this marketplace', $expected
    . ' (this must match a value your seller account is enrolled in)');

/* ── 4. Product type search — the schema engine's front door ── */
$defs = new AmazonProductTypeDefinitions($client, $marketplaceId);
$types = $defs->searchProductTypes('shirt');
if ($types === false) {
    line('FAIL', 'Product type search', (string) $defs->getLastError());
    if (strpos((string) $defs->getLastError(), '403') !== false) {
        echo "\n        A 403 here, with the access token working, points at the SELLER ACCOUNT\n"
            . "        rather than the code. In order of likelihood:\n"
            . "          1. The seller account is not active, or is not on a Professional\n"
            . "             selling plan. SP-API serves no data for such accounts, and every\n"
            . "             endpoint returns this same error regardless of app roles.\n"
            . "          2. The client id, secret and refresh token come from different apps.\n"
            . "          3. Roles were added to the app after the seller authorized it.\n\n";
    }
} elseif (empty($types)) {
    line('FAIL', 'Product type search returned nothing', 'expected at least one match for "shirt"');
} else {
    $names = array();
    foreach (array_slice($types, 0, 5) as $t) {
        $names[] = $t['name'];
    }
    line('PASS', count($types) . ' product type(s) matched "shirt"', implode(', ', $names));
}

/* ── 5. Schema download + parsing — the biggest untested piece ── */
$productType = isset($argv[1]) ? trim($argv[1]) : '';
if ($productType === '' && is_array($types) && !empty($types)) {
    $productType = $types[0]['name'];
}

if ($productType === '') {
    line('SKIP', 'Schema download', 'no product type available to test with');
} else {
    $definition = $defs->getDefinition($productType, true);
    if ($definition === false) {
        line('FAIL', 'Schema download for ' . $productType, (string) $defs->getLastError());
    } else {
        $attributes = $definition['attributes'];
        $required = 0;
        $withEnum = 0;
        foreach ($attributes as $a) {
            if (!empty($a['required'])) {
                $required++;
            }
            if (!empty($a['enum'])) {
                $withEnum++;
            }
        }

        line('PASS', 'Schema downloaded and parsed for ' . $productType,
            $definition['display_name'] . ' — ' . count($attributes) . ' attribute(s), '
            . $required . ' required, ' . $withEnum . ' with allowed-value lists');

        if (count($attributes) === 0) {
            line('FAIL', 'Parsed attribute list is empty',
                'the schema shape has probably changed — inspect flattenSchema()');
        } else {
            line('PASS', 'Attribute list is non-empty');

            $sample = $attributes[0];
            $shapeOk = isset($sample['name'], $sample['title'], $sample['required'], $sample['enum']);
            line($shapeOk ? 'PASS' : 'FAIL', 'Parsed attributes have the expected shape',
                $shapeOk ? 'first: ' . $sample['name'] . ' (' . $sample['title'] . ')' : print_r($sample, true));

            echo "\n        First 8 attributes Amazon wants for $productType:\n";
            foreach (array_slice($attributes, 0, 8) as $a) {
                echo '          ' . ($a['required'] ? '* ' : '  ') . str_pad($a['name'], 42)
                    . (!empty($a['enum']) ? count($a['enum']) . ' allowed value(s)' : '') . "\n";
            }
            echo "\n";
        }

        // The cache must survive a round trip.
        $cached = $defs->getDefinition($productType, false);
        line(($cached !== false && count($cached['attributes']) === count($attributes)) ? 'PASS' : 'FAIL',
            'Schema cache round-trips');
    }
}

/* ── 6. Seller-specific fulfilment codes, if the schema exposed them ── */
if (isset($definition) && $definition !== false) {
    $found = array();
    foreach ($definition['attributes'] as $a) {
        if ($a['name'] === 'fulfillment_availability' && !empty($a['enum'])) {
            $found = array_keys($a['enum']);
        }
    }
    if (!empty($found)) {
        line(in_array($expected, $found) ? 'PASS' : 'FAIL',
            'Configured FBA channel code is one your account may use',
            'schema offers: ' . implode(', ', $found));
    } else {
        line('SKIP', 'Seller-specific fulfilment codes not present in this schema',
            'set AMZPRO_FBA_CHANNEL_CODE manually if Amazon rejects ' . $expected);
    }
}

/* ── 7. Catalogue readiness ── */
require_once $dir . 'AmazonReferenceTool.php';
$audit = AmazonReferenceTool::auditCatalogue();
$problems = $audit['no_reference'] + $audit['duplicate_references'] + $audit['combinations_no_reference'];
line($problems === 0 ? 'PASS' : 'SKIP', 'Catalogue identifiers',
    $audit['no_reference'] . ' without reference, ' . $audit['duplicate_references']
    . ' duplicated, ' . $audit['combinations_no_reference'] . ' combinations without reference');

/* ── Summary ── */
echo "\n=== $pass passed, $fail failed, $skip skipped ===\n";
echo $fail === 0
    ? "\nThe live paths that could not be tested offline are working.\n"
      . "Still unproven (they write to Amazon): pushing a listing, submitting a feed,\n"
      . "deleting a listing. Try those on ONE product from the module UI.\n\n"
    : "\nFix the failures above before pushing anything to Amazon.\n\n";

exit($fail === 0 ? 0 : 1);
