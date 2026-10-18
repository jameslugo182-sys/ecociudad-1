<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Tacho extends Model
{
    public const ESTADO_OPERATIVO = 'Operativo';

    public const ESTADO_ALERTA = 'Alerta';

    public const ESTADO_LLENO = 'Lleno';

    public const ESTADO_INACTIVO = 'Inactivo';

    public const TIPO_INCIDENCIA = 'Contenedor IoT';

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'distancia_vacio_cm',
        'distancia_lleno_cm',
        'umbral_alerta',
        'nivel_porcentaje',
        'distancia_cm',
        'estado',
        'alerta_activa',
        'token_hash',
        'ultima_lectura_at',
        'activo',
        'creado_por',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
            'distancia_vacio_cm' => 'integer',
            'distancia_lleno_cm' => 'integer',
            'umbral_alerta' => 'integer',
            'nivel_porcentaje' => 'integer',
            'distancia_cm' => 'float',
            'alerta_activa' => 'boolean',
            'ultima_lectura_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturaTacho::class);
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }

    public function incidenciaAbiertaRelacion(): HasOne
    {
        return $this->hasOne(Reporte::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('estado', '!=', 'Atendido'),
        );
    }

    public function incidenciaAbierta(): ?Reporte
    {
        if ($this->relationLoaded('incidenciaAbiertaRelacion')) {
            return $this->incidenciaAbiertaRelacion;
        }

        return $this->reportes()
            ->where('estado', '!=', 'Atendido')
            ->latest()
            ->first();
    }

    public function capacidadRestante(): ?int
    {
        if ($this->nivel_porcentaje === null) {
            return null;
        }

        return max(0, 100 - $this->nivel_porcentaje);
    }

    public function generarToken(): string
    {
        $plano = Str::random(40);
        $this->token_hash = Hash::make($plano);

        return $plano;
    }

    public function tokenValido(string $plano): bool
    {
        return $this->token_hash !== '' && Hash::check($plano, $this->token_hash);
    }
}
