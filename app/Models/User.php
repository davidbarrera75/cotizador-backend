<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin', // Agregado
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean', // Agregado
    ];

    /**
     * Relación muchos a muchos con agencias.
     *
     * @return BelongsToMany
     */
    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'user_agency')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Verifica si el usuario es super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    /**
     * Obtiene la agencia actual.
     * Si es super admin, busca la agencia seleccionada en sesión.
     * Si no, devuelve la primera agencia del usuario.
     *
     * @return Agency|null
     */
    public function getCurrentAgency(): ?Agency
    {
        if ($this->is_super_admin) {
            $selectedAgencyId = session('selected_agency_id');
            if ($selectedAgencyId) {
                return Agency::find($selectedAgencyId);
            }
            return null;
        }

        return $this->agencies()->first();
    }

    /**
     * Establece la agencia actual en sesión (solo para super admin).
     *
     * @param int|null $agencyId
     * @return void
     */
    public function setCurrentAgency(?int $agencyId): void
    {
        if ($this->is_super_admin) {
            if ($agencyId) {
                session(['selected_agency_id' => $agencyId]);
            } else {
                session()->forget('selected_agency_id');
            }
        }
    }

    /**
     * Verifica si el usuario tiene acceso a una agencia dada.
     *
     * @param Agency $agency
     * @return bool
     */
    public function hasAccessToAgency(Agency $agency): bool
    {
        return $this->is_super_admin || $this->agencies->contains($agency);
    }
}