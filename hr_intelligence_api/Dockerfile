# HR Intelligence API (FastAPI) — prediksi model ML.
FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends build-essential \
    && rm -rf /var/lib/apt/lists/*

# Konteks build = root repo (lihat docker-compose.yml). requirements.txt memakai
# `-r ../requirements/base.txt`, jadi base ikut disalin ke path relatif yang sama.
COPY requirements/ ../requirements/
COPY hr_intelligence_api/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt \
    && apt-get purge -y --auto-remove build-essential

COPY hr_intelligence_api/ ./

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
