#!/usr/bin/env bash
# Production verification checks for HTTPS endpoints and container health (M12.5).
#
# Usage:
#   ./scripts/verify-production.sh
#   DOMAIN=app.example.com ./scripts/verify-production.sh
#
# Does not print secrets.

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

COMPOSE=(docker compose
  -f docker-compose.yml
  -f docker-compose.prod.yml
  -f docker-compose.ghcr.yml
)

base="https://${DOMAIN}"
failures=0

check_https() {
  local name="$1"
  local url="$2"
  local expect_substr="${3:-}"

  echo "Verify HTTPS: ${name} (${url})"
  local body
  body="$(curl -fsSk "${url}")" || { echo "  FAIL: request failed"; failures=$((failures + 1)); return; }

  if [ -n "${expect_substr}" ] && ! echo "${body}" | grep -q "${expect_substr}"; then
    echo "  FAIL: expected response to contain '${expect_substr}'"
    failures=$((failures + 1))
    return
  fi

  echo "  OK"
}

check_http_status() {
  local name="$1"
  local url="$2"
  local expected="$3"

  echo "Verify HTTP status: ${name} (${url})"
  local status
  status="$(curl -fsSk -o /dev/null -w '%{http_code}' "${url}")" || { echo "  FAIL: request failed"; failures=$((failures + 1)); return; }

  if [ "${status}" != "${expected}" ]; then
    echo "  FAIL: expected HTTP ${expected}, got ${status}"
    failures=$((failures + 1))
    return
  fi

  echo "  OK (${status})"
}

check_service_healthy() {
  local service="$1"
  echo "Verify container health: ${service}"

  local cid health
  cid="$("${COMPOSE[@]}" ps -q "${service}" 2>/dev/null || true)"
  if [ -z "${cid}" ]; then
    echo "  FAIL: container not running"
    failures=$((failures + 1))
    return
  fi

  health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${cid}" 2>/dev/null || echo "unknown")"
  if [ "${health}" != "healthy" ] && [ "${health}" != "running" ]; then
    echo "  FAIL: status=${health}"
    failures=$((failures + 1))
    return
  fi

  echo "  OK (${health})"
}

check_postgres() {
  echo "Verify PostgreSQL availability"
  if "${COMPOSE[@]}" exec -T postgres pg_isready -U "${POSTGRES_USER:-wsa}" -d "${POSTGRES_DB:-wsa_enterprise}" >/dev/null 2>&1; then
    echo "  OK"
  else
    echo "  FAIL: pg_isready failed"
    failures=$((failures + 1))
  fi
}

check_redis() {
  echo "Verify Redis availability"
  if "${COMPOSE[@]}" exec -T redis redis-cli ping 2>/dev/null | grep -q PONG; then
    echo "  OK"
  else
    echo "  FAIL: redis ping failed"
    failures=$((failures + 1))
  fi
}

echo "Production verification for ${base}"
echo "----------------------------------------"

check_https "legacy health" "${base}/api/v1/health" '"status":"ok"'
check_https "liveness" "${base}/api/v1/health/live" '"status":"ok"'
check_http_status "readiness" "${base}/api/v1/health/ready" "200"
check_http_status "Laravel up" "${base}/up" "200"

check_service_healthy "backend"
check_service_healthy "frontend"
check_service_healthy "nginx"
check_service_healthy "queue"
check_service_healthy "scheduler"

check_postgres
check_redis

echo "----------------------------------------"
if [ "${failures}" -gt 0 ]; then
  echo "Verification FAILED (${failures} check(s))"
  exit 1
fi

echo "All production verification checks passed."
