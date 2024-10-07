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
        Schema::create('promociones', function (Blueprint $table) {
            $table->id('id_promocion');
            $table->string('codigo')->unique(); // Código de la promoción
            $table->decimal('descuento', 5, 2); // Descuento en porcentaje
            $table->date('fecha_inicio'); // Fecha de inicio de la promoción
            $table->date('fecha_fin'); // Fecha de fin de la promoción
            $table->boolean('activo')->default(true); // Si la promoción está activa o no
            $table->timestamps();
            $table->softDeletes(); // Columna para soft deletes

        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
