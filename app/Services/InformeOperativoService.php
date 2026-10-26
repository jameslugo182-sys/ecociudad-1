<?php

namespace App\Services;

use App\Models\Camion;
use App\Models\Contrato;
use App\Models\LecturaTacho;
use App\Models\MantenimientoVehiculo;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\Tacho;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InformeOperativoService
{
    public const SECCIONES = ['resumen', 'incidencias', 'rutas', 'tachos', 'personal'];

    /**
     * @return array{fecha_desde: string, fecha_hasta: string, etiqueta: string, desde: Carbon, hasta: Carbon}
     */
    public function periodo(Request $request): array
    {
        $validated = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'estado' => ['nullable', 'string', 'max:40'],
        ]);

        $desde = isset($validated['fecha_desde'])
            ? Carbon::parse($validated['fecha_desde'])->startOfDay()
            : now()->startOfMonth();
        $hasta = isset($validated['fecha_hasta'])
            ? Carbon::parse($validated['fecha_hasta'])->endOfDay()
            : now()->endOfDay();

        return [
            'fecha_desde' => $desde->toDateString(),
            'fecha_hasta' => $hasta->toDateString(),
            'etiqueta' => $desde->format('d/m/Y').' – '.$hasta->format('d/m/Y'),
            'estado' => $validated['estado'] ?? '',
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * @param  array{fecha_desde: string, fecha_hasta: string, etiqueta: string, estado?: string}  $periodo
     * @return array{fecha_desde: string, fecha_hasta: string, etiqueta: string, estado: string}
     */
    public function filtrosVista(array $periodo): array
    {
        return [
            'fecha_desde' => $periodo['fecha_desde'],
            'fecha_hasta' => $periodo['fecha_hasta'],
            'etiqueta' => $periodo['etiqueta'],
            'estado' => $periodo['estado'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resumen(Carbon $desde, Carbon $hasta): array
    {
        $conteos = $this->entreFechas(Reporte::query(), 'created_at', $desde, $hasta)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $total = (int) $conteos->sum();
        $atendidos = (int) ($conteos['Atendido'] ?? 0);
        $kmRutas = (float) $this->entreFechas(RutaRecoleccion::query(), 'fecha', $desde, $hasta)
            ->sum('distancia_estimada_km');

        return [
            'resumen' => [
                'total' => $total,
                'atendidos' => $atendidos,
                'tasaAtencion' => $total === 0 ? 0 : round(($atendidos / $total) * 100, 1),
                'promedioHoras' => $this->promedioHorasAtencion($desde, $hasta),
                'personalActivo' => User::query()
                    ->where('activo', true)
                    ->where('rol', '!=', 'ciudadano')
                    ->count(),
                'vehiculosDisponibles' => Camion::query()->where('estado', 'Disponible')->count(),
                'rutasPeriodo' => $this->entreFechas(RutaRecoleccion::query(), 'fecha', $desde, $hasta)->count(),
                'kmEstimados' => round($kmRutas, 1),
                'tachosAlerta' => Tacho::query()->where('alerta_activa', true)->count(),
                'lecturasPeriodo' => $this->entreFechas(LecturaTacho::query(), 'created_at', $desde, $hasta)->count(),
                'contratosVigentes' => Contrato::query()->where('estado', 'Vigente')->count(),
            ],
            'porEstado' => $this->conteosEstado($conteos),
            'porTipo' => $this->totalesPorTipo($desde, $hasta),
            'porMes' => $this->totalesPorMes(),
            'porOrigen' => $this->totalesPorOrigen($desde, $hasta),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function incidencias(Carbon $desde, Carbon $hasta, ?string $estado = null, bool $paginar = true): array
    {
        $consulta = $this->entreFechas(
            Reporte::query()->with(['user:id,name', 'responsable:id,name', 'tacho:id,codigo,nombre']),
            'created_at',
            $desde,
            $hasta,
        )
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->latest();

        $listado = $paginar
            ? $consulta->paginate(15)->withQueryString()
            : $consulta->limit(200)->get();

        return [
            'incidencias' => $paginar
                ? $listado->through(fn (Reporte $reporte) => $this->filaIncidencia($reporte))
                : $listado->map(fn (Reporte $reporte) => $this->filaIncidencia($reporte))->values(),
            'estados' => ['Pendiente', 'Asignado', 'En atención', 'Atendido'],
            'porEstado' => $this->conteosEstado(
                $this->entreFechas(Reporte::query(), 'created_at', $desde, $hasta)
                    ->selectRaw('estado, COUNT(*) as total')
                    ->groupBy('estado')
                    ->pluck('total', 'estado')
            ),
            'porTipo' => $this->totalesPorTipo($desde, $hasta),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rutas(Carbon $desde, Carbon $hasta): array
    {
        $rutas = $this->entreFechas(
            RutaRecoleccion::query()->with(['camion:id,codigo,placa', 'reportes:id']),
            'fecha',
            $desde,
            $hasta,
        )
            ->latest('fecha')
            ->limit(100)
            ->get()
            ->map(fn (RutaRecoleccion $ruta) => [
                'id' => $ruta->id,
                'fecha' => $ruta->fecha?->format('d/m/Y'),
                'estado' => $ruta->estado,
                'camion' => $ruta->camion?->codigo.' · '.($ruta->camion?->placa ?? 's/placa'),
                'paradas' => $ruta->reportes->count(),
                'km' => round((float) $ruta->distancia_estimada_km, 1),
                'horario' => $ruta->horario_resumen,
            ]);

        $porEstado = $this->entreFechas(RutaRecoleccion::query(), 'fecha', $desde, $hasta)
            ->selectRaw('estado, COUNT(*) as total, COALESCE(SUM(distancia_estimada_km), 0) as km')
            ->groupBy('estado')
            ->get()
            ->map(fn ($fila) => [
                'label' => (string) $fila->estado,
                'total' => (int) $fila->total,
                'km' => round((float) $fila->km, 1),
            ]);

        $camiones = Camion::query()->withCount('personal')->orderBy('codigo')->get();
        $mantenimientos = $this->entreFechas(
            MantenimientoVehiculo::query()->with(['camion:id,codigo,placa', 'tipo:id,nombre']),
            'iniciado_at',
            $desde,
            $hasta,
        )
            ->latest('iniciado_at')
            ->limit(50)
            ->get()
            ->map(fn (MantenimientoVehiculo $item) => [
                'id' => $item->id,
                'camion' => $item->camion?->codigo,
                'tipo' => $item->tipo?->nombre ?? 'Sin tipo',
                'estado' => $item->estado,
                'inicio' => $item->iniciado_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);

        return [
            'rutas' => $rutas,
            'porEstado' => $porEstado,
            'resumenRutas' => [
                'total' => $rutas->count() === 100
                    ? $this->entreFechas(RutaRecoleccion::query(), 'fecha', $desde, $hasta)->count()
                    : $rutas->count(),
                'km' => round((float) $porEstado->sum('km'), 1),
                'activas' => RutaRecoleccion::query()->activas()->count(),
                'vehiculos' => $camiones->count(),
                'disponibles' => $camiones->where('estado', 'Disponible')->count(),
                'equiposCompletos' => $camiones->filter(fn (Camion $camion) => $camion->tieneEquipoCompleto())->count(),
                'enMantenimiento' => $camiones->where('estado', 'Mantenimiento')->count(),
            ],
            'flota' => $camiones->map(fn (Camion $camion) => [
                'id' => $camion->id,
                'codigo' => $camion->codigo,
                'placa' => $camion->placa,
                'estado' => $camion->estado,
                'personal' => $camion->personal_count,
            ]),
            'mantenimientos' => $mantenimientos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tachos(Carbon $desde, Carbon $hasta): array
    {
        $tachos = Tacho::query()->orderByDesc('alerta_activa')->orderByDesc('nivel_porcentaje')->orderBy('codigo')->get();
        $porEstado = $tachos->groupBy('estado')->map(fn (Collection $grupo, string $estado) => [
            'label' => $estado,
            'total' => $grupo->count(),
        ])->values();

        $lecturas = $this->entreFechas(LecturaTacho::query(), 'created_at', $desde, $hasta)
            ->selectRaw('tacho_id, COUNT(*) as total, AVG(nivel_porcentaje) as promedio')
            ->groupBy('tacho_id')
            ->get()
            ->keyBy('tacho_id');

        return [
            'resumenTachos' => [
                'total' => $tachos->count(),
                'alertas' => $tachos->where('alerta_activa', true)->count(),
                'llenos' => $tachos->where('estado', Tacho::ESTADO_LLENO)->count(),
                'lecturasPeriodo' => $this->entreFechas(LecturaTacho::query(), 'created_at', $desde, $hasta)->count(),
                'nivelPromedio' => round((float) ($tachos->whereNotNull('nivel_porcentaje')->avg('nivel_porcentaje') ?? 0), 1),
            ],
            'porEstado' => $porEstado,
            'tachos' => $tachos->map(function (Tacho $tacho) use ($lecturas) {
                $lectura = $lecturas->get($tacho->id);

                return [
                    'id' => $tacho->id,
                    'codigo' => $tacho->codigo,
                    'nombre' => $tacho->nombre,
                    'estado' => $tacho->estado,
                    'nivel' => $tacho->nivel_porcentaje,
                    'alerta' => $tacho->alerta_activa,
                    'lecturas' => (int) ($lectura->total ?? 0),
                    'promedioPeriodo' => isset($lectura->promedio) ? round((float) $lectura->promedio, 1) : null,
                    'ultimaLectura' => $tacho->ultima_lectura_at
                        ? $tacho->ultima_lectura_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                        : null,
                ];
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function personal(): array
    {
        $usuarios = User::query()
            ->where('rol', '!=', 'ciudadano')
            ->orderBy('rol')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'rol', 'activo']);

        $porRol = $usuarios->groupBy('rol')->map(fn (Collection $grupo, string $rol) => [
            'label' => $rol,
            'total' => $grupo->count(),
        ])->values();

        $contratos = Contrato::query()
            ->with(['colaborador', 'cargo:id,nombre'])
            ->latest('fecha_inicio')
            ->limit(80)
            ->get()
            ->map(fn (Contrato $contrato) => [
                'id' => $contrato->id,
                'numero' => $contrato->numero,
                'colaborador' => trim(($contrato->colaborador?->nombres ?? '').' '.($contrato->colaborador?->apellido_paterno ?? '')),
                'cargo' => $contrato->cargo?->nombre,
                'tipo' => $contrato->tipo,
                'estado' => $contrato->estado,
                'inicio' => $contrato->fecha_inicio?->format('d/m/Y'),
                'fin' => $contrato->fecha_fin?->format('d/m/Y'),
            ]);

        $porEstadoContrato = Contrato::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
            ->map(fn ($fila) => [
                'label' => (string) $fila->estado,
                'total' => (int) $fila->total,
            ]);

        return [
            'resumenPersonal' => [
                'cuentas' => $usuarios->count(),
                'activas' => $usuarios->where('activo', true)->count(),
                'ciudadanos' => User::query()->where('rol', 'ciudadano')->count(),
                'contratos' => Contrato::query()->count(),
                'vigentes' => Contrato::query()->where('estado', 'Vigente')->count(),
            ],
            'porRol' => $porRol,
            'usuarios' => $usuarios->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
                'activo' => $user->activo,
            ]),
            'contratos' => $contratos,
            'porEstadoContrato' => $porEstadoContrato,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosSeccion(string $seccion, array $periodo, bool $paginar = true): array
    {
        $desde = $periodo['desde'];
        $hasta = $periodo['hasta'];

        return match ($seccion) {
            'incidencias' => $this->incidencias($desde, $hasta, $periodo['estado'] ?: null, $paginar),
            'rutas' => $this->rutas($desde, $hasta),
            'tachos' => $this->tachos($desde, $hasta),
            'personal' => $this->personal(),
            default => $this->resumen($desde, $hasta),
        };
    }

    public function tituloSeccion(string $seccion): string
    {
        return match ($seccion) {
            'incidencias' => 'Informe de incidencias',
            'rutas' => 'Informe de rutas y flota',
            'tachos' => 'Informe de tachos IoT',
            'personal' => 'Informe de personal y contratos',
            default => 'Resumen operativo',
        };
    }

    private function promedioHorasAtencion(Carbon $desde, Carbon $hasta): float
    {
        $tiempos = $this->entreFechas(
            Reporte::query()->where('estado', 'Atendido')->whereNotNull('atendido_at'),
            'created_at',
            $desde,
            $hasta,
        )->get(['created_at', 'atendido_at']);

        if ($tiempos->isEmpty()) {
            return 0.0;
        }

        return round($tiempos->avg(
            fn (Reporte $reporte) => $reporte->created_at->diffInMinutes($reporte->atendido_at) / 60
        ), 1);
    }

    /**
     * @param  Collection<string, int|string>  $conteos
     * @return Collection<int, array{label: string, total: int}>
     */
    private function conteosEstado(Collection $conteos): Collection
    {
        return collect(['Pendiente', 'Asignado', 'En atención', 'Atendido'])
            ->map(fn (string $estado) => [
                'label' => $estado,
                'total' => (int) ($conteos[$estado] ?? 0),
            ]);
    }

    /**
     * @return Collection<int, array{label: string, total: int}>
     */
    private function totalesPorTipo(Carbon $desde, Carbon $hasta): Collection
    {
        return $this->entreFechas(Reporte::query(), 'created_at', $desde, $hasta)
            ->selectRaw("COALESCE(NULLIF(tipo_incidencia, ''), 'Por clasificar') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(tipo_incidencia, ''), 'Por clasificar')")
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($fila) => [
                'label' => (string) $fila->label,
                'total' => (int) $fila->total,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, total: int}>
     */
    private function totalesPorMes(): Collection
    {
        $desde = now()->subMonths(5)->startOfMonth();
        $fechas = Reporte::query()
            ->where('created_at', '>=', $desde)
            ->pluck('created_at');

        return collect(range(5, 0))->map(function (int $mesesAtras) use ($fechas) {
            $fecha = now()->subMonths($mesesAtras);

            return [
                'label' => $fecha->format('m/Y'),
                'total' => $fechas->filter(
                    fn (Carbon $creado) => $creado->isSameMonth($fecha)
                )->count(),
            ];
        });
    }

    /**
     * @return Collection<int, array{label: string, total: int}>
     */
    private function totalesPorOrigen(Carbon $desde, Carbon $hasta): Collection
    {
        $iot = $this->entreFechas(Reporte::query()->whereNotNull('tacho_id'), 'created_at', $desde, $hasta)->count();
        $ciudadanas = $this->entreFechas(Reporte::query()->whereNull('tacho_id'), 'created_at', $desde, $hasta)->count();

        return collect([
            ['label' => 'Ciudadanas', 'total' => $ciudadanas],
            ['label' => 'Contenedor IoT', 'total' => $iot],
        ]);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function entreFechas($query, string $columna, Carbon $desde, Carbon $hasta)
    {
        return $query
            ->whereDate($columna, '>=', $desde->toDateString())
            ->whereDate($columna, '<=', $hasta->toDateString());
    }

    /**
     * @return array<string, mixed>
     */
    private function filaIncidencia(Reporte $reporte): array
    {
        return [
            'id' => $reporte->id,
            'codigo' => '#'.$reporte->id,
            'ciudadano' => $reporte->user?->name ?? '—',
            'tipo' => $reporte->tipo_incidencia ?: 'Por clasificar',
            'estado' => $reporte->estado,
            'origen' => $reporte->tacho_id ? 'IoT '.$reporte->tacho?->codigo : 'Ciudadano',
            'responsable' => $reporte->responsable?->name,
            'registrado' => $reporte->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'atendido' => $reporte->atendido_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }
}
