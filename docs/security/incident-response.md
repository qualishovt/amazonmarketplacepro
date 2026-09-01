# Incident response plan

Scope: the IntelliPresta OAuth relay at `intellipresta.com/spapi/` and
`/ebay/`, and the accounts that administer it. See
`sp-api-security-controls.md` for why that is the whole of it.

Owner: sole operator. There is no on-call rotation and this plan does not
pretend otherwise.

Last reviewed: 1 September 2026. Next review due: 1 March 2027 — tracked with
the other recurring tasks in `schedule.md`.

---

## What can actually go wrong

The relay stores no Amazon Information — no database, no file writes, no
sessions. So the realistic incidents are narrower than for a typical provider,
and worth naming precisely rather than planning against a generic "breach".

| Incident | Consequence |
|---|---|
| LWA client secret disclosed | An attacker who also holds a merchant refresh token could mint access tokens for that merchant |
| Master key disclosed | Equivalent to the above, since the key decrypts the secret |
| Hosting account compromised | Both of the above, plus relay code could be altered to capture tokens in transit |
| Source repository compromised | Malicious code could reach merchants through an update |
| Amazon developer account compromised | The OAuth redirect URI could be repointed to harvest merchants' refresh tokens |

Merchant order and buyer data is not among these. It lives in each merchant's
own PrestaShop database and is never transmitted to IntelliPresta.

## Detection

- Weekly automated integrity check emails on failure if the master key becomes
  unreadable or any stored credential is no longer encrypted
- Fortnightly review of archived relay access logs:
  `php log-archive.php review`. It flags every request for `config.php`,
  `secrets.php`, `secret-tool.php`, `*.key` and `*.env`. Those must all be
  403; a 200 is an incident and starts this plan at step 1
- `php tools/security-scan.php` before each release, and weekly as a guard
- Amazon notifications about the developer account or unusual API activity
- Hosting provider notifications
- Web server access and error logs, retained 12 months (`log-archive.php`)
- Reports from merchants

## Response

### 1. Contain — first hour

Act before completing the investigation. Every one of these is reversible, and
all of them are cheap relative to leaving a compromised credential live.

**If the LWA client secret or master key may be exposed:**

1. Rotate the LWA client secret in the Solution Provider Portal
2. Encrypt the new value: `php secret-tool.php encrypt`
3. Update `config.php`, then confirm with `php secret-tool.php check`
4. Generate a new master key and re-encrypt if the key itself was exposed
5. Verify the relay by completing one authorisation end to end

**If the hosting account may be compromised:**

1. Change the cPanel password; confirm two-factor is still enrolled
2. Review `~/.ssh/authorized_keys` and remove anything unrecognised
3. Compare deployed relay files against the repository — the code is small
   enough to read in full, and `md5sum` against a fresh clone is definitive
4. Rotate the LWA client secret regardless, since it was readable

**If the source repository may be compromised:**

1. Change the password, confirm two-factor, revoke unrecognised tokens and keys
2. Review recent commits for changes that were not made by the operator
3. Notify merchants if a released version may have been altered

**If the Amazon developer account may be compromised:**

1. Change the password and confirm two-factor
2. **Check the OAuth redirect URI first** — repointing it is the highest-value
   attack, because it harvests merchants' refresh tokens rather than ours
3. Rotate the LWA client secret

### 2. Assess

Establish what was actually reachable, and prefer evidence over inference.

- Amazon's API logs are the authoritative record of what was called with our
  credentials, and are independent of any system an attacker could alter
- Web server logs show what reached the relay
- The hosting account has approximately 30 days of daily off-site restore
  points, so the pre-incident state can be compared rather than recalled

Write down: what was accessed, when it started, when it stopped, whether any
merchant's tokens could have been used, and whether any Amazon Information was
reachable at any point. If the answer to the last is yes, the scope statement
in `sp-api-security-controls.md` is wrong and must be corrected.

### 3. Notify

- **Amazon, within 24 hours of detection**, by email to security@amazon.com,
  and in the Solution Provider Portal case log. This is the deadline the Data
  Protection Policy sets, and it runs from detection, not from understanding.
  Report on the basis of what is known at the time; a first notification that
  says "we detected X at 04:12 UTC, scope not yet established" is on time and
  a complete one sent on day three is not
- **Affected merchants**, if their tokens could have been used, with what
  happened and what they should do — reauthorise, and check their own shop
- **The hosting provider**, if the compromise appears to be at their layer

Notify on suspicion, not on proof. A retracted notification costs far less than
a late one.

### 4. Recover

- Restore altered files from the repository, not from memory
- Restore the hosting account from a pre-incident restore point if needed
- Re-verify: `secret-tool.php check`, the external 403 checks on `config.php`,
  `secrets.php` and `secret-tool.php`, and one complete authorisation
- Confirm the relay's three endpoints answer normally: `token.php` 405,
  `callback.php` 400, `login.php` 200

### 5. Record

In the repository, as an issue or a commit message:

- What happened and how it was detected
- What was contained, when, and what was ruled out
- What changed so that it cannot recur
- What did not work — a detection that fired late, or a step in this plan that
  turned out to be wrong

That last point is the reason to write anything down at all. A plan that is
never corrected by contact with a real incident is decoration.

## Contacts

| | |
|---|---|
| Amazon security | security@amazon.com — the 24-hour notification goes here |
| Amazon Solution Provider Portal | Case log — the channel of record |
| Hosting provider | A2 Hosting support |
| Merchant contact | support@intellipresta.com |

## Review

Reviewed **at least every six months**, and after any incident. The review is
not a re-read: it checks that the contacts still work, that each containment
step still matches how the systems are actually administered, and that the
scope statement is still true.

Any change to what the relay stores invalidates the scope statement and
requires this plan to be rewritten rather than amended.

| Reviewed | By | Outcome |
|---|---|---|
| 1 September 2026 | Operator | Plan established; notification deadline and review cadence set |
