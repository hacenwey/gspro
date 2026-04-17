#!/bin/sh
set -e

PORT="${PORT:-80}"
echo "[entrypoint] starting, PORT=${PORT}"

# Replace Apache's Listen directive and the VirtualHost port.
sed -ri "s!^Listen 80\$!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \\*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf

echo "[entrypoint] ports.conf:"
grep -E '^Listen' /etc/apache2/ports.conf || true

echo "[entrypoint] config test:"
apache2ctl -t || { echo "[entrypoint] apache config invalid"; exit 1; }

echo "[entrypoint] launching apache2-foreground"
exec apache2-foreground
