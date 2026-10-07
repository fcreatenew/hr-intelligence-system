# Build statis hr_dashboard (React + Vite) lalu sajikan dengan nginx.
FROM node:24-alpine AS build

# Vite membakar env saat build. Default `demo` agar image tetap bisa dibuka
# tanpa backend; set `live` lewat build arg saat deploy.
ARG VITE_DATA_MODE=demo
ARG VITE_HR_API_BASE=/api
ARG VITE_PAYROLL_API_BASE=/payroll-api
ENV VITE_DATA_MODE=$VITE_DATA_MODE \
    VITE_HR_API_BASE=$VITE_HR_API_BASE \
    VITE_PAYROLL_API_BASE=$VITE_PAYROLL_API_BASE

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


FROM nginx:alpine AS runtime

# gettext menyediakan `envsubst`; image nginx resmi menjalankan
# /docker-entrypoint.d/20-envsubst-on-templates.sh yang mengisi
# /etc/nginx/templates/*.template -> /etc/nginx/conf.d/default.conf.
RUN apk add --no-cache gettext

COPY --from=build /app/dist /usr/share/nginx/html

# SPA fallback + proxy /api & /payroll-api. Nilai upstream diisi saat start
# dari env, sehingga nama host tidak lagi hardcoded (lihat nginx.conf.template).
COPY nginx.conf.template /etc/nginx/templates/default.conf.template

# Default untuk docker-compose lokal; di PaaS di-override lewat env
# HR_API_UPSTREAM / PAYROLL_API_UPSTREAM.
ENV HR_API_UPSTREAM=http://hr-api:8000 \
    PAYROLL_API_UPSTREAM=http://payroll-api:8300

EXPOSE 80
