<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetalleMantenimiento;

class DetalleMantenimientoController extends Controller
{
    public function index()
    {
        $detalles = DetalleMantenimiento::all();

        return response()->json([
            'status' => 200,
            'message' => 'Todos los detalles de mantenimiento :)',
            'data' => $detalles
        ]);
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
                $detalle = DetalleMantenimiento::create($data);

                $response = [
                    'status' => 201,
                    'message' => 'Detalle de mantenimiento registrado :)',
                    'detalle' => $detalle
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
        $detalle = DetalleMantenimiento::find($id);

        if ($detalle) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del mantenimiento :)',
                'detalle' => $detalle
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Detalle de mantenimiento no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function destroy($id)
    {
        $deleted = DetalleMantenimiento::destroy($id);

        if ($deleted) {
            $response = [
                'status' => 200,
                'message' => 'Detalle de mantenimiento eliminado correctamente :)'
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Detalle de mantenimiento no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $id)
    {
        $detalle = DetalleMantenimiento::find($id);

        if ($detalle) {
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
                    if (isset($data['idAdmin'])) $detalle->idAdmin = $data['idAdmin'];
                    if (isset($data['idEquipo'])) $detalle->idEquipo = $data['idEquipo'];
                    if (isset($data['idMantenimiento'])) $detalle->idMantenimiento = $data['idMantenimiento'];
                    if (isset($data['fechaMantenimiento'])) $detalle->fechaMantenimiento = $data['fechaMantenimiento'];

                    $detalle->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Detalle de mantenimiento actualizado :)',
                        'detalle' => $detalle
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
                'message' => 'Detalle de mantenimiento no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }
}
