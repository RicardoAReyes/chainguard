#!/usr/bin/env bash
# bin/generate-scan-story.sh
# Generates an AI-written (or template-based) security blog post from the
# latest scan data and publishes it to WordPress.
#
# If ANTHROPIC_API_KEY is set in the environment or .env, Claude writes the prose.
# Otherwise, a data-driven template generates the article automatically.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Optionally load ANTHROPIC_API_KEY from .env (not required)
if [ -z "${ANTHROPIC_API_KEY:-}" ]; then
    ENV_FILE="${SCRIPT_DIR}/../.env"
    if [ -f "$ENV_FILE" ]; then
        export $(grep -v '^#' "$ENV_FILE" | grep 'ANTHROPIC_API_KEY' | xargs) 2>/dev/null || true
    fi
fi

if [ -z "${ANTHROPIC_API_KEY:-}" ] || [ "${ANTHROPIC_API_KEY:-}" = "your-api-key-here" ]; then
    echo "[scan-story] No API key found — using template mode."
    unset ANTHROPIC_API_KEY
else
    echo "[scan-story] API key found — Claude will write the article."
fi

docker cp "${SCRIPT_DIR}/generate-scan-story.php" wp-nginx-mariadb-app-1:/tmp/generate-scan-story.php

docker exec \
    ${ANTHROPIC_API_KEY:+-e ANTHROPIC_API_KEY="${ANTHROPIC_API_KEY}"} \
    wp-nginx-mariadb-app-1 \
    php /tmp/generate-scan-story.php
