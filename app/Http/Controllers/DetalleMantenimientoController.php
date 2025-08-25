<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetalleMantenimiento;

class DetalleMantenimientoController extends Controller
{
    public function index()
    {
        $detalles = \DB::select('EXEC pa_ObtenerDetalleMantenimientos');
        return response()->json([
            'status' => 200,
            'message' => 'Todos los detalles de mantenimiento :)',
            'data' => $detalles
        ]);
    }

    public function show($id)
    {
        $detalle = \DB::select('EXEC pa_ObtenerDetalleMantenimientoID ?', [$id]);
        if ($detalle) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del mantenimiento :)',
                'detalle' => $detalle[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Detalle de mantenimiento no encontrado >:('
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
                'idAdmin' => 'required|integer',
                'idEquipo' => 'required|integer',
                'idMantenimiento' => 'required|integer',
                'fechaMantenimiento' => 'required|date'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearDetalleMantenimiento ?,?,?,?', [
                    $data['idAdmin'],
                    $data['idEquipo'],
                    $data['idMantenimiento'],
                    $data['fechaMantenimiento']
                ]);
                $response = [
                    'status' => 201,
                    'message' => 'Detalle de mantenimiento registrado :)'
                ];
            } else {
                $response = [
                    'status' => 406,
                    'message' => 'Datos inválidos >:(',
                    'errors' => $isValid->errors()
                ];
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'No se encontró el objeto data >:('
            ];
        }
        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $id)
    {
        $data_input = $request->input('data', null);
        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));
            $rules = [
                'idAdmin' => 'integer',
                'idEquipo' => 'integer',
                'idMantenimiento' => 'integer',
                'fechaMantenimiento' => 'date'
            ];
            $isValid = \validator($data, $rules);
            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_ActualizarDetalleMantenimiento ?,?,?,?,?', [
                    $id,
                    $data['idAdmin'] ?? null,
                    $data['idEquipo'] ?? null,
                    $data['idMantenimiento'] ?? null,
                    $data['fechaMantenimiento'] ?? null
                ]);
                $response = [
                    'status' => 200,
                    'message' => 'Detalle de mantenimiento actualizado :)'
                ];
            } else {
                $response = [
                    'status' => 406,
                    'message' => 'Datos inválidos >:(',
                    'errors' => $isValid->errors()
                ];
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'No se encontraron datos para actualizar >:('
            ];
        }
        return response()->json($response, $response['status']);
    }

    public function destroy($id)
    {
        \DB::statement('EXEC pa_BorrarDetalleMantenimiento ?', [$id]);
        $response = [
            'status' => 200,
            'message' => 'Detalle de mantenimiento eliminado correctamente :)'
        ];
        return response()->json($response, $response['status']);
    }
}
