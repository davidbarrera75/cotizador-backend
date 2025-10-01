<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizador extends Model
{

    protected $table = 'cotizadores'; // 👈 AGREGAR ESTA LÍNEA
    protected $fillable = [
        'slug',
    'hotel_name',
    'hotel_slogan',
    'price_validity',
    'whatsapp_number',
    'operator_info',
    'plans',
    'adicionales',
    'info_sections',
    'active',
    'last_export_at',
    'export_filename',
    ];

    protected $casts = [
        'plans' => 'array',
    'adicionales' => 'array',
    'info_sections' => 'array',
    'active' => 'boolean',
    'last_export_at' => 'datetime',
    ];
}