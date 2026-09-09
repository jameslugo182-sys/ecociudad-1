<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolSistema extends Model
{
    protected $table = 'roles_sistema';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'modulos',
        'protegido',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'modulos' => 'array',
            'protegido' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
