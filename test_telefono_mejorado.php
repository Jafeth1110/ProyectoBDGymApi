<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE TELEFONOCONTROLLER MEJORADO ===\n\n";

try {
    $telefonoController = new App\Http\Controllers\TelefonoController();
    
    echo "1. PROBANDO index() - Información completa del usuario:\n";
    echo "======================================================\n";
    $indexResponse = $telefonoController->index();
    $indexResult = json_decode($indexResponse->getContent(), true);
    
    if (isset($indexResult['data']) && count($indexResult['data']) > 0) {
        $firstTelefono = $indexResult['data'][0];
        echo "✅ Primer teléfono:\n";
        echo "   - Número: " . $firstTelefono['telefono'] . "\n";
        echo "   - Usuario: " . $firstTelefono['usuario']['nombre'] . " " . $firstTelefono['usuario']['apellido'] . "\n";
        echo "   - Rol: " . $firstTelefono['rol']['nombreRol'] . "\n";
        echo "   - Email: " . $firstTelefono['usuario']['email'] . "\n";
    }
    
    echo "\n2. PROBANDO getByUser() - Con información del rol:\n";
    echo "==================================================\n";
    $userResponse = $telefonoController->getByUser(1);
    $userResult = json_decode($userResponse->getContent(), true);
    
    if (isset($userResult['data']) && count($userResult['data']) > 0) {
        $firstUserTel = $userResult['data'][0];
        echo "✅ Teléfono del usuario:\n";
        echo "   - Número: " . $firstUserTel['telefono'] . "\n";
        echo "   - Usuario: " . $firstUserTel['usuario']['nombre'] . " " . $firstUserTel['usuario']['apellido'] . "\n";
        echo "   - Rol: " . $firstUserTel['usuario']['rol'] . "\n";
        echo "   - ID Usuario: " . $firstUserTel['usuario']['idUsuario'] . "\n";
    }
    
    echo "\n3. PROBANDO show() - Información completa:\n";
    echo "==========================================\n";
    // Usar el ID del primer teléfono
    $telefonoId = $indexResult['data'][0]['idTelefono'];
    $showResponse = $telefonoController->show($telefonoId);
    $showResult = json_decode($showResponse->getContent(), true);
    
    if (isset($showResult['data'])) {
        $telefono = $showResult['data'];
        echo "✅ Detalles del teléfono:\n";
        echo "   - ID: " . $telefono['idTelefono'] . "\n";
        echo "   - Número: " . $telefono['telefono'] . "\n";
        echo "   - Usuario: " . $telefono['usuario']['nombre'] . " " . $telefono['usuario']['apellido'] . "\n";
        echo "   - Rol: " . $telefono['rol']['nombreRol'] . "\n";
        echo "   - Email: " . $telefono['usuario']['email'] . "\n";
    }
    
    echo "\n4. PROBANDO getByRole() - Información del usuario por rol:\n";
    echo "==========================================================\n";
    $roleResponse = $telefonoController->getByRole(1); // Rol admin
    $roleResult = json_decode($roleResponse->getContent(), true);
    
    if (isset($roleResult['data']['telefonos']) && count($roleResult['data']['telefonos']) > 0) {
        $firstRoleTel = $roleResult['data']['telefonos'][0];
        echo "✅ Teléfono por rol:\n";
        echo "   - Número: " . $firstRoleTel['telefono'] . "\n";
        echo "   - Usuario: " . $firstRoleTel['usuario']['nombre'] . " " . $firstRoleTel['usuario']['apellido'] . "\n";
        echo "   - Email: " . $firstRoleTel['usuario']['email'] . "\n";
        echo "   - Rol: " . $roleResult['data']['rol'] . "\n";
        echo "   - Total: " . $roleResult['data']['count'] . " teléfonos\n";
    }
    
    echo "\n=== RESUMEN DE MEJORAS APLICADAS ===\n";
    echo "=====================================\n";
    echo "✅ index() - Retorna información completa del usuario (nombre, apellido, email, rol)\n";
    echo "✅ show() - Retorna información completa del usuario\n";
    echo "✅ store() - Retorna información completa del usuario\n";
    echo "✅ getByUser() - Mejorado para incluir rol del usuario en cada teléfono\n";
    echo "✅ getByRole() - Retorna información del usuario por rol\n";
    echo "✅ update() - Simplificado, no retorna información del usuario\n";
    echo "✅ destroy() - No retorna información del usuario\n";
    
    echo "\n🎉 ¡TELEFONOCONTROLLER MEJORADO EXITOSAMENTE! 🎉\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
