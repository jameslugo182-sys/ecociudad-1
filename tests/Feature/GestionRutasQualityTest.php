<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GestionRutasQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake([
            'https://router.project-osrm.org/*' => Http::response([
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
                            [-75.2200, -12.0500],
                        ],
                    ],
                    'legs' => [
                        ['distance' => 1200],
                    ],
                ]],
                'routes' => [[
                    'distance' => 2500,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [-75.2215, -12.0504],
                            [-75.2200, -12.0500],
                        ],
                    ],
                    'legs' => [
                        ['distance' => 1200],
                    ],
                ]],
            ], 200),
        ]);
    }

    public function test_administrator_can_open_the_routes_board(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->get(route('administracion.rutas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rutas/Index')
                ->has('reportes')
                ->has('equipos')
                ->has('rutas')
                ->where('routeNames.index', 'administracion.rutas.index'));
    }

    public function test_citizen_cannot_create_or_propose_routes(): void
    {
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);

        $this->actingAs($ciudadano)
            ->post(route('administracion.rutas.store'), [])
            ->assertForbidden();

        $this->actingAs($ciudadano)
            ->postJson(route('administracion.rutas.proponer'))
            ->assertForbidden();
    }

    public function test_store_rejects_vehicle_without_complete_team(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $camion = Camion::create(['codigo' => 'INC-01', 'placa' => 'INC-101', 'estado' => 'Disponible']);
        $reporte = $this->crearReporte($ciudadano);

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => [$reporte->id],
            ])
            ->assertSessionHasErrors('camion_id');

        $this->assertDatabaseCount('rutas_recoleccion', 0);
    }

    public function test_store_rejects_incident_already_on_an_active_route(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $camion = $this->camionConEquipo();
        $reporte = $this->crearReporte($ciudadano);

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => [$reporte->id],
            ])
            ->assertSessionHasNoErrors();

        $otroCamion = $this->camionConEquipo('INC-02', 'INC-202');

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $otroCamion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => [$reporte->id],
            ])
            ->assertSessionHasErrors('reporte_ids');
    }

    public function test_store_rejects_past_dates_and_missing_incidents(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $camion = $this->camionConEquipo();

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => now()->subDay()->toDateString(),
                'reporte_ids' => [],
            ])
            ->assertSessionHasErrors(['fecha', 'reporte_ids']);
    }

    public function test_propose_requires_available_incidents_and_complete_teams(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->postJson(route('administracion.rutas.proponer'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reportes');

        $this->crearReporte(User::factory()->create(['rol' => 'ciudadano']));

        $this->actingAs($administrador)
            ->postJson(route('administracion.rutas.proponer'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('equipos');
    }

    public function test_cancel_rejects_routes_that_are_no_longer_planned(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ruta = RutaRecoleccion::create([
            'camion_id' => $this->camionConEquipo()->id,
            'generado_por' => $administrador->id,
            'fecha' => today()->toDateString(),
            'estado' => 'En curso',
            'distancia_estimada_km' => 1,
            'geometria' => [[-12.05, -75.22]],
        ]);

        $this->actingAs($administrador)
            ->patch(route('administracion.rutas.cancelar', $ruta))
            ->assertStatus(422);
    }

    public function test_cronograma_rejects_invalid_month(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->get(route('administracion.rutas.cronograma', ['mes' => '2026-13']))
            ->assertSessionHasErrors('mes');
    }

    private function camionConEquipo(string $codigo = 'CAL-01', string $placa = 'CAL-101'): Camion
    {
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = Camion::create([
            'codigo' => $codigo,
            'placa' => $placa,
            'estado' => 'Disponible',
        ]);
        $camion->personal()->attach([
            $personal[0]->id => ['puesto' => 'conductor'],
            $personal[1]->id => ['puesto' => 'recolector'],
            $personal[2]->id => ['puesto' => 'recolector'],
            $personal[3]->id => ['puesto' => 'recolector'],
        ]);

        return $camion;
    }

    private function crearReporte(User $ciudadano): Reporte
    {
        return Reporte::create([
            'user_id' => $ciudadano->id,
            'foto_path' => 'reportes_fotos/test.jpg',
            'latitud' => -12.05,
            'longitud' => -75.22,
            'descripcion' => 'Incidencia de calidad',
            'estado' => 'Pendiente',
        ]);
    }
}
