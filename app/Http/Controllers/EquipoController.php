<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipo;

class EquipoController extends Controller
{
    public function index()
    {
        $equipos = Equipo::all();

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
                // Buscar si ya existe un equipo con el mismo nombre y tipo
                $equipo = Equipo::where('nombre', $data['nombre'])
                    ->where('tipo', $data['tipo'])
                    ->first();

                if ($equipo) {
                    // Si existe, solo suma la cantidad
                    $equipo->cantidad += $data['cantidad'];
                    $equipo->estado = $data['estado']; // Puedes actualizar el estado si lo deseas
                    $equipo->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Cantidad actualizada :)',
                        'equipo' => $equipo
                    ];
                } else {
                    // Si no existe, crea uno nuevo
                    $equipo = Equipo::create($data);

                    $response = [
                        'status' => 201,
                        'message' => 'Equipo registrado :)',
                        'equipo' => $equipo
                    ];
                }
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
        $equipo = Equipo::find($id);

        if ($equipo) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del equipo :)',
                'equipo' => $equipo
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
        $cantidad = $request->input('cantidad', null);
        $equipo = Equipo::find($id);

        if ($equipo) {
            if ($cantidad && is_numeric($cantidad) && $cantidad > 0) {
                if ($equipo->cantidad > $cantidad) {
                    $equipo->cantidad -= $cantidad;
                    $equipo->save();
                    $response = [
                        'status' => 200,
                        'message' => "Se eliminaron $cantidad unidades del equipo :)",
                        'equipo' => $equipo
                    ];
                } else {
                    // Si la cantidad a eliminar es igual o mayor, elimina el registro
                    $equipo->delete();
                    $response = [
                        'status' => 200,
                        'message' => 'Equipo eliminado completamente :)'
                    ];
                }
            } else {
                // Si no se especifica cantidad, elimina todo el registro
                $equipo->delete();
                $response = [
                    'status' => 200,
                    'message' => 'Equipo eliminado completamente :)'
                ];
            }
        } else {
            $response = [
                'status' => 404,
                'message' => 'Equipo no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $id)
    {
        $equipo = Equipo::find($id);

        if ($equipo) {
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
                    if (isset($data['nombre'])) $equipo->nombre = $data['nombre'];
                    if (isset($data['tipo'])) $equipo->tipo = $data['tipo'];
                    if (isset($data['estado'])) $equipo->estado = $data['estado'];
                    if (isset($data['cantidad'])) $equipo->cantidad = $data['cantidad'];

                    $equipo->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Equipo actualizado :)',
                        'equipo' => $equipo
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
                'message' => 'Equipo no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }
}
