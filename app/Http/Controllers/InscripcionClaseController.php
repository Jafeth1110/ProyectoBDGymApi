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
                'idCliente' => 'required|integer|exists:cliente,idCliente',
                'idEntrenador' => 'required|integer|exists:entrenador,idEntrenador',
                'idClase' => 'required|integer|exists:clase,idClase',
                'fechaInscripcion' => 'required|date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Validación adicional: verificar cupo disponible
            $clase = \DB::selectOne('SELECT cupoMax FROM clase WHERE idClase = ?', [$data['idClase']]);
            $inscritos = \DB::selectOne('SELECT COUNT(*) as total FROM inscripcionclase WHERE idClase = ?', [$data['idClase']]);
            
            if ($inscritos->total >= $clase->cupoMax) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'La clase ya alcanzó su cupo máximo'
                ], 400);
            }

            // Validación adicional: verificar que el cliente no esté ya inscrito
            $yaInscrito = \DB::selectOne('SELECT COUNT(*) as total FROM inscripcionclase WHERE idCliente = ? AND idClase = ?', 
                [$data['idCliente'], $data['idClase']]);
            
            if ($yaInscrito->total > 0) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'El cliente ya está inscrito en esta clase'
                ], 400);
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
            ], 500);
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
            // Verificar que la inscripción existe
            $inscripcionExiste = \DB::selectOne('SELECT COUNT(*) as total FROM inscripcionclase WHERE idInscripcionClase = ?', [$id]);
            
            if ($inscripcionExiste->total == 0) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'La inscripción no existe'
                ], 404);
            }

            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer|exists:cliente,idCliente',
                'idEntrenador' => 'required|integer|exists:entrenador,idEntrenador',
                'idClase' => 'required|integer|exists:clase,idClase',
                'fechaInscripcion' => 'required|date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 400);
            }

            \DB::statement('EXEC pa_ActualizarInscripcionClase ?, ?, ?, ?, ?', [
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
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Verificar que la inscripción existe antes de eliminar
            $inscripcionExiste = \DB::selectOne('SELECT COUNT(*) as total FROM inscripcionclase WHERE idInscripcionClase = ?', [$id]);
            
            if ($inscripcionExiste->total == 0) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'La inscripción no existe'
                ], 404);
            }

            \DB::statement('EXEC pa_BorrarInscripcionClase ?', [$id]);

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
            ], 500);
        }
    }
}