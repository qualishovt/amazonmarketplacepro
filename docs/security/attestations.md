# Security attestations submitted to Amazon

The security answers given on the Solution Provider Profile, recorded here
because they are commitments rather than a form-filling exercise. Each one is
re-read at the six-monthly review: if the practice behind an answer has
lapsed, the answer has become false and either the practice or the answer has
to change.

Submitted: (fill in on submission)  
Answers as at: 1 September 2026

Supporting detail is in `sp-api-security-controls.md`, `data-handling.md`,
`change-management.md` and `incident-response.md`. Cadences and due dates are
in `schedule.md`.

---

## Yes/No answers

| Question | Answer |
|---|---|
| Does your organization implement the following network security controls: firewalls, IDS/IPS, anti-virus/anti-malware, and network segmentation? | Yes |
| Does your organization restrict access to Amazon Information based on users' job duties or business functions? | Yes |
| Does your organization encrypt Amazon Information in transit? | Yes |
| Does your organization maintain an incident response plan with defined roles, 6-month reviews, and 24-hour incident notification procedures? | Yes |
| Does your incident response plan include reporting security incidents involving Amazon Information to security@amazon.com within 24 hours of detection? | Yes |
| Does your organization enforce password requirements including 12-character minimum with special characters, MFA, 365-day expiration, and annual rotation? | Yes |
| Are credentials (passwords, encryption keys, secret access keys) stored securely? | Yes |
| How long do you retain Personally Identifiable Information data? | **Less than 31 days after order shipments** |
| Does your organization maintain documented data handling, classification, and privacy policies with processing records? | Yes |
| Does your organization encrypt PII at REST using AES-128/RSA-2048 (or better) and maintain a Key Management System? | Yes |
| Does your organization use fine-grained access controls to restrict access to Personally Identifiable Information? | Yes |
| Does your organization use audit logs to detect security incidents with at least bi-weekly reviews and minimum 12-month retention? | Yes |
| Are application changes evaluated in a dedicated test environment before pushing to production? | Yes |
| Does your organization conduct vulnerability scans every 30 days, annual penetration tests, and remediate critical findings within required timeframes? | **Yes** — answered No on 1 September 2026, which is what Amazon declined the submission on. The practice now exists; see below |
| Does your organization scan application code for vulnerabilities prior to each release? | Yes |
| Does your organization have a formal change management process which defines responsibilities for testing, verifying, and approving changes? | Yes |

**Amazon declined the submission of 1 September 2026 on exactly this question,
and on nothing else.** Every other answer passed.

The reasoning behind answering No - that a declared gap reads better than an
unsupported claim - was wrong in effect. A declared gap on this control is
disqualifying on its own.

The answer was made true rather than changed: OWASP ZAP now scans the
relay and the site on a 30-day cycle with reports committed as evidence, the
remediation deadlines are set at 7 days critical and 30 days high, and the
first scan of 1 September 2026 is triaged in `vulnerability-management.md`.
The annual penetration test is conducted internally and is described that way.

The penetration test of 1 September 2026 was completed: a ZAP full active scan
against a staging replica of the relay (0 fail, 14 warn, 127 pass), plus a
manual checklist run against production. Two things are stated rather than
glossed:

- The test is **internal, not independent**. It is not equivalent to a
  third-party assessment.
- The active scan runs against a **replica, not the live deployment**, because
  the hosting provider declined in writing on 2 September 2026 to permit an
  active scan of a shared-tier account (ticket ULQ-581-06565). The replica
  answers identically across every endpoint; production is covered by the
  manual checklist and the monthly passive scan. Both the refusal and what it
  costs the assessment are recorded in `vulnerability-management.md`.

A new case must be submitted once the practice is running - Amazon states
that the declined case must not be reopened.

## Free-text answers

### Describe the network protection controls used by your organization to restrict public access to databases, file servers, and desktop/developer endpoints.

> The relay has no database; merchant data stays in the merchant's own PrestaShop. An .htaccess denies HTTP access to config.php, secrets.php, secret-tool.php and any .key, .pem, .env or dotfile, and disables directory listing. Verified externally on 1 September 2026: each returns 403, while the live endpoints answer normally. The master key is a 0600 file outside the web root. Shell access is by SSH key only; deployment is over HTTPS. One operator, no shared accounts, no third-party access.

### Describe how your organization individually identifies employees with Amazon Information access. Explain how it restricts employee access to Amazon information on a need-to-know basis.

> IntelliPresta is one person, the founder, with no employees. There are no shared or generic accounts: hosting, source repository, Amazon developer account and password manager each have a single named account with a unique generated password and two-factor authentication, so every action is attributable to that identity. Need-to-know is absolute rather than administered - nobody else holds credentials to any in-scope system. Access is reviewed on any change to hosting or tooling.

### Describe the monitoring mechanism your organization uses to prevent employees to access Amazon Information from personal devices (USB flash drives, cellphones). Specify how your organization is alerted if such incidents occur.

