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

# The targets. The relay is the system that handles Amazon credentials; the
# site shares the host and so shares its exposure.
TARGETS=(
  "https://intellipresta.com/spapi/login.php"
  "https://intellipresta.com/"
)

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
