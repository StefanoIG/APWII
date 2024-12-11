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
        Schema::create('proveedor', function (Blueprint $table) {
            $table->id("id_proveedor");
            $table->string("nombre", 255);
            $table->string("direccion", 255);
            $table->string("email",255);
            $table->string("telefono", 255);
            $table->string("Cuidad", 255);
            $table->boolean("Activo",)->default(true);
            $table->softDeletes(); // Agregar columna para soft deletes
            $table->timestamps();

            // Relación con la tabla de sitios
            $table->unsignedBigInteger('sitio_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedor');
    }
};
