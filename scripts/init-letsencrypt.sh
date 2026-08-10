#!/usr/bin/env bash
# Obtain initial Let's Encrypt certificates for production Docker deployment (M12.1).
# Run once on the production host before or during first deploy.
#
# Prerequisites:
#   - .env with DOMAIN and CERTBOT_EMAIL set
#   - DNS A/AAAA record for DOMAIN pointing to this host
#   - Ports 80 and 443 reachable from the internet
#
# Usage: ./scripts/init-letsencrypt.sh

set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_root"

if [ ! -f .env ]; then
  echo "Missing .env — copy .env.example and set DOMAIN, CERTBOT_EMAIL, POSTGRES_PASSWORD."
  exit 1
fi

# shellcheck disable=SC1091
set -a
source .env
set +a

: "${DOMAIN:?Set DOMAIN in .env}"
: "${CERTBOT_EMAIL:?Set CERTBOT_EMAIL in .env}"

COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "Creating dummy certificate for ${DOMAIN} (allows nginx to start before real cert)..."
path="/etc/letsencrypt/live/${DOMAIN}"
$COMPOSE run --rm --entrypoint sh certbot -c "
  mkdir -p ${path}
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout ${path}/privkey.pem \
    -out ${path}/fullchain.pem \
    -subj '/CN=${DOMAIN}'
"

echo "Starting nginx with dummy certificate..."
$COMPOSE up -d nginx

echo "Removing dummy certificate..."
$COMPOSE run --rm --entrypoint sh certbot -c "rm -Rf /etc/letsencrypt/live/${DOMAIN} /etc/letsencrypt/archive/${DOMAIN} /etc/letsencrypt/renewal/${DOMAIN}.conf"

echo "Requesting Let's Encrypt certificate..."
$COMPOSE run --rm --entrypoint certbot certbot certonly \
  --webroot -w /var/www/certbot \
  --email "${CERTBOT_EMAIL}" \
  --agree-tos --no-eff-email \
  -d "${DOMAIN}"

echo "Reloading nginx with real certificate..."
$COMPOSE exec nginx nginx -s reload

echo "Starting full production stack..."
$COMPOSE up -d

echo "Done. Verify: curl -fsS https://${DOMAIN}/api/v1/health"
