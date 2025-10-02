<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agency extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'expires_at',
        'active',
        'max_cotizadores',
        'max_users',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_agency')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function cotizadores(): HasMany
    {
        return $this->hasMany(Cotizador::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canAddCotizador(): bool
    {
        return $this->cotizadores()->count() < $this->max_cotizadores;
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }
    public function cotizaciones()
{
    return $this->hasMany(Cotizacion::class);
}
}