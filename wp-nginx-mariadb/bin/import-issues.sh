#!/usr/bin/env bash
# bin/import-issues.sh — Sync latest Grype scan results into WordPress Issue Tracker.
#
# Reads the most recent CG and DHI scan JSON files, then:
#   - Creates new issues for CVEs that appeared since the last import
#   - Closes issues for CVEs that are no longer present (patched)
#   - Skips issues that already exist (idempotent)
#
# Safe to run standalone or called from bin/scan.sh after a fresh scan.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCANS_DIR="${SCRIPT_DIR}/../scans"
CONTAINER="wp-nginx-mariadb-app-1"
IMAGES=(wordpress nginx mariadb node grype prometheus grafana)

log() { echo "[import] $*"; }

# ── Check container is running ────────────────────────────────────────────────
if ! docker inspect "${CONTAINER}" --format '{{.State.Running}}' 2>/dev/null | grep -q true; then
  log "ERROR: container ${CONTAINER} is not running — skipping issue import"
  exit 1
fi

# ── Stage latest scan files in the container ──────────────────────────────────
log "Staging scan files in container..."
docker exec "${CONTAINER}" mkdir -p $(printf "/tmp/scans/%s " "${IMAGES[@]}")

for img in "${IMAGES[@]}"; do
  img_dir="${SCANS_DIR}/${img}"

  cg_latest=$(ls "${img_dir}"/*.json 2>/dev/null | grep -v 'dhi_' | sort | tail -1 || true)
  dhi_latest=$(ls "${img_dir}"/dhi_*.json 2>/dev/null | sort | tail -1 || true)

  if [[ -n "${cg_latest}" ]]; then
    docker cp "${cg_latest}" "${CONTAINER}:/tmp/scans/${img}/cg_latest.json" 2>/dev/null
    log "  CG  ${img}: $(basename "${cg_latest}")"
  else
    log "  WARN: no CG scan found for ${img}"
  fi

  if [[ -n "${dhi_latest}" ]]; then
    docker cp "${dhi_latest}" "${CONTAINER}:/tmp/scans/${img}/dhi_latest.json" 2>/dev/null
    log "  DHI ${img}: $(basename "${dhi_latest}")"
  else
    log "  WARN: no DHI scan found for ${img}"
  fi
done

# ── Copy and run the PHP import script ───────────────────────────────────────
docker cp "${SCRIPT_DIR}/import-issues.php" "${CONTAINER}:/tmp/import-issues.php"
log "Running issue sync..."
docker exec "${CONTAINER}" php /tmp/import-issues.php 2>/dev/null

log "Issue sync complete."
