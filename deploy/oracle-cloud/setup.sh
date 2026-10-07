#!/usr/bin/env bash
# Setup demo HR Intelligence System di VM Oracle Cloud Always Free (Ubuntu).
#
# Menyiapkan Docker + Compose, meng-clone repo, membuat .env (secret acak),
# lalu menjalankan seluruh stack. Jalankan sebagai user biasa yang punya sudo.
#
# Pemakaian:
#   REPO_URL=https://github.com/<user>/<repo>.git bash deploy/oracle-cloud/setup.sh
#
# Setelah selesai, dashboard ada di http://<IP-VM>:8081.
# Untuk HTTPS + domain, lihat Caddyfile di folder ini.

set -euo pipefail

REPO_URL="${REPO_URL:-}"
TARGET_DIR="${TARGET_DIR:-$HOME/hr-intelligence-system}"

if [ -z "$REPO_URL" ]; then
	echo "REPO_URL belum diset. Contoh:"
	echo "  REPO_URL=https://github.com/<user>/<repo>.git bash deploy/oracle-cloud/setup.sh"
	exit 1
fi

echo "==> Memasang Docker & Compose plugin (bila belum ada)"
if ! command -v docker >/dev/null 2>&1; then
	sudo apt-get update
	sudo apt-get install -y ca-certificates curl git
	sudo install -m 0755 -d /etc/apt/keyrings
	curl -fsSL https://download.docker.com/linux/ubuntu/gpg |
		sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
	sudo chmod a+r /etc/apt/keyrings/docker.gpg
	echo \
		"deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" |
		sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
	sudo apt-get update
	sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
	sudo usermod -aG docker "$USER" || true
fi

echo "==> Mengambil source code"
if [ ! -d "$TARGET_DIR/.git" ]; then
	git clone "$REPO_URL" "$TARGET_DIR"
fi
cd "$TARGET_DIR"

echo "==> Membuat .env (secret acak) bila belum ada"
if [ ! -f .env ]; then
	HR_API_KEY="$(python3 -c 'import secrets; print(secrets.token_urlsafe(32))')"
	APP_KEY="base64:$(python3 -c 'import os,base64; print(base64.b64encode(os.urandom(32)).decode())')"
	{
		echo "HR_API_KEY=${HR_API_KEY}"
		echo "APP_KEY=${APP_KEY}"
		echo "MODEL_VERSION=v2_mitigated"
	} >.env
	echo "    .env dibuat. Simpan nilai HR_API_KEY untuk login dashboard."
fi

echo "==> Menjalankan stack"
sudo docker compose \
	-f docker-compose.yml \
	-f deploy/oracle-cloud/docker-compose.override.yml \
	up -d --build

echo
echo "Selesai. Dashboard: http://<IP-VM>:8081"
echo "Health API      : http://<IP-VM>:8000/health (internal; lihat Caddyfile untuk publik)"
