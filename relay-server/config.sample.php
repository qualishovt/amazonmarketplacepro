<?php
/**
 * IntelliPresta SP-API OAuth relay — configuration.
 *
 * DEPLOY: copy this file to config.php on the server and fill in the values
 * from the Amazon Solution Provider Portal (your app client "MarketplacePro").
 * NEVER commit config.php or ship it inside the PrestaShop module.
 */

// LWA credentials of YOUR app client (Solution Provider Portal -> App clients)
define('IPRESTA_LWA_CLIENT_ID', 'amzn1.application-oa2-client.XXXXXXXXXXXXXXXX');
define('IPRESTA_LWA_CLIENT_SECRET', 'amzn1.oa2-cs.v1.XXXXXXXXXXXXXXXX');

// LWA credentials of the SANDBOX app client, if you keep a separate one for
// development. Optional: leave undefined and the relay simply refuses sandbox
// token requests. Refresh tokens are app-scoped, so a token minted by the
// sandbox app is rejected by the production app and vice versa.
define('IPRESTA_LWA_SANDBOX_CLIENT_ID', 'amzn1.application-oa2-client.XXXXXXXXXXXXXXXX');
define('IPRESTA_LWA_SANDBOX_CLIENT_SECRET', 'amzn1.oa2-cs.v1.XXXXXXXXXXXXXXXX');

// Public URL of callback.php — must EXACTLY match the OAuth Redirect URI
// registered for the app client in the Solution Provider Portal.
define('IPRESTA_REDIRECT_URI', 'https://intellipresta.com/spapi/callback.php');
