# Change management

Scope: the `amazonmarketplacepro` module, the `ebaymarketplacepro` module, and
the OAuth relay at `intellipresta.com/spapi/` and `/ebay/`.

A single person develops, reviews, approves and deploys. Writing this down is
not an attempt to make one person look like a team. It is here because a
process that exists only as habit cannot be audited, and because the controls
that do the real work — a test environment that is not production, a scan that
blocks a release, an immutable history — do not depend on how many people there
are.

---

## Environments

| Environment | What it is | What runs there |
|---|---|---|
| Development | Local PrestaShop install on the operator's workstation, own database, own PHP | All development and testing |
| Production — relay | `intellipresta.com/spapi/`, shared hosting | The OAuth relay only |
| Production — module | Each merchant's own PrestaShop install | The released module |

**No change reaches a merchant without running first on the development
install.** It is a full PrestaShop install with its own database, not a copy of
production data: it holds test products and test orders, never a merchant's.

The relay has no staging counterpart. It is a handful of small files with no
database,
and its behaviour is verified directly against the live Amazon authorisation
flow, which is the only thing that could meaningfully validate it. This is
stated rather than glossed: it is the weakest part of this process.

## The path a change takes

1. **Change** — made on the development install
2. **Test** — the affected flow exercised there; for anything touching the
   SP-API, `live-verify.php` runs read-only checks against the real API
3. **Scan** — `php tools/security-scan.php`. It exits non-zero on any ERROR,
   so a release cannot proceed past a finding by being forgotten
4. **Syntax check** — `php -l` on changed files, on the same PHP version the
   oldest supported merchant runs
5. **Commit** — one logical change per commit, with a message saying what
   changed and why. Security-relevant changes say so explicitly
6. **Approve** — the operator reviews the diff before pushing. Reviewing one's
   own diff catches less than a second reader would, so the automated gates in
   3 and 4 carry proportionately more of the weight
7. **Release** — tagged version, packaged, published
8. **Deploy (relay)** — via cPanel File Manager over HTTPS, or SSH with key
   authentication. Never by plain FTP

## Who may do what

One operator. There are no shared accounts, no third-party contributors, and no
other identity with write access to the repository, the hosting account or the
Amazon developer account. Each is protected by a unique generated password and
two-factor authentication (see `sp-api-security-controls.md` §7).

The practical restriction on who may deploy is therefore the same as the
restriction on who may authenticate, and that is enforced technically rather
than by policy.

## Emergency changes

A change made to contain a security incident skips step 6 and may skip step 2,
because containment beats process — see `incident-response.md`. It does not skip
step 5: the commit still records what was changed and why, on the same day. The
skipped steps are then performed retrospectively before the next ordinary
release.

## Rollback

Every deployed file is in version control, so rollback is `git checkout` of the
previous tag followed by redeployment. The hosting account additionally holds
roughly 30 daily off-site restore points. Restoration has been exercised in
practice, not merely configured.

## Record

The commit history is the change record: immutable, timestamped, and attributed.
Releases are tagged. Nothing is deployed to the relay that is not committed
first — a fix edited directly on the server would be invisible to this record,
which is why deployment copies from the repository rather than the other way
round.
