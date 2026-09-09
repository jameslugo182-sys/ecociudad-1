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
        'fecha_nacimiento',
        'telefono',
        'email',
        'direccion',
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

    public function getNombreCompletoAttribute(): string
    {
        return collect([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->join(' ');
    }
}
