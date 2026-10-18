<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaTacho extends Model
{
    protected $table = 'lecturas_tacho';

    protected $fillable = [
        'tacho_id',
        'distancia_cm',
        'nivel_porcentaje',
    ];

    protected function casts(): array
    {
        return [
            'distancia_cm' => 'float',
            'nivel_porcentaje' => 'integer',
        ];
    }

    public function tacho(): BelongsTo
    {
        return $this->belongsTo(Tacho::class);
    }
}
