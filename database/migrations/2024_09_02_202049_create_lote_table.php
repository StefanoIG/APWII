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
        Schema::create('lote', function (Blueprint $table) {
            $table->id('id_lote'); // Llave primaria automática como 'id_lote'
            $table->unsignedBigInteger('id_producto'); // Relación con Producto
            $table->unsignedBigInteger('id_proveedor'); // Relación con Proveedor
            $table->unsignedBigInteger('id_sitio')->nullable(); // Relación opcional
            $table->string('codigo_lote');
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->integer('cantidad');
            $table->boolean('expirable')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Claves foráneas
            $table->foreign('id_producto')->references('id_producto')->on('producto')->onDelete('cascade');
            $table->foreign('id_proveedor')->references('id_proveedor')->on('proveedor')->onDelete('cascade');
            $table->foreign('id_sitio')->references('id_sitio')->on('sitio')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote');
    }
};
