<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Models\User;

class UserController extends Controller
{
    public function index() {
        $users = User::all();

        return response()->json([
            'status' => 200,
            'message' => 'Todos los usuarios :)',
            'data' => $users
        ]);
    }

    public function store(Request $request) {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'idUsuario' => 'required|alpha_num|unique:users',
                'nombre' => 'required|regex:/^[\pL\s]+$/u',
                'apellido' => 'required|regex:/^[\pL\s]+$/u',
                'cedula' => 'required|numeric|digits_between:8,12',
                'email' => 'required|email|unique:users',
                'password' => 'required|alpha_num|min:6',
                'rol' => 'required|alpha'
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                $user = new User();
                $user->idUsuario = $data['idUsuario'];
                $user->nombre = $data['nombre'];
                $user->apellido = $data['apellido'];
                $user->cedula = $data['cedula'];
                $user->email = $data['email'];
                $user->password = hash('sha256', $data['password']);
                $user->rol = $data['rol'];
                $user->save();

                $response = [
                    'status' => 201,
                    'message' => 'Usuario registrado :)',
                    'user' => $user
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

    public function show($email) {
        $user = User::where('email', $email)->first();

        if ($user) {
            $response = [
                'status' => 200,
                'message' => 'Detalles del usuario :)',
                'user' => $user
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'Usuario no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function destroy($email) {
        if ($email) {
            $deleted = User::where('email', $email)->delete();

            if ($deleted) {
                $response = [
                    'status' => 200,
                    'message' => 'Usuario eliminado correctamente :)'
                ];
            } else {
                $response = [
                    'status' => 404,
                    'message' => 'Usuario no encontrado >:('
                ];
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'Email inválido >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function update(Request $request, $email) {
        $user = User::where('email', $email)->first();

        if ($user) {
            $data_input = $request->input('data', null);

            if ($data_input) {
                $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

                $rules = [
                    'idUsuario' => 'alpha_num',
                    'nombre' => 'regex:/^[\pL\s]+$/u',
                    'apellido' => 'regex:/^[\pL\s]+$/u',
                    'cedula' => 'numeric|digits_between:8,12',
                    'email' => 'email',
                    'password' => 'alpha_num|min:6',
                    'rol' => 'alpha'
                ];

                $isValid = \validator($data, $rules);

                if (!$isValid->fails()) {
                    if (isset($data['idUsuario'])) $user->idUsuario = $data['idUsuario'];
                    if (isset($data['nombre'])) $user->nombre = $data['nombre'];
                    if (isset($data['apellido'])) $user->apellido = $data['apellido'];
                    if (isset($data['cedula'])) $user->cedula = $data['cedula'];
                    if (isset($data['email'])) $user->email = $data['email'];
                    if (isset($data['password'])) $user->password = hash('sha256', $data['password']);
                    if (isset($data['rol'])) $user->rol = $data['rol'];

                    $user->save();

                    $response = [
                        'status' => 200,
                        'message' => 'Usuario actualizado :)',
                        'user' => $user
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
                'message' => 'Usuario no encontrado >:('
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function login(Request $request) {
        $data_input = $request->input('data', null);
        $data = array_map('trim', $data_input);

        $rules = [
            'email' => 'required',
            'password' => 'required'
        ];

        $isValid = \validator($data, $rules);

        if (!$isValid->fails()) {
            $jwt = new JwtAuth();
            $response = $jwt->getToken($data['email'], $data['password']);
            return response()->json($response);
        } else {
            $response = [
                'status' => 406,
                'message' => 'Error en la validación de los datos >:(',
                'errors' => $isValid->errors()
            ];
        }

        return response()->json($response, $response['status'] ?? 406);
    }

    public function getIdentity(Request $request) {
        $jwt = new JwtAuth();
        $token = $request->header('bearertoken');

        if ($token) {
            $response = $jwt->checkToken($token, true);
        } else {
            $response = [
                'status' => 404,
                'message' => 'Token (bearertoken) no encontrado >:('
            ];
        }

        return response()->json($response);
    }
}
