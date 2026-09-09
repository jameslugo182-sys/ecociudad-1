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
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'capacidad_kg' => 'integer',
        ];
    }

    public function personal(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('puesto')
            ->withTimestamps();
    }

    public function rutas(): HasMany
    {
        return $this->hasMany(RutaRecoleccion::class);
    }
}
