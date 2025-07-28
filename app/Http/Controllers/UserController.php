<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Models\User;
use App\Models\Admin;      
use App\Models\Cliente;      
use App\Models\Entrenador;
use App\Models\Telefono;

class UserController extends Controller
{
    /**
     * Función auxiliar para limpiar datos recursivamente
     */
    private function cleanData($data) {
        if (is_array($data)) {
            array_walk_recursive($data, function(&$value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
            });
            return $data;
        }
        return is_string($data) ? trim($data) : $data;
    }
    
    public function index() {
        $users = User::with(['rol', 'telefonos'])->get();
        
        // Transformar los datos para incluir teléfonos de manera consistente
        $usersFormatted = $users->map(function ($user) {
            $telefonos = $user->telefonos->map(function ($tel) {
                return [
                    'id' => $tel->idTelefono,
                    'telefono' => $tel->telefono,
                    'tipoTel' => $tel->tipoTel
                ];
            });

            return [
                'idUsuario' => $user->idUsuario,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'cedula' => $user->cedula,
                'email' => $user->email,
                'idRol' => $user->idRol,
                'rol' => $user->rol,
                'telefonos_list' => $telefonos
            ];
        });

        $response = [
            'status' => 200,
            'message' => 'Lista de usuarios :)',
            'users' => $usersFormatted
        ];

        return response()->json($response, $response['status']);
    }

