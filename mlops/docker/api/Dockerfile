FROM python:3.14-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# requirements.txt memakai `-r ../requirements/base.txt`; base ikut disalin
# dengan struktur relatif yang sama.
COPY requirements/ ./requirements/
COPY mlops/requirements.txt ./mlops/requirements.txt
RUN pip install --no-cache-dir -r mlops/requirements.txt

# Model dan metadata dibutuhkan registry & validasi.
COPY models/ ./models/
COPY mlops/ ./mlops/

WORKDIR /app/mlops

EXPOSE 8200

CMD ["uvicorn", "src.api:app", "--host", "0.0.0.0", "--port", "8200"]
