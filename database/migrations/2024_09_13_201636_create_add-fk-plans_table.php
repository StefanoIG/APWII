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
        //agregar fk de planes a usuarios
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unsignedBigInteger('id_plan')->nullable(); // Relación opcional
            $table->foreign('id_plan')->references('id_plan')->on('planes')->onDelete('set null')->nullable();
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
