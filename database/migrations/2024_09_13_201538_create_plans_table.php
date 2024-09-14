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
            $table->string('product_id', 22); // ID del producto en PayPal
            $table->string('name', 127); // Nombre del plan
            $table->text('description')->nullable(); // Descripción del plan
            $table->enum('status', ['CREATED', 'ACTIVE'])->default('CREATED'); // Estado del plan
            $table->json('billing_cycles'); // Ciclos de facturación (en formato JSON)
            $table->json('payment_preferences'); // Preferencias de pago (en formato JSON)
            $table->json('taxes')->nullable(); // Detalles de impuestos (en formato JSON)
            $table->boolean('quantity_supported')->default(false); // Si el plan soporta cantidades
            $table->timestamps();
            $table->enum('prioridad', ['alta', 'media', 'baja'])->default('media');
            $table->string('id_paypal', 50)->nullable(); // Campo para guardar la ID del plan de PayPal
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
