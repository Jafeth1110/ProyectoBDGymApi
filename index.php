<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// Redirigir todo a public/index.php preservando la ruta
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Si la solicitud es para public/, redirigir sin ese prefijo
if (str_starts_with($uri, '/public')) {
    $uri = substr($uri, 7); // Remover '/public'
    $_SERVER['REQUEST_URI'] = $uri . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
}

// Cambiar al directorio public
chdir(__DIR__ . '/public');

// Actualizar variables de servidor para que Laravel detecte las rutas correctamente
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';

// Cargar el bootstrap de Laravel
require __DIR__ . '/public/index.php';
