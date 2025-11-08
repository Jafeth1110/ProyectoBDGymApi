#!/bin/bash
set -e  # Exit on error

echo "========================================="
echo "INICIANDO CONFIGURACION LARAVEL EN AZURE"
echo "========================================="
echo "Directorio actual: $(pwd)"
echo "Usuario: $(whoami)"
echo "Fecha: $(date)"

# Asegurar permisos correctos
echo "Configurando permisos..."
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
    echo "===== NGINX SETUP ====="
    echo "Archivo default encontrado. Contenido:"
    cat /home/site/wwwroot/default | head -40
    
    echo -e "\nCopiando a /etc/nginx/sites-available..."
    cp -v /home/site/wwwroot/default /etc/nginx/sites-available/default
    
    echo "Copiando a /etc/nginx/sites-enabled..."
    cp -v /home/site/wwwroot/default /etc/nginx/sites-enabled/default
    
    echo -e "\nProbando configuración nginx..."
    nginx -t
    
    echo -e "\nRecargando nginx..."
    nginx -s reload || echo "WARNING: No se pudo recargar nginx"
    
    echo -e "\nContenido ACTUAL de /etc/nginx/sites-available/default:"
    cat /etc/nginx/sites-available/default | head -40
else
    echo "WARNING: Archivo default NO encontrado"
    echo "Creando configuración nginx on-the-fly..."
    
    cat > /etc/nginx/sites-available/default << 'NGINXCONF'
server {
    listen 8080;
    listen [::]:8080;
    root /home/site/wwwroot;
    index index.php index.html;
    server_name _;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINXCONF
    
    cp /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
    echo "Configuración nginx creada y aplicada"
    nginx -t && nginx -s reload || true
fi

echo "Configuración completada. Laravel listo para servir."