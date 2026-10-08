# Dockerfile tipis untuk Hugging Face Space.
#
# Image dibangun dari repo aplikasi (butuh token GitHub read untuk repo
# privat), sehingga Space tidak menyimpan salinan kode. Dockerfile asli
# (hr_intelligence_api/Dockerfile) memakai build context = root repo; di sini
# context = repo aplikasi yang dikloning, jadi build-arg-nya berbeda.
#
# Space mengekspos satu port (app_port di README). Kita pakai 7860.

FROM python:3.14-slim

ARG APP_REPO=fcreatenew/hr-intelligence-system
ARG APP_REF=main
ARG GITHUB_TOKEN

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    HR_NUM_THREADS=1 \
    HF_HOME=/home/user/.cache/huggingface

RUN apt-get update \
    && apt-get install -y --no-install-recommends git build-essential \
    && rm -rf /var/lib/apt/lists/*

# Clone repo aplikasi ke /src (repo privat: pakai token read).
RUN git clone --depth 1 --branch "$APP_REF" \
        "https://${GITHUB_TOKEN}@github.com/${APP_REPO}.git" /src \
    && rm -rf /src/.git

WORKDIR /src/hr_intelligence_api

# requirements memakai `-r ../requirements/base.txt` (relatif root repo).
RUN pip install --no-cache-dir -r requirements.txt \
    && apt-get purge -y --auto-remove build-essential

EXPOSE 7860

# Port 7860 agar cocok dengan app_port Space.
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "7860"]
