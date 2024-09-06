<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('producto', function (Blueprint $table) {
            $table->string('codigo_barra')->nullable()->after('id');
        });

        Schema::table('lote', function (Blueprint $table) {
            $table->string('codigo_barra')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('producto', function (Blueprint $table) {
            $table->dropColumn('codigo_barra');
        });

        Schema::table('lote', function (Blueprint $table) {
            $table->dropColumn('codigo_barra');
        });
    }
};
