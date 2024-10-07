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
        //tabla usuarios agregar un campo estado que sea un enum con valores: deshabilitado y habilitado
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('estado', ['deshabilitado', 'habilitado'])->default('deshabilitado');
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
