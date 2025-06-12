<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = Admin::with('user')->get();

        $result = $admins->map(function ($admin) {
            return [
                'idAdmin'   => $admin->idAdmin,
                'idUsuario' => $admin->user->idUsuario,
                'nombre'    => $admin->user->nombre,
                'apellido'  => $admin->user->apellido,
                'email'     => $admin->user->email,
                'rol'       => $admin->user->rol,
            ];
        });

        return response()->json($result);
    }
}
