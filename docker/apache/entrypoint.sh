#!/usr/bin/env bash
set -e

# Render (and many PaaS) injects $PORT; fall back to 80 for local Docker.
PORT="${PORT:-80}"

# Replace Apache's Listen directive and the VirtualHost port.
sed -ri "s!^Listen 80\$!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \\*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
