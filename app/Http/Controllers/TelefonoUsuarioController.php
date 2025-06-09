<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelefonoUsuario;

class TelefonoUsuarioController extends Controller
{
    public function index()
    {
        $telefonos = TelefonoUsuario::all();

        return response()->json([
            'status' => 200,
            'message' => 'Todos los teléfonos de usuarios :)',
            'data' => $telefonos
        ]);
    }

    public function store(Request $request)
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'idUsuario' => 'required|integer',
                'tipoTel' => 'required|string|max:20',
                'telefono' => 'required|string|max:45|unique:telefonousuario,telefono'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                $telefono = TelefonoUsuario::create($data);

                $response = [
                    'status' => 201,
                    'message' => 'Teléfono registrado :)',
                    'telefono' => $telefono
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
        $telefono = TelefonoUsuario::find($id);

        if ($telefono) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del teléfono :)',
                'telefono' => $telefono
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Teléfono no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function destroy($id)
    {
        $deleted = TelefonoUsuario::destroy($id);

        if ($deleted) {
            $response = [
                'status' => 200,
                'message' => 'Teléfono eliminado correctamente :)'
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Teléfono no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $id)
    {
        $telefono = TelefonoUsuario::find($id);

        if ($telefono) {
            $data_input = $request->input('data', null);

            if ($data_input) {
                $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

                $rules = [
                    'idUsuario' => 'integer',
                    'tipoTel' => 'string|max:20',
                    'telefono' => 'string|max:45|unique:telefonousuario,telefono,' . $id . ',idTelefonoUsuario'
                ];

                $isValid = \validator($data, $rules);

                if (!$isValid->fails()) {
                    if (isset($data['idUsuario'])) $telefono->idUsuario = $data['idUsuario'];
                    if (isset($data['tipoTel'])) $telefono->tipoTel = $data['tipoTel'];
                    if (isset($data['telefono'])) $telefono->telefono = $data['telefono'];

                    $telefono->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Teléfono actualizado :)',
                        'telefono' => $telefono
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
                'message' => 'Teléfono no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }
}
