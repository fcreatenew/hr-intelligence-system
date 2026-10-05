# AI HR Assistant (FastAPI) — asisten tanya-jawab kebijakan HR.
FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# Konteks build = root repo (lihat docker-compose.yml). requirements.txt memakai
# `-r ../requirements/base.txt`, jadi base ikut disalin ke path relatif yang sama.
COPY requirements/ ../requirements/
COPY ai_hr_assistant/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY ai_hr_assistant/ ./

EXPOSE 8100

CMD ["uvicorn", "src.main:app", "--host", "0.0.0.0", "--port", "8100"]
