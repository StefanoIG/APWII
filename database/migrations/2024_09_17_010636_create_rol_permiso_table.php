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
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('rol_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreignId('permiso_id')->references('id')->on('permisos')->onDelete('cascade');
            $table->primary(['rol_id', 'permiso_id']); //pk compuesta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
    }
};
