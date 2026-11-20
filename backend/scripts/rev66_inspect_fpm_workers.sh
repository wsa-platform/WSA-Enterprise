#!/bin/sh
# Rev 6.6 — print php-fpm pool worker UIDs (run as root inside backend container).
echo "master_cmdline=$(tr '\0' ' ' </proc/1/cmdline)"
grep -E '^(user|group)[[:space:]]*=' /usr/local/etc/php-fpm.d/www.conf || true
for p in /proc/[0-9]*; do
  cmd=$(tr '\0' ' ' <"$p/cmdline" 2>/dev/null || true)
  case "$cmd" in
    *'php-fpm: pool'*)
      uid=$(awk '/^Uid:/{print $2}' "$p/status")
      echo "worker_uid=$uid cmd=$cmd"
      ;;
  esac
done
