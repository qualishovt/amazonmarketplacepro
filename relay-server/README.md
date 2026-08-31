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

## Encryption at rest for the LWA client secret

Amazon's Data Protection Policy (Encryption at Rest, control 2.4) does not
allow the LWA client secret to be stored in plain text. `secrets.php` keeps it
encrypted in `config.php`; the key lives elsewhere.

**Cipher:** AES-256-CBC with encrypt-then-MAC (HMAC-SHA256), verified before
decryption. Not GCM — `openssl_encrypt` only gained GCM in PHP 7.1 and the
relay has to run on whatever the shared host provides.

### Setting it up

```bash
php secret-tool.php genkey          # prints a key and where to put it
php secret-tool.php encrypt         # reads the secret from stdin
php secret-tool.php check           # confirms every secret decrypts
```

`encrypt` reads from standard input on purpose. A secret passed as a command
line argument is visible in shell history and in the process list to every
other user on a shared host.

Put the key in **one** place, never both, and never in `config.php`. Both keep
it on the same server as the ciphertext — on shared hosting nothing can do
otherwise. The point is that a leak of `config.php` alone is not enough.

1. **Recommended on shared hosting:** a file outside the web root at mode
   0600, named by `IPRESTA_KEY_FILE`.
2. A real environment variable, if the host provides one — a panel setting or
   vhost directive you cannot edit over FTP. Not `SetEnv` in an `.htaccess`
   inside the web root: that writes the key beside the file it protects.

Keep a copy in a password manager. Losing the key is recoverable: rotate the
client secret in the Solution Provider Portal and re-encrypt the new one.

### What this does and does not protect

It defends against the ways a config file actually leaks — the server serving
`.php` as text after a misconfiguration, the file landing in a backup archive
or a git commit, a directory listing or path traversal elsewhere in the stack.

It does **not** defend against an attacker who already has arbitrary file read
as the web user, since they can read the key file too. Defending against that
needs an external KMS, which is out of proportion for a relay that stores no
Amazon Information at all. State exactly this if Amazon asks — an accurate
modest answer is worth more than an overclaimed one in a security review.

### Upgrading an existing relay

Plain values still decrypt to themselves, so deployment is not a flag day:
upload the new files, verify the relay still answers, then generate the key,
encrypt the secret, and paste the blob into `config.php`. `check` reports
anything still in plain text and exits non-zero, so it works in a cron guard.

### Verify after deploying

Each of these must answer **403**:

- `https://intellipresta.com/spapi/config.php`
- `https://intellipresta.com/spapi/secrets.php`
- `https://intellipresta.com/spapi/secret-tool.php`

The bundled `.htaccess` enforces that and disables directory listing. This is
part of the answer to Network Protection 1.1.
