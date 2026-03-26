#!/usr/bin/env bash
# bin/setup.sh — Restore the WordPress demo site from scratch.
#
# Run this after:
#   1. `docker compose up -d --build`
#   2. Waiting ~10s for MariaDB to be ready
#
# Usage:
#   bash bin/setup.sh
#
# What it does:
#   - Installs WordPress core (if not already installed)
#   - Activates the twentytwentyfour-child theme
#   - Sets pretty permalinks
#   - Creates / updates all demo pages with their full HTML content

set -euo pipefail

CONTAINER="wp-nginx-mariadb-app-1"
WP="docker exec -i ${CONTAINER} wp --path=/var/www/html --allow-root"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONTENT_DIR="${SCRIPT_DIR}/content"

# ── helpers ──────────────────────────────────────────────────────────────────

log()  { echo "[setup] $*"; }
die()  { echo "[setup] ERROR: $*" >&2; exit 1; }

wait_for_wp() {
  log "Waiting for WordPress container to be ready..."
  for i in $(seq 1 30); do
    if docker exec "${CONTAINER}" php -r 'exit(0);' 2>/dev/null; then
      return 0
    fi
    sleep 2
  done
  die "Container ${CONTAINER} did not become ready in time."
}

# Write content from a local file into the container's /tmp, then return the path.
upload_content() {
  local slug="$1"
  local src="${CONTENT_DIR}/${slug}.html"
  local dst="/tmp/${slug}.html"
  [[ -f "$src" ]] || die "Content file not found: ${src}"
  docker cp "${src}" "${CONTAINER}:${dst}"
  echo "${dst}"
}

# Create or update a page by slug.
#   $1 = post_title
#   $2 = post_name (slug)
#   $3 = content file path inside container
upsert_page() {
  local title="$1"
  local slug="$2"
  local content_file="$3"

  log "Upserting page: ${title} (/${slug}/)"

  # Check if a page with this slug already exists
  local existing_id
  existing_id=$(docker exec "${CONTAINER}" wp post list \
    --path=/var/www/html --allow-root \
    --post_type=page \
    --post_status=any \
    --name="${slug}" \
    --field=ID \
    --format=csv 2>/dev/null | tail -n1 || true)

  local content
  content=$(docker exec "${CONTAINER}" cat "${content_file}")

  if [[ -n "${existing_id}" && "${existing_id}" =~ ^[0-9]+$ ]]; then
    log "  Updating existing page ID ${existing_id}"
    docker exec "${CONTAINER}" wp post update "${existing_id}" \
      --path=/var/www/html --allow-root \
      --post_title="${title}" \
      --post_name="${slug}" \
      --post_status=publish \
      --post_content="${content}" \
      --quiet
  else
    log "  Creating new page"
    docker exec "${CONTAINER}" wp post create \
      --path=/var/www/html --allow-root \
      --post_type=page \
      --post_title="${title}" \
      --post_name="${slug}" \
      --post_status=publish \
      --post_content="${content}" \
      --quiet
  fi
}

# ── main ─────────────────────────────────────────────────────────────────────

wait_for_wp

# ── 1. WordPress core install ─────────────────────────────────────────────────
if docker exec "${CONTAINER}" wp core is-installed --path=/var/www/html --allow-root 2>/dev/null; then
  log "WordPress already installed — skipping core install."
else
  log "Installing WordPress core..."
  # Load DB credentials from .env if present
  ENV_FILE="${SCRIPT_DIR}/../.env"
  if [[ -f "${ENV_FILE}" ]]; then
    # shellcheck disable=SC1090
    set -a; source "${ENV_FILE}"; set +a
  fi
  : "${WORDPRESS_DB_USER:?'Set WORDPRESS_DB_USER in .env or environment'}"
  : "${WORDPRESS_DB_PASSWORD:?'Set WORDPRESS_DB_PASSWORD in .env or environment'}"
  : "${WORDPRESS_DB_NAME:?'Set WORDPRESS_DB_NAME in .env or environment'}"

  docker exec "${CONTAINER}" wp core install \
    --path=/var/www/html --allow-root \
    --url="http://localhost:8000" \
    --title="Ricardo's Chainguard Demo" \
    --admin_user="admin" \
    --admin_password="admin" \
    --admin_email="admin@example.com" \
    --skip-email
fi

# ── 2. Theme ──────────────────────────────────────────────────────────────────
log "Activating child theme..."
docker exec "${CONTAINER}" wp theme activate twentytwentyfour-child \
  --path=/var/www/html --allow-root --quiet 2>/dev/null || \
  log "  (theme already active or not yet copyable — ensure the node container has run 'npm run start' first)"

# ── 3. Permalinks ─────────────────────────────────────────────────────────────
log "Setting pretty permalinks..."
docker exec "${CONTAINER}" wp rewrite structure '/%postname%/' \
  --path=/var/www/html --allow-root --quiet
docker exec "${CONTAINER}" wp rewrite flush \
  --path=/var/www/html --allow-root --quiet

# ── 4. Pages ─────────────────────────────────────────────────────────────────
log "Uploading page content files to container..."

SC_FILE=$(upload_content "supply-chain-attacks")
PROD_FILE=$(upload_content "our-products")

upsert_page "Supply Chain Attacks: 10 Years of Data" "supply-chain-attacks" "${SC_FILE}"
upsert_page "Our Products" "our-products" "${PROD_FILE}"

# ── done ─────────────────────────────────────────────────────────────────────
log ""
log "✓ Setup complete."
log "  Site URL : http://localhost:8000"
log "  Pages    : http://localhost:8000/supply-chain-attacks/"
log "             http://localhost:8000/our-products/"