> There are no employees and no device fleet, so there is no MDM or DLP tooling and we do not claim any. The control is structural instead: Amazon Information never reaches IntelliPresta systems or devices at all. The relay holds none, and development runs against a local PrestaShop install with synthetic test data - merchant or buyer data is never copied onto the workstation. Removable media is not used for anything in scope, because there is nothing in scope for it to carry.

### Provide your organization's privacy and data handling policies to describe how Amazon data is collected, processed, stored, used, shared and disposed.

> https://intellipresta.com/privacy.html states what the software collects and why. Internally, a data handling policy classifies every data category, records each processing purpose, and sets retention and disposal. Its central point: IntelliPresta holds no Amazon Information. The module runs inside the merchant's own PrestaShop, where the merchant is the controller, and the relay only exchanges an OAuth code for a token and keeps nothing. No Amazon Information is shared with any third party.

### Describe how your organization stores Amazon information at Rest including: (a) encryption methods (AES-128, RSA-2048, etc.), and (b) key management systems.

> No Amazon Information is stored on IntelliPresta systems, so we hold none at rest. It stays in the merchant's own PrestaShop database, under their control and their hosting. What we do store at rest is the LWA client secret, encrypted with AES-256-CBC under encrypt-then-MAC (HMAC-SHA256, verified before decryption). Key management: a 256-bit master key in a 0600 file outside the web root, never in source or backups, held in a password manager, rotated annually and on suspicion.

### Describe how your organization stores encrypted backups/archives of Amazon Information including: (a) geographically separated backup location, and (b) restore procedures (RTO/RPO).

> No Amazon Information is held, so none is backed up. What exists: source is version-controlled in a hosted Git repository on separate infrastructure; the encryption key is in a password manager; the hosting account is backed up daily by JetBackup to an off-site cloud destination over SSH, retaining about 30 daily restore points. Restoration has been exercised in practice, not merely configured. RPO is effectively zero; RTO is under one hour - a redeploy plus one configuration file.

### Describe your organization's security logging and monitoring system, including monitoring mechanisms for suspicious activities, and incident investigation procedures.

> Relay access logs are archived daily into monthly files outside the web root at 0600 and retained 12 months. A fortnightly review summarises requests by endpoint, status and client, and separately lists every request for config.php, secrets.php, secret-tool.php, .key or .env - all of which must return 403, so a 200 there is treated as an incident. A weekly cron alerts if the master key becomes unreadable or a stored credential is no longer encrypted. There is no SIEM and no 24/7 monitoring.

### Summarize the steps taken within your organization's incident response plan to handle database hacks, unauthorized access, and data leaks.

> Detect, contain, assess, notify, recover, record. Containment is fast because no Amazon Information is stored: rotating the LWA client secret invalidates what an attacker obtained. Scope is assessed against Amazon's own API logs, which are independent of anything an attacker could alter. Amazon is notified at security@amazon.com within 24 hours of detection, on suspicion rather than proof, and affected merchants told what to do. The plan is reviewed every six months and each review recorded.

### How does your organization enforce password management practices for all the systems handling Amazon information as it relates to required length, complexity and expiration period?

> Every password is generated by a password manager - at least 20 characters of mixed case, digits and symbols, beyond what any of these services enforces - unique per service, never reused, and never recorded in source, configuration or documentation. Two-factor authentication is enabled on every in-scope account: hosting, source repository, Amazon developer account, and the password manager itself. Passwords are rotated at least annually, and immediately on any suspicion of compromise.

### How is Personally Identifiable Information (PII) protected during testing?

> No production PII is used in testing. Development runs on a local PrestaShop install with synthetic products and orders; merchant or buyer data is never copied into it. The live verification script performs read-only checks against the real SP-API - it creates, updates and deletes nothing - and is CLI-only, refusing to answer over HTTP. In production, buyer PII is retrieved only through a Restricted Data Token, scoped to the call that needs it, and only into the merchant's own database.

### What measures are taken to prevent exposure of credentials?

> Credentials are never in source. The relay's client secret is stored encrypted with AES-256-CBC under encrypt-then-MAC; the master key is a 0600 file outside the web root, held in a password manager. .htaccess denies HTTP access to config.php, secrets.php and any .key or .env, verified externally as 403. The encryption tool reads the secret from stdin rather than argv, keeping it out of shell history. Secrets are never echoed back into admin form fields, and the release scan checks for both.

### How does your organization track remediation progress of findings identified from vulnerability scans and penetration tests?

> Findings are recorded as issues in the source repository, prioritised by exploitability against in-scope systems, and closed by a commit that references the issue, so the fix and its rationale stay attached to the finding. Security-relevant commits say so explicitly rather than being summarised as a fix. The release scan exits non-zero on any error, so an unremediated finding blocks a release instead of depending on someone remembering it. Open gaps are listed explicitly in our documentation.

### How does your organization remediate code vulnerabilities identified in the development lifecycle and during runtime?

> A static security scan runs before each release and exits non-zero on any error, blocking that release. It targets the failure modes that actually occur in PrestaShop modules: SQL built by concatenation without escaping or an integer cast; eval, unserialize and dynamic includes; disabled TLS verification; hardcoded credentials; secrets echoed into form fields; unescaped template output; and missing direct-access guards. Its first run found seven unguarded files, all fixed the same day.
