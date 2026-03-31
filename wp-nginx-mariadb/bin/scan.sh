#!/usr/bin/env bash
# bin/scan.sh — Run Grype vulnerability scans on all project container images.
#
# Scans two sets of images in parallel:
#   CG  — Chainguard hardened images (project runtime)
#   DHI — Docker Hardened Images (comparison baseline)
#
# Scanner: cgr.dev/chainguard-private/grype:latest
#
# Results are saved to:
#   scans/<name>/<YYYYMMDDTHHMMSSZ>.json        (Chainguard)
#   scans/<name>/dhi_<YYYYMMDDTHHMMSSZ>.json    (DHI)

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

# Images are pulled fresh before scanning so Grype always sees the latest
# registry digest. docker pull uses Docker Desktop's keychain credentials,
# avoiding the auth issue that arises when Grype tries to hit cgr.dev directly.
CG_IMAGES=(
  "wordpress:wordpress-custom:latest"
  "nginx:cgr.dev/chainguard-private/nginx:latest"
  "mariadb:cgr.dev/chainguard-private/mariadb:latest"
  "node:cgr.dev/chainguard-private/node:latest"
  "grype:cgr.dev/chainguard-private/grype:latest"
  "prometheus:cgr.dev/chainguard-private/prometheus:latest"
  "grafana:cgr.dev/chainguard-private/grafana:latest"
)

DHI_IMAGES=(
  "wordpress:wordpress:latest"
  "nginx:nginx:latest"
  "mariadb:mariadb:latest"
  "node:node:lts"
  "grype:anchore/grype:latest"
  "prometheus:prom/prometheus:latest"
  "grafana:grafana/grafana:latest"
)

log "Timestamp: ${TS}"
log "Pulling latest images from registry before scanning..."
echo ""
for entry in "${CG_IMAGES[@]}" "${DHI_IMAGES[@]}"; do
  name="${entry%%:*}"
  image="${entry#*:}"
  # wordpress-custom is a local build — skip pull
  if [[ "${image}" == "wordpress-custom:latest" ]]; then
    log "  skip pull (local build): ${image}"
    continue
  fi
  log "  docker pull ${image}"
  docker pull "${image}" --quiet || log "  WARNING: pull failed for ${image}"
done
echo ""

log "Scanning ${#CG_IMAGES[@]} Chainguard + ${#DHI_IMAGES[@]} DHI images in parallel..."
echo ""

pids=()

log "  [CG] Chainguard images:"
for entry in "${CG_IMAGES[@]}"; do
  name="${entry%%:*}"
  image="${entry#*:}"
  out="${SCANS_DIR}/${name}/${TS}.json"
  mkdir -p "${SCANS_DIR}/${name}"
  log "    → ${image}"
  grype "${image}" --output json 2>/dev/null > "${out}" &
  pids+=($!)
done

echo ""
log "  [DHI] Docker Hardened Images:"
for entry in "${DHI_IMAGES[@]}"; do
  name="${entry%%:*}"
  image="${entry#*:}"
  out="${SCANS_DIR}/${name}/dhi_${TS}.json"
  mkdir -p "${SCANS_DIR}/${name}"
  log "    → ${image}"
  grype "${image}" --output json 2>/dev/null > "${out}" &
  pids+=($!)
done

# Wait for all scans
failed=0
for pid in "${pids[@]}"; do
  wait "${pid}" || { log "WARNING: a scan process failed (pid ${pid})"; failed=1; }
done

echo ""
if [[ "${failed}" -eq 0 ]]; then
  log "All scans complete."
  log "  CG  results: scans/<image>/${TS}.json"
  log "  DHI results: scans/<image>/dhi_${TS}.json"
else
  log "One or more scans failed — check output above."
  exit 1
fi

# Summary table — Chainguard
echo ""
printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n" "IMAGE [CG]" "CRITICAL" "HIGH" "MEDIUM" "LOW" "UNKNOWN" "TOTAL"
printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n" "------------" "--------" "--------" "--------" "--------" "--------" "--------"
for entry in "${CG_IMAGES[@]}"; do
  name="${entry%%:*}"
  out="${SCANS_DIR}/${name}/${TS}.json"
  jq -r --arg name "${name}" '
    [ .matches[] | .vulnerability.severity ] |
    { name: $name,
      critical: (map(select(. == "Critical")) | length),
      high:     (map(select(. == "High"))     | length),
      medium:   (map(select(. == "Medium"))   | length),
      low:      (map(select(. == "Low"))      | length),
      unknown:  (map(select(. == "Unknown"))  | length),
      total:    length } |
    "\(.name)  \(.critical)  \(.high)  \(.medium)  \(.low)  \(.unknown)  \(.total)"
  ' "${out}" | awk '{printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n", $1, $2, $3, $4, $5, $6, $7}'
done

# Summary table — DHI
echo ""
printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n" "IMAGE [DHI]" "CRITICAL" "HIGH" "MEDIUM" "LOW" "UNKNOWN" "TOTAL"
printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n" "------------" "--------" "--------" "--------" "--------" "--------" "--------"
for entry in "${DHI_IMAGES[@]}"; do
  name="${entry%%:*}"
  out="${SCANS_DIR}/${name}/dhi_${TS}.json"
  jq -r --arg name "${name}" '
    [ .matches[] | .vulnerability.severity ] |
    { name: $name,
      critical: (map(select(. == "Critical")) | length),
      high:     (map(select(. == "High"))     | length),
      medium:   (map(select(. == "Medium"))   | length),
      low:      (map(select(. == "Low"))      | length),
      unknown:  (map(select(. == "Unknown"))  | length),
      total:    length } |
    "\(.name)  \(.critical)  \(.high)  \(.medium)  \(.low)  \(.unknown)  \(.total)"
  ' "${out}" | awk '{printf "%-12s  %8s  %8s  %8s  %8s  %8s  %8s\n", $1, $2, $3, $4, $5, $6, $7}'
done

# ── Sync results into WordPress Issue Tracker ─────────────────────────────────
echo ""
bash "${SCRIPT_DIR}/import-issues.sh"

# ── Generate AI scan story blog post ──────────────────────────────────────────
echo ""
bash "${SCRIPT_DIR}/generate-scan-story.sh"
