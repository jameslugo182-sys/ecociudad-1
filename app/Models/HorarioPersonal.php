<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioPersonal extends Model
{
    protected $table = 'horarios_personal';

    protected $fillable = [
        'colaborador_id',
        'plantilla_horario_id',
        'turnos',
        'dias',
        'horas_semanales',
        'vigencia_inicio',
        'vigencia_fin',
        'estado',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'turnos' => 'array',
            'dias' => 'array',
            'horas_semanales' => 'float',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaHorario::class, 'plantilla_horario_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
