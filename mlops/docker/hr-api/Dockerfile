FROM python:3.13-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# requirements.txt memakai `-r ../requirements/base.txt`; base ikut disalin
# dengan struktur relatif yang sama.
COPY requirements/ ./requirements/
COPY hr_intelligence_api/requirements.txt ./hr_intelligence_api/requirements.txt
RUN pip install --no-cache-dir -r hr_intelligence_api/requirements.txt

COPY models/ ./models/
COPY hr_intelligence_api/ ./hr_intelligence_api/

WORKDIR /app/hr_intelligence_api

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
