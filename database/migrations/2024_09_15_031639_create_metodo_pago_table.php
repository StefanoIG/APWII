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
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_metodo'); // Nombre del método de pago (PayPal, transferencia bancaria, criptomonedas, etc.)
            $table->string('descripcion')->nullable(); // Descripción opcional
            $table->timestamps();  
            $table->softDeletes(); // Columna para soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metodo_pago');
    }
};
