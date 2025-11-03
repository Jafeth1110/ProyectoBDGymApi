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
        try {
            $entrenadores = \DB::select('EXEC pa_ObtenerEntrenadores');
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $entrenadores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener los entrenadores: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $entrenador = \DB::select('EXEC pa_ObtenerEntrenadorID ?', [$id]);
            
            if (empty($entrenador)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Entrenador no encontrado'
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $entrenador[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener el entrenador: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data_input = $request->input('data', null);
            
            if (!$data_input) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se encontró el objeto data'
                ], 400);
            }

            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            
            $rules = [
                'idUsuario' => 'required|integer',
                'especialidad' => 'required|string|max:45'
            ];
            
            $isValid = \validator($data, $rules);
            
            if ($isValid->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $isValid->errors()
                ], 400);
            }

            \DB::select('EXEC pa_CrearEntrenador ?,?', [
                $data['idUsuario'],
                $data['especialidad']
            ]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Entrenador creado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear el entrenador: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $data_input = $request->input('data', null);
            
            if (!$data_input) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se encontraron datos para actualizar'
                ], 400);
            }

            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            
            $rules = [
                'especialidad' => 'required|string|max:45'
            ];
            
            $isValid = \validator($data, $rules);
            
            if ($isValid->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $isValid->errors()
                ], 400);
            }

            \DB::select('EXEC pa_ActualizarEntrenador ?,?', [
                $id,
                $data['especialidad']
            ]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Entrenador actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar el entrenador: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            \DB::select('EXEC pa_BorrarEntrenador ?', [$id]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Entrenador eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar el entrenador: ' . $e->getMessage()
            ], 500);
        }
    }
}
