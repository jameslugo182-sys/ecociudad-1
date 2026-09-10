<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SedeTrabajo extends Model
{
    protected $table = 'sedes_trabajo';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'sede_id');
    }
}
