<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos de telefonoadministrador
        $telefonosAdmin = DB::table('telefonoadministrador')
            ->join('admin', 'telefonoadministrador.idAdmin', '=', 'admin.idAdmin')
            ->join('users', 'admin.idUsuario', '=', 'users.idUsuario')
            ->select(
                'users.idUsuario',
                'telefonoadministrador.telefono',
                'telefonoadministrador.tipoTel',
                'users.idRol'
            )
            ->get();

        foreach ($telefonosAdmin as $telefono) {
            DB::table('telefonos')->insert([
                'idUsuario' => $telefono->idUsuario,
                'telefono' => $telefono->telefono,
                'tipoTel' => $telefono->tipoTel,
                'idRol' => $telefono->idRol,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Migrar datos de telefonocliente
        $telefonosCliente = DB::table('telefonocliente')
            ->join('cliente', 'telefonocliente.idCliente', '=', 'cliente.idCliente')
            ->join('users', 'cliente.idUsuario', '=', 'users.idUsuario')
            ->select(
                'users.idUsuario',
                'telefonocliente.telefono',
                'telefonocliente.tipoTel',
                'users.idRol'
            )
            ->get();

        foreach ($telefonosCliente as $telefono) {
            DB::table('telefonos')->insert([
                'idUsuario' => $telefono->idUsuario,
                'telefono' => $telefono->telefono,
                'tipoTel' => $telefono->tipoTel,
                'idRol' => $telefono->idRol,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Migrar datos de telefonoentrenador
        $telefonosEntrenador = DB::table('telefonoentrenador')
            ->join('entrenador', 'telefonoentrenador.idEntrenador', '=', 'entrenador.idEntrenador')
            ->join('users', 'entrenador.idUsuario', '=', 'users.idUsuario')
            ->select(
                'users.idUsuario',
                'telefonoentrenador.telefono',
                'telefonoentrenador.tipoTel',
                'users.idRol'
            )
            ->get();

        foreach ($telefonosEntrenador as $telefono) {
            DB::table('telefonos')->insert([
                'idUsuario' => $telefono->idUsuario,
                'telefono' => $telefono->telefono,
                'tipoTel' => $telefono->tipoTel,
                'idRol' => $telefono->idRol,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En caso de rollback, eliminar todos los datos de la tabla telefonos
        DB::table('telefonos')->truncate();
    }
};
