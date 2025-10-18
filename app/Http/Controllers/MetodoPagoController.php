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
                'nombre' => 'required|string|max:45',
                'descripcion' => 'nullable|string|max:100',
                'comision' => 'required|numeric|min:0|max:100',
                'requiereAutorizacion' => 'required|boolean',
                'estado' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_CrearMetodoPago ?, ?, ?, ?, ?', [
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['comision'],
                $data['requiereAutorizacion'] ? 1 : 0,
                $data['estado'] ? 1 : 0
            ]);

            // El procedimiento siempre retorna un resultado con código y mensaje
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

            // Fallback si no hay resultado (no debería pasar)
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
                'nombre' => 'required|string|max:45',
                'descripcion' => 'nullable|string|max:100',
                'comision' => 'required|numeric|min:0|max:100',
                'requiereAutorizacion' => 'required|boolean',
                'estado' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_ActualizarMetodoPago ?, ?, ?, ?, ?, ?', [
                $id,
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['comision'],
                $data['requiereAutorizacion'] ? 1 : 0,
                $data['estado'] ? 1 : 0
            ]);

            // El procedimiento siempre retorna un resultado con código y mensaje
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

            // Fallback si no hay resultado (no debería pasar)
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
            $result = \DB::select('EXEC pa_BorrarMetodoPago ?', [$id]);

            // El procedimiento siempre retorna un resultado con código y mensaje
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

            // Fallback si no hay resultado (no debería pasar)
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