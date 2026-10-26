<?php

namespace Tests\Unit;

use App\Models\Reporte;
use App\Services\PlanificadorFlotaService;
use App\Services\TrazadoRutaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrazadoRutaCasosBordeTest extends TestCase
{
    private const HTTPS_ROUTER_PROJECT_OSRM_ORG_ROUTE = 'https://router.project-osrm.org/route/*';

    public function test_empty_stop_lists_only_return_the_depot(): void
    {
        $servicio = app(TrazadoRutaService::class);
        $esperado = [
            'paradas' => [],
            'geometria' => [[TrazadoRutaService::BASE_LATITUD, TrazadoRutaService::BASE_LONGITUD]],
            'distancia_km' => 0.0,
            'por_vias' => false,
        ];

        $this->assertSame($esperado, $servicio->trazar([]));
        $this->assertSame($esperado, $servicio->trazarEnOrden([]));
    }

    public function test_unusable_trip_and_route_answers_fall_back_to_straight_lines(): void
    {
        Http::fake([
            'https://router.project-osrm.org/trip/*' => Http::response([
                'code' => 'Ok',
                'waypoints' => [['waypoint_index' => 1], ['waypoint_index' => 0]],
                'trips' => [['distance' => 100, 'geometry' => ['coordinates' => []], 'legs' => []]],
            ], 200),
            self::HTTPS_ROUTER_PROJECT_OSRM_ORG_ROUTE => Http::response(['code' => 'Ok', 'routes' => []], 200),
        ]);

        $trazado = app(TrazadoRutaService::class)->trazar($this->reportes());

        $this->assertFalse($trazado['por_vias']);
        $this->assertCount(3, $trazado['geometria']);
        $this->assertCount(2, $trazado['paradas']);
    }

    public function test_geometry_with_fewer_than_two_valid_points_is_discarded(): void
    {
        $respuestaTrip = [
            'code' => 'Ok',
            'waypoints' => [
                ['waypoint_index' => 0],
                ['waypoint_index' => 1],
                ['waypoint_index' => 2],
            ],
            'trips' => [[
                'distance' => 2000,
                'geometry' => ['coordinates' => [[-75.22, -12.05], [1], 'invalido']],
                'legs' => [['distance' => 1000], ['distance' => 1000]],
            ]],
        ];

        Http::fake([
            'https://router.project-osrm.org/trip/*' => Http::response($respuestaTrip, 200),
            self::HTTPS_ROUTER_PROJECT_OSRM_ORG_ROUTE => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 10, 'geometry' => ['coordinates' => [[-75.22, -12.05]]], 'legs' => []]],
            ], 200),
        ]);

        $trazado = app(TrazadoRutaService::class)->trazar($this->reportes());

        $this->assertFalse($trazado['por_vias']);
        $this->assertCount(3, $trazado['geometria']);
    }

    public function test_route_without_leg_distances_uses_haversine_between_stops(): void
    {
        Http::fake([
            self::HTTPS_ROUTER_PROJECT_OSRM_ORG_ROUTE => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 4200,
                    'geometry' => ['coordinates' => [[-75.2215, -12.0504], [-75.22, -12.05], [-75.23, -12.06]]],
                    'legs' => [],
                ]],
            ], 200),
        ]);

        $trazado = app(TrazadoRutaService::class)->trazarEnOrden($this->reportes());

        $this->assertTrue($trazado['por_vias']);
        $this->assertSame(4.2, $trazado['distancia_km']);
        $this->assertGreaterThan(0, $trazado['paradas'][0]['distancia']);
    }

    public function test_network_failure_is_logged_and_keeps_the_given_stop_order(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Sin conexión');
        });

        $reportes = $this->reportes();
        $trazado = app(TrazadoRutaService::class)->trazarEnOrden($reportes);

        $this->assertFalse($trazado['por_vias']);
        $this->assertSame($reportes[0], $trazado['paradas'][0]['reporte']);
        $this->assertSame($reportes[1], $trazado['paradas'][1]['reporte']);
        $this->assertCount(3, $trazado['geometria']);
        $this->assertGreaterThan(0, $trazado['distancia_km']);
    }

    public function test_planner_with_a_single_vehicle_keeps_all_stops_together(): void
    {
        Http::fake(['*' => Http::response(['code' => 'NoRoute'], 200)]);

        $propuestas = app(PlanificadorFlotaService::class)->proponer($this->reportes(), 1);

        $this->assertCount(1, $propuestas);
        $this->assertCount(2, $propuestas[0]['reporte_ids']);
    }

    public function test_planner_rebalances_when_stops_share_the_same_location(): void
    {
        $mismoPunto = fn () => new Reporte(['latitud' => -12.05, 'longitud' => -75.22]);

        $grupos = app(PlanificadorFlotaService::class)->agrupar([$mismoPunto(), $mismoPunto(), $mismoPunto()], 3);

        $this->assertCount(3, $grupos);
        $this->assertSame([1, 1, 1], array_map('count', $grupos));
    }

    /**
     * @return array<int, Reporte>
     */
    private function reportes(): array
    {
        return [
            new Reporte(['id' => 1, 'latitud' => -12.05, 'longitud' => -75.22, 'descripcion' => 'A']),
            new Reporte(['id' => 2, 'latitud' => -12.06, 'longitud' => -75.23, 'descripcion' => 'B']),
        ];
    }
}
