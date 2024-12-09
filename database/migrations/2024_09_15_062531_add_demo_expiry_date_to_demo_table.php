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
        Schema::table('demo', function (Blueprint $table) {
            $table->timestamp('demo_expiry_date')->nullable()->after('isActive');
        });
    }

    public function down(): void
    {
        Schema::table('demo', function (Blueprint $table) {
            $table->dropColumn('demo_expiry_date');
        });
    }
};
