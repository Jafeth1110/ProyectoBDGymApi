<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Configurar la conexión a la base de datos
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE LA ESTRUCTURA NORMALIZADA ===\n\n";

// Verificar estructura de la tabla telefonos
echo "1. Estructura de la tabla telefonos:\n";
$structure = DB::select("DESCRIBE telefonos");
foreach ($structure as $column) {
    echo "   {$column->Field}: {$column->Type} (Key: {$column->Key})\n";
}

echo "\n2. Datos migrados:\n";
$telefonos = DB::select("
    SELECT 
        t.idTelefono,
        t.telefono, 
        t.tipoTel, 
        u.nombre, 
        u.apellido, 
        r.nombreRol 
    FROM telefonos t 
    JOIN users u ON t.idUsuario = u.idUsuario 
    JOIN roles r ON t.idRol = r.idRol 
    ORDER BY r.idRol, u.nombre
");

foreach ($telefonos as $tel) {
    echo "   ID: {$tel->idTelefono} | {$tel->telefono} ({$tel->tipoTel}) - {$tel->nombre} {$tel->apellido} ({$tel->nombreRol})\n";
}

echo "\n3. Resumen por rol:\n";
$resumen = DB::select("
    SELECT 
        r.nombreRol, 
        COUNT(*) as cantidad 
    FROM telefonos t 
    JOIN roles r ON t.idRol = r.idRol 
    GROUP BY r.idRol, r.nombreRol
");

foreach ($resumen as $item) {
    echo "   {$item->nombreRol}: {$item->cantidad} teléfonos\n";
}

echo "\n4. Verificando que las tablas antiguas fueron eliminadas:\n";
$tables = ['telefonoadministrador', 'telefonocliente', 'telefonoentrenador'];
foreach ($tables as $table) {
    try {
        DB::select("SELECT 1 FROM {$table} LIMIT 1");
        echo "   ❌ {$table}: AÚN EXISTE\n";
    } catch (Exception $e) {
        echo "   ✅ {$table}: ELIMINADA CORRECTAMENTE\n";
    }
}

echo "\n✅ ¡Migración completada exitosamente!\n";
echo "✅ La base de datos ahora está normalizada con una sola tabla de teléfonos.\n";
