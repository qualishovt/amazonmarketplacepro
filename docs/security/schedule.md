# Security operations schedule

Every cadence claimed in `sp-api-security-controls.md` and
`incident-response.md` is listed here with a due date, so that a claim and the
evidence it rests on live in the same place. A control described in a document
but not carried out is worse than no control, because it is also a false
statement to Amazon.

Scope is the OAuth relay at `intellipresta.com/spapi/` and `/ebay/` and the
accounts that administer it. See the scope statement in
`sp-api-security-controls.md`.

---

## Automated

Run by cron on the hosting account. Silent when healthy, so mail arriving
means something.

| Task | Cadence | Command | Installed |
|---|---|---|---|
| Verify secrets remain encrypted | Weekly | `secret-tool.php check --quiet` | Yes |
| Archive relay access logs | Daily 03:17 | `log-archive.php archive` | Yes |

First archive run on 1 September 2026 recovered 4,334 relay requests already
present in the host's live logs, so the 12-month window starts with history
behind it rather than from zero.

Cron does not expand `~` or `$HOME`, and does not necessarily set `HOME`
either. The archive job needs it for both the log location and the archive
location, so it is set in the command itself. Add via cPanel → Cron Jobs,
substituting the real user:

```
17 3 * * *  HOME=/home/USER /usr/local/bin/php /home/USER/public_html/spapi/log-archive.php archive >/dev/null
```

Test a cron command under a stripped environment before trusting it:
`env -i HOME=/home/USER /usr/local/bin/php .../log-archive.php archive`.
That is stricter than cron actually is, so passing it is conclusive. Doing
this caught a real failure: without `HOME` the job refused to run at all.

`check --quiet` prints nothing and exits 0 when every configured secret is
encrypted and decryptable; on any failure it prints and exits non-zero, which
cron delivers by mail. `archive` discards its output because a failure there
shows up as a gap in the archive at the next review, and a daily success mail
would train the operator to ignore mail from this account.

After installing the archive job, confirm it the next day with
`php log-archive.php status` — it reports what is archived and how far back.

## Manual

| Task | Cadence | Command / reference | Last done | Next due |
|---|---|---|---|---|
| Review relay access logs | Fortnightly | `php log-archive.php review` | 1 Sep 2026 | on first merchant |
| Static security scan | Before each release | `php tools/security-scan.php` | 1 Sep 2026 | each release |
| Vulnerability scan | Every 30 days | `tools/vuln-scan.sh` | 1 Sep 2026 | 1 Oct 2026 |
| Penetration test | Annually | `tools/vuln-scan.sh --full` + manual checklist | — | see below |
| Review incident response plan | Every 6 months | `incident-response.md` | 1 Sep 2026 | 1 Mar 2027 |
| Rotate passwords on in-scope accounts | Annually | Password manager | 1 Sep 2026 | 1 Sep 2027 |
| Rotate LWA client secret | Annually | `secret-tool.php encrypt` | 1 Sep 2026 | 1 Sep 2027 |
| Review SSH authorised keys | Annually and on any hosting change | `~/.ssh/authorized_keys` | 1 Sep 2026 | 1 Sep 2027 |

The log review is listed as due "on first merchant" rather than a date because
the relay currently carries no merchant traffic. Reviewing an empty window
fortnightly would be theatre; the archive still runs daily from now, so when
the first merchant connects there is already history behind them.

## What each review actually checks

**Relay access log review.** `log-archive.php review` ends with a list of every
request for `config.php`, `secrets.php`, `secret-tool.php`, `*.key` or `*.env`.
Those requests are expected — the internet scans for them constantly. The only
question the review asks is whether each was answered **403**. A 200 there is an
incident and starts `incident-response.md` at containment.

**Security scan.** `tools/security-scan.php` exits non-zero on any ERROR, so it
gates a release rather than producing a report someone has to remember to read.

**Vulnerability scan.** `tools/vuln-scan.sh` runs OWASP ZAP against the relay
and the site, writes its reports into `docs/security/scans/<date>/`, and they
are committed. Findings are triaged against the deadlines in
`vulnerability-management.md`: 7 days critical, 30 days high. The report is the
evidence the scan happened - an uncommitted one proves nothing later.

**Attestation review.** Re-read `attestations.md` against reality. An answer
whose practice has lapsed is no longer true, and either the practice or the
answer has to change - telling Amazon something that stopped being true is
worse than having declared the gap in the first place.

**Incident response plan review.** Not a re-read. It checks that the contacts
still work, that each containment step still matches how the systems are
actually administered, and that the scope statement is still true. Recorded in
the table at the foot of `incident-response.md`.

**Password rotation.** Every password is generated, not remembered, so rotation
means replacement rather than incrementing a suffix.

## Recording

Anything found by any of these is recorded in the repository — an issue, or a
commit message that says what was found and what changed. That includes reviews
that found nothing worth acting on, because "we looked and it was clean" is
itself the evidence the cadence was kept.

### Review log

| Date | Review | Result |
|---|---|---|
| 1 Sep 2026 | Relay access logs, first run, 30-day window | 4,334 requests. 4,308 were eBay's own account-deletion notifications from its published IP ranges, answered 200 — legitimate. One host walked the full list of sensitive filenames: 4 refused 403, 3 absent 404, nothing served. **That host was us** ( 37.114.181.112, the operator's own address ) running the external verification checks earlier the same day -- not a third party. The first review misread it as hostile scanning, which is worth recording: a review that cannot tell its own traffic from an attacker's is not yet a control. **The review itself was defective**: it listed six of the seven probes, omitting the GET for `.ipresta-key` because the pattern matched `.key` and the filename contains `-key`. Fixed in `b139b03`; the matcher now works from named rules and the report grades 403/404 as correct rather than asserting all must be 403. |
