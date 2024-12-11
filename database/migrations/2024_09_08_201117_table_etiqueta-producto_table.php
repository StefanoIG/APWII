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
        //
        Schema::create('etiqueta_producto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('etiqueta_id');
            $table->timestamps();
            $table->softDeletes();
        
            // Llaves foráneas
            $table->foreign('producto_id')->references('id_producto')->on('producto')->onDelete('cascade');
            $table->foreign('etiqueta_id')->references('id_etiqueta')->on('etiqueta')->onDelete('cascade');  // Cambia a 'id_etiqueta'
        });
        
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
