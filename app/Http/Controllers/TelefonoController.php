<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Telefono;
use App\Models\User;

class TelefonoController extends Controller
{
    /**
     * Obtener todos los teléfonos del sistema con depuración
     */
    public function index()
    {
        try {
            $telefonos = \DB::select('EXEC pa_ObtenerTelefonos');

            // Depuración: mostrar la estructura de los teléfonos en logs
            \Log::info('Telefonos obtenidos: ', ['telefonos' => $telefonos]);

            // Mapear los datos para devolver rol correctamente
            $telefonosFormatted = array_map(function($tel) {
                // Depuración: mostrar cada teléfono antes de mapear
                \Log::info('Teléfono raw: ', (array)$tel);

                return [
                    'idTelefono' => $tel->idTelefono ?? null,
                    'idUsuario' => $tel->idUsuario ?? null,
                    'telefono' => $tel->telefono ?? null,
                    'tipoTel' => $tel->tipoTel ?? null,
                    // Usar idRol si existe, sino 2 (cliente) por defecto
                    'idRol' => $tel->idRol ?? 2,
                    // Rol: si el backend devuelve nombreRol, usarlo; sino adivinar por idRol
                    'rol' => [
                        'idRol' => $tel->idRol ?? 2,
                        'nombreRol' => $tel->nombreRol ?? $this->getRolNameById($tel->idRol ?? 2),
                        'descripcion' => $tel->descripcion ?? ''
                    ],
                    // Datos de usuario anidado sin email ni cedula
                    'user' => [
                        'idUsuario' => $tel->idUsuario ?? null,
                        'nombre' => $tel->nombre ?? 'N/A',
                        'apellido' => $tel->apellido ?? '',
                        'rol' => $tel->nombreRol ?? $this->getRolNameById($tel->idRol ?? 2),
                    ]
                ];
            }, $telefonos);

            return response()->json([
                'status' => 200,
                'message' => 'Lista de teléfonos obtenida exitosamente',
                'data' => $telefonosFormatted
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener teléfonos: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo teléfono
     */
    public function store(Request $request)
        {
            $data_input = $request->input('data', null);

            if (!$data_input) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se encontró el objeto data'
                ], 400);
            }

            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            // Validaciones
            $rules = [
                'idUsuario' => 'required|integer',
                'telefono' => 'required|numeric|digits_between:8,12',
                'tipoTel' => 'required|string|in:celular,casa,trabajo,otro',
                'idRol' => 'sometimes|integer|in:1,2,3'
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
                    $data['idUsuario'],
                    $data['telefono'],
                    $data['tipoTel'],
                    $data['idRol'] ?? 2
                ]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Teléfono creado exitosamente'
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error interno del servidor al crear el teléfono',
                    'error' => $e->getMessage()
                ], 500);
            }
        }


    /**
     * Obtener un teléfono específico
     */
    public function show($id)
{
    try {
        // Ejecutar el procedimiento almacenado que trae teléfono + usuario + rol
        $telefono = \DB::select('EXEC pa_ObtenerTelefonoID ?', [$id]);

        if (!$telefono || count($telefono) === 0) {
            return response()->json([
                'status' => 404,
                'message' => 'Teléfono no encontrado'
            ], 404);
        }

        $tel = $telefono[0]; // Tomamos el primer registro

        // Formatear la respuesta
        $responseData = [
            'idTelefono' => $tel->idTelefono,
            'telefono' => $tel->telefono,
            'tipoTel' => $tel->tipoTel,
            'usuario' => [
                'idUsuario' => $tel->idUsuario,
                'nombre' => $tel->nombre,
                'apellido' => $tel->apellido,
                'rol' => !empty($tel->nombreRol) ? $tel->nombreRol : ''
            ]
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Detalles del teléfono',
            'data' => $responseData
        ]);

    } catch (\Exception $e) {
        \Log::error('Error al obtener teléfono: ' . $e->getMessage());
        return response()->json([
            'status' => 500,
            'message' => 'Error interno del servidor',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Actualizar un teléfono
     */
    public function update(Request $request, $id)
    {
        $data_input = $request->all();
        if (isset($data_input['data'])) {
            $data_input = $data_input['data'];
        }
        
        $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

        $rules = [
            'telefono' => 'sometimes|required|numeric|digits_between:8,12',
            'tipoTel' => 'sometimes|required|string|in:celular,casa,trabajo',
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
            \DB::statement('EXEC pa_ActualizarTelefono ?,?,?', [
                $id,
                $data['telefono'] ?? null,
                $data['tipoTel'] ?? null
            ]);
            return response()->json([
                'status' => 200,
                'message' => 'Teléfono actualizado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al actualizar el teléfono',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un teléfono
     */
    public function destroy($id)
    {
        try {
            \DB::statement('EXEC pa_BorrarTelefono ?', [$id]);
            return response()->json([
                'status' => 200,
                'message' => 'Teléfono eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor al eliminar el teléfono',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener teléfonos por usuario
     */
    public function getByUser($userId)
    {
        try {
            $user = User::with('rol')->find($userId);

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $telefonos = Telefono::with(['rol'])
                ->where('idUsuario', $userId)
                ->get();

            $telefonosFormatted = $telefonos->map(function ($telefono) use ($user) {
                return [
                    'idTelefono' => $telefono->idTelefono,
                    'telefono' => $telefono->telefono,
                    'tipoTel' => $telefono->tipoTel,
                    'usuario' => [
                        'idUsuario' => $user->idUsuario,
                        'nombre' => $user->nombre,
                        'apellido' => $user->apellido,
                        'rol' => $user->rol->nombreRol,
                    ],
                    'created_at' => $telefono->created_at,
                    'updated_at' => $telefono->updated_at,
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => "Teléfonos del usuario {$user->nombre} {$user->apellido}",
                'data' => $telefonosFormatted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener teléfonos por rol
     */
    public function getByRole($rolId)
    {
        try {
            $telefonos = Telefono::with(['user', 'rol'])
                ->where('idRol', $rolId)
                ->orderBy('idUsuario')
                ->get();

            $telefonosFormatted = $telefonos->map(function ($telefono) {
                return [
                    'idTelefono' => $telefono->idTelefono,
                    'telefono' => $telefono->telefono,
                    'tipoTel' => $telefono->tipoTel,
                    'usuario' => [
                        'idUsuario' => $telefono->user->idUsuario,
                        'nombre' => $telefono->user->nombre,
                        'apellido' => $telefono->user->apellido,
                        'rol' => $telefono->rol->nombreRol ?? $this->getRolNameById($telefono->idRol ?? 2),
                    ],
                    'created_at' => $telefono->created_at,
                ];
            });

            $rolName = $telefonos->first()?->rol->nombreRol ?? 'Rol no encontrado';

            return response()->json([
                'status' => 200,
                'message' => "Teléfonos del rol {$rolName}",
                'data' => [
                    'rol' => $rolName,
                    'count' => $telefonos->count(),
                    'telefonos' => $telefonosFormatted
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función auxiliar para obtener nombre del rol por ID
     */
    private function getRolNameById($idRol)
    {
        switch ($idRol) {
            case 1: return 'admin';
            case 2: return 'cliente';
            case 3: return 'entrenador';
            default: return 'N/A';
        }
    }
}
