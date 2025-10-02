<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    
    protected $fillable = [
        'cotizador_id',
        'agency_id',
        'plan_selected',
        'num_adultos',
        'num_ninos',
        'acomodacion',
        'precio_base',
        'precio_adicionales',
        'precio_total',
        'noches_adicionales',
        'almuerzos_adicionales',
        'cenas_adicionales',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'notes',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'precio_adicionales' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    public function cotizador(): BelongsTo
    {
        return $this->belongsTo(Cotizador::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
    
    public function getCustomerFullInfoAttribute(): string
    {
        $info = [];
        if ($this->customer_name) $info[] = $this->customer_name;
        if ($this->customer_phone) $info[] = $this->customer_phone;
        if ($this->customer_email) $info[] = $this->customer_email;
        
        return !empty($info) ? implode(' - ', $info) : 'Sin datos de contacto';
    }
    
    public function getWhatsappUrlAttribute(): ?string
    {
        if (!$this->customer_phone) {
            return null;
        }
        
        // Limpiar el número (solo dígitos)
        $phone = preg_replace('/[^0-9]/', '', $this->customer_phone);
        
        return "https://wa.me/{$phone}";
    }
}