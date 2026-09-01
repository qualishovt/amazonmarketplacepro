# SP-API security control answers

Answers to the nine questions Amazon raised when declining case 21374552171,
drawn from the Acceptable Use Policy, the Data Protection Policy and the
[Key Security Control Guidance](https://developer-docs.amazon.com/sp-api/docs/guidance-to-address-key-security-controls-in-sp-api-integration).

**Every statement here is verified, not aspirational.** Where something is
pending it says so. These are attestations subject to third-party assessment —
an accurate modest answer is worth more than an overclaimed one, and anything
untrue is worse than a gap honestly declared.

Last verified: 1 September 2026.

Companion documents: `data-handling.md` (what data exists, who controls it and
how long it stays), `change-management.md` (how a change reaches a merchant),
`incident-response.md` (what happens when something goes wrong) and
`schedule.md` (the due date behind every cadence claimed here).

---

## Scope — read this first

Most of the questionnaire presupposes that the provider stores Amazon
Information. IntelliPresta does not, and establishing that up front collapses
most of what follows.

**Where Amazon Information goes.** The module runs inside each merchant's own
PrestaShop installation, on hosting the merchant controls. Listings, prices,
stock, orders and the buyer details needed to fulfil them travel directly
between the merchant's server and Amazon's API, and are stored only in the
merchant's own database. Each merchant is the data controller for their own
shop. IntelliPresta is not a processor of Amazon Information in normal
operation.

**What IntelliPresta operates.** One component: a stateless OAuth relay at
`intellipresta.com/spapi/`, which completes the LWA authorisation handshake and
exchanges refresh tokens for short-lived access tokens. Verified by inspection
to contain no database, no `file_put_contents` or `fopen`, no sessions and no
cookies. Values exist in memory for the duration of a request and are
transmitted only over TLS.

**In-scope systems** are therefore: the relay, and the developer endpoints and
accounts that administer it. Nothing else.

**No Amazon Information has ever been retrieved.** The associated Amazon seller
account has never been active; every SP-API call has returned 403. No order or
buyer data has been imported into any IntelliPresta system at any time. This is
checkable against Amazon's own logs.

---

## 1. Data usage 4.1 — why restricted roles are required

> *Describe why your organization requires Restricted roles containing
> Personally Identifiable Information to build your application or feature.*

**One role is requested: Direct-to-Consumer Shipping.**

The module imports Amazon orders into PrestaShop so the merchant can fulfil
them from the back office they already use. Creating a PrestaShop order
requires the buyer's name and delivery address; without them no delivery
address record can be created, no shipping label can be produced, and the
carrier has nothing to collect against. This is the ordinary fulfilment purpose
the role exists for.

The data is used only for order fulfilment and permitted order-related
communication. It is not used for marketing, profiling or enrichment, and it is
never aggregated across merchants — the architecture makes that impossible,
since each shop holds only its own data and IntelliPresta holds none.

Buyer personal data is requested through Restricted Data Tokens: scoped to a
specific order and data element, obtained per operation, never stored by
IntelliPresta.

**Tax Remittance is not requested.** The module reads tax amounts already
present on the order and performs no remittance calculation, so there is no
defensible need.

**Tax Invoicing is not requested** at this time. It would only become relevant
if VCS invoice upload ships as a supported feature.

## 2. Network Protection 1.1

> *Describe the network protection controls used by your organization to
> restrict public access to databases, file servers, and desktop/developer
> endpoints.*

**Databases.** The relay has none. The merchant's database is on the merchant's
own hosting and is not reachable by IntelliPresta.

**File and configuration exposure.** An `.htaccess` in the relay directory
denies HTTP access to `config.php`, `config.sample.php`, `secrets.php`,
`secret-tool.php`, and any file matching `*.key`, `*.pem`, `*.env` or a leading
dot. Directory listing is disabled. Verified externally on 1 September 2026:
`config.php`, `secrets.php` and `secret-tool.php` each return **403**, the
directory index returns **403**, and the three live endpoints continue to
respond normally (`token.php` 405, `callback.php` 400, `login.php` 200).

**The encryption key is not in the web root.** It is held in a file at mode
0600 in the account home directory, outside `public_html`, and is not
retrievable over HTTP.

**Administrative access.** Shell access is by SSH key only for the sole
operator (see 7). Deployment is via cPanel File Manager over HTTPS,
authenticated by password and two-factor authentication.

**Constraint of the hosting tier, with compensating controls.** SSH password
authentication and plain FTP remain available. Both are server-wide settings on
shared hosting; the provider confirmed in writing on 1 September 2026 that
neither can be restricted for an individual account without affecting every
other account on the machine. Disablement was requested and declined on that
basis, and the response is retained.

Compensating controls in place:

- Connections are made exclusively over SSH with key authentication, and over
  FTPS where a file transfer client is used at all — deployment is normally via
  cPanel File Manager over HTTPS
- The one additional FTP account has been removed; only the cPanel default
  remains, which cannot be deleted
- All account passwords are long, random, unique, generated and stored in a
  password manager, and are not reused anywhere
- Exactly one SSH key is authorised, for the sole operator
- One person has access; there are no shared accounts and no third-party access

The residual risk is credential guessing against a password that is random and
unique, on an account that holds no Amazon Information.

**Platform-layer controls — pending written confirmation.** Network firewalling,
intrusion detection and prevention, and anti-malware scanning at the host level
are operated by the hosting provider, not by us: on a shared tier there is no
other place they could sit. Their specifics have been requested in writing so
that this section can state what is actually running rather than what shared
hosting usually includes. Until that reply is retained, this document does not
claim them. See Open items.

## 3. Encryption at Rest 2.4

> *Describe how your organization stores Amazon information at Rest including:
> (a) encryption methods, and (b) key management systems.*

**No Amazon Information is stored at rest** on IntelliPresta infrastructure
(see Scope). The only credential held is the LWA client secret of our own
application.

**(a) Method.** AES-256-CBC with encrypt-then-MAC (HMAC-SHA256), the MAC
verified before decryption. GCM is not used because `openssl_encrypt` gained
GCM support only in PHP 7.1 and the relay must run on the PHP version the
shared host provides. Each encryption uses a fresh random IV from
`openssl_random_pseudo_bytes`, and the implementation refuses to proceed if the
platform reports the source as not cryptographically strong.

**(b) Key management.** A 256-bit master key, generated with
`openssl_random_pseudo_bytes`, held in a file at mode 0600 outside the web
root, referenced from configuration by path. Separate encryption and
authentication keys are derived from it via HMAC-SHA256, so the same key
material is never used for both purposes. The key is never stored alongside the
ciphertext and is never committed to version control. A copy is held in a
password manager protected by two-factor authentication.

**Key rotation.** Losing the key is recoverable without data loss: the LWA
client secret is rotated in the Solution Provider Portal and re-encrypted.

**Verification.** `secret-tool.php check` confirms the key is readable and every
configured secret decrypts. Confirmed working end to end on 1 September 2026 by
a live OAuth authorisation, in which the relay decrypted the secret and
completed a real token exchange with Amazon.

## 4. Data Retention 2.1 — backups

> *Describe how your organization stores encrypted backups/archives of Amazon
> Information including: (a) geographically separated backup location, and
> (b) restore procedures (RTO/RPO).*

**No Amazon Information is held, so none is backed up** (see Scope).

**(a) Off-site copies of what does exist.** Application source is
version-controlled in a hosted Git repository on separate infrastructure. The
encryption key is held in a password manager. The hosting account is backed up
daily by JetBackup, incrementally, to an off-site cloud destination over SSH,
with approximately 30 daily restore points retained.

**(b) Restore procedures.** Restoration from backup has been exercised in
practice, not merely configured: a retained restore point was used to recover
the account.

- **RPO — effectively zero.** No Amazon Information is held, so none can be
  lost. Application code is committed on change.
- **RTO — under one hour.** Recovery of the relay is a code redeploy from the
  repository plus recreation of one configuration file, whose contents are
  reconstructible from the Solution Provider Portal and the password manager.

## 5. Logging and Monitoring 2.6

> *Describe your organization's security logging and monitoring system,
> including monitoring mechanisms for suspicious activities, and incident
> investigation procedures.*

**Logging.** Web server access and error logs cover every relay endpoint. The
hosting provider rotates its own copies on a short cycle, so
`relay-server/log-archive.php archive` runs daily and appends the relay's
request lines to a monthly archive held outside the web root at 0600, **retained
for 12 months** and pruned automatically beyond that. Amazon's own authorisation
and API logs provide an independent record of every call made with our
credentials.

The archived lines are web server access records — path, status, client address,
user agent. They contain no Amazon Information: the relay receives none, and the
tokens it does handle travel in POST bodies, which are not logged.

**Automated monitoring.** A weekly scheduled task runs
`secret-tool.php check --quiet`, which verifies that the master key remains
readable and that every stored credential remains encrypted. It is silent when
healthy and emails a failure report otherwise, so that mail arriving means
something. Tested on 1 September 2026 by removing the key file and confirming
the alert fired.

**Review.** `log-archive.php review` is run **fortnightly**. It summarises the
window by endpoint, status and client, and separately lists every request for a
file that must never be served — `config.php`, `secrets.php`, `secret-tool.php`,
`*.key`, `*.env`. Those are expected to appear, because the internet scans for
them; what matters is that each is answered 403. A 200 in that list is treated
as an incident and starts the response plan at containment. Reducing the review
to one question with an unambiguous wrong answer is what makes a fortnightly
cadence sustainable for a single operator.

**Investigation.** Twelve months of archived relay requests are searchable
directly, so an investigation can establish what reached the relay and when
rather than inferring it. On indication of compromise the incident response plan
applies (see `incident-response.md`).

**Limitations, stated plainly.** There is no SIEM, no real-time intrusion
detection and no 24/7 monitoring. For a stateless service holding no Amazon
Information, operated by one person, the controls above are proportionate. We
would rather state this than imply capability we do not have.

## 6. Risk Management and Incident Response 1.6

> *Summarize the steps taken within your organization's incident response plan
> to handle database hacks, unauthorized access, and data leaks.*

Full plan in `incident-response.md`. Summary: detect, contain by rotating the
LWA client secret and revoking authorisations, assess scope against Amazon's
logs, **notify Amazon at security@amazon.com within 24 hours of detection**,
notify affected merchants, remediate, and record the cause and fix.

The plan is **reviewed at least every six months** and after any incident, with
each review recorded in a table at the end of it. Notification is on suspicion
rather than on proof: the 24-hour clock runs from detection, not from
understanding, so a first report that states only what is known at the time is
the correct output.

Because no Amazon Information is stored, the realistic worst case is
compromise of the LWA client secret rather than disclosure of merchant or buyer
data. Containment is correspondingly fast: rotating one credential invalidates
what an attacker obtained.

## 7. Credential Management 1.4

> *How does your organization enforce password management practices for all the
> systems handling Amazon information as it relates to required length,
> complexity and expiration period?*

**Two-factor authentication is enabled on every account with access to
in-scope systems**: the hosting control panel, the source repository, the
Amazon developer account, and the password manager itself.

**Passwords** are generated by a password manager, **at least 20 characters**
of mixed random case, digits and symbols — beyond the length any of these
services enforces — unique per service, never reused, and never recorded in
source, configuration or documentation. Recovery codes are stored in the same
vault.

**Shell access** is by SSH key. A single 4096-bit RSA key pair, generated
1 September 2026, is authorised for the sole operator. Three stale authorised
keys of unknown provenance were identified and removed on the same date, and
the private key generated server-side by the control panel was deleted after
transfer, so no private key remains on the server it unlocks.

**Rotation.** Passwords for all in-scope accounts — hosting control panel,
source repository, Amazon developer account, password manager — are rotated
**at least annually**, and immediately on suspicion of compromise, on personnel
change, or when a service reports exposure. Because every password is generated
rather than remembered, rotation is a replacement, not a variation on the
previous value; the failure mode that makes scheduled expiry counterproductive
elsewhere does not arise here.

The LWA client secret is rotated on the same annual schedule and on suspicion.
It can be rotated, re-encrypted and verified in minutes:
`secret-tool.php encrypt`, update `config.php`, `secret-tool.php check`.

Due dates are tracked in `schedule.md`.

**Access review.** One person has access to all in-scope systems. There are no
shared accounts and no third-party access. The stale-key removal above is the
first such review; it is now performed on any change to hosting or tooling.

## 8. Vulnerability Management 2.7 — tracking remediation

> *How does your organization track remediation progress of findings identified
> from vulnerability scans and penetration tests?*

Findings are recorded as issues in the source repository, prioritised by
exploitability against the in-scope systems, and closed by a commit that
references the issue — so the fix and its rationale stay attached to the
finding. Security-relevant changes are described in the commit message rather
than summarised as "fix", to keep the audit trail readable later.

**Stated plainly:** no third-party penetration test has been commissioned, and
no automated vulnerability scanner runs against the relay. The attack surface
is three endpoints with no database and no user input beyond an OAuth code and
a refresh token. We will commission an assessment if the scope of stored data
ever changes.

Recent examples of the process working, all on 1 September 2026: three
stale SSH keys found and removed; a defect found where the integrity check
returned success when a secret could not be decrypted, which would have made
the monitoring silently useless; and key-storage guidance corrected after it
was found to recommend writing the key inside the web root.

## 9. Vulnerability Management 2.7 — code vulnerabilities

> *How does your organization remediate code vulnerabilities identified in the
> development lifecycle and during runtime?*

**No third-party code is bundled.** Verified by inspection: no `composer.json`,
no `composer.lock`, no `vendor/`, no `package.json`, no autoloader, and no
scripts or stylesheets loaded from third-party CDNs. The only external
dependencies are PHP extensions maintained by the platform — `curl`, `openssl`,
`json`, and optionally `imap`. There is consequently no dependency tree to scan
and no supply-chain surface.

**In development.** Database access uses PrestaShop's parameter escaping;
output is escaped in templates; secrets are never echoed back into forms.
Changes are reviewed before deployment and every change is committed with its
rationale.

**At runtime.** Platform and PHP patching is performed by the hosting provider.
PrestaShop core security releases are the merchant's responsibility on their own
installation; the module supports the maintained 1.6–8 range. Amazon's
deprecation notices are tracked so that API changes are handled before removal.

**Remediation path.** Fixes are committed, deployed to the relay via cPanel over
HTTPS, and released to merchants through the module update channel. The relay
is a small enough surface that a fix can be live within the hour.

---

## Open items

| Item | Status |
|---|---|
| SSH password authentication | Server-wide on this hosting tier; provider confirmed 1 Sep 2026 it cannot be disabled per account. Compensating controls in §2 |
| Plain FTP | As above — same confirmation, same compensating controls |
| Additional FTP account | Removed. The cPanel default remains and cannot be deleted |
| 30-day PII retention limit | **Not implemented.** The module does not purge buyer name, e-mail and shipping address 30 days after delivery. See `data-handling.md` |
| Vulnerability scanning cadence and penetration testing | No 30-day external scan cycle and no annual penetration test |
| Platform firewall / IDS-IPS / anti-malware | Provider-operated. Written confirmation of what runs has been requested; not yet retained |
| Daily log-archive cron | Written and tested; not yet installed on the server. See `schedule.md` |
| Third-party security assessment | Not commissioned. Amazon's own Data Security Assessment is free and conducted by an Amazon agent; that is the intended route |

Both hosting constraints were raised with the provider rather than assumed, and
their written response is retained. If the account moves to a VPS or dedicated
tier, both become enforceable and this document should be revised.
