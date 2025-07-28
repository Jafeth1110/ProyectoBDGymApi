<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Probando TelefonoController:\n";
echo "============================\n";

try {
    // Simular una petición al índice
    $controller = new App\Http\Controllers\TelefonoController();
    
    // Test del método index (listar todos los teléfonos)
    echo "1. Probando método index():\n";
    $request = new Illuminate\Http\Request();
    $response = $controller->index();
    $data = json_decode($response->getContent(), true);
    
    echo "Status: " . $data['status'] . "\n";
    echo "Message: " . $data['message'] . "\n";
    echo "Cantidad de teléfonos: " . count($data['data']) . "\n";
    
    if (!empty($data['data'])) {
        echo "Primer teléfono:\n";
        $primer = $data['data'][0];
        echo "- ID: " . $primer['idTelefono'] . "\n";
        echo "- Teléfono: " . $primer['telefono'] . "\n";
        echo "- Usuario: " . $primer['usuario']['nombre'] . " " . $primer['usuario']['apellido'] . "\n";
        echo "- Rol: " . $primer['rol']['nombreRol'] . "\n";
    }
    
    echo "\n2. Probando método getByRole() para admins (rol 1):\n";
    $response2 = $controller->getByRole(1);
    $data2 = json_decode($response2->getContent(), true);
    
    echo "Status: " . $data2['status'] . "\n";
    echo "Message: " . $data2['message'] . "\n";
    echo "Rol: " . $data2['data']['rol'] . "\n";
    echo "Cantidad: " . $data2['data']['count'] . "\n";
    
    echo "\n✅ TelefonoController funcionando correctamente!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
