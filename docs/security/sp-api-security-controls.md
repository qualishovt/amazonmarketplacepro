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
`incident-response.md` (what happens when something goes wrong),
`vulnerability-management.md` (scanning, testing and the findings register)
and `schedule.md` (the due date behind every cadence claimed here).

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

**What IntelliPresta operates.** An OAuth relay at `intellipresta.com/spapi/`,
which completes the LWA authorisation handshake and exchanges refresh tokens
for short-lived access tokens, and alongside it an optional scheduler. The
OAuth path holds nothing: no database, no sessions, no cookies, and values
exist in memory for one request before being returned over TLS.

The scheduler does hold something, and it is stated here rather than left to be
discovered. Merchants whose hosting has no crontab can register their shop; we
then store that shop's cron URL and cron token in a JSON file outside the web
root, mode 0600, and call the shop every five minutes so its own schedule runs
on time. No Amazon Information is involved - the shop performs its own sync and
tells us only how many tasks it ran.

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
authentication and plain FTP remain available. Both are server-wide daemon
settings on shared hosting; the provider confirmed in writing on 1 September
2026, by support ticket, that neither can be toggled per account because the
underlying services are shared across every tenant on the node, and that
per-instance daemon customisation would require an unmanaged or dedicated
environment. Disablement was requested and declined on that basis, and the
reply is retained.

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

**Platform-layer controls.** Firewalling, intrusion detection and anti-malware
at host level are operated by the hosting provider; on a shared tier there is
no other place they could sit. Confirmed in writing by the provider on
1 September 2026, by support ticket, and retained:

| Control | What runs |
|---|---|
| Network firewall | CSF with IP filtering, restricting inbound traffic by default and blocking unauthorised port access |
| Web application firewall | ModSecurity, inspecting HTTP requests |
| Intrusion detection and prevention | Imunify360 — multi-layered IDS/IPS, proactive defence, automated brute-force protection |
| Anti-malware | Imunify360 real-time scanning of files on upload, creation and modification; ClamAV on demand via cPanel |
| Tenant isolation | CloudLinux OS with CageFS — a private virtualised filesystem per account, so no tenant can see another's files, processes or binaries. Resources isolated per tenant via LVE |

One precision, since the questionnaire asks about *network segmentation*: what
the provider operates is tenant isolation, not a separate network segment for
this account. On shared hosting those are not the same thing, and the honest
description is the one above. It is adequate here because the account exposes
no database and no file server to segment away from — the relay is a handful
of PHP files, static pages, and one registry file that is not web-reachable.

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

*Revised 26 October 2026 after case 21847001071, which noted that the earlier
answer did not say whether PII is stored in logs.*

**No PII is stored in any log.** This is a rule, enforced in two places:

- **The relay** (`intellipresta.com/spapi/`, the only server we operate that
  talks to Amazon) receives no buyer data. Its log lines hold path, status,
  client IP and user agent only. Tokens travel in POST bodies, which are not
  logged.
- **The module** keeps an activity log in the merchant's own database. It
  records what each job did: Amazon order IDs, counts and statuses. It never
  writes names, addresses, e-mail addresses or phone numbers. As a safeguard,
  every message is filtered before it is stored and any e-mail address is
  removed, so an error quoted from a mail server or an API cannot carry one
  (since 1.6.11, covered by an automated test). Buyer PII in imported orders
  is deleted 30 days after delivery by the module's purge, unless tax law
  requires the merchant to keep it.

Logs are readable only by the sole operator (SSH key only, control panel with
two-factor authentication), and in the module only by back-office employees
with access to it.

**Retention.** The hosting provider rotates its own copies on a short cycle, so
`relay-server/log-archive.php archive` runs daily and appends the relay's
request lines to a monthly archive outside the web root, mode 0600. The archive
is **kept for 12 months** and pruned automatically after that. Amazon's own
authorisation and API logs give an independent record of every call made with
our credentials.

**Automated monitoring.** A weekly scheduled task runs
`secret-tool.php check --quiet`. It checks that the master key is readable and
that every stored credential is still encrypted. It stays silent when healthy
and e-mails a failure report otherwise, so any mail it sends needs action.
Tested on 1 September 2026 by removing the key file and confirming the alert
fired.

**Review.** `log-archive.php review` runs **every two weeks**. It summarises
the period by endpoint, status and client, which shows:

- bursts of unauthorised calls (401/403);
- unexpected request rates from one client;
- unusual access patterns.

It also lists every request for a file that must never be served:
`config.php`, `secrets.php`, `secret-tool.php`, `*.key`, `*.env`. These requests
are expected, because the internet scans for them. Each one must be answered
403. A 200 in that list is treated as an incident.

