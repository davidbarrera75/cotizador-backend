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
    Schema::create('cotizadores', function (Blueprint $table) {
        $table->id();
        $table->string('slug')->unique();
        $table->string('hotel_name');
        $table->text('hotel_slogan');
        $table->string('whatsapp_number');
        $table->string('operator_info');
        $table->json('plans');
        $table->json('adicionales');
        $table->json('info_sections');
        $table->boolean('active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizadors');
    }
};
