<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cotizador_id')->constrained('cotizadores')->onDelete('cascade');
        $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            
            // Datos de la cotización
            $table->string('plan_selected');
            $table->integer('num_adultos')->default(0);
            $table->integer('num_ninos')->default(0);
            $table->string('acomodacion')->nullable();
            $table->decimal('precio_base', 12, 2)->default(0);
            $table->decimal('precio_adicionales', 12, 2)->default(0);
            $table->decimal('precio_total', 12, 2)->default(0);
            
            // Servicios adicionales
            $table->integer('noches_adicionales')->default(0);
            $table->integer('almuerzos_adicionales')->default(0);
            $table->integer('cenas_adicionales')->default(0);
            
            // Datos del cliente (opcionales)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            
            // Seguimiento
            $table->enum('status', ['nuevo', 'contactado', 'negociando', 'cerrado', 'perdido'])->default('nuevo');
            $table->text('notes')->nullable();
            $table->enum('source', ['web', 'whatsapp_click'])->default('whatsapp_click');
            
            // Metadata
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Índices para búsqueda rápida
            $table->index(['agency_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};