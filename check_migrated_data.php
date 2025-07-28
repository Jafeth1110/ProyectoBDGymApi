<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Verificar los datos migrados
    $telefonos = DB::table('telefonos')
        ->join('users', 'telefonos.idUsuario', '=', 'users.idUsuario')
        ->join('roles', 'telefonos.idRol', '=', 'roles.idRol')
        ->select(
            'telefonos.*',
            'users.nombre',
            'users.apellido',
            'users.email',
            'roles.nombreRol'
        )
        ->get();
    
    echo "Datos migrados en la tabla telefonos:\n";
    echo "=====================================\n";
    
    foreach ($telefonos as $telefono) {
        echo "ID: {$telefono->idTelefono}\n";
        echo "Usuario: {$telefono->nombre} {$telefono->apellido} ({$telefono->email})\n";
        echo "Teléfono: {$telefono->telefono} ({$telefono->tipoTel})\n";
        echo "Rol: {$telefono->nombreRol} (ID: {$telefono->idRol})\n";
        echo "Creado: {$telefono->created_at}\n";
        echo "-----------------------------------\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
