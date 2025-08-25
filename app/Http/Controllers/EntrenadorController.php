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
        $entrenadores = \DB::select('EXEC pa_ObtenerEntrenadores');
        return response()->json([
            'status' => 200,
            'message' => 'Lista de entrenadores',
            'data' => $entrenadores
        ]);
    }

    public function show($id): JsonResponse
    {
        $entrenador = \DB::select('EXEC pa_ObtenerEntrenadorID ?', [$id]);
        if ($entrenador) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del entrenador',
                'data' => $entrenador[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Entrenador no encontrado'
            ];
        }
        return response()->json($response, $response['status']);
    }

    public function store(Request $request): JsonResponse
    {
        $data_input = $request->input('data', null);
        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            $rules = [
                'idUsuario' => 'required|integer',
                'especialidad' => 'required|string|max:45'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearEntrenador ?,?', [
                    $data['idUsuario'],
                    $data['especialidad']
                ]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Entrenador creado correctamente'
                ]);
            } else {
                return response()->json([
                    'status' => 406,
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
        $data_input = $request->input('data', null);
        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            $rules = [
                'especialidad' => 'string|max:45'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_ActualizarEntrenador ?,?', [
                    $id,
                    $data['especialidad'] ?? null
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'Entrenador actualizado correctamente'
                ]);
            } else {
                return response()->json([
                    'status' => 406,
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
        \DB::statement('EXEC pa_BorrarEntrenador ?', [$id]);
        return response()->json([
            'status' => 200,
            'message' => 'Entrenador eliminado correctamente'
        ]);
    }
}
