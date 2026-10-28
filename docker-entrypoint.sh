#!/bin/sh
set -e
# Render inyecta $PORT; Apache debe escuchar ahí
sed -ri "s/Listen 80/Listen ${PORT:-80}/; s/\*:80/*:${PORT:-80}/" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf
php artisan migrate --force
# Datos DEMO- solo cuando se pide (primer despliegue); repetirlo duplicaría registros
[ "$RUN_SEED" = "1" ] && php artisan db:seed --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
exec apache2-foreground
