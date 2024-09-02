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
            $table->id(); // Llave primaria automática como 'id'
            $table->unsignedBigInteger('producto_id'); // Relación con Producto
            $table->unsignedBigInteger('proveedor_id'); // Relación con Proveedor
            $table->string('codigo_lote');
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->integer('cantidad');
            $table->boolean('espirable')->default(false);
            $table->boolean('isActive')->default(true);
            $table->timestamps();

            // Claves foráneas
            $table->foreign('producto_id')->references('id_producto')->on('producto')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedor')->onDelete('cascade');
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
