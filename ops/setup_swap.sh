#!/bin/bash
# One-time server hardening for t3.micro (1 GB RAM).
# Adds 2 GB swap, tuned swappiness, and a docker health watchdog.
set -e

echo "=== 1. Swap ==="
if [ ! -f /swapfile ]; then
    sudo fallocate -l 512M /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
    echo "OK: 2G swap created"
else
    echo "SKIP: /swapfile already exists"
fi

echo "=== 2. Swappiness ==="
# Low swappiness: only swap when really needed
echo 'vm.swappiness=10' | sudo tee /etc/sysctl.d/99-swappiness.conf
echo 'vm.vfs_cache_pressure=50' | sudo tee -a /etc/sysctl.d/99-swappiness.conf
sudo sysctl -p /etc/sysctl.d/99-swappiness.conf

echo "=== 3. Docker daemon log limits ==="
sudo mkdir -p /etc/docker
sudo tee /etc/docker/daemon.json >/dev/null <<'JSON'
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "live-restore": true
}
JSON
sudo systemctl restart docker || true

echo "=== 4. Watchdog cron (restart stack if site down 2 min) ==="
sudo tee /usr/local/bin/gc-healthcheck.sh >/dev/null <<'SH'
#!/bin/bash
URL="https://gestionpro.it.com/"
COMPOSE_DIR="/home/ubuntu/gestion_commerciale"
LOG="/var/log/gc-healthcheck.log"

code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$URL")
if [ "$code" != "200" ] && [ "$code" != "302" ] && [ "$code" != "301" ]; then
    echo "[$(date -u)] site down (HTTP $code), restarting stack" >> "$LOG"
    cd "$COMPOSE_DIR" && sudo -u ubuntu docker compose -f docker-compose.prod.yml --env-file .env.prod restart >> "$LOG" 2>&1
fi
SH
sudo chmod +x /usr/local/bin/gc-healthcheck.sh

# Add cron entry (every 2 min) if not already present
if ! sudo crontab -l 2>/dev/null | grep -q gc-healthcheck; then
    (sudo crontab -l 2>/dev/null; echo '*/2 * * * * /usr/local/bin/gc-healthcheck.sh') | sudo crontab -
    echo "OK: watchdog cron installed"
else
    echo "SKIP: watchdog cron already installed"
fi

echo "=== DONE ==="
echo "Memory:"
free -h
echo "Swap:"
swapon --show
