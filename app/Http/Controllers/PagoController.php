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
                'data' => $pagos ?? [],
                'total' => count($pagos ?? [])
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

            // Validaciones base
            $rules = [
                'idMetodoPago' => 'required|integer',
                'fechaPago' => 'required|date',
                'monto' => 'required|numeric|min:0',
                'tipoPago' => 'required|in:membresia,mantenimiento',
                'descripcion' => 'nullable|string|max:255'
            ];

            // Validaciones condicionales según tipo de pago
            if (isset($data['tipoPago'])) {
                if ($data['tipoPago'] === 'membresia') {
                    $rules['idMembresia'] = 'required|integer';
                    $rules['idDetalleMantenimiento'] = 'nullable';
                } elseif ($data['tipoPago'] === 'mantenimiento') {
                    $rules['idDetalleMantenimiento'] = 'required|integer';
                    $rules['idMembresia'] = 'nullable';
                }
            }

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_CrearPago ?, ?, ?, ?, ?, ?, ?', [
                $data['idMembresia'] ?? null,
                $data['idMetodoPago'],
                $data['fechaPago'],
                $data['monto'],
                $data['tipoPago'],
                $data['idDetalleMantenimiento'] ?? null,
                $data['descripcion'] ?? null
            ]);

            // El procedimiento retorna código y mensaje
            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo != 200) {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
                
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => $mensaje
                ]);
            }

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

            $result = \DB::select('EXEC pa_ActualizarPago ?, ?, ?, ?, ?', [
                $id,
                $data['idMembresia'],
                $data['idMetodoPago'],
                $data['fechaPago'],
                $data['monto']
            ]);

            // El procedimiento retorna código y mensaje
            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo != 200) {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
                
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => $mensaje
                ]);
            }

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
            $result = \DB::select('EXEC pa_BorrarPago ?', [$id]);

            // El procedimiento retorna código y mensaje
            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo != 200) {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
                
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => $mensaje
                ]);
            }

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