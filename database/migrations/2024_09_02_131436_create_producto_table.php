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
        Schema::create('producto', function (Blueprint $table) {
            $table->id('id_producto');
            $table->string('nombre_producto');
            $table->string('tipo_producto');
            $table->string('descripcion_producto');
            $table->decimal('precio');
            //foranea a id_etiqueta de la tabla etiqueta
            $table->unsignedBigInteger('id_etiqueta')->nullable();
            $table->foreign('id_etiqueta')->references('id_etiqueta')->on('etiqueta')->nullable();
            $table->boolean('iSActive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
