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
        Schema::table('tenants', function (Blueprint $table) {
            // Agregar la columna 'name' como identificador del tenant
            $table->string('name')->unique()->after('id')->nullable();

            // Agregar la columna 'database_path' para almacenar la ruta del archivo SQLite o detalles de la base de datos
            $table->string('database_path')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['name', 'database_path']);
        });
    }
};
