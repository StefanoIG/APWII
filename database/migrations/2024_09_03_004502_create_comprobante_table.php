<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComprobanteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comprobante', function (Blueprint $table) {
            $table->id('id_comprobante'); // Llave primaria automática como 'id'
            $table->date('fecha_emision')->nullable();
            $table->unsignedBigInteger('id_lote');// Clave foránea
            $table->unsignedBigInteger('usuario_id'); // Clave foránea
            $table->unsignedBigInteger('id_producto'); // Clave foránea
            $table->integer('cantidad');
            $table->integer('precio_total');
            $table->timestamps();
            $table->softDeletes(); // Agregar columna para soft deletes
            // Definir claves foráneas
            $table->foreign('id_lote')->references('id_lote')->on('lote')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_producto')->references('id_producto')->on('producto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante');
    }
}