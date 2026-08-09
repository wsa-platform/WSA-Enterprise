#!/usr/bin/env bash
# WSA-Enterprise — Docker staging bootstrap (Linux/macOS/WSL)
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is not installed or not on PATH." >&2
  exit 1
fi

if [[ ! -f backend/.env ]]; then
  cp backend/.env.example backend/.env
  echo "Created backend/.env from .env.example"
fi

docker compose build
docker compose up -d postgres redis

echo "Waiting for postgres to become healthy..."
for _ in $(seq 1 60); do
  status="$(docker inspect --format '{{.State.Health.Status}}' "$(docker compose ps -q postgres)")" || true
  if [[ "$status" == "healthy" ]]; then
    break
  fi
  sleep 3
done

docker compose run --rm --no-deps backend php artisan key:generate --force
docker compose up -d --build
docker compose exec backend php artisan migrate --seed --force

echo
echo "Staging stack is ready at http://localhost:8080"
echo "Health:  curl http://localhost:8080/api/v1/health"
echo "Login:   admin@wsa.test / password"
