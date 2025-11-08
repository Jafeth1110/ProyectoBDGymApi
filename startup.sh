#!/bin/bash

# Copiar el archivo .env si no existe
if [ ! -f /home/site/wwwroot/.env ]; then
    echo ".env no encontrado"
fi

# Limpiar y optimizar Laravel
php /home/site/wwwroot/artisan config:clear
php /home/site/wwwroot/artisan cache:clear
php /home/site/wwwroot/artisan route:clear
php /home/site/wwwroot/artisan view:clear

# Optimizar para producción
php /home/site/wwwroot/artisan config:cache
php /home/site/wwwroot/artisan route:cache

# Asegurar permisos
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache

# Iniciar PHP-FPM
php-fpm
