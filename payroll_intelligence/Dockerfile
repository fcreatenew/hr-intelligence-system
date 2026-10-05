# Payroll Intelligence (FastAPI) — analitik & skor anomali payroll.
FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# Konteks build = root repo (lihat docker-compose.yml). Kode berjalan di
# /app = folder modul, sehingga REPO_ROOT = "/" dan data ter-mount di /data.
COPY requirements/ ../requirements/
COPY payroll_intelligence/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY payroll_intelligence/ ./

EXPOSE 8300

CMD ["uvicorn", "src.api:app", "--host", "0.0.0.0", "--port", "8300"]
