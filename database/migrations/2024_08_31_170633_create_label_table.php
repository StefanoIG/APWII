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
        Schema::create('etiqueta', function (Blueprint $table) {
            $table->id('id_etiqueta');  // id primary key
            $table->string('nombre');
            $table->string('color_hex');
            $table->text('descripcion')->nullable();
            $table->string('categoria');
            $table->enum('prioridad', ['alta', 'media', 'baja']);
            $table->boolean('isActive')->default(true);
            $table->timestamps();  // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('etiquetas');
    }
};