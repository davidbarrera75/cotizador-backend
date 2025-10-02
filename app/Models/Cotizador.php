<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizador extends Model
{
    protected $table = 'cotizadores'; // 👈 AGREGAR ESTA LÍNEA

    protected $fillable = [
        'agency_id',
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

    /**
     * Relación inversa con Agency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function cotizaciones()
{
    return $this->hasMany(Cotizacion::class);
}
}