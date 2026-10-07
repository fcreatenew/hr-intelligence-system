#!/bin/sh
# Jalankan cron di foreground dengan crontab proyek.
set -e

mkdir -p /var/log/mlops
touch /var/log/mlops/cron.log

crontab /etc/mlops/crontab

echo "[mlops-cron] jadwal aktif:"
crontab -l

exec cron -f
