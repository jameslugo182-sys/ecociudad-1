<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaUnidad extends Model
{
    protected $table = 'areas_unidad';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'area_id');
    }
}
