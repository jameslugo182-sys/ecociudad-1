<?php

namespace Tests\Unit;

use App\Models\Reporte;
use App\Services\TrazadoRutaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrazadoRutaServiceTest extends TestCase
{
    public function test_uses_road_geometry_from_osrm_trip(): void
    {
        Http::fake([
            'https://router.project-osrm.org/trip/*' => Http::response([
                'code' => 'Ok',
                'waypoints' => [
                    ['waypoint_index' => 0, 'trips_index' => 0],
                    ['waypoint_index' => 1, 'trips_index' => 0],
                    ['waypoint_index' => 2, 'trips_index' => 0],
                ],
                'trips' => [[
                    'distance' => 2500,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [-75.2215, -12.0504],
                            [-75.2205, -12.0510],
                            [-75.2190, -12.0520],
                        ],
                    ],
                    'legs' => [
                        ['distance' => 1000],
                        ['distance' => 1500],
                    ],
                ]],
            ], 200),
        ]);

        $trazado = app(TrazadoRutaService::class)->trazar([
            new Reporte(['latitud' => -12.0510, 'longitud' => -75.2205]),
            new Reporte(['latitud' => -12.0520, 'longitud' => -75.2190]),
        ]);

        $this->assertTrue($trazado['por_vias']);
        $this->assertSame(2.5, $trazado['distancia_km']);
        $this->assertCount(3, $trazado['geometria']);
        $this->assertSame([-12.0504, -75.2215], $trazado['geometria'][0]);
        $this->assertCount(2, $trazado['paradas']);
        $this->assertSame(1.0, $trazado['paradas'][0]['distancia']);
    }

    public function test_falls_back_to_straight_line_when_osrm_fails(): void
    {
        Http::fake([
            'https://router.project-osrm.org/*' => Http::response(['code' => 'NoRoute'], 200),
        ]);

        $trazado = app(TrazadoRutaService::class)->trazar([
            new Reporte(['latitud' => -12.05, 'longitud' => -75.22]),
            new Reporte(['latitud' => -12.06, 'longitud' => -75.23]),
        ]);

        $this->assertFalse($trazado['por_vias']);
        $this->assertCount(3, $trazado['geometria']);
        $this->assertSame(TrazadoRutaService::BASE_LATITUD, $trazado['geometria'][0][0]);
        $this->assertCount(2, $trazado['paradas']);
        $this->assertGreaterThan(0, $trazado['distancia_km']);
    }

    public function test_trazar_en_orden_keeps_the_given_stop_sequence(): void
    {
        Http::fake([
            'https://router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 3000,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [-75.2215, -12.0504],
                            [-75.23, -12.06],
                            [-75.22, -12.05],
                        ],
                    ],
                    'legs' => [
                        ['distance' => 2000],
                        ['distance' => 1000],
                    ],
                ]],
            ], 200),
        ]);

        $lejos = new Reporte(['latitud' => -12.06, 'longitud' => -75.23]);
        $cerca = new Reporte(['latitud' => -12.05, 'longitud' => -75.22]);
        $trazado = app(TrazadoRutaService::class)->trazarEnOrden([$lejos, $cerca]);

        $this->assertTrue($trazado['por_vias']);
        $this->assertSame($lejos, $trazado['paradas'][0]['reporte']);
        $this->assertSame($cerca, $trazado['paradas'][1]['reporte']);
        $this->assertSame(2.0, $trazado['paradas'][0]['distancia']);
    }
}
