<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\HorarioPersonal;
use App\Models\PlantillaHorario;
use App\Services\HorarioSemanal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HorarioPersonalController extends Controller
{
    public function plantillas(): InertiaResponse
    {
        return Inertia::render('Contratos/Horarios/Plantillas', [
            'plantillas' => PlantillaHorario::query()
                ->with('creador:id,name')
                ->latest()
                ->get(),
        ]);
    }

    public function storePlantilla(Request $request): RedirectResponse
    {
        $validated = $this->validarHorario($request, conNombre: true);

        PlantillaHorario::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'turnos' => $validated['turnos'],
            'dias' => $validated['dias'],
            'horas_semanales' => HorarioSemanal::totalHoras($validated['dias']),
            'creado_por' => $request->user()->id,
        ]);

        return back()
            ->with('success', 'Plantilla de horario registrada.')
            ->with('registered', true);
    }

    public function destroyPlantilla(PlantillaHorario $plantilla): RedirectResponse
    {
        $plantilla->delete();

        return back()->with('success', 'Plantilla eliminada.');
    }

    public function registro(): InertiaResponse
    {
        return Inertia::render('Contratos/Horarios/Registro', [
            'horarios' => HorarioPersonal::query()
                ->with(['colaborador', 'plantilla:id,nombre'])
                ->latest()
                ->get(),
            'colaboradores' => Colaborador::query()
                ->where('activo', true)
                ->whereHas('contratos', fn ($query) => $query->where('estado', 'Vigente'))
                ->orderBy('nombres')
                ->get(['id', 'documento', 'nombres', 'apellido_paterno', 'apellido_materno']),
            'plantillas' => PlantillaHorario::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'turnos', 'dias', 'horas_semanales']),
        ]);
    }

    public function storeRegistro(Request $request): RedirectResponse
    {
        $validated = $this->validarHorario($request, conNombre: false);

        HorarioPersonal::create([
            'colaborador_id' => $validated['colaborador_id'],
            'plantilla_horario_id' => $validated['plantilla_horario_id'] ?? null,
            'turnos' => $validated['turnos'],
            'dias' => $validated['dias'],
            'horas_semanales' => HorarioSemanal::totalHoras($validated['dias']),
            'vigencia_inicio' => $validated['vigencia_inicio'],
            'vigencia_fin' => $validated['vigencia_fin'] ?? null,
            'estado' => 'Vigente',
            'registrado_por' => $request->user()->id,
        ]);

        return back()
            ->with('success', 'Horario asignado al colaborador.')
            ->with('registered', true);
    }

    public function pdf(HorarioPersonal $horario): Response
    {
        $horario->load(['colaborador', 'plantilla:id,nombre']);
        $contrato = $horario->colaborador?->contratos()
            ->with('cargo:id,nombre')
            ->where('estado', 'Vigente')
            ->latest('fecha_inicio')
            ->first();

        $turnos = $horario->turnos ?? [];
        $dias = $horario->dias ?? [];
        $filas = [];

        foreach (HorarioSemanal::DIAS as $diaId => $nombre) {
            $celdas = [];
            $horasDia = 0.0;

            foreach ($turnos as $turno) {
                $celda = HorarioSemanal::celda($dias, $diaId, $turno['clave'] ?? '');
                $celdas[] = HorarioSemanal::rango($celda);
                $horasDia += HorarioSemanal::horasEntre($celda['inicio'] ?? null, $celda['fin'] ?? null);
            }

            $filas[] = [
                'nombre' => $nombre,
                'celdas' => $celdas,
                'horas' => $horasDia,
            ];
        }

        $documento = $horario->colaborador?->documento ?? $horario->id;

        return Pdf::loadView('contratos.horario', [
            'horario' => $horario,
            'contrato' => $contrato,
            'filas' => $filas,
            'turnos' => $turnos,
        ])->setPaper('a4')->download("horario-{$documento}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    private function validarHorario(Request $request, bool $conNombre): array
    {
        $this->normalizarHoras($request);

        $reglas = [
            'turnos' => ['required', 'array', 'min:1'],
            'turnos.*.clave' => ['required', 'string', 'max:40'],
            'turnos.*.nombre' => ['required', 'string', 'max:80'],
            'turnos.*.inicio' => ['required', 'date_format:H:i'],
            'turnos.*.fin' => ['required', 'date_format:H:i'],
            'dias' => ['required', 'array'],
            'vigencia_inicio' => ['nullable', 'date'],
            'vigencia_fin' => ['nullable', 'date', 'after_or_equal:vigencia_inicio'],
            'plantilla_horario_id' => ['nullable', 'integer', 'exists:plantillas_horario,id'],
        ];

        if ($conNombre) {
            $reglas['nombre'] = ['required', 'string', 'max:120'];
            $reglas['descripcion'] = ['nullable', 'string', 'max:500'];
        } else {
            $reglas['colaborador_id'] = ['required', 'integer', Rule::exists('colaboradores', 'id')];
            $reglas['vigencia_inicio'] = ['required', 'date'];
        }

        $validated = $request->validate($reglas, [
            'turnos.required' => 'Define al menos un turno.',
            'colaborador_id.required' => 'Selecciona al colaborador.',
        ]);

        if (HorarioSemanal::totalHoras($validated['dias'] ?? []) <= 0) {
            throw ValidationException::withMessages([
                'dias' => 'Pulsa + para cargar al menos un turno en la tabla semanal.',
            ]);
        }

        return $validated;
    }

    private function normalizarHoras(Request $request): void
    {
        $turnos = collect($request->input('turnos', []))->map(function ($turno) {
            $turno['inicio'] = substr((string) ($turno['inicio'] ?? ''), 0, 5);
            $turno['fin'] = substr((string) ($turno['fin'] ?? ''), 0, 5);

            return $turno;
        })->all();

        $dias = [];
        foreach ($request->input('dias', []) as $dia => $turnosDelDia) {
            $dias[$dia] = [];
            foreach ((array) $turnosDelDia as $clave => $turno) {
                $dias[$dia][$clave] = [
                    'inicio' => substr((string) ($turno['inicio'] ?? ''), 0, 5),
                    'fin' => substr((string) ($turno['fin'] ?? ''), 0, 5),
                ];
            }
        }

        $request->merge([
            'turnos' => $turnos,
            'dias' => $dias,
        ]);
    }
}
