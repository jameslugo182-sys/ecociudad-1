<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapaController extends Controller
{
    public function index(Request $request): Response
    {
        $esAdministracion = $request->routeIs('administracion.*');

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
            'routeNames' => [
                'index' => $esAdministracion ? 'administracion.incidencias.index' : 'admin.reportes.index',
                'mapa' => $esAdministracion ? 'administracion.incidencias.mapa' : 'admin.mapa',
            ],
        ]);
    }
}
