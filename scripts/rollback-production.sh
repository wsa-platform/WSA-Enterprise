#!/usr/bin/env bash
# Roll back production to a previous immutable GHCR image tag (M12.5).
#
# Does NOT restore database backups or run destructive migration commands.
# Redeploys containers only, then runs verification checks.
#
# Usage:
#   IMAGE_TAG=<full-git-sha> ./scripts/rollback-production.sh
#   IMAGE_TAG=sha-12345 ./scripts/rollback-production.sh
#
# Refuses:
#   - Missing IMAGE_TAG
#   - Mutable tag "main"
#   - Empty or whitespace-only IMAGE_TAG

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

if [ -z "${IMAGE_TAG:-}" ]; then
  echo "ERROR: IMAGE_TAG is required. Set an immutable GHCR tag (full git SHA or sha-*)."
  exit 1
fi

if [[ "${IMAGE_TAG}" =~ ^[[:space:]]*$ ]]; then
  echo "ERROR: IMAGE_TAG cannot be empty or whitespace."
  exit 1
fi

if [ "${IMAGE_TAG}" = "main" ]; then
  echo "ERROR: Refusing rollback to mutable tag 'main'. Use an immutable IMAGE_TAG."
  exit 1
fi

# Immutable targets: 7+ hex SHA prefix, full SHA, or sha-<run> CI tag
if ! [[ "${IMAGE_TAG}" =~ ^([0-9a-f]{7,40}|sha-[0-9]+)$ ]]; then
  echo "ERROR: IMAGE_TAG must be an immutable reference (git SHA or sha-*), got: ${IMAGE_TAG}"
  exit 1
fi

export IMAGE_TAG

echo "=========================================="
echo " PRODUCTION ROLLBACK"
echo " Target IMAGE_TAG: ${IMAGE_TAG}"
echo " Domain: ${DOMAIN}"
echo " Database restore: NOT performed (manual only)"
echo " Auto migrate: disabled for rollback"
echo "=========================================="

COMPOSE=(docker compose
  -f docker-compose.yml
  -f docker-compose.prod.yml
  -f docker-compose.ghcr.yml
)

if [ -n "${GHCR_TOKEN:-}" ]; then
  echo "Logging in to ghcr.io..."
  echo "${GHCR_TOKEN}" | docker login ghcr.io -u "${GHCR_USERNAME:-deploy}" --password-stdin
fi

echo "Pulling GHCR images for rollback (tag: ${IMAGE_TAG})..."
"${COMPOSE[@]}" pull

echo "Redeploying production stack at tag ${IMAGE_TAG}..."
"${COMPOSE[@]}" up -d --no-build --remove-orphans --wait

echo "Skipping automatic database migrations on rollback."
echo "Review docs/phase-12-m12-5-rollback-runbook.md for migration guidance."

echo "Running production verification..."
"${repo_root}/scripts/verify-production.sh"

echo "Rollback redeploy complete (tag: ${IMAGE_TAG})."
echo "Record the incident and obtain operator sign-off per the rollback runbook."
