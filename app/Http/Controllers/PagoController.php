<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Pago;

class PagoController extends Controller
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
            $pagos = \DB::select('EXEC pa_ObtenerPagos');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $pagos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener los pagos: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idMembresia' => 'required|integer',
                'idMetodoPago' => 'required|integer',
                'fechaPago' => 'required|date',
                'monto' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_CrearPago ?, ?, ?, ?', [
                $data['idMembresia'],
                $data['idMetodoPago'],
                $data['fechaPago'],
                $data['monto']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Pago registrado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $pago = \DB::select('EXEC pa_ObtenerPagoID ?', [$id]);
            
            if (empty($pago)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Pago no encontrado'
                ]);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $pago[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener el pago: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idMembresia' => 'required|integer',
                'idMetodoPago' => 'required|integer',
                'fechaPago' => 'required|date',
                'monto' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_ActualizarPago ?, ?, ?, ?, ?', [
                $id,
                $data['idMembresia'],
                $data['idMetodoPago'],
                $data['fechaPago'],
                $data['monto']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Pago actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar el pago: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            \DB::select('EXEC pa_BorrarPago ?', [$id]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Pago eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar el pago: ' . $e->getMessage()
            ]);
        }
    }
}