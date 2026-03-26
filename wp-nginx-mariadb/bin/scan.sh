#!/usr/bin/env bash
# bin/scan.sh — Run Grype vulnerability scans on all project container images.
#
# Scanner: cgr.dev/chainguard-private/grype:latest (run via Docker)
#
# Usage:
#   bash bin/scan.sh
#
# Results are saved to:
#   scans/<image>/<YYYYMMDDTHHMMSSZ>.json

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCANS_DIR="${SCRIPT_DIR}/../scans"
TS=$(date -u +"%Y%m%dT%H%M%SZ")

GRYPE_IMAGE="cgr.dev/chainguard-private/grype:latest"

log() { echo "[scan] $*"; }

grype() {
  docker run --rm \
    -u root \
    -v /var/run/docker.sock:/var/run/docker.sock \
    "${GRYPE_IMAGE}" \
    "$@"
}

IMAGES=(
  "wordpress:wordpress-custom:latest"
  "nginx:cgr.dev/chainguard-private/nginx:latest"
  "mariadb:cgr.dev/chainguard-private/mariadb:latest"
  "node:cgr.dev/chainguard-private/node:latest"
)

log "Timestamp: ${TS}"
log "Scanning ${#IMAGES[@]} images in parallel..."
echo ""

pids=()
for entry in "${IMAGES[@]}"; do
  name="${entry%%:*}"
  image="${entry#*:}"
  out="${SCANS_DIR}/${name}/${TS}.json"
  mkdir -p "${SCANS_DIR}/${name}"
  log "  → ${image}"
  grype "${image}" --output json 2>/dev/null > "${out}" &
  pids+=($!)
done

# Wait for all scans and check exit codes
failed=0
for pid in "${pids[@]}"; do
  wait "${pid}" || { log "WARNING: a scan process failed (pid ${pid})"; failed=1; }
done

echo ""
if [[ "${failed}" -eq 0 ]]; then
  log "All scans complete. Results saved to scans/<image>/${TS}.json"
else
  log "One or more scans failed — check output above."
  exit 1
fi

# Print summary table
echo ""
printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n" "IMAGE" "CRITICAL" "HIGH" "MEDIUM" "LOW" "UNKNOWN" "TOTAL"
printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n" "------------" "--------" "--------" "--------" "--------" "--------" "--------"
for entry in "${IMAGES[@]}"; do
  name="${entry%%:*}"
  out="${SCANS_DIR}/${name}/${TS}.json"
  jq -r --arg name "${name}" '
    [ .matches[] | .vulnerability.severity ] |
    {
      name: $name,
      critical: (map(select(. == "Critical")) | length),
      high:     (map(select(. == "High"))     | length),
      medium:   (map(select(. == "Medium"))   | length),
      low:      (map(select(. == "Low"))      | length),
      unknown:  (map(select(. == "Unknown"))  | length),
      total:    length
    } |
    "\(.name)  \(.critical)  \(.high)  \(.medium)  \(.low)  \(.unknown)  \(.total)"
  ' "${out}" | awk '{printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n", $1, $2, $3, $4, $5, $6, $7}'
done
