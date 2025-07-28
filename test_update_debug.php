<?php
/**
 * Script de depuración para verificar actualizaciones de usuarios y teléfonos
 * Ejecutar desde la terminal: php test_update_debug.php
 */

require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

use App\Models\User;
use App\Models\TelefonoCliente;
use App\Models\TelefonoAdministrador;
use App\Models\TelefonoEntrenador;

echo "=== SCRIPT DE DEPURACIÓN DE ACTUALIZACIONES ===\n\n";

// 1. Verificar conexión a la base de datos
try {
    DB::connection()->getPdo();
    echo "✅ Conexión a la base de datos: OK\n";
    echo "Base de datos: " . DB::connection()->getDatabaseName() . "\n\n";
} catch (Exception $e) {
    echo "❌ Error de conexión a la base de datos: " . $e->getMessage() . "\n";
    exit;
}

// 2. Verificar usuarios existentes
echo "=== USUARIOS EN LA BASE DE DATOS ===\n";
$users = User::with(['admin.telefonos', 'cliente.telefonos', 'entrenador.telefonos'])->get();

foreach ($users as $user) {
    echo "ID: {$user->idUsuario} | Email: {$user->email} | Rol: {$user->idRol}\n";
    
    // Mostrar teléfonos según el rol
    switch ($user->idRol) {
        case 1: // Admin
            if ($user->admin) {
                $telefonos = $user->admin->telefonos;
                echo "  Admin ID: {$user->admin->idAdmin}\n";
                echo "  Teléfonos: " . $telefonos->count() . "\n";
                foreach ($telefonos as $tel) {
                    echo "    - {$tel->telefono} ({$tel->tipoTel})\n";
                }
            }
            break;
        case 2: // Cliente
            if ($user->cliente) {
                $telefonos = $user->cliente->telefonos;
                echo "  Cliente ID: {$user->cliente->idCliente}\n";
                echo "  Teléfonos: " . $telefonos->count() . "\n";
                foreach ($telefonos as $tel) {
                    echo "    - {$tel->telefono} ({$tel->tipoTel})\n";
                }
            }
            break;
        case 3: // Entrenador
            if ($user->entrenador) {
                $telefonos = $user->entrenador->telefonos;
                echo "  Entrenador ID: {$user->entrenador->idEntrenador}\n";
                echo "  Teléfonos: " . $telefonos->count() . "\n";
                foreach ($telefonos as $tel) {
                    echo "    - {$tel->telefono} ({$tel->tipoTel})\n";
                }
            }
            break;
    }
    echo "\n";
}

// 3. Función para simular actualización
function testUserUpdate($email, $newPhone) {
    echo "=== SIMULANDO ACTUALIZACIÓN DE USUARIO ===\n";
    echo "Email: $email\n";
    echo "Nuevo teléfono: $newPhone\n\n";
    
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "❌ Usuario no encontrado\n";
        return;
    }
    
    echo "Usuario encontrado: {$user->nombre} {$user->apellido}\n";
    echo "Rol actual: {$user->idRol}\n";
    
    // Verificar si tiene registro específico de rol
    switch ($user->idRol) {
        case 1: // Admin
            $admin = $user->admin;
            if ($admin) {
                echo "✅ Registro de admin existe (ID: {$admin->idAdmin})\n";
                
                // Limpiar teléfonos existentes
                $deletedCount = TelefonoAdministrador::where('idAdmin', $admin->idAdmin)->delete();
                echo "Teléfonos eliminados: $deletedCount\n";
                
                // Crear nuevo teléfono
                $newTel = TelefonoAdministrador::create([
                    'idAdmin' => $admin->idAdmin,
                    'telefono' => $newPhone,
                    'tipoTel' => 'celular'
                ]);
                echo "✅ Nuevo teléfono creado (ID: {$newTel->idTelefonoAdmin})\n";
                
                // Verificar en la base de datos
                $telefonosDB = TelefonoAdministrador::where('idAdmin', $admin->idAdmin)->get();
                echo "Teléfonos en BD después de la actualización: " . $telefonosDB->count() . "\n";
                foreach ($telefonosDB as $tel) {
                    echo "  - {$tel->telefono} ({$tel->tipoTel})\n";
                }
            } else {
                echo "❌ No tiene registro de admin\n";
            }
            break;
            
        case 2: // Cliente
            $cliente = $user->cliente;
            if ($cliente) {
                echo "✅ Registro de cliente existe (ID: {$cliente->idCliente})\n";
                
                // Limpiar teléfonos existentes
                $deletedCount = TelefonoCliente::where('idCliente', $cliente->idCliente)->delete();
                echo "Teléfonos eliminados: $deletedCount\n";
                
                // Crear nuevo teléfono
                $newTel = TelefonoCliente::create([
                    'idCliente' => $cliente->idCliente,
                    'telefono' => $newPhone,
                    'tipoTel' => 'celular'
                ]);
                echo "✅ Nuevo teléfono creado (ID: {$newTel->idTelefonoCliente})\n";
                
                // Verificar en la base de datos
                $telefonosDB = TelefonoCliente::where('idCliente', $cliente->idCliente)->get();
                echo "Teléfonos en BD después de la actualización: " . $telefonosDB->count() . "\n";
                foreach ($telefonosDB as $tel) {
                    echo "  - {$tel->telefono} ({$tel->tipoTel})\n";
                }
            } else {
                echo "❌ No tiene registro de cliente\n";
            }
            break;
            
        case 3: // Entrenador
            $entrenador = $user->entrenador;
            if ($entrenador) {
                echo "✅ Registro de entrenador existe (ID: {$entrenador->idEntrenador})\n";
                
                // Limpiar teléfonos existentes
                $deletedCount = TelefonoEntrenador::where('idEntrenador', $entrenador->idEntrenador)->delete();
                echo "Teléfonos eliminados: $deletedCount\n";
                
                // Crear nuevo teléfono
                $newTel = TelefonoEntrenador::create([
                    'idEntrenador' => $entrenador->idEntrenador,
                    'telefono' => $newPhone,
                    'tipoTel' => 'celular'
                ]);
                echo "✅ Nuevo teléfono creado (ID: {$newTel->idTelefonoEntrenador})\n";
                
                // Verificar en la base de datos
                $telefonosDB = TelefonoEntrenador::where('idEntrenador', $entrenador->idEntrenador)->get();
                echo "Teléfonos en BD después de la actualización: " . $telefonosDB->count() . "\n";
                foreach ($telefonosDB as $tel) {
                    echo "  - {$tel->telefono} ({$tel->tipoTel})\n";
                }
            } else {
                echo "❌ No tiene registro de entrenador\n";
            }
            break;
    }
}

// 4. Si hay usuarios, probar con el primero
if ($users->count() > 0) {
    $testUser = $users->first();
    $randomPhone = '99' . rand(100000, 999999);
    testUserUpdate($testUser->email, $randomPhone);
}

echo "\n=== FIN DEL SCRIPT DE DEPURACIÓN ===\n";
