<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Verificar si la tabla telefonos existe
    $telefonosExists = count(DB::select("SHOW TABLES LIKE 'telefonos'")) > 0;
    echo "Tabla telefonos existe: " . ($telefonosExists ? "SÍ" : "NO") . "\n";
    
    if ($telefonosExists) {
        // Contar registros en telefonos
        $countTelefonos = DB::table('telefonos')->count();
        echo "Registros en telefonos: $countTelefonos\n";
        
        // Mostrar estructura
        $structure = DB::select("DESCRIBE telefonos");
        echo "Estructura de telefonos:\n";
        foreach ($structure as $column) {
            echo "- {$column->Field}: {$column->Type}\n";
        }
    }
    
    // Verificar tablas antiguas
    $oldTables = ['telefonoadministrador', 'telefonocliente', 'telefonoentrenador'];
    foreach ($oldTables as $table) {
        $exists = count(DB::select("SHOW TABLES LIKE '$table'")) > 0;
        echo "Tabla $table existe: " . ($exists ? "SÍ" : "NO");
        if ($exists) {
            $count = DB::table($table)->count();
            echo " (registros: $count)";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
