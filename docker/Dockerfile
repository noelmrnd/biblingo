FROM php:8.3-fpm-alpine

# Instalar Nginx, Supervisor y la extensión pdo_mysql
RUN apk add --no-cache nginx supervisor && \
    docker-php-ext-install pdo pdo_mysql

# Crear directorios para Nginx y Supervisor
RUN mkdir -p /run/nginx /var/log/supervisor

# Copiar configuraciones
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar las aplicaciones
COPY web /var/www/web
COPY app/dist /var/www/app
COPY api /var/www/api

WORKDIR /var/www

# Exponer los 3 puertos: Web (8082), App SPA (8083), API PHP (8084)
EXPOSE 8082 8083 8084

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
