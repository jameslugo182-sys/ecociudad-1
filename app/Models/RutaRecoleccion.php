<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RutaRecoleccion extends Model
{
    protected $table = 'rutas_recoleccion';

    protected $fillable = [
        'camion_id',
        'generado_por',
        'fecha',
        'estado',
        'distancia_estimada_km',
        'iniciada_at',
        'finalizada_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'distancia_estimada_km' => 'float',
            'iniciada_at' => 'datetime',
            'finalizada_at' => 'datetime',
        ];
    }

    public function camion(): BelongsTo
    {
        return $this->belongsTo(Camion::class);
    }

    public function generador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function reportes(): BelongsToMany
    {
        return $this->belongsToMany(Reporte::class, 'reporte_ruta')
            ->withPivot(['orden', 'distancia_desde_anterior_km'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }
}
