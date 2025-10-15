<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Membresia;

class MembresiaController extends Controller
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
            $membresias = \DB::select('EXEC pa_ObtenerMembresias');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener las membresías: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'tipoMem' => 'required|string|max:45',
                'fechaVenc' => 'required|date|after:fechaInicio',
                'fechaInicio' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_CrearMembresia ?, ?, ?, ?', [
                $data['idCliente'],
                $data['tipoMem'],
                $data['fechaVenc'],
                $data['fechaInicio']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Membresía creada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear la membresía: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $membresia = \DB::select('EXEC pa_ObtenerMembresiaID ?', [$id]);
            
            if (empty($membresia)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Membresía no encontrada'
                ]);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresia[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener la membresía: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'tipoMem' => 'required|string|max:45',
                'fechaVenc' => 'required|date|after:fechaInicio',
                'fechaInicio' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            \DB::select('EXEC pa_ActualizarMembresia ?, ?, ?, ?, ?', [
                $id,
                $data['idCliente'],
                $data['tipoMem'],
                $data['fechaVenc'],
                $data['fechaInicio']
            ]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Membresía actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar la membresía: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            \DB::select('EXEC pa_BorrarMembresia ?', [$id]);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Membresía eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar la membresía: ' . $e->getMessage()
            ]);
        }
    }
}