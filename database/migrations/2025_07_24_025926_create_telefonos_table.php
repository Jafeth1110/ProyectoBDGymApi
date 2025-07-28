<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telefonos', function (Blueprint $table) {
            $table->id('idTelefono');
            $table->integer('idUsuario')->unsigned();
            $table->string('telefono', 45)->unique();
            $table->string('tipoTel', 20);
            $table->integer('idRol')->unsigned();
            $table->timestamps();
            
            // Indexes
            $table->index(['idUsuario', 'idRol']);
            $table->index('telefono');
        });
        
        // Agregar foreign keys después de crear la tabla
        Schema::table('telefonos', function (Blueprint $table) {
            $table->foreign('idUsuario')->references('idUsuario')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('idRol')->references('idRol')->on('roles')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telefonos');
    }
};
