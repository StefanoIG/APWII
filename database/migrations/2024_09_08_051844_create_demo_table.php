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
        Schema::create('demo', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->unsignedBigInteger('usuario_id')->nullable(); // Relación con la tabla usuarios
            $table->softDeletes(); // Agregar columna para soft deletes
            $table->timestamps(); // Corrección de duplicado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo');
    }
};
