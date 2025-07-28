<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(): JsonResponse
    {
        $clientes = Cliente::with(['user', 'user.rol'])->get();

        $result = $clientes->map(function ($cliente) {
            return [
                'idCliente'     => $cliente->idCliente,
                'idUsuario'     => $cliente->user->idUsuario,
                'nombre'        => $cliente->user->nombre,
                'apellido'      => $cliente->user->apellido,
                'email'         => $cliente->user->email,
                'cedula'        => $cliente->user->cedula,
                'rol'           => $cliente->user->rol,  // Ya es una cadena gracias al accessor
                'fechaRegistro' => $cliente->fechaRegistro,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Lista de clientes',
            'data' => $result
        ]);
    }

    public function show($id): JsonResponse
    {
        $cliente = Cliente::with(['user', 'user.rol', 'asistencias', 'membresias'])->find($id);

        if (!$cliente) {
            return response()->json([
                'status' => 404,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $result = [
            'idCliente'     => $cliente->idCliente,
            'idUsuario'     => $cliente->user->idUsuario,
            'nombre'        => $cliente->user->nombre,
            'apellido'      => $cliente->user->apellido,
            'email'         => $cliente->user->email,
            'cedula'        => $cliente->user->cedula,
            'rol'           => $cliente->user->rol->nombreRol,
            'fechaRegistro' => $cliente->fechaRegistro,
            'asistencias'   => $cliente->asistencias,
            'membresias'    => $cliente->membresias,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Detalles del cliente',
            'data' => $result
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'idUsuario' => 'required|exists:users,idUsuario',
                'fechaRegistro' => 'nullable|date'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                // Verificar que el usuario tenga rol de cliente
                $user = User::find($data['idUsuario']);
                if ($user->idRol !== 2) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'El usuario debe tener rol de cliente'
                    ], 400);
                }

                $cliente = new Cliente();
                $cliente->idUsuario = $data['idUsuario'];
                $cliente->fechaRegistro = $data['fechaRegistro'] ?? now()->toDateString();
                $cliente->save();

                return response()->json([
                    'status' => 201,
                    'message' => 'Cliente creado correctamente',
                    'data' => $cliente
                ]);
            } else {
                return response()->json([
                    'status' => 406,
                    'message' => 'Datos inválidos',
                    'errors' => $isValid->errors()
                ], 406);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontró el objeto data'
            ], 400);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'status' => 404,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'fechaRegistro' => 'nullable|date'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                if (isset($data['fechaRegistro'])) $cliente->fechaRegistro = $data['fechaRegistro'];
                $cliente->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Cliente actualizado correctamente',
                    'data' => $cliente
                ]);
            } else {
                return response()->json([
                    'status' => 406,
                    'message' => 'Datos inválidos',
                    'errors' => $isValid->errors()
                ], 406);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'No se encontraron datos para actualizar'
            ], 400);
        }
    }

    public function destroy($id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'status' => 404,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $cliente->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Cliente eliminado correctamente'
        ]);
    }
}
