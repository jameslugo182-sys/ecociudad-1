<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camion extends Model
{
    protected $table = 'camiones';

    protected $fillable = [
        'codigo',
        'placa',
        'marca',
        'modelo',
        'anio',
        'capacidad_kg',
        'estado',
        'foto_path',
    ];

    protected $appends = [
        'foto_url',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'capacidad_kg' => 'integer',
        ];
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path
            ? url('storage/'.ltrim($this->foto_path, '/'))
            : null;
    }

    public function personal(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('puesto')
            ->withTimestamps();
    }

    public function tieneEquipoCompleto(): bool
    {
        $this->loadMissing('personal');

        return $this->personal->count() === 4
            && $this->personal->where('pivot.puesto', 'conductor')->count() === 1
            && $this->personal->where('pivot.puesto', 'recolector')->count() === 3;
    }

    public function rutas(): HasMany
    {
        return $this->hasMany(RutaRecoleccion::class);
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(MantenimientoVehiculo::class);
    }

    public function enMantenimiento(): bool
    {
        return $this->estado === 'Mantenimiento'
            || $this->mantenimientos()->where('estado', 'En curso')->exists();
    }

    public function scopeDisponiblesParaEquipo($query)
    {
        return $query
            ->where('estado', '!=', 'Mantenimiento')
            ->whereDoesntHave('mantenimientos', fn ($subquery) => $subquery->where('estado', 'En curso'));
    }

    public function scopeDisponiblesParaMantenimiento($query)
    {
        return $query->whereDoesntHave(
            'mantenimientos',
            fn ($subquery) => $subquery->where('estado', 'En curso')
        );
    }
}
