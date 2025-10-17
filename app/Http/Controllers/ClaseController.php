<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Clase;

class ClaseController extends Controller
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
            $clases = \DB::select('EXEC pa_ObtenerClases');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $clases
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener las clases: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'diaSemana' => 'required|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
                'hora' => 'required|date_format:H:i',
                'nombre' => 'required|string|max:45',
                'descripcion' => 'nullable|string|max:255',
                'cupoMax' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_CrearClase ?, ?, ?, ?, ?', [
                $data['diaSemana'],
                $data['hora'],
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['cupoMax']
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
                'message' => 'Clase creada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear la clase: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $clase = \DB::select('EXEC pa_ObtenerClaseID ?', [$id]);
            
            if (empty($clase)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Clase no encontrada'
                ]);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $clase[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener la clase: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'diaSemana' => 'required|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
                'hora' => 'required|date_format:H:i',
                'nombre' => 'required|string|max:45',
                'descripcion' => 'nullable|string|max:255',
                'cupoMax' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_ActualizarClase ?, ?, ?, ?, ?, ?', [
                $id,
                $data['diaSemana'],
                $data['hora'],
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['cupoMax']
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
                'message' => 'Clase actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar la clase: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $result = \DB::select('EXEC pa_BorrarClase ?', [$id]);

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
                'message' => 'Clase eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar la clase: ' . $e->getMessage()
            ]);
        }
    }
}