<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'colaborador_id',
        'cargo_id',
        'area_id',
        'sede_id',
        'numero',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'remuneracion',
        'jornada_horas',
        'area',
        'sede',
        'documento_path',
        'estado',
        'observaciones',
    ];

    protected $appends = ['documento_url'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'remuneracion' => 'decimal:2',
            'jornada_horas' => 'integer',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function areaUnidad(): BelongsTo
    {
        return $this->belongsTo(AreaUnidad::class, 'area_id');
    }

    public function sedeTrabajo(): BelongsTo
    {
        return $this->belongsTo(SedeTrabajo::class, 'sede_id');
    }

    public function getDocumentoUrlAttribute(): ?string
    {
        return $this->documento_path
            ? url('storage/'.ltrim($this->documento_path, '/'))
            : null;
    }
}