**Investigation.** Twelve months of archived relay requests can be searched
directly, so an investigation can show what reached the relay and when. Any
suspicious finding starts the incident response plan (`incident-response.md`):

1. Contain: rotate the LWA client secret and revoke authorisations.
2. Assess the scope against our logs and Amazon's.
3. Notify security@amazon.com within 24 hours of detection.
4. Notify the affected merchants.
5. Remediate, and record the cause and the fix.

**Limitations.** There is no SIEM, no real-time intrusion detection and no 24/7
monitoring. For a stateless service that holds no Amazon Information and is run
by one person, these controls are proportionate.

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

*Revised 26 October 2026 after case 21847001071 ("password complexity does not
comply"). The policy now covers each point of DPP 1.4 explicitly.*

**Scope.** Every account that can reach Amazon information or credentials:

- the hosting control panel and server;
- the source repository;
- the Amazon Solution Provider Portal and Seller Central;
- the password manager.

One person has access. There are no shared accounts and no third-party access.

**Password policy for all of these accounts:**

| Requirement | Our rule |
|---|---|
| Length and complexity | At least 12 characters, with upper-case and lower-case letters, numbers and special characters. In practice each password is generated by the password manager, 20 or more random characters using all four types. |
| Personal information | A password never contains any part of the user's name or other personal data. Generated passwords are random. |
| Reuse | Reusing any of the last 10 passwords is prohibited. Each new password is newly generated and unique to one service. |
| Minimum age | 1 day. A password is not changed again within 24 hours, except after a suspected compromise. |
| Expiry | 365 days at most. Every password is changed at least once a year, and immediately on suspicion of compromise, a change of personnel, or an exposure reported by a service. Due dates are tracked in `schedule.md`. |
| Lockout | An account is locked after 10 or fewer failed sign-in attempts. The hosting login is protected by the provider's brute-force protection (Imunify360/cPHulk), which blocks repeated failures. The repository and Amazon accounts apply their own lockout. |
| Multi-factor authentication | Two-factor authentication is on for every account above. |

**Shell access** is by SSH key only. A single 4096-bit RSA key pair, generated
1 September 2026, is authorised for the sole operator. Three stale authorised
keys of unknown origin were found and removed on the same date. The private key
generated server-side by the control panel was deleted after transfer, so no
private key remains on the server it unlocks.

**The LWA client secret** is encrypted at rest and rotated on the same yearly
schedule, and on suspicion of compromise. Rotation takes minutes:
`secret-tool.php encrypt`, update `config.php`, then `secret-tool.php check`.

**Access review.** The stale-key removal above was the first review. A review
now runs on any change to hosting or tooling.

## 8. Vulnerability Management 2.7 — tracking remediation

> *How does your organization track remediation progress of findings identified
> from vulnerability scans and penetration tests?*

**Scanning.** OWASP ZAP runs against the relay and the public site every 30
days (`tools/vuln-scan.sh`), and a full active scan plus a manual checklist
runs annually. Reports are committed to `docs/security/scans/<date>/`, so a
scan can be shown afterwards rather than merely asserted. A static analysis
of the module source gates every release. The open findings register is in
`vulnerability-management.md`.

**Deadlines.** Critical findings are remediated within 7 days and high within
30, as the Data Protection Policy requires; medium within 90 days and low by
the next release, which are limits we set ourselves. A finding accepted
rather than fixed is recorded with its reason, so an accepted risk stays
visible instead of quietly becoming a habit.

**Tracking.** Findings are recorded as issues in the source repository,
prioritised by exploitability against the in-scope systems, and closed by a
commit that references the issue — so the fix and its rationale stay
attached to the finding. Security-relevant changes are described in the
commit message rather than summarised as "fix".

**Stated plainly:** the annual penetration test is conducted internally,
using OWASP ZAP's active scan and the manual checklist in
`vulnerability-management.md`. It is not an independent assessment and is
not offered as one. The attack surface is a small number of endpoints with no
database, and user input limited to an OAuth code, a refresh token, and a
scheduler registration. That registration is the one place where input decides
what the server will connect to, so it is guarded accordingly: HTTPS only,
private and loopback and link-local addresses refused, and the shop made to
echo a nonce with its own token before anything is stored.

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
| Third-party penetration test | The annual test is conducted internally with OWASP ZAP and a manual checklist; an independent assessment has not been commissioned |
| Third-party security assessment | Not commissioned. Amazon's own Data Security Assessment is free and conducted by an Amazon agent; that is the intended route |

Both hosting constraints were raised with the provider rather than assumed, and
their written response is retained. If the account moves to a VPS or dedicated
tier, both become enforceable and this document should be revised.
