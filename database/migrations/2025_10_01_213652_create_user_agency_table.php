<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_agency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('user'); // owner, admin, user
            $table->timestamps();
            
            $table->unique(['user_id', 'agency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_agency');
    }
};