    public function store(Request $request) {
        $data_input = $request->input('data', null);

        if (!$data_input) {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontró el objeto data'
            ], 400);
        }

        $data = $this->cleanData(is_array($data_input) ? $data_input : json_decode($data_input, true));

        $rules = [
            'nombre' => 'required|string|max:45',
            'apellido' => 'required|string|max:45',
            'cedula' => 'required|string|max:45|unique:users,cedula',
            'email' => 'required|email|max:45|unique:users,email',
            'password' => 'required|string|min:6',
            'idRol' => 'required|integer|in:1,2,3',
        ];

        // Validación de teléfonos si se proporcionan
        if (isset($data['telefonos']) && is_array($data['telefonos'])) {
            $rules['telefonos'] = 'array|min:1';
            $rules['telefonos.*.telefono'] = 'required|numeric|digits_between:8,12|unique:telefonos,telefono';
            $rules['telefonos.*.tipoTel'] = 'required|string|in:celular,casa,trabajo';
        } elseif (isset($data['telefono']) && !empty($data['telefono'])) {
            $rules['telefono'] = 'required|numeric|digits_between:8,12|unique:telefonos,telefono';
            $rules['tipoTel'] = 'required|string|in:celular,casa,trabajo';
        }

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Crear usuario
            $user = User::create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'cedula' => $data['cedula'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'idRol' => $data['idRol']
            ]);

            // Crear registro específico según el rol
            $this->createRoleSpecificRecord($user, $data['idRol']);

            // Manejar teléfonos si se proporcionan
            $this->handleTelefonos($user, $data);

            // Cargar relaciones para la respuesta
            $user->load(['rol', 'telefonos']);

            $telefonos = $user->telefonos->map(function ($tel) {
                return [
                    'id' => $tel->idTelefono,
                    'telefono' => $tel->telefono,
                    'tipoTel' => $tel->tipoTel
                ];
            });

            return response()->json([
                'status' => 201,
                'message' => 'Usuario creado exitosamente',
                'user' => [
                    'idUsuario' => $user->idUsuario,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'cedula' => $user->cedula,
                    'email' => $user->email,
                    'idRol' => $user->idRol,
                    'rol' => $user->rol,
                    'telefonos_list' => $telefonos
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al crear el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($email) {
        $user = User::with(['rol', 'telefonos'])
                   ->where('email', $email)
                   ->first();

        if ($user) {
            $telefonos = $user->telefonos->map(function ($tel) {
                return [
                    'id' => $tel->idTelefono,
                    'telefono' => $tel->telefono,
                    'tipoTel' => $tel->tipoTel
                ];
            });
            
            $user->telefonos_list = $telefonos;
            
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
        if (!$email) {
            return response()->json([
                'status' => 400,
                'message' => 'Email inválido >:('
            ], 400);
        }

        $deleted = User::where('email', $email)->delete();

        if ($deleted) {
            $response = [
                'status' => 200,
                'message' => 'Usuario eliminado exitosamente :)'
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Usuario no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $email) {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado >:('
            ], 404);
        }

        $data_input = $request->input('data', null);

        if (!$data_input) {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontraron datos para actualizar >:('
            ], 400);
        }

        $data = $this->cleanData(is_array($data_input) ? $data_input : json_decode($data_input, true));

        $rules = [
            'nombre' => 'sometimes|required|string|max:45',
            'apellido' => 'sometimes|required|string|max:45',
            'cedula' => 'sometimes|required|string|max:45|unique:users,cedula,' . $user->idUsuario . ',idUsuario',
            'email' => 'sometimes|required|email|max:45|unique:users,email,' . $user->idUsuario . ',idUsuario',
            'password' => 'sometimes|required|string|min:6',
            'idRol' => 'sometimes|required|integer|in:1,2,3',
        ];

        // Validación de teléfonos si se proporcionan
        if (isset($data['telefonos']) && is_array($data['telefonos'])) {
            foreach ($data['telefonos'] as $index => $telefono) {
                $telefonoExistente = $this->isTelefonoExistente($telefono['telefono'], $user->idUsuario);
                if ($telefonoExistente) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'El teléfono ' . $telefono['telefono'] . ' ya está registrado',
                        'errors' => ["telefonos.{$index}.telefono" => ['El teléfono ya está en uso']]
                    ], 422);
                }
            }
        } elseif (isset($data['telefono']) && !empty($data['telefono'])) {
            $telefonoExistente = $this->isTelefonoExistente($data['telefono'], $user->idUsuario);
            if ($telefonoExistente) {
                return response()->json([
                    'status' => 422,
                    'message' => 'El teléfono ya está registrado',
                    'errors' => ['telefono' => ['El teléfono ya está en uso']]
                ], 422);
            }
        }

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Actualizar campos del usuario
            if (isset($data['nombre'])) $user->nombre = $data['nombre'];
            if (isset($data['apellido'])) $user->apellido = $data['apellido'];
            if (isset($data['cedula'])) $user->cedula = $data['cedula'];
            if (isset($data['email'])) $user->email = $data['email'];
            if (isset($data['password'])) $user->password = bcrypt($data['password']);
            if (isset($data['idRol'])) $user->idRol = $data['idRol'];

            $user->save();

            // Manejar actualización de teléfonos
            $this->handleTelefonos($user, $data, true);

            // Cargar relaciones para la respuesta
            $user->load(['rol', 'telefonos']);

            $telefonos = $user->telefonos->map(function ($tel) {
                return [
                    'id' => $tel->idTelefono,
                    'telefono' => $tel->telefono,
                    'tipoTel' => $tel->tipoTel
                ];
            });

            $user->telefonos_list = $telefonos;

            return response()->json([
                'status' => 200,
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request) {
        $data_input = $request->input('data', null);
        
        if (empty($data_input) || !is_array($data_input)) {
            return response()->json([
                'status' => 400,
                'message' => 'Formato de datos incorrecto. Se espera un objeto con email y password.'
            ], 400);
        }
        
        $data = $this->cleanData($data_input);

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jwt = new JwtAuth();
            $response = $jwt->getToken($data['email'], $data['password']);
            
            if (is_array($response) && isset($response['status'])) {
                return response()->json($response, $response['status']);
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Login exitoso',
                    'token' => $response,
                    'token_type' => 'bearer',
                    'expires_in' => 2000
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al generar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request) {
        $token = $request->bearerToken();

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

    public function getIdentity(Request $request) {
        $token = $request->bearerToken();

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

    /**
     * Agregar teléfono a un usuario
     */
    public function addTelefono(Request $request, $email) {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $data_input = $request->input('data', null);

        if (!$data_input) {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontró el objeto data'
            ], 400);
        }

        $data = $this->cleanData(is_array($data_input) ? $data_input : json_decode($data_input, true));

        $rules = [
            'telefono' => 'required|numeric|digits_between:8,12|unique:telefonos,telefono',
            'tipoTel' => 'required|string|in:celular,casa,trabajo'
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $telefono = Telefono::create([
                'idUsuario' => $user->idUsuario,
                'telefono' => $data['telefono'],
                'tipoTel' => $data['tipoTel'],
                'idRol' => $user->idRol
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'Teléfono agregado correctamente',
                'data' => [
                    'idTelefono' => $telefono->idTelefono,
                    'telefono' => $telefono->telefono,
                    'tipoTel' => $telefono->tipoTel
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al agregar el teléfono',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener teléfonos de un usuario
     */
    public function getTelefonos($email) {
        $user = User::with('telefonos')->where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $telefonos = $user->telefonos->map(function ($tel) {
            return [
                'id' => $tel->idTelefono,
                'telefono' => $tel->telefono,
                'tipoTel' => $tel->tipoTel
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Teléfonos del usuario',
            'data' => $telefonos
        ]);
    }

    /**
     * Actualizar todos los teléfonos de un usuario (reemplaza los existentes)
     */
    public function updateTelefonos(Request $request, $email) {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $data_input = $request->input('data', null);

        if (!$data_input) {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontró el objeto data'
            ], 400);
        }

        $data = $this->cleanData(is_array($data_input) ? $data_input : json_decode($data_input, true));

        $rules = [
            'telefonos' => 'required|array|min:1',
            'telefonos.*.telefono' => 'required|numeric|digits_between:8,12',
            'telefonos.*.tipoTel' => 'required|string|in:celular,casa,trabajo'
        ];

        // Validar teléfonos únicos en el array
        if (isset($data['telefonos'])) {
            $telefonos = array_column($data['telefonos'], 'telefono');
            if (count($telefonos) !== count(array_unique($telefonos))) {
                return response()->json([
                    'status' => 422,
                    'message' => 'No se pueden repetir teléfonos en la misma solicitud',
                    'errors' => ['telefonos' => ['Teléfonos duplicados encontrados']]
                ], 422);
            }

            // Validar que no existan en otros usuarios
            foreach ($data['telefonos'] as $index => $telefono) {
                $telefonoExistente = $this->isTelefonoExistente($telefono['telefono'], $user->idUsuario);
                
                if ($telefonoExistente) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'El teléfono ' . $telefono['telefono'] . ' ya está registrado por otro usuario',
                        'errors' => ["telefonos.{$index}.telefono" => ['El teléfono ya está en uso']]
                    ], 422);
                }
            }
        }

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Eliminar teléfonos existentes
            Telefono::where('idUsuario', $user->idUsuario)->delete();

            // Crear nuevos teléfonos
            foreach ($data['telefonos'] as $telefonoData) {
                Telefono::create([
                    'idUsuario' => $user->idUsuario,
                    'telefono' => $telefonoData['telefono'],
                    'tipoTel' => $telefonoData['tipoTel'],
                    'idRol' => $user->idRol
                ]);
            }

            // Cargar usuario con telefonos actualizados
            $user->load('telefonos');

            $telefonos = $user->telefonos->map(function ($tel) {
                return [
                    'id' => $tel->idTelefono,
                    'telefono' => $tel->telefono,
                    'tipoTel' => $tel->tipoTel
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Teléfonos actualizados correctamente',
                'data' => $telefonos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al actualizar teléfonos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar todos los teléfonos de un usuario
     */
    public function clearTelefonos($email) {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        try {
            $deletedCount = Telefono::where('idUsuario', $user->idUsuario)->delete();

            return response()->json([
                'status' => 200,
                'message' => "Se eliminaron {$deletedCount} teléfonos correctamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al eliminar teléfonos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear registro específico según el rol del usuario
     */
    private function createRoleSpecificRecord($user, $idRol) {
        switch ($idRol) {
            case 1: // admin
                Admin::create([
                    'idUsuario' => $user->idUsuario
                ]);
                break;
            case 2: // cliente
                Cliente::create([
                    'idUsuario' => $user->idUsuario,
                    'fechaRegistro' => now()->format('Y-m-d')
                ]);
                break;
            case 3: // entrenador
                Entrenador::create([
                    'idUsuario' => $user->idUsuario,
                    'especialidad' => 'General'
                ]);
                break;
        }
    }

    /**
     * Manejar creación/actualización de teléfonos
     */
    private function handleTelefonos($user, $data, $isUpdate = false) {
        // Si es actualización, limpiar teléfonos existentes cuando se envían nuevos
        if ($isUpdate && (isset($data['telefonos']) || isset($data['telefono']))) {
            Telefono::where('idUsuario', $user->idUsuario)->delete();
        }

        // Manejar array de teléfonos (formato múltiple)
        if (isset($data['telefonos']) && is_array($data['telefonos'])) {
            foreach ($data['telefonos'] as $telefono) {
                Telefono::create([
                    'idUsuario' => $user->idUsuario,
                    'telefono' => $telefono['telefono'],
                    'tipoTel' => $telefono['tipoTel'],
                    'idRol' => $user->idRol
                ]);
            }
        }
        // Manejar teléfono individual (retrocompatibilidad)
        elseif (isset($data['telefono']) && !empty($data['telefono'])) {
            Telefono::create([
                'idUsuario' => $user->idUsuario,
                'telefono' => $data['telefono'],
                'tipoTel' => $data['tipoTel'] ?? 'celular',
                'idRol' => $user->idRol
            ]);
        }
    }

    /**
     * Verificar si un teléfono ya existe excluyendo a un usuario específico
     */
    private function isTelefonoExistente($telefono, $excludeUserId = null) {
        $query = Telefono::where('telefono', $telefono);
        
        if ($excludeUserId) {
            $query->where('idUsuario', '!=', $excludeUserId);
        }
        
        return $query->exists();
    }

    /**
     * Verificar datos actuales del usuario (para depuración)
     */
    public function verifyUserData($email) {
        $user = User::with(['telefonos', 'rol'])
                   ->where('email', $email)
                   ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $telefonos = $user->telefonos->map(function ($tel) {
            return [
                'id' => $tel->idTelefono,
                'telefono' => $tel->telefono,
                'tipoTel' => $tel->tipoTel
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Datos actuales del usuario verificados',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'user' => [
                'idUsuario' => $user->idUsuario,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'cedula' => $user->cedula,
                'idRol' => $user->idRol,
                'rol' => $user->rol->nombreRol
            ],
            'telefonos' => $telefonos,
            'telefonos_count' => count($telefonos)
        ]);
    }
}
