<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Reporte extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_to',
        'foto_path',
        'evidencia_path',
        'latitud',
        'longitud',
        'descripcion',
        'estado',
        'tipo_incidencia',
        'prioridad',
        'ruta_orden',
        'assigned_at',
        'atendido_at',
    ];

    protected $appends = [
        'foto_url',
        'evidencia_url',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
            'prioridad' => 'integer',
            'ruta_orden' => 'integer',
            'assigned_at' => 'datetime',
            'atendido_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function rutas(): BelongsToMany
    {
        return $this->belongsToMany(RutaRecoleccion::class, 'reporte_ruta')
            ->withPivot(['orden', 'distancia_desde_anterior_km'])
            ->withTimestamps();
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path
            ? Storage::disk('public')->url($this->foto_path)
            : null;
    }

    public function getEvidenciaUrlAttribute(): ?string
    {
        return $this->evidencia_path
            ? Storage::disk('public')->url($this->evidencia_path)
            : null;
    }
}
