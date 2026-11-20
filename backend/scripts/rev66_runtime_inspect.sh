#!/bin/sh
set -e
echo "=== id ==="
id
echo "=== storage root ==="
ls -la /var/www/html/storage/app/scientific_stable_results 2>&1 || true
echo "=== org 8 ==="
ls -la /var/www/html/storage/app/scientific_stable_results/8 2>&1 | head -12 || true
echo "=== fpm pool config ==="
grep -E '^(user|group|listen\.owner|listen\.group)' /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true
echo "=== processes ==="
# shellcheck disable=SC2012
for p in /proc/[0-9]*; do
  cmd=$(tr '\0' ' ' < "$p/cmdline" 2>/dev/null || true)
  case "$cmd" in
    *php-fpm*)
      uid=$(awk '/^Uid:/{print $2}' "$p/status" 2>/dev/null || echo '?')
      echo "PID=$(basename "$p") UID=$uid CMD=$cmd"
      ;;
  esac
done
echo "=== mount for /var/www/html ==="
mount | grep '/var/www/html' || true
df -T /var/www/html/storage/app 2>/dev/null || true
