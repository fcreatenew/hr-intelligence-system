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

COPY --from=build /app/dist /usr/share/nginx/html

# SPA fallback + proxy /api & /payroll-api (lihat nginx.conf).
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
