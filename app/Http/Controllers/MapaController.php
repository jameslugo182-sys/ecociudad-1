<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use Inertia\Inertia;
use Inertia\Response;

class MapaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Mapa', [
            'reportes' => Reporte::query()
                ->with(['user:id,name', 'responsable:id,name'])
                ->latest()
                ->get([
                    'id',
                    'user_id',
                    'assigned_to',
                    'foto_path',
                    'latitud',
                    'longitud',
                    'descripcion',
                    'estado',
                    'tipo_incidencia',
                    'prioridad',
                    'created_at',
                ]),
        ]);
    }
}
