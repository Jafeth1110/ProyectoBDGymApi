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
        try {
            $clientes = \DB::select('EXEC pa_ObtenerClientes');
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $clientes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener los clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $cliente = \DB::select('EXEC pa_ObtenerClienteID ?', [$id]);
            
            if (empty($cliente)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $cliente[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
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
                'fechaRegistro' => 'required|date'
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

            \DB::select('EXEC pa_CrearCliente ?,?', [
                $data['idUsuario'],
                $data['fechaRegistro']
            ]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Cliente creado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
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
                'fechaRegistro' => 'required|date'
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

            \DB::select('EXEC pa_ActualizarCliente ?,?', [
                $id,
                $data['fechaRegistro']
            ]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Cliente actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            \DB::select('EXEC pa_BorrarCliente ?', [$id]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Cliente eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}
