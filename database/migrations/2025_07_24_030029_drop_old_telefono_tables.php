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
        // Eliminar las tablas antiguas de teléfonos en el orden correcto para evitar errores de foreign key
        Schema::dropIfExists('telefonoadministrador');
        Schema::dropIfExists('telefonocliente');
        Schema::dropIfExists('telefonoentrenador');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear las tablas en caso de rollback
        
        // Tabla telefonoadministrador
        Schema::create('telefonoadministrador', function (Blueprint $table) {
            $table->id('idTelefonoAdmin');
            $table->unsignedInteger('idAdmin');
            $table->string('tipoTel', 20);
            $table->string('telefono', 45)->unique();
            
            $table->foreign('idAdmin')->references('idAdmin')->on('admin')->onDelete('cascade')->onUpdate('cascade');
            $table->index('idAdmin');
        });
        
        // Tabla telefonocliente
        Schema::create('telefonocliente', function (Blueprint $table) {
            $table->id('idTelefonoCliente');
            $table->unsignedInteger('idCliente');
            $table->string('tipoTel', 20);
            $table->string('telefono', 45)->unique();
            
            $table->foreign('idCliente')->references('idCliente')->on('cliente')->onDelete('cascade')->onUpdate('cascade');
            $table->index('idCliente');
        });
        
        // Tabla telefonoentrenador
        Schema::create('telefonoentrenador', function (Blueprint $table) {
            $table->id('idTelefonoEntrenador');
            $table->unsignedInteger('idEntrenador');
            $table->string('tipoTel', 20);
            $table->string('telefono', 45)->unique();
            
            $table->foreign('idEntrenador')->references('idEntrenador')->on('entrenador')->onDelete('cascade')->onUpdate('cascade');
            $table->index('idEntrenador');
        });
    }
};
