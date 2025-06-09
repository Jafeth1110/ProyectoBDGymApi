<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;

class MantenimientoController extends Controller
{
    public function index()
    {
        $mantenimientos = Mantenimiento::all();

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
                'descripcion' => 'required|string|max:100|unique:mantenimiento,descripcion',
                'costo' => 'required|integer'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                $mantenimiento = Mantenimiento::create($data);

                $response = [
                    'status' => 201,
                    'message' => 'Mantenimiento registrado :)',
                    'mantenimiento' => $mantenimiento
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
        $mantenimiento = Mantenimiento::find($id);

        if ($mantenimiento) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del mantenimiento :)',
                'mantenimiento' => $mantenimiento
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Mantenimiento no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function destroy($id)
    {
        $deleted = Mantenimiento::destroy($id);

        if ($deleted) {
            $response = [
                'status' => 200,
                'message' => 'Mantenimiento eliminado correctamente :)'
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
        $mantenimiento = Mantenimiento::find($id);

        if ($mantenimiento) {
            $data_input = $request->input('data', null);

            if ($data_input) {
                $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

                $rules = [
                    'descripcion' => 'string|max:100|unique:mantenimiento,descripcion,' . $id . ',idMantenimiento',
                    'costo' => 'integer'
                ];

                $isValid = \validator($data, $rules);

                if (!$isValid->fails()) {
                    if (isset($data['descripcion'])) $mantenimiento->descripcion = $data['descripcion'];
                    if (isset($data['costo'])) $mantenimiento->costo = $data['costo'];

                    $mantenimiento->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Mantenimiento actualizado :)',
                        'mantenimiento' => $mantenimiento
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
        } else {
            $response = [
                'status' => 404,
                'message' => 'Mantenimiento no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }
}
