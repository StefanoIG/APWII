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
        Schema::create('planes', function (Blueprint $table) {
            $table->id('id_plan');
            $table->string('name');          // Nombre del plan
            $table->decimal('price', 8, 2);  // Precio del plan
            $table->integer('duration');     // Duración en días
            $table->string('features');      // Características del plan (puede ser JSON o texto)
            $table->timestamps();
            $table->enum('prioridad', ['alta', 'media', 'baja'])->default('media');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
