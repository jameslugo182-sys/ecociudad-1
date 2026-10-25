<?php

namespace App\Http\Controllers;

use App\Models\RutaRecoleccion;
use App\Models\Tacho;
use App\Services\NivelTachoService;
use App\Services\TrazadoRutaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TachoController extends Controller
{
    public function __construct(
        private readonly NivelTachoService $niveles,
        private readonly TrazadoRutaService $trazado,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in($this->estados())],
        ]);

        $tachos = Tacho::query()
            ->with(['incidenciaAbiertaRelacion.rutas'])
            ->when($filters['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    $subquery
                        ->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('direccion', 'like', "%{$buscar}%");
                });
            })
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->orderByDesc('alerta_activa')
            ->orderByDesc('nivel_porcentaje')
            ->orderBy('codigo')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Tacho $tacho) => $this->resumen($tacho));

        return Inertia::render('Tachos/Index', [
            'tachos' => $tachos,
            'filters' => [
                'buscar' => $filters['buscar'] ?? '',
                'estado' => $filters['estado'] ?? '',
            ],
            'estados' => $this->estados(),
            'siguienteCodigo' => $this->siguienteCodigo(),
            'alertas' => Tacho::query()->where('alerta_activa', true)->count(),
            'rutasActivas' => RutaRecoleccion::query()
                ->activas()
                ->with('camion:id,codigo,placa')
                ->latest('fecha')
                ->get(['id', 'camion_id', 'fecha', 'estado']),
        ]);
    }

    public function mapa(): Response
    {
        return Inertia::render('Tachos/Mapa', [
            'tachos' => Tacho::query()
                ->with(['incidenciaAbiertaRelacion.rutas'])
                ->orderBy('codigo')
                ->get()
                ->map(fn (Tacho $tacho) => $this->resumen($tacho)),
            'alertas' => Tacho::query()->where('alerta_activa', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);
        $tacho = new Tacho($validated);
        $tacho->codigo = $this->siguienteCodigo();
        $tacho->creado_por = $request->user()->id;
        $tacho->estado = Tacho::ESTADO_OPERATIVO;
        $tacho->activo = true;
        $token = $tacho->generarToken();
        $tacho->save();

        return back()
            ->with('success', "Tacho {$tacho->codigo} registrado. Copia el token del ESP32 ahora; no se volverá a mostrar.")
            ->with('device_token', $token)
            ->with('registered', true);
    }

    public function update(Request $request, Tacho $tacho): RedirectResponse
    {
        $validated = $this->validar($request);
        $activo = array_key_exists('activo', $validated)
            ? (bool) $validated['activo']
            : $tacho->activo;

        if (! $activo) {
            $validated['estado'] = Tacho::ESTADO_INACTIVO;
            $validated['alerta_activa'] = false;
        } elseif ($tacho->estado === Tacho::ESTADO_INACTIVO) {
            $validated['estado'] = Tacho::ESTADO_OPERATIVO;
        }

        $tacho->update($validated);

        return back()->with('success', "Tacho {$tacho->codigo} actualizado.");
    }

    public function regenerarToken(Tacho $tacho): RedirectResponse
    {
        $token = $tacho->generarToken();
        $tacho->save();

        return back()
            ->with('success', "Nuevo token para {$tacho->codigo}. Actualízalo en el ESP32.")
            ->with('device_token', $token)
            ->with('registered', true);
    }

    public function agregarARuta(Request $request, Tacho $tacho): RedirectResponse
    {
        if (! $tacho->alerta_activa && ($tacho->nivel_porcentaje ?? 0) < $tacho->umbral_alerta) {
            throw ValidationException::withMessages([
                'ruta_id' => 'Solo se incorporan a ruta los tachos en alerta o llenos.',
            ]);
        }

        $validated = $request->validate([
            'ruta_id' => [
                'required',
                'integer',
                Rule::exists('rutas_recoleccion', 'id')->whereIn('estado', RutaRecoleccion::ESTADOS_ACTIVAS),
            ],
        ], [
            'ruta_id.required' => 'Selecciona una ruta activa de un camión.',
        ]);

        DB::transaction(function () use ($tacho, $validated) {
            $ruta = RutaRecoleccion::query()
                ->whereKey($validated['ruta_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $ruta->load(['reportes', 'camion.personal']);

            $reporte = $this->niveles->abrirIncidencia($tacho->fresh());

            if ($ruta->reportes->contains('id', $reporte->id)) {
                throw ValidationException::withMessages([
                    'ruta_id' => 'Este tacho ya está en esa ruta.',
                ]);
            }

            $enOtra = $reporte->rutas()
                ->whereIn('rutas_recoleccion.estado', RutaRecoleccion::ESTADOS_ACTIVAS)
                ->where('rutas_recoleccion.id', '!=', $ruta->id)
                ->exists();

            if ($enOtra) {
                throw ValidationException::withMessages([
                    'ruta_id' => 'El tacho ya está asignado a otra ruta activa.',
                ]);
            }

            $paradas = $ruta->reportes->all();
            $paradas[] = $reporte;
            $trazado = $this->trazado->trazarEnOrden($paradas);
            $conductor = $ruta->camion->personal->firstWhere('pivot.puesto', 'conductor');
            $puntos = [];

            foreach ($trazado['paradas'] as $indice => $punto) {
                $puntos[$punto['reporte']->id] = [
                    'orden' => $indice + 1,
                    'distancia_desde_anterior_km' => round($punto['distancia'], 2),
                ];
            }

            $ruta->reportes()->sync($puntos);
            $ruta->update([
                'geometria' => $trazado['geometria'],
                'distancia_estimada_km' => $trazado['distancia_km'],
            ]);

            $reporte->update([
                'assigned_to' => $conductor?->id,
                'estado' => 'Asignado',
                'ruta_orden' => count($puntos),
                'assigned_at' => now(),
            ]);
        });

        return back()->with('success', "Tacho {$tacho->codigo} agregado a la ruta del camión.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'distancia_vacio_cm' => ['required', 'integer', 'min:20', 'max:300'],
            'distancia_lleno_cm' => ['required', 'integer', 'min:2', 'max:80', 'lt:distancia_vacio_cm'],
            'umbral_alerta' => ['required', 'integer', 'min:50', 'max:100'],
            'activo' => ['sometimes', 'boolean'],
        ], [
            'nombre.required' => 'Ingresa un nombre para el punto fijo.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'latitud.required' => 'Marca el tacho en el mapa.',
            'longitud.required' => 'Marca el tacho en el mapa.',
            'distancia_lleno_cm.lt' => 'La distancia de lleno debe ser menor que la de vacío (tapa al fondo).',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resumen(Tacho $tacho): array
    {
        $incidencia = $tacho->relationLoaded('incidenciaAbiertaRelacion')
            ? $tacho->incidenciaAbiertaRelacion
            : $tacho->incidenciaAbierta();

        $enRuta = false;
        if ($incidencia && $incidencia->relationLoaded('rutas')) {
            $enRuta = $incidencia->rutas->contains(
                fn ($ruta) => $ruta->estaActiva()
            );
        } elseif ($incidencia) {
            $enRuta = $incidencia->rutas()
                ->whereIn('rutas_recoleccion.estado', RutaRecoleccion::ESTADOS_ACTIVAS)
                ->exists();
        }

        return [
            'id' => $tacho->id,
            'codigo' => $tacho->codigo,
            'nombre' => $tacho->nombre,
            'direccion' => $tacho->direccion,
            'latitud' => $tacho->latitud,
            'longitud' => $tacho->longitud,
            'distancia_vacio_cm' => $tacho->distancia_vacio_cm,
            'distancia_lleno_cm' => $tacho->distancia_lleno_cm,
            'umbral_alerta' => $tacho->umbral_alerta,
            'nivel_porcentaje' => $tacho->nivel_porcentaje,
            'capacidad_restante' => $tacho->capacidadRestante(),
            'distancia_cm' => $tacho->distancia_cm,
            'estado' => $tacho->estado,
            'alerta_activa' => $tacho->alerta_activa,
            'activo' => $tacho->activo,
            'ultima_lectura_at' => $tacho->ultima_lectura_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'incidencia_id' => $incidencia?->id,
            'en_ruta' => $enRuta,
        ];
    }

    /**
     * @return list<string>
     */
    private function estados(): array
    {
        return [
            Tacho::ESTADO_OPERATIVO,
            Tacho::ESTADO_ALERTA,
            Tacho::ESTADO_LLENO,
            Tacho::ESTADO_INACTIVO,
        ];
    }

    private function siguienteCodigo(): string
    {
        $ultimo = Tacho::query()
            ->where('codigo', 'like', 'TCH%')
            ->orderByDesc('id')
            ->value('codigo');

        $numero = 1;
        if (is_string($ultimo) && preg_match('/TCH(\d+)/i', $ultimo, $coincidencias)) {
            $numero = ((int) $coincidencias[1]) + 1;
        }

        return 'TCH'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
    }
}
