<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = \DB::select('EXEC pa_ObtenerClientes');
        return response()->json([
            'status' => 200,
            'message' => 'Lista de clientes',
            'data' => $clientes
        ]);
    }

    public function show($id)
    {
        $cliente = \DB::select('EXEC pa_ObtenerClienteID ?', [$id]);
        if ($cliente) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del cliente',
                'data' => $cliente[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Cliente no encontrado'
            ];
        }
        return response()->json($response, $response['status']);
    }

    public function store(Request $request)
    {
        $data_input = $request->input('data', null);
        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            $rules = [
                'idUsuario' => 'required|integer',
                'fechaRegistro' => 'nullable|date'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearCliente ?,?', [
                    $data['idUsuario'],
                    $data['fechaRegistro'] ?? null
                ]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Cliente creado correctamente'
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

    public function update(Request $request, $id)
    {
        $data_input = $request->input('data', null);
        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            $rules = [
                'fechaRegistro' => 'nullable|date'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_ActualizarCliente ?,?', [
                    $id,
                    $data['fechaRegistro'] ?? null
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'Cliente actualizado correctamente'
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

    public function destroy($id)
    {
        \DB::statement('EXEC pa_BorrarCliente ?', [$id]);
        return response()->json([
            'status' => 200,
            'message' => 'Cliente eliminado correctamente'
        ]);
    }
}
