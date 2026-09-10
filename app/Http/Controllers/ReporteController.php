<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReporteController extends Controller
{
    public function index(Request $request): Response
    {
        $reportes = $request->user()
            ->reportes()
            ->with('responsable:id,name')
            ->latest()
            ->get();

        return Inertia::render('Reportes/Index', [
            'reportes' => $reportes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Reportes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'descripcion' => 'required|string|max:1000',
            'tipo_incidencia' => ['nullable', 'string', 'max:100'],
        ], [
            'foto.image' => 'El archivo debe ser una imagen válida.',
            'foto.max' => 'La imagen no debe pesar más de 10MB.',
            'latitud.required' => 'No se pudo obtener la ubicación GPS.',
            'longitud.required' => 'No se pudo obtener la ubicación GPS.',
        ]);

        $rutaFoto = $request->file('foto')->store('reportes_fotos', 'public');

        $request->user()->reportes()->create([
            'foto_path' => $rutaFoto,
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
            'descripcion' => $validated['descripcion'],
            'tipo_incidencia' => $validated['tipo_incidencia'] ?? null,
            'estado' => 'Pendiente',
        ]);

        return redirect()
            ->route('reportes.index')
            ->with('success', 'Tu reporte fue enviado a la Municipalidad.')
            ->with('registered', true);
    }
}
