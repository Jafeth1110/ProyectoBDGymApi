<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = \DB::select('EXEC pa_ObtenerAdmins');
        return response()->json([
            'status' => 200,
            'message' => 'Lista de administradores',
            'data' => $admins
        ]);
    }

    public function show($id): JsonResponse
    {
        $admin = \DB::select('EXEC pa_ObtenerAdminID ?', [$id]);
        if ($admin) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del administrador',
                'data' => $admin[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Administrador no encontrado'
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
                'idUsuario' => 'required|integer'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearAdmin ?', [$data['idUsuario']]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Administrador creado correctamente'
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
                'idUsuario' => 'sometimes|integer'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_ActualizarAdmin ?,?', [
                    $id,
                    $data['idUsuario'] ?? null
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'Administrador actualizado correctamente'
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
        \DB::statement('EXEC pa_BorrarAdmin ?', [$id]);
        return response()->json([
            'status' => 200,
            'message' => 'Administrador eliminado correctamente'
        ]);
    }
}
