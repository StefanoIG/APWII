<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetornoTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('retorno', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_comprobante'); // Clave foránea
            $table->unsignedBigInteger('id_producto'); // Clave foránea
            $table->date('fecha_retorno')->nullable();
            $table->integer('cantidad');
            $table->text('motivo_retorno');
            $table->text('estado_retorno');
            $table->timestamps();
            $table->softDeletes(); // Agregar columna para soft deletes

            // Definir claves foráneas dentro del closure
            $table->foreign('id_comprobante')->references('id_comprobante')->on('comprobante')->onDelete('cascade');
            $table->foreign('id_producto')->references('id_producto')->on('producto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retorno');
    }
}