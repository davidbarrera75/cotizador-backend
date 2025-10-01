<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizadores', function (Blueprint $table) {
            $table->string('price_validity', 500)->nullable()->after('hotel_slogan');
        });
    }

    public function down(): void
    {
        Schema::table('cotizadores', function (Blueprint $table) {
            $table->dropColumn('price_validity');
        });
    }
};