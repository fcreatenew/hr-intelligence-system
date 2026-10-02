# Payroll Intelligence (FastAPI) — analitik & skor anomali payroll.
FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8300

CMD ["uvicorn", "src.api:app", "--host", "0.0.0.0", "--port", "8300"]
