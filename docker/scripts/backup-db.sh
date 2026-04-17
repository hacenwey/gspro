#!/usr/bin/env bash
# Daily DB backup. Designed to be run from host cron:
#   0 3 * * * /home/ubuntu/gestion_commerciale/docker/scripts/backup-db.sh >> /var/log/gc-backup.log 2>&1

set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/gestion_commerciale}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
FILE="$BACKUP_DIR/gc-$STAMP.sql.gz"

mkdir -p "$BACKUP_DIR"

cd "$APP_DIR"
set -a; . ./.env.prod; set +a

echo "==> mysqldump --> $FILE"
docker compose -f docker-compose.prod.yml exec -T db \
    mysqldump \
        --all-databases \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        --events \
        -uroot -p"$MYSQL_ROOT_PASSWORD" \
    | gzip > "$FILE"

echo "==> Rotating local backups older than $RETENTION_DAYS days"
find "$BACKUP_DIR" -type f -name 'gc-*.sql.gz' -mtime "+$RETENTION_DAYS" -delete

if [ -n "${BACKUP_S3_BUCKET:-}" ]; then
    echo "==> Uploading to s3://$BACKUP_S3_BUCKET/"
    ENDPOINT_FLAG=""
    if [ -n "${BACKUP_S3_ENDPOINT:-}" ]; then
        ENDPOINT_FLAG="--endpoint-url $BACKUP_S3_ENDPOINT"
    fi
    docker run --rm \
        -e AWS_ACCESS_KEY_ID \
        -e AWS_SECRET_ACCESS_KEY \
        -e AWS_DEFAULT_REGION \
        -v "$BACKUP_DIR":/b \
        amazon/aws-cli s3 cp $ENDPOINT_FLAG "/b/$(basename "$FILE")" "s3://$BACKUP_S3_BUCKET/"
fi

echo "==> Done."
