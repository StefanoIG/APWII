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
        Schema::create('etiqueta', function (Blueprint $table) {
            $table->id('id_etiqueta');
            $table->string('nombre');
            $table->string('color_hex');
            $table->text('descripcion')->nullable();
            $table->string('categoria');
            $table->enum('prioridad', ['alta', 'media', 'baja']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiqueta');
    }
};
