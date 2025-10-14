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
        // Obtener el usuario logueado del token JWT
        $jwt = new \App\Helpers\JwtAuth();
        $token = $request->bearerToken();
        $userDecoded = $jwt->checkToken($token, true);
        
        if (!$userDecoded || !is_object($userDecoded)) {
            return response()->json([
                'status' => 401,
                'message' => 'Token inválido o expirado'
            ], 401);
        }
        
        // Verificar que el usuario logueado sea admin
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$userDecoded->email]);
        if (!$user || $user[0]->idRol != 1) {
            return response()->json([
                'status' => 403,
                'message' => 'Solo los administradores pueden crear mantenimientos'
            ], 403);
        }
        
        // Obtener el idAdmin del usuario logueado
        $admin = \DB::select('SELECT idAdmin FROM admin WHERE idUsuario = ?', [$user[0]->idUsuario]);
        if (!$admin) {
            return response()->json([
                'status' => 404,
                'message' => 'No se encontró el registro de administrador para este usuario'
            ], 404);
        }
        
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = is_array($data_input) ? array_map('trim', $data_input) : array_map('trim', json_decode($data_input, true));

            $rules = [
                'descripcion' => 'required|string|max:100',
                'costo' => 'required|integer'
                // Removido 'idAdmin' porque se auto-detecta
            ];

            $isValid = \validator($data, $rules);

            if (!$isValid->fails()) {
                \DB::statement('EXEC pa_CrearMantenimiento ?,?,?', [
                    $data['descripcion'],
                    $data['costo'],
                    $admin[0]->idAdmin  // Usar el admin logueado
                ]);

                $response = [
                    'status' => 201,
                    'message' => 'Mantenimiento registrado exitosamente',
                    'admin_info' => [
                        'nombre' => $user[0]->nombre,
                        'apellido' => $user[0]->apellido,
                        'email' => $user[0]->email
                    ]
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
    
    /**
     * Obtener información del admin logueado
     */
    public function getCurrentAdmin(Request $request)
    {
        $jwt = new \App\Helpers\JwtAuth();
        $token = $request->bearerToken();
        $userDecoded = $jwt->checkToken($token, true);
        
        if (!$userDecoded || !is_object($userDecoded)) {
            return response()->json([
                'status' => 401,
                'message' => 'Token inválido o expirado'
            ], 401);
        }
        
        // Obtener el usuario completo
        $user = \DB::select('EXEC pa_ObtenerUsuarioPorEmail ?', [$userDecoded->email]);
        if (!$user || $user[0]->idRol != 1) {
            return response()->json([
                'status' => 403,
                'message' => 'El usuario no es administrador'
            ], 403);
        }
        
        // Obtener el registro de admin
        $admin = \DB::select('SELECT idAdmin FROM admin WHERE idUsuario = ?', [$user[0]->idUsuario]);
        if (!$admin) {
            return response()->json([
                'status' => 404,
                'message' => 'No se encontró el registro de administrador'
            ], 404);
        }
        
        return response()->json([
            'status' => 200,
            'message' => 'Admin encontrado',
            'data' => [
                'idAdmin' => $admin[0]->idAdmin,
                'idUsuario' => $user[0]->idUsuario,
                'nombre' => $user[0]->nombre,
                'apellido' => $user[0]->apellido,
                'email' => $user[0]->email
            ]
        ]);
    }
}
