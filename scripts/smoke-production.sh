#!/usr/bin/env bash
# Post-deploy smoke checks for production HTTPS endpoints (M12.2).
#
# Usage: ./scripts/smoke-production.sh
# Requires DOMAIN in .env

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

base="https://${DOMAIN}"

echo "Smoke: GET ${base}/api/v1/health/live"
live_body="$(curl -fsSk "${base}/api/v1/health/live")"
echo "${live_body}" | grep -q '"status":"ok"'

echo "Smoke: GET ${base}/api/v1/health/ready"
ready_status="$(curl -fsSk -o /dev/null -w '%{http_code}' "${base}/api/v1/health/ready")"
if [ "${ready_status}" != "200" ]; then
  echo "Expected HTTP 200 from /api/v1/health/ready, got ${ready_status}"
  exit 1
fi

echo "Smoke: GET ${base}/api/v1/health"
health_body="$(curl -fsSk "${base}/api/v1/health")"
echo "${health_body}" | grep -q '"status":"ok"'

echo "Smoke: GET ${base}/up"
up_status="$(curl -fsSk -o /dev/null -w '%{http_code}' "${base}/up")"
if [ "${up_status}" != "200" ]; then
  echo "Expected HTTP 200 from /up, got ${up_status}"
  exit 1
fi

echo "Smoke checks passed for ${base}"
