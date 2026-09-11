<?php

namespace Tests\Unit;

use App\Models\Reporte;
use App\Services\PlanificadorFlotaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlanificadorFlotaServiceTest extends TestCase
{
    public function test_splits_nearby_incidents_into_efficient_groups(): void
    {
        $oeste = [
            new Reporte(['latitud' => -12.0500, 'longitud' => -75.2300]),
            new Reporte(['latitud' => -12.0508, 'longitud' => -75.2290]),
        ];
        $este = [
            new Reporte(['latitud' => -12.0700, 'longitud' => -75.2000]),
            new Reporte(['latitud' => -12.0710, 'longitud' => -75.1990]),
        ];

        $grupos = app(PlanificadorFlotaService::class)->agrupar([...$oeste, ...$este], 2);

        $this->assertCount(2, $grupos);
        $this->assertSame(4, collect($grupos)->flatten()->count());

        foreach ($grupos as $grupo) {
            $lats = collect($grupo)->map(fn (Reporte $reporte) => (float) $reporte->latitud);
            $this->assertLessThan(0.01, $lats->max() - $lats->min());
        }
    }

    public function test_propose_does_not_require_equal_group_sizes(): void
    {
        Http::fake([
            'https://router.project-osrm.org/*' => Http::response(['code' => 'NoRoute'], 200),
        ]);

        $reportes = [
            new Reporte(['id' => 1, 'latitud' => -12.0500, 'longitud' => -75.2300, 'descripcion' => 'A']),
            new Reporte(['id' => 2, 'latitud' => -12.0504, 'longitud' => -75.2295, 'descripcion' => 'B']),
            new Reporte(['id' => 3, 'latitud' => -12.0508, 'longitud' => -75.2290, 'descripcion' => 'C']),
            new Reporte(['id' => 4, 'latitud' => -12.0800, 'longitud' => -75.1900, 'descripcion' => 'D']),
        ];

        $propuestas = app(PlanificadorFlotaService::class)->proponer($reportes, 2);

        $this->assertCount(2, $propuestas);
        $tamaños = collect($propuestas)->map(fn (array $propuesta) => count($propuesta['reporte_ids']));
        $this->assertSame(4, $tamaños->sum());
        $this->assertNotEquals($tamaños->first(), $tamaños->last());
    }
}
