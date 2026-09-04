FROM php:8.3-fpm-alpine AS base

# Instalar Nginx, Supervisor, curl y la extensión pdo_mysql
RUN apk add --no-cache nginx supervisor curl && \
    docker-php-ext-install pdo pdo_mysql && \
    echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Crear directorios para Nginx, Supervisor y las aplicaciones
RUN mkdir -p /run/nginx /var/log/supervisor /var/www/web /var/www/app /var/www/api

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
COPY web /var/www/web
COPY app/dist /var/www/app
COPY api /var/www/api

