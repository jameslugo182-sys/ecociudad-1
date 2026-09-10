<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMantenimiento extends Model
{
    protected $table = 'tipos_mantenimiento';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(MantenimientoVehiculo::class, 'tipo_mantenimiento_id');
    }
}
