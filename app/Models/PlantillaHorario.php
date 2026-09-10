<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaHorario extends Model
{
    protected $table = 'plantillas_horario';

    protected $fillable = [
        'nombre',
        'descripcion',
        'turnos',
        'dias',
        'horas_semanales',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'turnos' => 'array',
            'dias' => 'array',
            'horas_semanales' => 'float',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioPersonal::class);
    }
}
