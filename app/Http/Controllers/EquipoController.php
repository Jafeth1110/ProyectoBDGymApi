<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipo;

class EquipoController extends Controller
{
    public function index()
    {
        $equipos = \DB::select('EXEC pa_ObtenerEquipos');
        return response()->json([
            'status' => 200,
            'message' => 'Todos los equipos :)',
            'data' => $equipos
        ]);
    }

    public function store(Request $request)
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'nombre' => 'required|string|max:45',
                'tipo' => 'required|string|max:45',
                'estado' => 'required|integer',
                'cantidad' => 'required|integer|min:1'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearEquipo ?,?,?,?', [
                    $data['nombre'],
                    $data['tipo'],
                    $data['estado'],
                    $data['cantidad']
                ]);
                $response = [
                    'status' => 201,
                    'message' => 'Equipo registrado :)'
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
        $equipo = \DB::select('EXEC pa_ObtenerEquipoID ?', [$id]);

        if ($equipo) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del equipo :)',
                'equipo' => $equipo[0]
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Equipo no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function destroy(Request $request, $id)
    {
        \DB::statement('EXEC pa_BorrarEquipo ?', [$id]);
        $response = [
            'status' => 200,
            'message' => 'Equipo eliminado correctamente :)'
        ];
        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $id)
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'nombre' => 'string|max:45',
                'tipo' => 'string|max:45',
                'estado' => 'integer',
                'cantidad' => 'integer'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_ActualizarEquipo ?,?,?,?,?', [
                    $id,
                    $data['nombre'] ?? null,
                    $data['tipo'] ?? null,
                    $data['estado'] ?? null,
                    $data['cantidad'] ?? null
                ]);
                $response = [
                    'status' => 200,
                    'message' => 'Equipo actualizado :)'
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
}
