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
        Schema::table('sitio', function (Blueprint $table) {
            // Asegúrate de que 'created_by' ya esté creado como nullable en la migración anterior, solo necesitas agregar la FK
            $table->unsignedBigInteger('created_by')->nullable(); // Agregar columna para el ID del usuario que creó el sitio y hacerla nulleable

            $table->foreign('created_by')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sitio', function (Blueprint $table) {
            // Eliminar la clave foránea en caso de rollback
            $table->dropForeign(['created_by']);
        });
    }
};
