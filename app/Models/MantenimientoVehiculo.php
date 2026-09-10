<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoVehiculo extends Model
{
    protected $table = 'mantenimientos_vehiculo';

    protected $fillable = [
        'camion_id',
        'tipo_mantenimiento_id',
        'solicitado_por',
        'finalizado_por',
        'motivo',
        'estado',
        'estado_anterior',
        'iniciado_at',
        'finalizado_at',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_at' => 'datetime',
            'finalizado_at' => 'datetime',
        ];
    }

    public function camion(): BelongsTo
    {
        return $this->belongsTo(Camion::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoMantenimiento::class, 'tipo_mantenimiento_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function finalizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por');
    }
}
