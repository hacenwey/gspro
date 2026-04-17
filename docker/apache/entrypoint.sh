#!/bin/sh
set -e

PORT="${PORT:-80}"
DB_USER_NAME="${DB_USER:-gestion}"
DB_USER_PASS="${DB_PASS:-gestion_secret}"
DB_MAIN_NAME="${DB_NAME:-gestion_commerciale}"
MASTER_DB="gestionpro_master"
DATA_DIR="/var/lib/mysql"
APP_DIR="/var/www/html/gestion_commerciale"

echo "[entrypoint] PORT=${PORT}"

# --- Apache port rewrite ---
sed -ri "s!^Listen 80\$!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \\*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf
apache2ctl -t

# --- Embedded MariaDB (enable only when START_DB=1, e.g. on Render) ---
if [ "${START_DB:-0}" != "1" ]; then
    echo "[entrypoint] embedded DB disabled (START_DB!=1); launching apache"
    exec apache2-foreground
fi

mkdir -p "${DATA_DIR}" /var/run/mysqld
chown -R mysql:mysql "${DATA_DIR}" /var/run/mysqld

if [ ! -d "${DATA_DIR}/mysql" ]; then
    echo "[entrypoint] initializing MariaDB data dir"
    mariadb-install-db --user=mysql --datadir="${DATA_DIR}" --auth-root-authentication-method=normal >/dev/null
    FIRST_RUN=1
else
    FIRST_RUN=0
fi

echo "[entrypoint] starting mariadbd in background"
mariadbd --user=mysql --datadir="${DATA_DIR}" --bind-address=127.0.0.1 --skip-log-bin &
MYSQL_PID=$!

# Wait for socket
for i in $(seq 1 30); do
    if mariadb -uroot -e 'SELECT 1' >/dev/null 2>&1; then
        break
    fi
    sleep 1
done

if ! mariadb -uroot -e 'SELECT 1' >/dev/null 2>&1; then
    echo "[entrypoint] MariaDB failed to start"
    exit 1
fi

if [ "${FIRST_RUN}" = "1" ]; then
    echo "[entrypoint] provisioning app user and schemas"
    mariadb -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${MASTER_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`${DB_MAIN_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER_NAME}'@'%' IDENTIFIED BY '${DB_USER_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER_NAME}'@'localhost' IDENTIFIED BY '${DB_USER_PASS}';
GRANT ALL PRIVILEGES ON \`gestionpro\\_%\`.* TO '${DB_USER_NAME}'@'%';
GRANT ALL PRIVILEGES ON \`gestionpro\\_%\`.* TO '${DB_USER_NAME}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_MAIN_NAME}\`.* TO '${DB_USER_NAME}'@'%';
GRANT ALL PRIVILEGES ON \`${DB_MAIN_NAME}\`.* TO '${DB_USER_NAME}'@'localhost';
GRANT CREATE, DROP, ALTER, REFERENCES, INDEX ON *.* TO '${DB_USER_NAME}'@'%';
GRANT CREATE, DROP, ALTER, REFERENCES, INDEX ON *.* TO '${DB_USER_NAME}'@'localhost';
FLUSH PRIVILEGES;
SQL

    if [ -f "${APP_DIR}/database/master_schema.sql" ]; then
        echo "[entrypoint] loading master schema"
        mariadb -uroot "${MASTER_DB}" < "${APP_DIR}/database/master_schema.sql" || true
    fi
fi

# --- Launch Apache in foreground (PID 1 replacement) ---
echo "[entrypoint] launching apache"
exec apache2-foreground
