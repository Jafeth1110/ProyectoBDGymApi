<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Telefono;
use App\Models\User;

class TelefonoController extends Controller
{
    /**
     * Obtener todos los teléfonos del sistema
     */
    public function index()
    {
        $telefonos = \DB::select('EXEC pa_ObtenerTelefonos');
        return response()->json([
            'status' => 200,
            'message' => 'Lista de teléfonos obtenida exitosamente',
            'data' => $telefonos
        ]);
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
            'tipoTel' => 'required|string|in:celular,casa,trabajo',
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
            \DB::statement('EXEC pa_CrearTelefono ?,?,?', [
                $data['idUsuario'],
                $data['telefono'],
                $data['tipoTel']
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
        $telefono = \DB::select('EXEC pa_ObtenerTelefonoID ?', [$id]);
        if ($telefono) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del teléfono',
                'data' => $telefono[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Teléfono no encontrado'
            ];
        }
        return response()->json($response, $response['status']);
    }

    /**
     * Actualizar un teléfono
     */
    public function update(Request $request, $id)
    {
        $data_input = $request->input('data', null);

        if (!$data_input) {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontraron datos para actualizar'
            ], 400);
        }

        $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

        // Validaciones
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
            ]);
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
            ]);
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
                        'email' => $telefono->user->email,
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
}
