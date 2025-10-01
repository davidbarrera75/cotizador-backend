<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizadores', function (Blueprint $table) {
            $table->timestamp('last_export_at')->nullable()->after('active');
            $table->string('export_filename')->nullable()->after('last_export_at');
        });
    }

    public function down(): void
    {
        Schema::table('cotizadores', function (Blueprint $table) {
            $table->dropColumn(['last_export_at', 'export_filename']);
        });
    }
};