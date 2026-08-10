#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_root"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required." >&2
  exit 1
fi

echo "Ensuring isolated test database exists..."
if ! docker compose exec -T postgres psql -U wsa -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = 'wsa_enterprise_test'" | grep -q 1; then
  docker compose exec -T postgres psql -U wsa -d postgres -c "CREATE DATABASE wsa_enterprise_test;"
fi

echo "Running PHPUnit in isolated test container..."
docker compose --profile test run --rm backend-test
