#!/usr/bin/env bash
# One-shot bootstrap for a fresh Ubuntu 22.04 / 24.04 Lightsail instance.
# Run as the default `ubuntu` user. DO NOT run blindly on a server you care about.

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/CHANGE_ME/gestion_commerciale.git}"
APP_DIR="${APP_DIR:-$HOME/gestion_commerciale}"

echo "==> Updating system packages"
sudo apt-get update -y
sudo apt-get upgrade -y

echo "==> Installing prerequisites"
sudo apt-get install -y ca-certificates curl gnupg git ufw fail2ban

echo "==> Installing Docker Engine + Compose plugin"
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update -y
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"

echo "==> Firewall (ufw): only 22, 80, 443"
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
yes | sudo ufw enable

echo "==> fail2ban default jail on SSH"
sudo systemctl enable --now fail2ban

echo "==> Cloning the app"
if [ ! -d "$APP_DIR" ]; then
    git clone "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR"

echo "==> Preparing .env.prod"
if [ ! -f .env.prod ]; then
    cp .env.prod.example .env.prod
    echo "!! Edit $APP_DIR/.env.prod with real secrets, then run:"
    echo "   cd $APP_DIR && docker compose -f docker-compose.prod.yml up -d --build"
else
    echo "   .env.prod already present, skipping copy."
fi

echo
echo "==> Next steps:"
echo "   1. Log out & back in (so the 'docker' group is applied to your user)."
echo "   2. nano $APP_DIR/.env.prod   # set real passwords"
echo "   3. cd $APP_DIR && docker compose -f docker-compose.prod.yml up -d --build"
echo "   4. Check: curl -I http://localhost/"
