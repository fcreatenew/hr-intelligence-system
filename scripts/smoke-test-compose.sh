#!/usr/bin/env bash
set -euo pipefail

# Lightweight, safe check for a fresh checkout. This validates the Compose
# topology without building images, starting services, or requiring secrets.
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

command -v docker >/dev/null 2>&1 || {
  echo "ERROR: Docker CLI is required. Install Docker Desktop/Engine first." >&2
  exit 1
}
docker compose version >/dev/null

# Use explicit dummy values only for config rendering. They are not credentials
# and are never used to start containers.
HR_API_KEY="smoke-test-placeholder" APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" \
  docker compose config --quiet

echo "PASS: docker compose configuration is valid."
echo "NOTE: This smoke check does not prove containers start or services are healthy."
