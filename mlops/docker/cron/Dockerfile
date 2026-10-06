# Image penjadwal MLOps: memakai image API yang sama (berisi model + src),
# menambahkan cron, lalu menjalankan tugas terjadwal dari crontab.
FROM hr-intelligence-mlops:latest

USER root

RUN apt-get update \
    && apt-get install -y --no-install-recommends cron tzdata \
    && rm -rf /var/lib/apt/lists/*

COPY mlops/docker/cron/crontab /etc/mlops/crontab
COPY mlops/docker/cron/entrypoint.sh /usr/local/bin/mlops-cron-entrypoint

RUN chmod +x /usr/local/bin/mlops-cron-entrypoint

ENTRYPOINT ["/usr/local/bin/mlops-cron-entrypoint"]
