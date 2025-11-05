<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Models\User;
use App\Models\Admin;      
use App\Models\Cliente;      
use App\Models\Entrenador;
use App\Models\Telefono;

class UserController extends Controller
{
    private function getActorId(Request $request)
    {
        $token = $request->bearerToken();
        $jwt = new \App\Helpers\JwtAuth();
        $decoded = $jwt->checkToken($token, true);
        if ($decoded && is_object($decoded) && isset($decoded->sub)) {
            return (int)$decoded->sub;
        }
        return null;
    }
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
        $users = \DB::select('EXEC pa_ObtenerUsuarios');
        $usersFormatted = collect($users)->map(function ($user) {
            $telefonos = \DB::select('EXEC pa_ObtenerTelefonosPorUsuario ?', [$user->idUsuario]);
            $rol = null;
            switch ($user->idRol) {
                case 1: $rol = 'admin'; break;
                case 2: $rol = 'cliente'; break;
                case 3: $rol = 'entrenador'; break;
            }
            return [
                'idUsuario' => $user->idUsuario,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'cedula' => $user->cedula,
                'email' => $user->email,
                'idRol' => $user->idRol,
                'rol' => $rol,
                'telefonos' => $telefonos
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
        $data = $this->cleanData($request->input('data', []));
        $actorId = $this->getActorId($request) ?? 0; // permitir registro público, auditar como 0
        
        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:45',
            'apellido' => 'required|string|max:45',
            'cedula' => 'required|string|max:45|unique:users,cedula',
            'email' => 'required|email|max:45|unique:users,email',
            'password' => 'required|string|min:6',
            'idRol' => 'required|integer|in:1,2,3',
            'telefonos' => 'sometimes|array',
            'telefonos.*.telefono' => 'required|numeric|digits_between:8,12',
            'telefonos.*.tipoTel' => 'required|string|in:celular,casa,trabajo,otro'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = \DB::select('EXEC sp_AuditCrearUsuario ?,?,?,?,?,?,?,?', [
                $data['nombre'],
                $data['apellido'],
                $data['cedula'],
                strtolower($data['email']),
                bcrypt($data['password']),
                $data['idRol'],
                $actorId,
                $request->ip()
            ]);

            if (empty($result) || !isset($result[0]->codigo) || (int)$result[0]->codigo !== 200) {
                return response()->json([
                    'status' => 500,
                    'message' => $result[0]->mensaje ?? 'Error al crear usuario'
                ], 500);
            }

            $newUserId = $result[0]->idUsuario ?? null;

            if (!empty($data['telefonos']) && is_array($data['telefonos']) && $newUserId) {
                foreach ($data['telefonos'] as $tel) {
                    if (!empty($tel['telefono']) && !empty($tel['tipoTel'])) {
                        \DB::statement('EXEC pa_CrearTelefono ?,?,?,?', [
                            $newUserId,
                            $tel['telefono'],
                            $tel['tipoTel'],
                            $data['idRol']
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => 201,
                'message' => 'Usuario creado exitosamente',
                'idUsuario' => $newUserId
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($email) {
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
        if ($user && count($user) > 0) {
            $user = $user[0];
            
            // AGREGAR MAPEO DEL ROL IGUAL QUE EN INDEX()
            $rol = null;
            switch ($user->idRol) {
                case 1: $rol = 'admin'; break;
                case 2: $rol = 'cliente'; break;
                case 3: $rol = 'entrenador'; break;
            }
            $user->rol = $rol; // Agregar el rol mapeado al objeto user
            
            $telefonos = \DB::select('EXEC pa_ObtenerTelefonosPorUsuario ?', [$user->idUsuario]);
            $user->telefonos_list = $telefonos;
            $response = [
                'status' => 200,
                'message' => 'Usuario encontrado',
                'user' => $user
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ];
        }
        return response()->json($response, $response['status']);
    }

    public function destroy($email) {
        // autenticación
        $request = request();
        $actorId = $this->getActorId($request);
        if (!$actorId) {
            return response()->json([
                'status' => 401,
                'message' => 'No autenticado'
            ], 401);
        }
        if (!$email) {
            return response()->json([
                'status' => 400,
                'message' => 'Email inválido >:('
            ], 400);
        }
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
        if ($user && count($user) > 0) {
            $idUsuario = $user[0]->idUsuario;
            try {
                $result = \DB::select('EXEC sp_AuditEliminarUsuario ?,?,?', [
                    $idUsuario,
                    $actorId,
                    $request->ip()
                ]);
                if (empty($result) || !isset($result[0]->codigo) || (int)$result[0]->codigo !== 200) {
                    return response()->json([
                        'status' => 500,
                        'message' => $result[0]->mensaje ?? 'Error al eliminar usuario'
                    ], 500);
                }
                $response = [
                    'status' => 200,
                    'message' => 'Usuario eliminado exitosamente :)'
                ];
            } catch (\Exception $e) {
                $response = [
                    'status' => 500,
                    'message' => 'Error al eliminar usuario',
                    'error' => $e->getMessage()
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

    public function update(Request $request, $email)
        {
            $actorId = $this->getActorId($request);
            if (!$actorId) {
                return response()->json([
                    'status' => 401,
                    'message' => 'No autenticado'
                ], 401);
            }
            // Buscar usuario por email
            $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
            if (!$user || count($user) === 0) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $idUsuario = $user[0]->idUsuario;

            // Obtener y limpiar datos
            $data_input = $request->all();

            // Aceptar casos donde Angular manda {"data": {...}}
            if (isset($data_input['data'])) {
                $data_input = $data_input['data'];
            }

            if (!$data_input || !is_array($data_input)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se encontraron datos válidos para actualizar'
                ], 400);
            }

            $data = $this->cleanData($data_input);

            // Reglas de validación
            $rules = [
                'nombre' => 'sometimes|required|string|max:45',
                'apellido' => 'sometimes|required|string|max:45',
                'cedula' => 'sometimes|required|string|max:45',
                'email' => 'sometimes|required|email|max:45',
                'password' => 'nullable|string|min:6',
                'idRol' => 'sometimes|required|integer|in:1,2,3',
                'telefonos' => 'sometimes|array',
                'telefonos.*.telefono' => 'sometimes|required|numeric|digits_between:8,12',
                'telefonos.*.tipoTel' => 'sometimes|required|string|in:celular,casa,trabajo,otro'
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
                // Preparar datos para el procedimiento
                $nombre = $data['nombre'] ?? $user[0]->nombre;
                $apellido = $data['apellido'] ?? $user[0]->apellido;
                $cedula = $data['cedula'] ?? $user[0]->cedula;
                $emailActualizado = $data['email'] ?? $user[0]->email;
                $password = isset($data['password']) && !empty($data['password'])
                    ? bcrypt($data['password'])
                    : $user[0]->password; // usa el actual si no viene nuevo
                $idRol = $data['idRol'] ?? $user[0]->idRol;
                $telefonosJson = isset($data['telefonos']) ? json_encode($data['telefonos']) : null;

                // Actualizar usuario con auditoría
                $resAudit = \DB::select('EXEC sp_AuditActualizarUsuario ?,?,?,?,?,?,?,?,?', [
                    $idUsuario,
                    $nombre,
                    $apellido,
                    $cedula,
                    $emailActualizado,
                    $password,
                    $idRol,
                    $actorId,
                    $request->ip()
                ]);

                if (empty($resAudit) || !isset($resAudit[0]->codigo) || (int)$resAudit[0]->codigo !== 200) {
                    return response()->json([
                        'status' => 500,
                        'message' => $resAudit[0]->mensaje ?? 'Error al actualizar usuario'
                    ], 500);
                }

                // Si vienen teléfonos, actualizar usando PA existente
                if ($telefonosJson !== null) {
                    \DB::statement('EXEC pa_ActualizarUsuarioConTelefonos ?,?,?,?,?,?,?,?', [
                        $idUsuario,
                        $nombre,
                        $apellido,
                        $cedula,
                        $emailActualizado,
                        null, // no cambiar password aquí
                        $idRol,
                        $telefonosJson
                    ]);
                }

                // Obtener usuario actualizado
                $userUpdated = \DB::select('EXEC pa_ObtenerUsuarioID ?', [$idUsuario]);
                $telefonos = \DB::select('EXEC pa_ObtenerTelefonosPorUsuario ?', [$idUsuario]);
                $userUpdated[0]->telefonos_list = $telefonos;

                return response()->json([
                    'status' => 200,
                    'message' => 'Usuario actualizado exitosamente con teléfonos',
                    'user' => $userUpdated[0]
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error interno del servidor al actualizar usuario',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        }

       public function login(Request $request)
        {
            $data = $request->input('data');

            if (!is_array($data)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Formato incorrecto. Se esperaba {"data": {"email": "...", "password": "..."}}'
                ], 400);
            }

            $validator = \Validator::make($data, [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Error en la validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                $jwtAuth = new \App\Helpers\JwtAuth();
                $token = $jwtAuth->getToken($data['email'], $data['password']);

                if (is_array($token) && isset($token['status']) && $token['status'] !== 200) {
                    return response()->json($token, $token['status']);
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Login exitoso',
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => 86400 // 24 horas
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error interno en login',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 400,
                'message' => 'Token no proporcionado'
            ], 400);
        }

        $jwtAuth = new \App\Helpers\JwtAuth();
        $valid = $jwtAuth->checkToken($token);

        if ($valid) {
            return response()->json([
                'status' => 200,
                'message' => 'Logout exitoso. Elimina el token en el cliente.'
            ]);
        }

        return response()->json([
            'status' => 401,
            'message' => 'Token inválido o expirado'
        ], 401);
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
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
        if (!$user || count($user) == 0) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        $user = $user[0];
        $data_input = $request->input('data', null);
        if (!$data_input) {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontró el objeto data'
            ], 400);
        }
        $data = $this->cleanData(is_array($data_input) ? $data_input : json_decode($data_input, true));
        $rules = [
            'telefono' => 'required|numeric|digits_between:8,12',
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
            \DB::statement('EXEC pa_CrearTelefono ?,?,?,?', [
                $user->idUsuario,
                $data['telefono'],
                $data['tipoTel'],
                $user->idRol
            ]);
            return response()->json([
                'status' => 201,
                'message' => 'Teléfono agregado correctamente'
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
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
        if (!$user || count($user) == 0) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        $idUsuario = $user[0]->idUsuario;
        $telefonos = \DB::select('EXEC pa_ObtenerTelefonosPorUsuario ?', [$idUsuario]);
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
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
        if (!$user || count($user) == 0) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        $user = $user[0];
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
        $validator = \Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            // Eliminar teléfonos existentes usando PA (debes crear pa_BorrarTelefonosPorUsuario)
            \DB::statement('EXEC pa_BorrarTelefonosPorUsuario ?', [$user->idUsuario]);
            // Crear nuevos teléfonos usando PA
            foreach ($data['telefonos'] as $telefonoData) {
                \DB::statement('EXEC pa_CrearTelefono ?,?,?,?', [
                    $user->idUsuario,
                    $telefonoData['telefono'],
                    $telefonoData['tipoTel'],
                    $user->idRol
                ]);
            }
            $telefonos = \DB::select('EXEC pa_ObtenerTelefonosPorUsuario ?', [$user->idUsuario]);
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
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$email]);
        if (!$user || count($user) == 0) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        $idUsuario = $user[0]->idUsuario;
        try {
            \DB::statement('EXEC pa_BorrarTelefonosPorUsuario ?', [$idUsuario]);
            return response()->json([
                'status' => 200,
                'message' => 'Se eliminaron todos los teléfonos correctamente'
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
        if ($isUpdate && !empty($data['telefonos'])) {
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