FROM python:3.12-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

COPY hr_intelligence_api/requirements.txt ./requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY models/ ./models/
COPY hr_intelligence_api/ ./hr_intelligence_api/

WORKDIR /app/hr_intelligence_api

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
