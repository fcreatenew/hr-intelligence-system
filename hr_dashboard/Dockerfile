# Build statis hr_dashboard (React + Vite) lalu sajikan dengan nginx.
FROM node:20-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


FROM nginx:alpine AS runtime

COPY --from=build /app/dist /usr/share/nginx/html

# SPA fallback: route klien (mis. /payroll) harus dilayani index.html.
RUN printf 'server {\n\
    listen 80;\n\
    server_name _;\n\
    root /usr/share/nginx/html;\n\
    index index.html;\n\
\n\
    location / {\n\
        try_files $uri $uri/ /index.html;\n\
    }\n\
}\n' > /etc/nginx/conf.d/default.conf

EXPOSE 80
