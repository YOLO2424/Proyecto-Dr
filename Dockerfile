FROM dunglas/frankenphp:latest

# Extensiones necesarias: PDO SQLite ya viene integrado en PHP.
# gd => generación de códigos QR · zip => backups · mbstring => PDF.
RUN install-php-extensions gd zip mbstring

WORKDIR /app

COPY . /app

RUN mkdir -p /app/storage/database /app/storage/documents /app/storage/backups \
    /app/storage/quarantine /app/storage/logs /app/storage/cache \
 && chmod +x /app/bin/migrate.php

ENV APP_DEBUG=0

EXPOSE 8085

# php-server de FrankenPHP escuchando en todas las interfaces.
CMD ["frankenphp", "php-server", "--listen=:8085", "--root=/app/public"]