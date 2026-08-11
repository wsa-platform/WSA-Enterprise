#!/usr/bin/env bash
# Create a timestamped PostgreSQL backup on the production Docker host (M12.5).
#
# Prerequisites:
#   - Production stack running (postgres service healthy)
#   - .env with POSTGRES_* variables (never logged by this script)
#
# Usage:
#   ./scripts/backup-production.sh
#   DRY_RUN=1 ./scripts/backup-production.sh
#   BACKUP_DIR=/var/backups/wsa ./scripts/backup-production.sh
#
# Safety:
#   - Never prints secrets
#   - Never deletes production data
#   - Never restores automatically

set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_root"

if [ -f .env ]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

: "${POSTGRES_DB:?Set POSTGRES_DB in .env}"
: "${POSTGRES_USER:?Set POSTGRES_USER in .env}"
: "${POSTGRES_PASSWORD:?Set POSTGRES_PASSWORD in .env}"

BACKUP_DIR="${BACKUP_DIR:-${PROD_BACKUP_DIR:-./backups/production}}"
DRY_RUN="${DRY_RUN:-0}"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_BASENAME="wsa_enterprise_${POSTGRES_DB}_${TIMESTAMP}.sql.gz"
BACKUP_PATH="${BACKUP_DIR%/}/${BACKUP_BASENAME}"

COMPOSE=(docker compose
  -f docker-compose.yml
  -f docker-compose.prod.yml
)

if [ "${DRY_RUN}" = "1" ]; then
  echo "DRY RUN: backup configuration validated."
  echo "DRY RUN: would write backup to: ${BACKUP_PATH}"
  echo "DRY RUN: postgres database: ${POSTGRES_DB} (user: ${POSTGRES_USER})"
  echo "DRY RUN: no dump executed."
  exit 0
fi

mkdir -p "${BACKUP_DIR}"

echo "Creating PostgreSQL backup: ${BACKUP_PATH}"
"${COMPOSE[@]}" exec -T \
  -e PGPASSWORD="${POSTGRES_PASSWORD}" \
  postgres pg_dump \
  -U "${POSTGRES_USER}" \
  -d "${POSTGRES_DB}" \
  --no-owner \
  --no-privileges \
  | gzip -c > "${BACKUP_PATH}"

if [ ! -s "${BACKUP_PATH}" ]; then
  echo "Backup failed: file missing or empty (${BACKUP_PATH})"
  exit 1
fi

backup_size="$(wc -c < "${BACKUP_PATH}" | tr -d ' ')"
echo "Backup verified: ${BACKUP_PATH} (${backup_size} bytes)"
