# --- Etapa de build: resuelve el autoloader de Composer en una imagen aparte, para
# que el binario de composer y su cache nunca lleguen a la imagen final ---
FROM composer:2 AS composer-build
WORKDIR /app
COPY api/ ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# --- Etapa de build: genera el HTML estático de la landing page con Astro ---
FROM node:22-slim AS web-build
RUN corepack enable
WORKDIR /web
COPY web/package.json web/pnpm-lock.yaml web/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY web/ ./
RUN pnpm build

FROM php:8.3-fpm-alpine AS base

# Instalar Nginx, Supervisor y la extensión pdo_mysql; crear directorios necesarios
# en una sola capa para minimizar el tamaño de la imagen
RUN apk add --no-cache nginx supervisor && \
    docker-php-ext-install pdo pdo_mysql && \
    echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    mkdir -p /run/nginx /var/log/supervisor /var/www/web /var/www/app /var/www/api

# Copiar configuraciones base de Nginx y Supervisor
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www

# Exponer los 3 puertos: Web (8082), App SPA (8083), API PHP (8084)
EXPOSE 8082 8083 8084

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# --- Entorno de desarrollo (sin copiar archivos del host, se leen del sistema de archivos vía volúmenes) ---
FROM base AS dev

# --- Entorno de producción (copia los archivos empaquetados para la imagen autónoma) ---
FROM base AS prod
COPY --from=web-build /web/dist /var/www/web
COPY app/dist /var/www/app
COPY --from=composer-build /app /var/www/api

