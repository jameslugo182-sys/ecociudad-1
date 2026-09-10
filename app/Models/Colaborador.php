<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Colaborador extends Model
{
    protected $table = 'colaboradores';

    protected $fillable = [
        'documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'sexo',
        'estado_civil',
        'nacionalidad',
        'fecha_nacimiento',
        'telefono',
        'email',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'contacto_emergencia',
        'telefono_emergencia',
        'nivel_educativo',
        'institucion_estudios',
        'especialidad',
        'grado_titulo',
        'anio_egreso',
        'activo',
    ];

    protected $appends = ['nombre_completo'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioPersonal::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return collect([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->join(' ');
    }
}
