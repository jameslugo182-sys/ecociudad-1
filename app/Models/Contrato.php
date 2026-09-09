<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'colaborador_id',
        'cargo_id',
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

    public function getDocumentoUrlAttribute(): ?string
    {
        return $this->documento_path
            ? Storage::disk('public')->url($this->documento_path)
            : null;
    }
}
