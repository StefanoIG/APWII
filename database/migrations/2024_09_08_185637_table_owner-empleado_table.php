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
        Schema::create('owner_empleado', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('sitio_id'); // ID del sitio
            $table->timestamps();

            // Claves foráneas
            $table->foreign('owner_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('empleado_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('sitio_id')->references('id_sitio')->on('sitio')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('owner_empleado');

    }
};
