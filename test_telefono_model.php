<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Probando el modelo Telefono:\n";
    echo "============================\n";
    
    // Obtener todos los teléfonos con sus relaciones
    $telefonos = App\Models\Telefono::with(['user', 'rol'])->get();
    
    foreach ($telefonos as $telefono) {
        echo "ID: {$telefono->idTelefono}\n";
        echo "Teléfono: {$telefono->telefono} ({$telefono->tipoTel})\n";
        echo "Usuario: {$telefono->user->nombre} {$telefono->user->apellido}\n";
        echo "Email: {$telefono->user->email}\n";
        echo "Rol: {$telefono->rol->nombreRol}\n";
        echo "-----------------------------------\n";
    }
    
    echo "\nProbando relación User -> telefonos:\n";
    echo "====================================\n";
    
    // Obtener un usuario con sus teléfonos
    $user = App\Models\User::with('telefonos')->where('email', 'jafethespinoz@gmail.com')->first();
    
    if ($user) {
        echo "Usuario: {$user->nombre} {$user->apellido}\n";
        echo "Teléfonos:\n";
        foreach ($user->telefonos as $telefono) {
            echo "- {$telefono->telefono} ({$telefono->tipoTel})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
