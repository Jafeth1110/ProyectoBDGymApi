<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class JwtAuth {
    private $key;

    function __construct()
    {
        // Llave secreta de la aplicación
        $this->key = "aswqdfewqeddafe23ewresa";
    }

    public function getToken($email, $password)
    {
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            if (empty($user->idUsuario)) {
                return [
                    'status' => 500,
                    'message' => 'Error: El usuario no tiene ID válido'
                ];
            }

            $token = [
                'sub' => $user->idUsuario,   // Mejor usar 'sub' en vez de 'iss'
                'email' => $user->email,
                'nombre' => $user->nombre,
                'rol' => $user->rol,
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60) // 24 horas
            ];

            return JWT::encode($token, $this->key, 'HS256');
        }

        return [
            'status' => 401,
            'message' => 'Datos de autenticación incorrectos'
        ];
    }

    public function checkToken($jwt, $getId = false)
    {
        $authFlag = false;
        $decoded = null;

        if (isset($jwt)) {
            try {
                $decoded = JWT::decode($jwt, new Key($this->key, 'HS256'));
                \Log::info("Token decodificado exitosamente: " . json_encode($decoded));
            } catch (\DomainException $ex) {
                \Log::error("DomainException en JWT: " . $ex->getMessage());
            } catch (ExpiredException $ex) {
                \Log::error("Token expirado: " . $ex->getMessage());
            } catch (\Exception $ex) {
                \Log::error("Error general en JWT: " . $ex->getMessage());
            }

            if (!empty($decoded) && is_object($decoded) && isset($decoded->email)) {
                $authFlag = true;
            }

            if ($getId && $authFlag) {
                return $decoded;
            }
        } else {
            \Log::warning("No se recibió token JWT");
        }

        return $authFlag;
    }
}
