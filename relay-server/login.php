<?php
/**
 * IntelliPresta SP-API OAuth relay — Appstore-initiated login endpoint.
 *
 * Registered as the app client's "OAuth Login URI". Amazon sends sellers
 * here when they click Authorize on an Appstore detail page (the
 * "appstore workflow"). Marketplaces Pro connections start from inside
 * the merchant's PrestaShop instead, so for now this page politely guides
 * the seller to the module. When the Selling Partner Appstore listing goes
 * live, this endpoint will implement the full appstore workflow.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connect Marketplaces Pro to Amazon</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="card">
    <h1><span class="brand">Marketplaces Pro</span> — Connect to Amazon</h1>
    <p>The Amazon connection for Marketplaces Pro is started from inside your PrestaShop store:</p>
    <ol>
        <li>Install the <strong>Marketplaces Pro</strong> module in your PrestaShop back office.</li>
        <li>Open <strong>Modules &rsaquo; Marketplaces Pro &rsaquo; Settings</strong>.</li>
        <li>Select your Amazon marketplace and click <strong>Connect to Amazon</strong>.</li>
    </ol>
    <p>You will be sent to Amazon Seller Central to approve the connection &mdash; no credentials to copy.</p>
    <p>Questions? Contact <a href="mailto:support@intellipresta.com">support@intellipresta.com</a>.</p>
</div>
</body>
</html>
