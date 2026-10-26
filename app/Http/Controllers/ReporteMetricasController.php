<?php

namespace App\Http\Controllers;

use App\Services\InformeOperativoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReporteMetricasController extends Controller
{
    public function __construct(
        private readonly InformeOperativoService $informes,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        return $this->pagina($request, 'resumen', 'Reportes/Metricas');
    }

    public function incidencias(Request $request): InertiaResponse
    {
        return $this->pagina($request, 'incidencias', 'Reportes/InformeIncidencias');
    }

    public function rutas(Request $request): InertiaResponse
    {
        return $this->pagina($request, 'rutas', 'Reportes/InformeRutas');
    }

    public function tachos(Request $request): InertiaResponse
    {
        return $this->pagina($request, 'tachos', 'Reportes/InformeTachos');
    }

    public function personal(Request $request): InertiaResponse
    {
        return $this->pagina($request, 'personal', 'Reportes/InformePersonal');
    }

    public function pdf(Request $request, string $seccion): Response
    {
        abort_unless(in_array($seccion, InformeOperativoService::SECCIONES, true), 404);

        $periodo = $this->informes->periodo($request);
        $datos = $this->informes->datosSeccion($seccion, $periodo, paginar: false);
        $archivo = 'informe-'.$seccion.'-'.$periodo['fecha_desde'].'-'.$periodo['fecha_hasta'].'.pdf';

        return Pdf::loadView('reportes.informe', [
            'seccion' => $seccion,
            'titulo' => $this->informes->tituloSeccion($seccion),
            'periodo' => $this->informes->filtrosVista($periodo),
            'generado' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ...$datos,
        ])->setPaper('a4', $seccion === 'resumen' ? 'portrait' : 'landscape')->download($archivo);
    }

    private function pagina(Request $request, string $seccion, string $vista): InertiaResponse
    {
        $periodo = $this->informes->periodo($request);
        $filtros = $this->informes->filtrosVista($periodo);

        return Inertia::render($vista, [
            'periodo' => $filtros,
            'pdfUrl' => route('administracion.reportes.pdf', [
                'seccion' => $seccion,
                'fecha_desde' => $filtros['fecha_desde'],
                'fecha_hasta' => $filtros['fecha_hasta'],
                'estado' => $filtros['estado'] ?: null,
            ]),
            ...$this->informes->datosSeccion($seccion, $periodo),
        ]);
    }
}
