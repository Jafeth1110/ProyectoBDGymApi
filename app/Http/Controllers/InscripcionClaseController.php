<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\InscripcionClase;

class InscripcionClaseController extends Controller
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
            $inscripciones = \DB::select('EXEC pa_ObtenerInscripcionesClase');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $inscripciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener las inscripciones: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'idEntrenador' => 'required|integer',
                'idClase' => 'required|integer',
                'fechaInscripcion' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_CrearInscripcionClase ?, ?, ?, ?', [
                $data['idCliente'],
                $data['idEntrenador'],
                $data['idClase'],
                $data['fechaInscripcion']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Inscripción creada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear la inscripción: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $inscripcion = \DB::select('EXEC pa_ObtenerInscripcionClaseID ?', [$id]);
            
            if (empty($inscripcion)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Inscripción no encontrada'
                ]);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $inscripcion[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener la inscripción: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'idEntrenador' => 'required|integer',
                'idClase' => 'required|integer',
                'fechaInscripcion' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_ActualizarInscripcionClase ?, ?, ?, ?, ?', [
                $id,
                $data['idCliente'],
                $data['idEntrenador'],
                $data['idClase'],
                $data['fechaInscripcion']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Inscripción actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar la inscripción: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            \DB::select('EXEC pa_BorrarInscripcionClase ?', [$id]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Inscripción eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar la inscripción: ' . $e->getMessage()
            ]);
        }
    }
}