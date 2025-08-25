<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;

class MantenimientoController extends Controller
{
    public function index()
    {
        $mantenimientos = \DB::select('EXEC pa_ObtenerMantenimientos');
        return response()->json([
            'status' => 200,
            'message' => 'Todos los mantenimientos :)',
            'data' => $mantenimientos
        ]);
    }

    public function store(Request $request)
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'descripcion' => 'required|string|max:100',
                'costo' => 'required|integer',
                'idAdmin' => 'required|integer'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearMantenimiento ?,?,?', [
                    $data['descripcion'],
                    $data['costo'],
                    $data['idAdmin']
                ]);

                $response = [
                    'status' => 201,
                    'message' => 'Mantenimiento registrado :)'
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

    public function show($id)
    {
        $mantenimiento = \DB::select('EXEC pa_ObtenerMantenimientoID ?', [$id]);

        if ($mantenimiento) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del mantenimiento :)',
                'mantenimiento' => $mantenimiento[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Mantenimiento no encontrado >:('
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
                'descripcion' => 'string|max:100',
                'costo' => 'integer',
                'idAdmin' => 'integer'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_ActualizarMantenimiento ?,?,?,?', [
                    $id,
                    $data['descripcion'] ?? null,
                    $data['costo'] ?? null,
                    $data['idAdmin'] ?? null
                ]);

                $response = [
                    'status' => 200,
                    'message' => 'Mantenimiento actualizado :)'
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
        \DB::statement('EXEC pa_BorrarMantenimiento ?', [$id]);
        $response = [
            'status' => 200,
            'message' => 'Mantenimiento eliminado correctamente :)'
        ];
        return response()->json($response, $response['status']);
    }
}
