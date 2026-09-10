<?php

namespace App\Http\Controllers;

use App\Models\TipoMantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TipoMantenimientoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Administracion/Mantenimiento/Tipos', [
            'tipos' => TipoMantenimiento::query()
                ->withCount('mantenimientos')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TipoMantenimiento::create($request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:tipos_mantenimiento,nombre'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()
            ->with('success', 'Tipo de mantenimiento agregado al maestro.')
            ->with('registered', true);
    }

    public function update(Request $request, TipoMantenimiento $tipo): RedirectResponse
    {
        $tipo->update($request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipos_mantenimiento', 'nombre')->ignore($tipo)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('success', 'Tipo de mantenimiento actualizado.');
    }

    public function cambiarEstado(Request $request, TipoMantenimiento $tipo): RedirectResponse
    {
        $tipo->update($request->validate([
            'activo' => ['required', 'boolean'],
        ]));

        return back()->with('success', 'Estado del tipo de mantenimiento actualizado.');
    }
}
