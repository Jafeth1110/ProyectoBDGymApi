<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JwtAuth;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
{
    $token = $request->header('Authorization');
    \Log::info("Token recibido: " . $token); // Verifica en logs/laravel.log
    
    $jwtAuth = new JwtAuth();

    if ($token) {
        $token = str_replace('Bearer ', '', $token);
        \Log::info("Token procesado: " . $token);
    }

    $check = $jwtAuth->checkToken($token);
    \Log::info("Resultado de checkToken: " . ($check ? 'true' : 'false'));

    if (!$check) {
        return response()->json([
            'status' => 401,
            'message' => 'Acceso denegado. Token inválido o ausente.',
            'debug' => [
                'token_received' => $request->header('Authorization'),
                'token_processed' => $token ?? 'null'
            ]
        ], 401);
    }

    return $next($request);
}

}
