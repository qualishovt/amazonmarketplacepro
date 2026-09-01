# Data handling, classification and retention

Companion to `sp-api-security-controls.md`. That document answers Amazon's
questions; this one is the underlying policy, and states which data exists,
where it lives, who controls it, and how long it stays.

Last reviewed: 1 September 2026. Reviewed every six months with the incident
response plan — see `schedule.md`.

---

## The distinction this policy turns on

Two different systems are involved, and conflating them is what makes most
answers to a security questionnaire wrong:

| | IntelliPresta systems | The merchant's PrestaShop |
|---|---|---|
| What it is | The website and the OAuth relay | The merchant's own shop, on their own hosting |
| Operated by | IntelliPresta | The merchant |
| Amazon Information held | **None** | Their own orders, imported from Amazon |
| Controller | — | The merchant |

The module is software the merchant installs and runs. IntelliPresta does not
host it, cannot reach it, and receives no copy of what it processes. The relay
exchanges an OAuth code for a token and returns it; it has no database, writes
no files, and keeps no session.

Everything below therefore splits into what we hold (almost nothing) and what
the software we publish causes the merchant to hold (their own customer data,
under their own control).

## Classification

| Class | Examples | Where it lives | Handling |
|---|---|---|---|
| **Secret** | LWA client secret, master encryption key, account passwords, SSH private key | Relay `config.php` (encrypted), key file outside the web root at 0600, password manager | Never in source, never in documentation, never in logs. Encrypted at rest. Rotated annually and on suspicion |
| **Merchant credential** | Refresh token | The merchant's own PrestaShop configuration | Never transmitted to IntelliPresta. Passes through the relay in a POST body and is returned; not logged, not stored |
| **Buyer PII** | Buyer name, buyer e-mail, shipping address | The merchant's PrestaShop database only | Never transmitted to IntelliPresta. See Retention |
| **Operational** | Relay access log lines: path, status, client address, user agent | Monthly archives outside the web root at 0600 | Retained 12 months, then deleted. Contains no Amazon Information |
| **Public** | Module source, documentation, website | Repository, website | — |

Request bodies are not logged by the web server, which is why the operational
class stays free of the credential class rather than merely being assumed to.

## Processing record

What the software does with Amazon Information, in the merchant's install:

| Purpose | Data | Source | Destination | Basis |
|---|---|---|---|---|
| Import Amazon orders into PrestaShop | Order, item, buyer name and e-mail, shipping address | SP-API Orders, via Restricted Data Token | Merchant's PrestaShop database | Necessary to fulfil the buyer's order |
| Create shipping labels | Shipping address | SP-API Merchant Fulfilment | Amazon | Necessary to ship the order |
| Generate tax invoices | Order and address data | Merchant's PrestaShop | Amazon (VCS invoice upload) | Legal obligation of the merchant |
| Buyer messaging | Amazon-anonymised address | SP-API Messaging | Amazon | Necessary to service the order |

**No processing occurs on IntelliPresta systems.** The relay's only processing
is a token exchange, which involves no Amazon Information.

Buyer PII is retrieved with a Restricted Data Token, not with the ordinary
access token, so the elevated grant is scoped to the call that needs it rather
than held open.

The module offers an **anonymised customer** setting, which creates the
PrestaShop customer under an anonymised address rather than the buyer's real
one where Amazon permits it — so a merchant who does not need buyer identity
in their shop can avoid holding it at all.

## Retention

**IntelliPresta systems.** No Amazon Information is held, so none is retained.
Relay access logs are retained 12 months and pruned automatically.

**The merchant's PrestaShop.** Imported order data persists in the merchant's
database. The merchant is the controller: it is their shop, their hosting and
their legal obligation, and PrestaShop's own GDPR tooling operates on it.

**The module's imported copy is deleted automatically.** The Data Protection
Policy caps retention of buyer personal data at 30 days after delivery, except
where a law obliges the merchant to keep it. `AmazonPiiPurger` clears the buyer
name, e-mail, street, city, region, postcode, phone and the stored raw Amazon
payload from the module's order table once an order is in a finished state and
older than the retention window. The window defaults to 30 days and is
configurable between 1 and 365.

It runs after every order import as well as from its own cron entry, so a
merchant who never schedules the extra job is still compliant as long as orders
are importing. Each cleared row is stamped with `pii_purged_at`, which is the
evidence that the control actually ran.

What survives is what carries no identifying value and what the merchant needs
to keep: Amazon order id, dates, status, totals, tax, fees, marketplace and
destination country. The country stays because it determines the VAT treatment
the merchant must be able to justify afterwards.

**The anchor date is the purchase date, not delivery.** Amazon does not return a
delivery date on the order record. Purchase is necessarily on or before
dispatch, and so before delivery, which makes the effective window at least as
strict as the policy requires and never looser. Only finished orders are
touched, so nothing is stripped from an order still being fulfilled.

**PrestaShop's own customer and address records are a separate question, and the
module leaves them alone by default.** A PrestaShop invoice is rendered from the
stored address, so anonymising it alters documents the merchant may be legally
obliged to keep - and that obligation is precisely the exception the policy
allows. A merchant who has taken their own advice can switch it on, and even
then a customer is anonymised only when every order they have is an Amazon order
already cleared: someone who also buys directly from the shop is never touched,
because that relationship is not Amazon's data.

## Sharing

Amazon Information is shared with **no outside party**. There are no
sub-processors, no analytics on any page that handles it, no third-party
error-reporting service, and no external system that receives it. The only
external destination in the processing record is Amazon itself.

## Disposal

- Relay log archives: deleted automatically past 12 months
- Secrets: rotated and superseded; the previous value is invalidated at Amazon,
  which is what makes it unusable rather than merely deleted
- Development install: holds test data only, never merchant or buyer data
- On module uninstall: the module's tables are dropped from the merchant's
  database

## Testing

Development and testing use a local PrestaShop install with synthetic products
and orders. **Production buyer data is never copied into it.** `live-verify.php`
runs read-only checks against the real SP-API and creates, updates and deletes
nothing; it is CLI-only and refuses to answer over HTTP.

## Published privacy policy

The public statement of what the software collects and why is at
<https://intellipresta.com/privacy.html>.
