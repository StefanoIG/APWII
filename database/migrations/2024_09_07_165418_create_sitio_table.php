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
        Schema::create('sitio', function (Blueprint $table) {
            $table->id('id_sitio');
            $table->string('nombre_sitio');
            $table->string('direccion');
            $table->string('ciudad');
            $table->string('pais');
            $table->unsignedBigInteger('created_by')->nullable(); // Agregar columna para el ID del usuario que creó el sitio y hacerla nulleable

            $table->timestamps();
            $table->softDeletes(); // Agregar columna para soft deletes

            // Agregar la clave foránea
            $table->foreign('created_by')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitio');
    }
};
