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
        Schema::create('usuarios', function (Blueprint $table) {  // El nombre de la tabla en plural es una convención
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono');
            $table->string('cedula')->unique();
            $table->string('correo_electronico')->unique();
            $table->string('password');
            $table->enum('rol', ['empleado','demo', 'owner','admin'])->default('empleado');  // El rol por defecto es empleado
            $table->softDeletes(); // Agregar columna para soft deletes

            //llaves fk
            $table->unsignedBigInteger('id_sitio')->nullable(); // Relación opcional
            $table->foreign('id_sitio')->references('id_sitio')->on('sitio')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
