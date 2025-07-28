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
        try {
            $telefonos = Telefono::with(['user', 'rol'])
                ->orderBy('idRol')
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
                        'cedula' => $telefono->user->cedula,
                    ],
                    'rol' => [
                        'idRol' => $telefono->rol->idRol,
                        'nombreRol' => $telefono->rol->nombreRol,
                    ],
                    'created_at' => $telefono->created_at,
                    'updated_at' => $telefono->updated_at,
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Lista de teléfonos obtenida exitosamente',
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
            'idUsuario' => 'required|integer|exists:users,idUsuario',
            'telefono' => 'required|numeric|digits_between:8,12|unique:telefonos,telefono',
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
            // Obtener el usuario para asignar el rol automáticamente
            $user = User::find($data['idUsuario']);
            
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $telefono = Telefono::create([
                'idUsuario' => $data['idUsuario'],
                'telefono' => $data['telefono'],
                'tipoTel' => $data['tipoTel'],
                'idRol' => $user->idRol // Asignar automáticamente el rol del usuario
            ]);

            // Cargar relaciones para la respuesta
            $telefono->load(['user', 'rol']);

            return response()->json([
                'status' => 201,
                'message' => 'Teléfono creado exitosamente',
                'data' => [
                    'idTelefono' => $telefono->idTelefono,
                    'telefono' => $telefono->telefono,
                    'tipoTel' => $telefono->tipoTel,
                    'usuario' => [
                        'idUsuario' => $telefono->user->idUsuario,
                        'nombre' => $telefono->user->nombre,
                        'apellido' => $telefono->user->apellido,
                        'email' => $telefono->user->email,
                    ],
                    'rol' => [
                        'idRol' => $telefono->rol->idRol,
                        'nombreRol' => $telefono->rol->nombreRol,
                    ],
                    'created_at' => $telefono->created_at,
                ]
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
            $telefono = Telefono::with(['user', 'rol'])->find($id);

            if (!$telefono) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Teléfono no encontrado'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Detalles del teléfono',
                'data' => [
                    'idTelefono' => $telefono->idTelefono,
                    'telefono' => $telefono->telefono,
                    'tipoTel' => $telefono->tipoTel,
                    'usuario' => [
                        'idUsuario' => $telefono->user->idUsuario,
                        'nombre' => $telefono->user->nombre,
                        'apellido' => $telefono->user->apellido,
                        'email' => $telefono->user->email,
                        'cedula' => $telefono->user->cedula,
                    ],
                    'rol' => [
                        'idRol' => $telefono->rol->idRol,
                        'nombreRol' => $telefono->rol->nombreRol,
                    ],
                    'created_at' => $telefono->created_at,
                    'updated_at' => $telefono->updated_at,
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
     * Actualizar un teléfono
     */
    public function update(Request $request, $id)
    {
        $telefono = Telefono::find($id);

        if (!$telefono) {
            return response()->json([
                'status' => 404,
                'message' => 'Teléfono no encontrado'
            ], 404);
        }

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
            'telefono' => 'sometimes|required|numeric|digits_between:8,12|unique:telefonos,telefono,' . $telefono->idTelefono . ',idTelefono',
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
            // Actualizar solo los campos enviados
            if (isset($data['telefono'])) {
                $telefono->telefono = $data['telefono'];
            }
            if (isset($data['tipoTel'])) {
                $telefono->tipoTel = $data['tipoTel'];
            }

            $telefono->save();

            return response()->json([
                'status' => 200,
                'message' => 'Teléfono actualizado exitosamente',
                'data' => [
                    'idTelefono' => $telefono->idTelefono,
                    'telefono' => $telefono->telefono,
                    'tipoTel' => $telefono->tipoTel,
                    'updated_at' => $telefono->updated_at,
                ]
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
            $telefono = Telefono::find($id);

            if (!$telefono) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Teléfono no encontrado'
                ], 404);
            }

            $telefono->delete();

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
