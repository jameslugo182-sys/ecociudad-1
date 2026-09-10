<?php

namespace App\Models;

use App\Services\TrazadoRutaService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RutaRecoleccion extends Model
{
    public const ETIQUETAS_DIAS = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mié',
        4 => 'Jue',
        5 => 'Vie',
        6 => 'Sáb',
        7 => 'Dom',
    ];

    protected $table = 'rutas_recoleccion';

    protected $fillable = [
        'camion_id',
        'generado_por',
        'fecha',
        'estado',
        'distancia_estimada_km',
        'geometria',
        'hora_inicio',
        'hora_fin',
        'dias_recoleccion',
        'horario_nota',
        'iniciada_at',
        'finalizada_at',
    ];

    protected $appends = [
        'horario_resumen',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'distancia_estimada_km' => 'float',
            'geometria' => 'array',
            'dias_recoleccion' => 'array',
            'iniciada_at' => 'datetime',
            'finalizada_at' => 'datetime',
        ];
    }

    protected function horaInicio(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? substr($value, 0, 5) : null,
        );
    }

    protected function horaFin(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? substr($value, 0, 5) : null,
        );
    }

    public function getHorarioResumenAttribute(): ?string
    {
        $inicio = $this->hora_inicio;
        $fin = $this->hora_fin;
        $dias = $this->dias_recoleccion ?? [];

        if (! $inicio || ! $fin || $dias === []) {
            return null;
        }

        return $this->resumenDias($dias).' · '.$inicio.'–'.$fin;
    }

    /**
     * @param  array<int, int|string>  $dias
     */
    private function resumenDias(array $dias): string
    {
        $ordenados = collect($dias)
            ->map(fn ($dia) => (int) $dia)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($ordenados === [1, 2, 3, 4, 5]) {
            return 'Lun–Vie';
        }

        if ($ordenados === [1, 2, 3, 4, 5, 6, 7]) {
            return 'Todos los días';
        }

        return collect($ordenados)
            ->map(fn (int $dia) => self::ETIQUETAS_DIAS[$dia] ?? '')
            ->filter()
            ->implode(', ');
    }

    public function camion(): BelongsTo
    {
        return $this->belongsTo(Camion::class);
    }

    public function generador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function reportes(): BelongsToMany
    {
        return $this->belongsToMany(Reporte::class, 'reporte_ruta')
            ->withPivot(['orden', 'distancia_desde_anterior_km'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    public function completarTrazadoVial(): static
    {
        if (! empty($this->geometria) || $this->reportes->isEmpty()) {
            return $this;
        }

        $trazado = app(TrazadoRutaService::class)->trazarEnOrden($this->reportes->all());

        $this->forceFill([
            'geometria' => $trazado['geometria'],
            'distancia_estimada_km' => $trazado['distancia_km'] ?: $this->distancia_estimada_km,
        ])->save();

        return $this;
    }
}
