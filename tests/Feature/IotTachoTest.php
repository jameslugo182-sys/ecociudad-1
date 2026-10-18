<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\Tacho;
use App\Models\User;
use App\Notifications\TachoNivelAlerta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IotTachoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_administrator_can_register_a_fixed_bin_and_see_the_device_token_once(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->get(route('administracion.tachos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tachos/Index')
                ->has('tachos.data', 0)
                ->where('siguienteCodigo', 'TCH001'));

        $this->actingAs($administrador)
            ->from(route('administracion.tachos.index'))
            ->post(route('administracion.tachos.store'), $this->payloadTacho())
            ->assertRedirect(route('administracion.tachos.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('device_token')
            ->assertSessionHas('registered', true);

        $this->assertDatabaseHas('tachos', [
            'codigo' => 'TCH001',
            'nombre' => 'Tacho Plaza Huamanmarca',
            'estado' => Tacho::ESTADO_OPERATIVO,
        ]);

        $tacho = Tacho::query()->firstOrFail();
        $this->assertNotSame(session('device_token'), $tacho->token_hash);
        $this->assertTrue($tacho->tokenValido(session('device_token')));
    }

    public function test_tacho_store_requires_map_point_and_calibration(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->from(route('administracion.tachos.index'))
            ->post(route('administracion.tachos.store'), [
                'nombre' => 'Ab',
                'distancia_vacio_cm' => 8,
                'distancia_lleno_cm' => 80,
                'umbral_alerta' => 80,
            ])
            ->assertRedirect(route('administracion.tachos.index'))
            ->assertSessionHasErrors(['nombre', 'latitud', 'longitud', 'distancia_lleno_cm']);
    }

    public function test_roles_without_tachos_module_cannot_open_the_panel(): void
    {
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);

        $this->actingAs($ciudadano)
            ->get(route('administracion.tachos.index'))
            ->assertForbidden();

        $this->actingAs($municipal)
            ->get(route('administracion.tachos.index'))
            ->assertForbidden();
    }

    public function test_esp32_reading_updates_fill_level_and_alerts_administrators(): void
    {
        Notification::fake();

        $administrador = User::factory()->create(['rol' => 'administrador']);
        $token = $this->registrarTacho($administrador);
        $tacho = Tacho::query()->firstOrFail();

        $this->postJson(route('iot.tachos.lecturas', $tacho), [
            'distancia_cm' => 10,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('alerta', true)
            ->assertJsonPath('estado', Tacho::ESTADO_LLENO);

        $tacho->refresh();
        $this->assertSame(97, $tacho->nivel_porcentaje);
        $this->assertSame(3, $tacho->capacidadRestante());
        $this->assertTrue($tacho->alerta_activa);
        $this->assertDatabaseHas('lecturas_tacho', [
            'tacho_id' => $tacho->id,
            'nivel_porcentaje' => 97,
        ]);
        $this->assertDatabaseHas('reportes', [
            'tacho_id' => $tacho->id,
            'tipo_incidencia' => Tacho::TIPO_INCIDENCIA,
            'estado' => 'Pendiente',
        ]);

        Notification::assertSentTo($administrador, TachoNivelAlerta::class);
    }

    public function test_esp32_rejects_invalid_device_token(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $this->registrarTacho($administrador);
        $tacho = Tacho::query()->firstOrFail();

        $this->postJson(route('iot.tachos.lecturas', $tacho), [
            'distancia_cm' => 40,
        ], [
            'Authorization' => 'Bearer token-falso',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('ok', false);

        $this->assertDatabaseCount('lecturas_tacho', 0);
    }

    public function test_fill_alert_hysteresis_does_not_reopen_until_level_drops(): void
    {
        Notification::fake();

        $administrador = User::factory()->create(['rol' => 'administrador']);
        $token = $this->registrarTacho($administrador, ['umbral_alerta' => 80]);
        $tacho = Tacho::query()->firstOrFail();

        $this->postJson(route('iot.tachos.lecturas', $tacho), [
            'distancia_cm' => 20,
        ], ['Authorization' => 'Bearer '.$token])->assertOk();

        Notification::assertSentTimes(TachoNivelAlerta::class, 1);
        $this->assertDatabaseCount('reportes', 1);

        $this->postJson(route('iot.tachos.lecturas', $tacho), [
            'distancia_cm' => 22,
        ], ['Authorization' => 'Bearer '.$token])->assertOk();

        Notification::assertSentTimes(TachoNivelAlerta::class, 1);
        $this->assertDatabaseCount('reportes', 1);
        $this->assertTrue($tacho->fresh()->alerta_activa);

        $this->postJson(route('iot.tachos.lecturas', $tacho), [
            'distancia_cm' => 50,
        ], ['Authorization' => 'Bearer '.$token])->assertOk();

        $liberado = $tacho->fresh();
        $this->assertFalse($liberado->alerta_activa);
        $this->assertSame(Tacho::ESTADO_OPERATIVO, $liberado->estado);
    }

    public function test_full_bin_can_be_appended_to_an_existing_truck_route(): void
    {
        $this->fakeOsrm();
        Notification::fake();

        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = Camion::create([
            'codigo' => 'IOT-01',
            'placa' => 'IOT-101',
            'estado' => 'Disponible',
        ]);
        $camion->personal()->attach([
            $personal[0]->id => ['puesto' => 'conductor'],
            $personal[1]->id => ['puesto' => 'recolector'],
            $personal[2]->id => ['puesto' => 'recolector'],
            $personal[3]->id => ['puesto' => 'recolector'],
        ]);
        $incidencia = Reporte::create([
            'user_id' => $ciudadano->id,
            'foto_path' => 'reportes_fotos/test.jpg',
            'latitud' => -12.05,
            'longitud' => -75.22,
            'descripcion' => 'Bolsa en la esquina',
            'estado' => 'Pendiente',
        ]);

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => [$incidencia->id],
            ])
            ->assertSessionHasNoErrors();

        $ruta = RutaRecoleccion::firstOrFail();
        $token = $this->registrarTacho($administrador);
        $tacho = Tacho::query()->firstOrFail();

        $this->postJson(route('iot.tachos.lecturas', $tacho), [
            'distancia_cm' => 8,
        ], ['Authorization' => 'Bearer '.$token])->assertOk();

        $this->actingAs($administrador)
            ->from(route('administracion.tachos.index'))
            ->post(route('administracion.tachos.ruta', $tacho), [
                'ruta_id' => $ruta->id,
            ])
            ->assertRedirect(route('administracion.tachos.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $ruta->fresh()->reportes()->count());
        $this->assertDatabaseHas('reportes', [
            'tacho_id' => $tacho->id,
            'estado' => 'Asignado',
            'assigned_to' => $personal[0]->id,
        ]);
    }

    public function test_bin_without_alert_cannot_join_a_route(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $this->registrarTacho($administrador);
        $tacho = Tacho::query()->firstOrFail();

        $this->actingAs($administrador)
            ->post(route('administracion.tachos.ruta', $tacho), [
                'ruta_id' => 1,
            ])
            ->assertSessionHasErrors('ruta_id');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadTacho(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Tacho Plaza Huamanmarca',
            'direccion' => 'Plaza Huamanmarca',
            'latitud' => -12.0464,
            'longitud' => -75.2195,
            'distancia_vacio_cm' => 80,
            'distancia_lleno_cm' => 8,
            'umbral_alerta' => 80,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function registrarTacho(User $administrador, array $overrides = []): string
    {
        $this->actingAs($administrador)
            ->from(route('administracion.tachos.index'))
            ->post(route('administracion.tachos.store'), $this->payloadTacho($overrides))
            ->assertSessionHasNoErrors();

        return session('device_token');
    }

    private function fakeOsrm(): void
    {
        Http::fake([
            'https://router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 2500,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [-75.2215, -12.0504],
                            [-75.2200, -12.0500],
                            [-75.2195, -12.0464],
                        ],
                    ],
                    'legs' => [
                        ['distance' => 1200],
                        ['distance' => 1300],
                    ],
                ]],
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
                'waypoints' => [
                    ['waypoint_index' => 0, 'trips_index' => 0],
                    ['waypoint_index' => 1, 'trips_index' => 0],
                ],
            ], 200),
        ]);
    }
}
