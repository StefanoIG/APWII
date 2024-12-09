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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            
            // Clave foránea correcta para usuarios
            $table->foreignId('usuario_id')->constrained('usuarios'); 
            
            // Resto de los campos
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->string('order_id')->nullable(); 
            $table->string('order_id_paypal')->nullable();
            $table->decimal('total', 10, 2);
            $table->dateTime('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->timestamps();
            $table->softDeletes();
            $table->date('proxima_fecha_pago')->nullable(); // Fecha opcional para periodo de gracia
            $table->date('fecha_gracia')->nullable(); // Fecha opcional para periodo de gracia

        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura');
    }
};
