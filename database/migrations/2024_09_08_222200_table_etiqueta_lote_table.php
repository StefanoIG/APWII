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
        Schema::create('etiqueta_lote', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id');
            $table->unsignedBigInteger('etiqueta_id');
            $table->timestamps();
            $table->softDeletes();

            // Definir las llaves foráneas
            $table->foreign('lote_id')->references('id_lote')->on('lote')->onDelete('cascade');
            $table->foreign('etiqueta_id')->references('id_etiqueta')->on('etiqueta')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiqueta_lote');
    }
};
