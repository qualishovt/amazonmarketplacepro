# IntelliPresta SP-API OAuth Relay

Tiny hosted service that lets Marketplaces Pro customers connect to Amazon
with one click. It holds the app's LWA client secret so the secret never
ships inside the PrestaShop module.

**⚠ This folder is NOT part of the PrestaShop module. Exclude it from the
module ZIP uploaded to PrestaShop Addons.**

## Endpoints

| File | Purpose |
|---|---|
| `callback.php` | Amazon OAuth redirect URI. Exchanges the one-time code for the merchant's refresh token, then redirects back to the merchant's shop. |
| `token.php` | Merchant shops POST their refresh token here (hourly) and get a short-lived access token back. |

## Deploy (cPanel)

1. In cPanel → File Manager, create the folder `public_html/spapi/`.
2. Upload `callback.php`, `token.php`, `lwa.php`, `.htaccess`, `config.sample.php` into it.
3. Copy `config.sample.php` to `config.php` and fill in:
   - `IPRESTA_LWA_CLIENT_ID` / `IPRESTA_LWA_CLIENT_SECRET` — from Solution
     Provider Portal → your app client → "View" credentials.
   - `IPRESTA_REDIRECT_URI` — `https://intellipresta.com/spapi/callback.php`
4. Make sure HTTPS works for the domain (cPanel → SSL/TLS, AutoSSL is fine).
5. In the Solution Provider Portal, edit the app client and set its
   **OAuth Redirect URI** to exactly `https://intellipresta.com/spapi/callback.php`.

## Test

- `https://intellipresta.com/spapi/token.php` in a browser → must answer
  `405 method_not_allowed` (JSON). That means it's deployed and configured.
- `https://intellipresta.com/spapi/config.php` → must answer **403 Forbidden**
  (the .htaccess protects it).

## Flow

```
Merchant shop  ── "Connect to Amazon" ──▶  Seller Central consent page
Seller Central ── code + state ──▶  callback.php (this server)
callback.php   ── exchanges code using client secret ──▶ Amazon LWA
callback.php   ── refresh_token ──▶  merchant shop (stores it)
merchant shop  ── POST refresh_token to token.php (hourly) ──▶ access token
merchant shop  ── all SP-API data calls go directly to Amazon
```
