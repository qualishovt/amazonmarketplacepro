# Public website for intellipresta.com

Static pages written against Amazon's
[website guidelines](https://developer-docs.amazon.com/sp-api/docs/website-guidelines)
after the restricted-access request was declined (case 21374552171), and extended to
cover the eBay module as well.

**⚠ NOT part of any PrestaShop module. Exclude from the module ZIPs, like relay-server/.**

## Why this exists

`https://intellipresta.com/` used to redirect to the PrestaShop **demo shop's admin
login** ("DemoShop", 1.7.4.2). That was the literal reason Amazon gave for rejecting the
website:

> The information in the website must be available without the need for authentication.
> Potential users must not be required to log in to view information about your app.

A reviewer opening the site saw a login form and nothing about the app. That redirect has
since been removed — the root, the hub and the policy all return 200 with the right pages.

## Structure

One product per marketplace: the modules are **Amazon Marketplace Pro** and **eBay
Marketplace Pro**, two separate licences. `/marketplaces/` is the hub that lists both;
each module has its own page underneath it.

| Local file | Deploy to | Serves at |
|---|---|---|
| `index.html` | `public_html/index.html` | `https://intellipresta.com/` — company page, lists every IntelliPresta module |
| `marketplaces/index.html` | `public_html/marketplaces/index.html` | `https://intellipresta.com/marketplaces/` — hub for the two marketplace modules |
| `marketplaces/amazon.html` | `public_html/marketplaces/amazon.html` | `https://intellipresta.com/marketplaces/amazon.html` — **Amazon app page** |
| `marketplaces/ebay.html` | `public_html/marketplaces/ebay.html` | `https://intellipresta.com/marketplaces/ebay.html` — **eBay app page** |
| `marketplaces/privacy.html` | `public_html/marketplaces/privacy.html` | `https://intellipresta.com/marketplaces/privacy.html` — one policy covering both modules |

### Which URL goes where

| Register with | URL |
|---|---|
| Amazon — app website on the Solution Provider Profile | `https://intellipresta.com/marketplaces/amazon.html` |
| Amazon — privacy policy | `https://intellipresta.com/marketplaces/privacy.html` |
| eBay — application / marketing URL | `https://intellipresta.com/marketplaces/ebay.html` |
| eBay — privacy policy | `https://intellipresta.com/marketplaces/privacy.html` |
| eBay — marketplace account deletion endpoint | `https://intellipresta.com/ebay/deletion.php` (unchanged) |

The privacy URL **must not move**: it is already registered with Amazon and eBay, and
eBay in particular validates it as part of the Marketplace Account Deletion programme.
Only the file's *content* changes; the address stays.

**Do not delete anything else already in `public_html/marketplaces/`.** The relay
endpoints live outside it and are untouched by this deployment:

- `public_html/spapi/` — Amazon LWA relay (`callback.php`, `token.php`, …)
- `public_html/ebay/` — eBay relay, including `deletion.php`

## Before deploying

1. **Set the eBay price.** `marketplaces/ebay.html` currently says *Price on request* with a
   `TODO` comment showing the exact markup to paste. The Amazon page is set to
   € 209.99 one-time including the first 12 months of Business care, then € 60 / year
   from the second year. Business care covers module updates and e-mail support; it is
   bundled into year one and optional afterwards. Declining it does not disable the
   module — updates and support simply stop.
   The eBay card on `index.html` still claims "12 months of updates and support" — confirm
   whether eBay follows the same model before publishing that.
   Confirm whether the figures are incl. or excl. VAT and say so on the page.

   **The price is not two free variables.** PrestaShop Addons requires the Business care
   price to be **40% of the core price**. The advertised total is the sum of the two:

   | | | |
   |---|---|---|
   | Core | € 149.99 | |
   | Business care | € 60.00 | = 40% of core |
   | **Advertised** | **€ 209.99** | what the merchant pays in year one |

   So a price change is not a free edit. Moving the core to € 179.99 forces Business care
   to € 72.00 and the advertised total to € 251.99. Recompute all three together, and
   update the pricing table and FAQ on `marketplaces/amazon.html`, the module card on
   `index.html`, and the pricing description on the Appstore listing, which a reviewer
   compares against the page.

   Do **not** publish the marketplace's commission split. It is a reseller arrangement,
   irrelevant to what the buyer pays or receives, and there is no disclosure obligation.
   Note that "we take no commission on your marketplace sales" elsewhere on the page
   refers to the merchant's own Amazon sales and is unrelated.
2. **Add your other (non-marketplace) modules** to `index.html` — there is a marked comment
   block showing where, and an "In development" placeholder block below it to replace or
   delete. Keep descriptions factual; superlatives ("best", "#1") are grounds for rejection.
3. **No broken links** — the guidelines check for them. The "In development" block uses a
   badge rather than a dead link for exactly this reason.

## Deploy (cPanel)

1. Upload `index.html` to `public_html/`, and all four files from `marketplaces/` into
   `public_html/marketplaces/` (overwriting `privacy.html`, and replacing the old
   `index.html`, which was the Amazon page — its content now lives at `amazon.html`).
2. Verify in a **private/incognito window** — that is exactly the reviewer's view, with no
   session:
   - `https://intellipresta.com/` → company page, no login
   - `https://intellipresta.com/marketplaces/` → hub listing both modules
   - `https://intellipresta.com/marketplaces/amazon.html` → Amazon page with pricing
   - `https://intellipresta.com/marketplaces/ebay.html` → eBay page with pricing
   - `https://intellipresta.com/marketplaces/privacy.html` → the full policy
3. Valid HTTPS certificate on all of them.
4. Keep the demo shop off the root and out of search engines — its admin login publicly
   lists demo credentials.

## Resubmission checklist (in order)

1. Seller account active — independent blocker for all SP-API calls; do it first.
2. Website deployed and verified in a private window (above).
3. **Security controls actually in place** before answering those questions again:
   https://developer-docs.amazon.com/sp-api/docs/guidance-to-address-key-security-controls-in-sp-api-integration
   The denial cited network protection, encryption at rest, backups with RTO/RPO,
   logging & monitoring, incident response, credential management and vulnerability
   management. A third-party assessment verifies these — claim only what exists.
   Scope note: IntelliPresta stores no Amazon Information, so the in-scope systems are
   the stateless relay at `/spapi/` and developer endpoints. State that explicitly;
   it collapses most of the surface. The real gap is the relay's LWA client secret,
   which Amazon requires encrypted and never in plain text.
4. Reduce the restricted-role request to **Direct-to-Consumer Shipping only** (add Tax
   Invoicing only if VCS support ships at launch). Do not re-request Tax Remittance —
   the module reads tax amounts already present on the order, so there is no defensible
   need, and it invites the same "Data usage 4.1" objection.
5. Update the Solution Provider Profile as a **new case** — Amazon said not to reopen
   21374552171.
6. Submit the Selling Partner Appstore listing form, which they explicitly required.
