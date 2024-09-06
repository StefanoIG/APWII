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
        Schema::table('lote', function (Blueprint $table) {
            // Eliminar las claves foráneas incorrectas
            $table->dropForeign(['proveedor_id']);
            
            // Renombrar la columna proveedor_id a id_proveedor
            $table->renameColumn('proveedor_id', 'id_proveedor');
            
            // Crear la clave foránea correcta
            $table->foreign('id_proveedor')->references('id_proveedor')->on('proveedor')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lote', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropForeign(['id_proveedor']);
            $table->renameColumn('id_proveedor', 'proveedor_id');
            $table->foreign('proveedor_id')->references('id')->on('proveedor')->onDelete('cascade');
        });
    }
};
