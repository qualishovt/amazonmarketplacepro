#!/usr/bin/env bash
#
# Monthly vulnerability scan of the in-scope systems.
#
#   tools/vuln-scan.sh              baseline (passive) scan  - the monthly run
#   tools/vuln-scan.sh --full       full active scan         - the annual run
#
# Runs OWASP ZAP against the public surface: the OAuth relay and the site it
# is hosted beside. ZAP is used rather than something hand-rolled because an
# assessor can recognise it, reproduce it, and read its report - which a
# bespoke script does not give them.
#
# Reports are written to docs/security/scans/YYYY-MM-DD/ and committed. The
# report IS the evidence that the scan happened; a scan whose output was
# thrown away cannot be shown to anyone.
#
# Exit codes: 0 clean or warnings only, 1 at least one FAIL, 2 could not run.

set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DATE="$(date +%Y-%m-%d)"
OUT="$ROOT/docs/security/scans/$DATE"
MODE="baseline"
SCRIPT="zap-baseline.py"

if [ "${1:-}" = "--full" ]; then
  MODE="full"
  SCRIPT="zap-full-scan.py"
fi

# Throttling. The host is shared, and the provider confirmed on 1 September
# 2026 that they cannot exempt our address from CSF, ModSecurity or
# Imunify360: an aggressive scan trips the rate limiter and locks us out of
# our own site, with nothing to do but wait or raise a ticket.
#
# Throttling did not save the active scan. It was blocked after ~14 minutes
# at one thread and 400ms, and the provider later named the trigger:
# CAPTCHA_DOS_ALERT after 101 CAPTCHA requests. The scan was not stopped by
# volume as such - it was stopped by the spider answering an interstitial
# challenge over and over, which the firewall reads as an attack on the
# challenge. Slowing down further would not have helped.
#
# One thread per host with a deliberate pause between requests. The scan
# takes considerably longer, but the surface is a handful of URLs, so that
# costs patience rather than coverage.
THROTTLE="-config scanner.threadPerHost=1 -config scanner.delayInMs=400"
THROTTLE="$THROTTLE -config spider.thread=1"
THROTTLE="$THROTTLE -config connection.timeoutInSecs=45"

# The targets. The relay is the system that handles Amazon credentials; the
# site shares the host and so shares its exposure.
TARGETS=(
  "https://intellipresta.com/spapi/login.php"
  "https://intellipresta.com/"
)

# The annual active scan does NOT run against production, and this is not a
# preference. A2 Hosting refused the request in writing on 2 September 2026
# (ticket ULQ-581-06565, level 2): on a shared tier they will not permit it,
# because the traffic reaches other tenants' server and their firewall will
# block the source address again. We accept that. Running it anyway would be
# an unauthorised scan of a host that has declined.
#
# So --full points at the local staging replica instead: same application
# code, dummy credentials, no shared tenancy. It answers identically to
# production across every endpoint, which is what makes the substitution
# honest rather than convenient. Bring it up with the XAMPP Apache on :81;
# host.docker.internal is how the container reaches it.
#
# If the relay ever moves to a VPS or dedicated host, point this back at
# production and delete the guard below - the constraint is the shared tier,
# not the scan.
STAGING_TARGETS=(
  "http://host.docker.internal:81/spapi-staging/login.php"
)

if [ "$MODE" = "full" ]; then
  TARGETS=("${STAGING_TARGETS[@]}")

  for t in "${TARGETS[@]}"; do
    case "$t" in
      *intellipresta.com*)
        echo "refusing: an active scan of intellipresta.com is not authorised." >&2
        echo "The host declined it in ticket ULQ-581-06565. See" >&2
        echo "docs/security/vulnerability-management.md, 'The host's final answer'." >&2
        exit 2
        ;;
    esac
  done
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "docker is required: the scanner runs as a container." >&2
  exit 2
fi
if ! docker info >/dev/null 2>&1; then
  echo "docker is installed but the daemon is not running." >&2
  exit 2
fi

mkdir -p "$OUT"
echo "ZAP $MODE scan  ->  docs/security/scans/$DATE"
echo

worst=0
for target in "${TARGETS[@]}"; do
  name="$(echo "$target" | sed -e 's|https\?://||' -e 's|[/:.]|_|g')"
  echo "── $target"

  # -I: warnings do not fail the run; only FAIL-level findings do.
  MSYS_NO_PATHCONV=1 docker run --rm -v "$OUT:/zap/wrk/:rw" -t zaproxy/zap-stable \
    "$SCRIPT" -t "$target" \
    -J "$name.json" -r "$name.html" -I \
    -z "$THROTTLE" \
    > "$OUT/$name.log" 2>&1
  code=$?

  tail -1 "$OUT/$name.log"
  if [ $code -gt $worst ]; then worst=$code; fi
  echo
done

# A short summary beside the reports, so the next review does not have to
# open three files to see what happened.
{
  echo "# Vulnerability scan — $DATE"
  echo
  echo "Mode: $MODE (OWASP ZAP)"
  echo
  for f in "$OUT"/*.log; do
    echo "## $(basename "$f" .log)"
    echo '```'
    tail -1 "$f"
    echo '```'
    echo
  done
  echo "Findings are triaged in ../../vulnerability-management.md against the"
  echo "remediation deadlines recorded there."
} > "$OUT/summary.md"

echo "written: docs/security/scans/$DATE/"
exit $worst
