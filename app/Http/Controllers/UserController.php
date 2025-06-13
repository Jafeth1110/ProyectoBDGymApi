<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Models\User;

class UserController extends Controller
{
    public function index() {
        $users = User::all();

        return response()->json([
            'status' => 200,
            'message' => 'Todos los usuarios :)',
            'data' => $users
        ]);
    }

    public function store(Request $request) {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'idUsuario' => 'required|alpha_num|unique:users',
                'nombre' => 'required|regex:/^[\pL\s]+$/u',
                'apellido' => 'required|regex:/^[\pL\s]+$/u',
                'cedula' => 'required|numeric|digits_between:8,12',
                'email' => 'required|email|unique:users',
                'password' => 'required|alpha_num|min:6',
                'rol' => 'required|in:admin,cliente,entrenador'

            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                $user = new User();
                $user->idUsuario = $data['idUsuario'];
                $user->nombre = $data['nombre'];
                $user->apellido = $data['apellido'];
                $user->cedula = $data['cedula'];
                $user->email = $data['email'];
                $user->password = hash('sha256', $data['password']);
                $user->rol = $data['rol'];
                $user->save();

                $response = [
                    'status' => 201,
                    'message' => 'Usuario registrado :)',
                    'user' => $user
                ];
            } else {
                $response = [
                    'status' => 406,
                    'message' => 'Datos inválidos >:(',
                    'errors' => $isValid->errors()
                ];
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'No se encontró el objeto data >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function show($email) {
        $user = User::where('email', $email)->first();

        if ($user) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del usuario :)',
                'user' => $user
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Usuario no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function destroy($email) {
        if ($email) {
            $deleted = User::where('email', $email)->delete();

            if ($deleted) {
                $response = [
                    'status' => 200,
                    'message' => 'Usuario eliminado correctamente :)'
                ];
            } else {
                $response = [
                    'status' => 404,
                    'message' => 'Usuario no encontrado >:('
                ];
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'Email inválido >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $email) {
        $user = User::where('email', $email)->first();

        if ($user) {
            $data_input = $request->input('data', null);

            if ($data_input) {
                $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

                $rules = [
                    'idUsuario' => 'alpha_num',
                    'nombre' => 'regex:/^[\pL\s]+$/u',
                    'apellido' => 'regex:/^[\pL\s]+$/u',
                    'cedula' => 'numeric|digits_between:8,12',
                    'email' => 'email',
                    'password' => 'alpha_num|min:6',
                    'rol' => 'in:admin,cliente,entrenador'
                ];

                $isValid = \validator($data, $rules);

                if (!$isValid->fails()) {
                    if (isset($data['idUsuario'])) $user->idUsuario = $data['idUsuario'];
                    if (isset($data['nombre'])) $user->nombre = $data['nombre'];
                    if (isset($data['apellido'])) $user->apellido = $data['apellido'];
                    if (isset($data['cedula'])) $user->cedula = $data['cedula'];
                    if (isset($data['email'])) $user->email = $data['email'];
                    if (isset($data['password'])) $user->password = hash('sha256', $data['password']);
                    if (isset($data['rol'])) $user->rol = $data['rol'];

                    $user->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Usuario actualizado :)',
                        'user' => $user
                    ];
                } else {
                    $response = [
                        'status' => 406,
                        'message' => 'Datos inválidos >:(',
                        'errors' => $isValid->errors()
                    ];
                }
            } else {
                $response = [
                    'status' => 400,
                    'message' => 'No se encontraron datos para actualizar >:('
                ];
            }
        } else {
            $response = [
                'status' => 404,
                'message' => 'Usuario no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

            public function login(Request $request) {
            // Obtener y limpiar los datos de entrada
            $data_input = $request->input('data', null);
            
            // Verificar si los datos vienen en el formato esperado
            if (empty($data_input) || !is_array($data_input)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Formato de datos incorrecto. Se espera un objeto con email y password.'
                ], 400);
            }
            
            $data = array_map('trim', $data_input);

            // Reglas de validación
            $rules = [
                'email' => 'required|email',  // Añadí validación de formato email
                'password' => 'required|min:6' // Añadí longitud mínima para seguridad
            ];

            $validator = \Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422, // 422 Unprocessable Entity es más apropiado para errores de validación
                    'message' => 'Error en la validación de los datos',
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                $jwt = new JwtAuth();
                $response = $jwt->getToken($data['email'], $data['password']);
                
                // Mejorar la respuesta del token
                if (is_array($response) && isset($response['status'])) {
                    // Error en las credenciales
                    return response()->json($response, $response['status']);
                } else {
                    // Credenciales correctas
                    return response()->json([
                        'status' => 200,
                        'message' => 'Login exitoso',
                        'token' => $response,
                        'token_type' => 'bearer',
                        'expires_in' => 2000 // Deberías usar el mismo valor que en JwtAuth
                    ]);
                }
            } catch (\Exception $e) {
                // Manejo de errores inesperados
                return response()->json([
                    'status' => 500,
                    'message' => 'Error interno del servidor al generar el token',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        public function logout(Request $request) {
            $token = $request->bearerToken(); // estándar

            if (!$token) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Token no proporcionado'
                ], 400);
            }

            $jwt = new JwtAuth();

            if ($jwt->checkToken($token)) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Logout exitoso :) (elimina el token del lado del cliente)'
                ]);
            }

            return response()->json([
                'status' => 401,
                'message' => 'Token inválido'
            ]);
        }



    public function getIdentity(Request $request)
        {
            $token = $request->bearerToken(); // <-- cambio aquí

            $jwt = new \App\Helpers\JwtAuth();
            $checkToken = $jwt->checkToken($token, true);

            if ($checkToken && is_object($checkToken)) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Identidad del usuario obtenida :)',
                    'user' => $checkToken
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Token inválido o expirado >:('
                ]);
            }
        }


}
