<?php
/**
 * IntelliPresta SP-API OAuth relay — configuration.
 *
 * DEPLOY: copy this file to config.php on the server and fill in the values
 * from the Amazon Solution Provider Portal (your app client "MarketplacePro").
 * NEVER commit config.php or ship it inside the PrestaShop module.
 *
 * SECRETS ARE STORED ENCRYPTED. Amazon's Data Protection Policy (Encryption at
 * Rest, 2.4) does not allow the LWA client secret to sit here in plain text.
 * See ENCRYPTION SETUP at the bottom of this file.
 */

// LWA client id of YOUR app client (Solution Provider Portal -> App clients).
// The id is not a secret and is stored as-is.
define('IPRESTA_LWA_CLIENT_ID', 'amzn1.application-oa2-client.XXXXXXXXXXXXXXXX');

// LWA client SECRET, encrypted. Produce this value with:
//     php secret-tool.php encrypt
// Plain values still work so an existing relay keeps running during the
// upgrade, but "php secret-tool.php check" will report them and they must not
// be left that way.
define('IPRESTA_LWA_CLIENT_SECRET', 'enc:v1:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

// Credentials of the SANDBOX app client, if you keep a separate one for
// development. Optional: leave undefined and the relay simply refuses sandbox
// token requests. Refresh tokens are app-scoped, so a token minted by the
// sandbox app is rejected by the production app and vice versa.
define('IPRESTA_LWA_SANDBOX_CLIENT_ID', 'amzn1.application-oa2-client.XXXXXXXXXXXXXXXX');
define('IPRESTA_LWA_SANDBOX_CLIENT_SECRET', 'enc:v1:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

// Public URL of callback.php — must EXACTLY match the OAuth Redirect URI
// registered for the app client in the Solution Provider Portal.
define('IPRESTA_REDIRECT_URI', 'https://intellipresta.com/spapi/callback.php');

/* ---------------------------------------------------------------------------
 * ENCRYPTION SETUP
 *
 * The master key must NOT live in this file - a key stored beside its own
 * ciphertext protects nothing. Choose exactly one of these:
 *
 * 1. Environment variable (preferred - the key never touches the disk).
 *    In the vhost, or in an .htaccess ABOVE the web root:
 *        SetEnv IPRESTA_MASTER_KEY <base64 key from: php secret-tool.php genkey>
 *    Many hosting panels also expose environment variables in their UI.
 *
 * 2. Key file outside the web root. Uncomment and point at it:
 *        define('IPRESTA_KEY_FILE', '/home/USER/.ipresta-key');
 *    Create it with mode 0600, owned by the web user, never inside public_html.
 *
 * Verify afterwards with:
 *        php secret-tool.php check
 * which reports whether the key is visible and whether every secret decrypts.
 *
 * If you lose the key, nothing is unrecoverable: rotate the client secret in
 * the Solution Provider Portal and re-encrypt the new one.
 * ------------------------------------------------------------------------- */
