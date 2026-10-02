FROM python:3.12-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

COPY mlops/requirements.txt ./requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Model dan metadata dibutuhkan registry & validasi.
COPY models/ ./models/
COPY mlops/ ./mlops/

WORKDIR /app/mlops

EXPOSE 8200

CMD ["uvicorn", "src.api:app", "--host", "0.0.0.0", "--port", "8200"]
