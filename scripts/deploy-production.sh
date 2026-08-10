#!/usr/bin/env bash
# Pull GHCR images and deploy the production stack on a single Docker host (M12.2).
#
# Prerequisites on host:
#   - Repository clone at repo root with .env and backend/.env configured
#   - TLS certificates (./scripts/init-letsencrypt.sh) for first deploy
#   - docker login ghcr.io OR GHCR_TOKEN + GHCR_USERNAME env vars
#
# Usage:
#   IMAGE_TAG=main ./scripts/deploy-production.sh
#   IMAGE_TAG=<git-sha> ./scripts/deploy-production.sh

set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_root"

if [ -f .env ]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

: "${DOMAIN:?Set DOMAIN in .env}"

IMAGE_TAG="${IMAGE_TAG:-main}"
export IMAGE_TAG

COMPOSE=(docker compose
  -f docker-compose.yml
  -f docker-compose.prod.yml
  -f docker-compose.ghcr.yml
)

if [ -n "${GHCR_TOKEN:-}" ]; then
  echo "Logging in to ghcr.io..."
  echo "${GHCR_TOKEN}" | docker login ghcr.io -u "${GHCR_USERNAME:-deploy}" --password-stdin
fi

echo "Pulling GHCR images (tag: ${IMAGE_TAG})..."
"${COMPOSE[@]}" pull

echo "Starting production stack..."
"${COMPOSE[@]}" up -d --no-build --wait

echo "Running database migrations (no seed)..."
"${COMPOSE[@]}" exec -T backend php artisan migrate --force

echo "Running smoke checks..."
"${repo_root}/scripts/smoke-production.sh"

echo "Production deploy complete (tag: ${IMAGE_TAG})."
