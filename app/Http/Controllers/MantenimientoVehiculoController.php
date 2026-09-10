<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\MantenimientoVehiculo;
use App\Models\TipoMantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MantenimientoVehiculoController extends Controller
{
    public function registro(): Response
    {
        return Inertia::render('Administracion/Mantenimiento/Registro', [
            'camiones' => Camion::query()
                ->disponiblesParaMantenimiento()
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'placa', 'marca', 'modelo', 'estado']),
            'tipos' => TipoMantenimiento::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'registros' => MantenimientoVehiculo::query()
                ->with(['camion:id,codigo,placa', 'tipo:id,nombre', 'solicitante:id,name'])
                ->latest()
                ->take(20)
                ->get(),
        ]);
    }

    public function enCurso(): Response
    {
        return Inertia::render('Administracion/Mantenimiento/EnCurso', [
            'mantenimientos' => MantenimientoVehiculo::query()
                ->where('estado', 'En curso')
                ->with(['camion:id,codigo,placa,marca,modelo,foto_path', 'tipo:id,nombre', 'solicitante:id,name'])
                ->latest('iniciado_at')
                ->latest()
                ->get(),
        ]);
    }

    public function historial(Request $request): Response
    {
        $filters = $request->validate([
            'camion_id' => ['nullable', 'integer', 'exists:camiones,id'],
        ]);

        return Inertia::render('Administracion/Mantenimiento/Historial', [
            'mantenimientos' => MantenimientoVehiculo::query()
                ->with([
                    'camion:id,codigo,placa',
                    'tipo:id,nombre',
                    'solicitante:id,name',
                    'finalizador:id,name',
                ])
                ->when(
                    $filters['camion_id'] ?? null,
                    fn ($query, $camionId) => $query->where('camion_id', $camionId)
                )
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'camiones' => Camion::query()->orderBy('codigo')->get(['id', 'codigo', 'placa']),
            'filters' => [
                'camion_id' => $filters['camion_id'] ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'camion_id' => [
                'required',
                'integer',
                Rule::exists('camiones', 'id'),
            ],
            'tipo_mantenimiento_id' => [
                'required',
                'integer',
                Rule::exists('tipos_mantenimiento', 'id')->where('activo', true),
            ],
            'motivo' => ['required', 'string', 'max:1000'],
        ], [
            'camion_id.required' => 'Selecciona el vehículo que ingresará a mantenimiento.',
            'tipo_mantenimiento_id.required' => 'Selecciona el tipo de mantenimiento.',
            'motivo.required' => 'Indica el motivo del mantenimiento.',
        ]);

        $camion = Camion::query()->findOrFail($validated['camion_id']);

        if ($camion->enMantenimiento()) {
            throw ValidationException::withMessages([
                'camion_id' => 'Este vehículo ya tiene un mantenimiento en curso.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $camion) {
            MantenimientoVehiculo::create([
                'camion_id' => $camion->id,
                'tipo_mantenimiento_id' => $validated['tipo_mantenimiento_id'],
                'solicitado_por' => $request->user()->id,
                'motivo' => $validated['motivo'],
                'estado' => 'En curso',
                'estado_anterior' => $camion->estado,
                'iniciado_at' => now(),
            ]);

            $camion->update(['estado' => 'Mantenimiento']);
        });

        return back()
            ->with('success', "Mantenimiento registrado para {$camion->codigo}.")
            ->with('registered', true);
    }

    public function finalizar(Request $request, MantenimientoVehiculo $mantenimiento): RedirectResponse
    {
        abort_unless($mantenimiento->estado === 'En curso', 422, 'El mantenimiento ya fue cerrado.');

        DB::transaction(function () use ($request, $mantenimiento) {
            $mantenimiento->load('camion');
            $mantenimiento->update([
                'estado' => 'Finalizado',
                'finalizado_por' => $request->user()->id,
                'finalizado_at' => now(),
            ]);

            $estadoAnterior = $mantenimiento->estado_anterior === 'Mantenimiento'
                ? 'Disponible'
                : ($mantenimiento->estado_anterior ?: 'Disponible');

            $mantenimiento->camion->update(['estado' => $estadoAnterior]);
        });

        return back()->with('success', "Mantenimiento de {$mantenimiento->camion->codigo} dado por terminado.");
    }
}
