{**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *}
{*
 * Page shown at the end of "Connect to Amazon" when the shop admin cannot be
 * returned to: expired link, an error from Amazon, no token, or a seller
 * account already connected to another shop.
 *}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$mkpro_oauth_title|escape:'htmlall':'UTF-8'}</title>
    <style>
        body { font-family: sans-serif; max-width: 620px; margin: 80px auto; color: #333; }
        h1 { font-size: 20px; }
        div { padding: 16px; border: 1px solid #ddd; border-radius: 6px; background: #fafafa; }
    </style>
</head>
<body>
    <div>
        <h1>{$mkpro_oauth_title|escape:'htmlall':'UTF-8'}</h1>
        <p>{$mkpro_oauth_message|escape:'htmlall':'UTF-8'}</p>
    </div>
</body>
</html>
