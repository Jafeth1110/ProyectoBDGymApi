#!/bin/bash

echo "Iniciando configuración de Laravel en Azure..."

# Asegurar permisos correctos
chmod -R 775 /home/site/wwwroot/storage 2>/dev/null || true
chmod -R 775 /home/site/wwwroot/bootstrap/cache 2>/dev/null || true

# Limpiar cachés (ignorar errores si .env no está)
cd /home/site/wwwroot
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Optimizar para producción (solo si .env existe)
if [ -f /home/site/wwwroot/.env ]; then
    php artisan config:cache
    # NO cachear rutas en Azure - causa problemas con 404
    # php artisan route:cache
    echo "Cachés optimizados (sin route cache)"
else
    echo "WARNING: .env no encontrado. Configure las variables de entorno en Azure."
fi

# Copiar configuración de nginx si existe
if [ -f /home/site/wwwroot/default ]; then
    cp /home/site/wwwroot/default /etc/nginx/sites-available/default 2>/dev/null || true
    cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default 2>/dev/null || true
    echo "Configuración nginx copiada"
fi

echo "Configuración completada."

# Matar cualquier proceso nginx/php-fpm previo
pkill nginx 2>/dev/null || true
pkill php-fpm 2>/dev/null || true

# Iniciar servidor embebido de PHP (como artisan serve)
echo "Iniciando servidor PHP embebido en puerto 8080..."
cd /home/site/wwwroot
php -S 0.0.0.0:8080 -t /home/site/wwwroot index.php