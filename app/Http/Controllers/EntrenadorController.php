<?php

namespace App\Http\Controllers;

use App\Models\Entrenador;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntrenadorController extends Controller
{
    public function index(): JsonResponse
    {
        $entrenadores = Entrenador::with(['user', 'user.rol'])->get();

        $result = $entrenadores->map(function ($entrenador) {
            return [
                'idEntrenador' => $entrenador->idEntrenador,
                'idUsuario'    => $entrenador->user->idUsuario,
                'nombre'       => $entrenador->user->nombre,
                'apellido'     => $entrenador->user->apellido,
                'email'        => $entrenador->user->email,
                'cedula'       => $entrenador->user->cedula,
                'rol'          => $entrenador->user->rol,  // Ya es una cadena gracias al accessor
                'especialidad' => $entrenador->especialidad,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Lista de entrenadores',
            'data' => $result
        ]);
    }

    public function show($id): JsonResponse
    {
        $entrenador = Entrenador::with(['user', 'user.rol', 'inscripcionesClase'])->find($id);

        if (!$entrenador) {
            return response()->json([
                'status' => 404,
                'message' => 'Entrenador no encontrado'
            ], 404);
        }

        $result = [
            'idEntrenador'      => $entrenador->idEntrenador,
            'idUsuario'         => $entrenador->user->idUsuario,
            'nombre'            => $entrenador->user->nombre,
            'apellido'          => $entrenador->user->apellido,
            'email'             => $entrenador->user->email,
            'cedula'            => $entrenador->user->cedula,
            'rol'               => $entrenador->user->rol,  // Ya es una cadena gracias al accessor
            'especialidad'      => $entrenador->especialidad,
            'inscripcionesClase' => $entrenador->inscripcionesClase,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Detalles del entrenador',
            'data' => $result
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'idUsuario' => 'required|exists:users,idUsuario',
                'especialidad' => 'required|string|max:45'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                // Verificar que el usuario tenga rol de entrenador
                $user = User::find($data['idUsuario']);
                if ($user->idRol !== 3) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'El usuario debe tener rol de entrenador'
                    ], 400);
                }

                $entrenador = new Entrenador();
                $entrenador->idUsuario = $data['idUsuario'];
                $entrenador->especialidad = $data['especialidad'];
                $entrenador->save();

                return response()->json([
                    'status' => 201,
                    'message' => 'Entrenador creado correctamente',
                    'data' => $entrenador
                ]);
            } else {
                return response()->json([
                    'status' => 406,
                    'message' => 'Datos inválidos',
                    'errors' => $isValid->errors()
                ], 406);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontró el objeto data'
            ], 400);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $entrenador = Entrenador::find($id);

        if (!$entrenador) {
            return response()->json([
                'status' => 404,
                'message' => 'Entrenador no encontrado'
            ], 404);
        }

        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'especialidad' => 'string|max:45'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                if (isset($data['especialidad'])) $entrenador->especialidad = $data['especialidad'];
                $entrenador->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Entrenador actualizado correctamente',
                    'data' => $entrenador
                ]);
            } else {
                return response()->json([
                    'status' => 406,
                    'message' => 'Datos inválidos',
                    'errors' => $isValid->errors()
                ], 406);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontraron datos para actualizar'
            ], 400);
        }
    }

    public function destroy($id): JsonResponse
    {
        $entrenador = Entrenador::find($id);

        if (!$entrenador) {
            return response()->json([
                'status' => 404,
                'message' => 'Entrenador no encontrado'
            ], 404);
        }

        $entrenador->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Entrenador eliminado correctamente'
        ]);
    }
}
