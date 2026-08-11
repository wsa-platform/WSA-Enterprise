#!/usr/bin/env bash
# Reload nginx after a successful Let's Encrypt renewal (M13.1).
# Used as certbot --deploy-hook (mounted in certbot container) or run on the host.
#
# Usage (host):
#   ./scripts/certbot-deploy-hook.sh

set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_root"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker CLI unavailable; run this hook on the production host after renewal."
  exit 0
fi

COMPOSE=(docker compose
  -f docker-compose.yml
  -f docker-compose.prod.yml
)

if ! "${COMPOSE[@]}" ps -q nginx >/dev/null 2>&1; then
  echo "nginx service not running; skipping reload."
  exit 0
fi

echo "Certbot deploy hook: reloading nginx..."
"${COMPOSE[@]}" exec -T nginx nginx -s reload
echo "nginx reload complete."
