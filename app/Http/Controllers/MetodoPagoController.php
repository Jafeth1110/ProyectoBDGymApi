<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\MetodoPago;

class MetodoPagoController extends Controller
{
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

    public function index()
    {
        try {
            $metodosPago = \DB::select('EXEC pa_ObtenerMetodosPago');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $metodosPago
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener los métodos de pago: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'nombre' => 'required|string|max:45'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_CrearMetodoPago ?, ?', [
                $data['idCliente'],
                $data['nombre']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Método de pago creado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear el método de pago: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $metodoPago = \DB::select('EXEC pa_ObtenerMetodoPagoID ?', [$id]);
            
            if (empty($metodoPago)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Método de pago no encontrado'
                ]);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $metodoPago[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener el método de pago: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'nombre' => 'required|string|max:45'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_ActualizarMetodoPago ?, ?, ?', [
                $id,
                $data['idCliente'],
                $data['nombre']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Método de pago actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar el método de pago: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            \DB::select('EXEC pa_BorrarMetodoPago ?', [$id]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Método de pago eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar el método de pago: ' . $e->getMessage()
            ]);
        }
    }
}