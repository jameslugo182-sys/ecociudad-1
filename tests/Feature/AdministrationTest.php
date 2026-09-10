<?php

namespace Tests\Feature;

use App\Models\AreaUnidad;
use App\Models\Camion;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\HorarioPersonal;
use App\Models\MantenimientoVehiculo;
use App\Models\PlantillaHorario;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\TipoMantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrator_can_access_administration_module(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);

        $this->actingAs($administrador)
            ->get(route('administracion.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panel')
                ->has('modulos', 5));

        $this->actingAs($administrador)
            ->get(route('administracion.incidencias.mapa'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Mapa')
                ->where('routeNames.mapa', 'administracion.incidencias.mapa'));

        $this->actingAs($municipal)
            ->get(route('administracion.dashboard'))
            ->assertForbidden();
    }

    public function test_account_can_only_be_created_after_registering_a_contract(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $cargo = Cargo::where('nombre', 'Recolector')->firstOrFail();
        $area = AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail();

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.store'), [
                'documento' => '72345678',
                'nombres' => 'Operario',
                'apellido_paterno' => 'Municipal',
                'sexo' => 'Masculino',
                'nivel_educativo' => 'Secundaria',
                'numero' => 'CON-2026-001',
                'cargo_id' => $cargo->id,
                'tipo' => 'CAS',
                'fecha_inicio' => today()->subDay()->toDateString(),
                'fecha_fin' => today()->addYear()->toDateString(),
                'area_id' => $area->id,
                'estado' => 'Vigente',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $colaborador = Colaborador::where('documento', '72345678')->firstOrFail();

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.usuarios.store'), [
                'colaborador_id' => $colaborador->id,
                'email' => 'operario@ecociudad.pe',
                'rol' => 'area_limpieza',
                'password' => 'Password-2026',
                'password_confirmation' => 'Password-2026',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'operario@ecociudad.pe',
            'rol' => 'area_limpieza',
            'colaborador_id' => $colaborador->id,
            'activo' => true,
        ]);

        $this->assertDatabaseHas('colaboradores', [
            'documento' => '72345678',
            'sexo' => 'Masculino',
            'nivel_educativo' => 'Secundaria',
        ]);

        $this->assertDatabaseHas('contratos', [
            'numero' => 'CON-2026-001',
            'area' => 'Limpieza pública',
            'area_id' => $area->id,
        ]);
    }

    public function test_contract_format_pdf_is_generated_from_form_data(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $cargo = Cargo::where('nombre', 'Recolector')->firstOrFail();
        $area = AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail();

        $response = $this->actingAs($administrador)
            ->post(route('administracion.contratos.formato'), [
                'documento' => '87654321',
                'nombres' => 'Ana',
                'apellido_paterno' => 'Quispe',
                'apellido_materno' => 'Rojas',
                'sexo' => 'Femenino',
                'nivel_educativo' => 'Universitario',
                'numero' => 'CON-2026-PDF',
                'cargo_id' => $cargo->id,
                'tipo' => 'CAS',
                'fecha_inicio' => today()->toDateString(),
                'fecha_fin' => today()->addMonths(6)->toDateString(),
                'area_id' => $area->id,
                'estado' => 'Vigente',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $response->headers->get('content-type')));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_schedule_template_and_assignment_can_be_registered(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $cargo = Cargo::where('nombre', 'Recolector')->firstOrFail();
        $area = AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail();

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.store'), [
                'documento' => '70111222',
                'nombres' => 'Luis',
                'apellido_paterno' => 'Paredes',
                'numero' => 'CON-2026-HOR',
                'cargo_id' => $cargo->id,
                'tipo' => 'CAS',
                'fecha_inicio' => today()->toDateString(),
                'fecha_fin' => today()->addYear()->toDateString(),
                'area_id' => $area->id,
                'estado' => 'Vigente',
            ])
            ->assertSessionHasNoErrors();

        $turnos = [
            ['clave' => 'manana', 'nombre' => 'Turno mañana', 'inicio' => '07:00', 'fin' => '13:00'],
            ['clave' => 'tarde', 'nombre' => 'Turno tarde', 'inicio' => '14:00', 'fin' => '18:00'],
            ['clave' => 'noche', 'nombre' => 'Turno noche', 'inicio' => '19:00', 'fin' => '23:00'],
        ];
        $dias = [
            1 => [
                'manana' => ['inicio' => '07:00', 'fin' => '13:00'],
                'tarde' => ['inicio' => '14:00', 'fin' => '18:00'],
                'noche' => ['inicio' => '19:00', 'fin' => '23:00'],
            ],
        ];

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.horarios.plantillas.store'), [
                'nombre' => 'Turno completo lunes',
                'turnos' => $turnos,
                'dias' => $dias,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $plantilla = PlantillaHorario::where('nombre', 'Turno completo lunes')->firstOrFail();
        $this->assertEquals(14.0, (float) $plantilla->horas_semanales);

        $colaborador = Colaborador::where('documento', '70111222')->firstOrFail();

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.horarios.registro.store'), [
                'colaborador_id' => $colaborador->id,
                'plantilla_horario_id' => $plantilla->id,
                'vigencia_inicio' => today()->toDateString(),
                'turnos' => $turnos,
                'dias' => $dias,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('horarios_personal', [
            'colaborador_id' => $colaborador->id,
            'plantilla_horario_id' => $plantilla->id,
        ]);

        $this->assertEquals(14.0, (float) HorarioPersonal::firstOrFail()->horas_semanales);

        $horario = HorarioPersonal::firstOrFail();
        $pdf = $this->actingAs($administrador)
            ->get(route('administracion.contratos.horarios.registro.pdf', $horario));

        $pdf->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $pdf->headers->get('content-type')));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($administrador)
            ->get(route('administracion.contratos.maestros.areas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contratos/Maestros/Catalogo')
                ->where('titulo', 'Áreas / unidades'));
    }

    public function test_role_master_limits_access_to_configured_modules(): void
    {
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);

        $this->actingAs($municipal)
            ->get(route('administracion.incidencias.index'))
            ->assertOk();

        $this->actingAs($municipal)
            ->get(route('administracion.contratos.index'))
            ->assertForbidden();
    }

    public function test_truck_team_requires_one_driver_and_three_collectors(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = Camion::create([
            'codigo' => 'CAM-01',
            'placa' => 'ABC-123',
            'estado' => 'Disponible',
        ]);

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Administracion/Camiones'));

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.equipos'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Administracion/Equipos'));

        $this->actingAs($administrador)
            ->put(route('administracion.vehiculos.equipo', $camion), [
                'conductor_id' => $personal[0]->id,
                'recolector_ids' => [
                    $personal[1]->id,
                    $personal[2]->id,
                    $personal[3]->id,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camion_user', [
            'camion_id' => $camion->id,
            'user_id' => $personal[0]->id,
            'puesto' => 'conductor',
        ]);
        $this->assertSame(3, $camion->personal()->wherePivot('puesto', 'recolector')->count());
    }

    public function test_vehicle_in_maintenance_is_hidden_from_team_assignment_until_finished(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = Camion::create([
            'codigo' => 'VH100',
            'placa' => 'MNT-100',
            'estado' => 'Disponible',
        ]);
        $tipo = TipoMantenimiento::query()->where('nombre', 'Preventivo')->firstOrFail();

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.mantenimiento.registro'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Mantenimiento/Registro')
                ->has('camiones', 1));

        $this->actingAs($administrador)
            ->post(route('administracion.vehiculos.mantenimiento.store'), [
                'camion_id' => $camion->id,
                'tipo_mantenimiento_id' => $tipo->id,
                'motivo' => 'Revisión programada de frenos y aceite.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camiones', [
            'id' => $camion->id,
            'estado' => 'Mantenimiento',
        ]);

        $mantenimiento = MantenimientoVehiculo::firstOrFail();
        $this->assertSame('En curso', $mantenimiento->estado);

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.equipos'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Equipos')
                ->has('camiones', 0));

        $this->actingAs($administrador)
            ->put(route('administracion.vehiculos.equipo', $camion), [
                'conductor_id' => $personal[0]->id,
                'recolector_ids' => [
                    $personal[1]->id,
                    $personal[2]->id,
                    $personal[3]->id,
                ],
            ])
            ->assertSessionHasErrors('equipo');

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.mantenimiento.en-curso'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Mantenimiento/EnCurso')
                ->has('mantenimientos', 1)
                ->where('mantenimientos.0.id', $mantenimiento->id));

        $this->actingAs($administrador)
            ->patch(route('administracion.vehiculos.mantenimiento.finalizar', $mantenimiento))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camiones', [
            'id' => $camion->id,
            'estado' => 'Disponible',
        ]);
        $this->assertDatabaseHas('mantenimientos_vehiculo', [
            'id' => $mantenimiento->id,
            'estado' => 'Finalizado',
        ]);

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.equipos'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Equipos')
                ->has('camiones', 1)
                ->where('camiones.0.id', $camion->id));

        $this->actingAs($administrador)
            ->get(route('administracion.vehiculos.mantenimiento.historial', ['camion_id' => $camion->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Mantenimiento/Historial')
                ->has('mantenimientos.data', 1)
                ->where('mantenimientos.data.0.id', $mantenimiento->id));
    }

    public function test_collaborator_without_contract_cannot_receive_an_account(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $colaborador = Colaborador::create([
            'documento' => '71234567',
            'nombres' => 'Sin',
            'apellido_paterno' => 'Contrato',
        ]);

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.usuarios.store'), [
                'colaborador_id' => $colaborador->id,
                'email' => 'sincontrato@ecociudad.pe',
                'rol' => 'area_limpieza',
                'password' => 'Password-2026',
                'password_confirmation' => 'Password-2026',
            ])
            ->assertSessionHasErrors('colaborador_id');

        $this->assertDatabaseMissing('users', ['colaborador_id' => $colaborador->id]);
    }

    public function test_administrator_can_generate_an_optimized_route_for_a_complete_team(): void
    {
        $this->fakeOsrmTrip();

        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = Camion::create([
            'codigo' => 'RUTA-01',
            'placa' => 'RUT-101',
            'estado' => 'Disponible',
        ]);
        $camion->personal()->attach([
            $personal[0]->id => ['puesto' => 'conductor'],
            $personal[1]->id => ['puesto' => 'recolector'],
            $personal[2]->id => ['puesto' => 'recolector'],
            $personal[3]->id => ['puesto' => 'recolector'],
        ]);
        $reportes = collect([
            [-12.05, -75.22],
            [-12.06, -75.23],
        ])->map(fn (array $coordenada, int $indice) => Reporte::create([
            'user_id' => $ciudadano->id,
            'foto_path' => 'reportes_fotos/test.jpg',
            'latitud' => $coordenada[0],
            'longitud' => $coordenada[1],
            'descripcion' => "Incidencia para ruta {$indice}",
            'estado' => 'Pendiente',
        ]));

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => $reportes->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $ruta = RutaRecoleccion::firstOrFail();
        $this->assertSame($camion->id, $ruta->camion_id);
        $this->assertSame(2, $ruta->reportes()->count());
        $this->assertNotEmpty($ruta->geometria);
        $this->assertEquals(-12.0504, $ruta->geometria[0][0]);
        $this->assertDatabaseMissing('reportes', ['id' => $reportes[0]->id, 'estado' => 'Pendiente']);

        $this->actingAs($personal[1])
            ->get(route('limpieza.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Limpieza/Dashboard')
                ->where('rutaAsignada.id', $ruta->id)
                ->where('rutaAsignada.camion_id', $camion->id)
                ->has('rutaAsignada.geometria'));

        $this->actingAs($personal[1])
            ->get(route('limpieza.rutas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Limpieza/Recorrido')
                ->where('rutaAsignada.id', $ruta->id));

        $ajeno = User::factory()->create(['rol' => 'area_limpieza']);
        $this->actingAs($ajeno)
            ->get(route('limpieza.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Limpieza/Dashboard')
                ->where('rutaAsignada', null));

        $this->actingAs($personal[1])
            ->patch(route('limpieza.reportes.iniciar', $reportes[0]))
            ->assertRedirect();
        $this->assertDatabaseHas('reportes', ['id' => $reportes[0]->id, 'estado' => 'En atención']);
    }

    public function test_administrator_can_assign_a_collection_schedule_to_an_assigned_fleet_route(): void
    {
        $this->fakeOsrmTrip();

        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = Camion::create([
            'codigo' => 'HOR-01',
            'placa' => 'HOR-101',
            'estado' => 'Disponible',
        ]);
        $camion->personal()->attach([
            $personal[0]->id => ['puesto' => 'conductor'],
            $personal[1]->id => ['puesto' => 'recolector'],
            $personal[2]->id => ['puesto' => 'recolector'],
            $personal[3]->id => ['puesto' => 'recolector'],
        ]);
        $reportes = collect([
            [-12.05, -75.22],
            [-12.06, -75.23],
        ])->map(fn (array $coordenada, int $indice) => Reporte::create([
            'user_id' => $ciudadano->id,
            'foto_path' => 'reportes_fotos/test.jpg',
            'latitud' => $coordenada[0],
            'longitud' => $coordenada[1],
            'descripcion' => "Incidencia para horario {$indice}",
            'estado' => 'Pendiente',
        ]));

        $this->actingAs($administrador)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => $reportes->pluck('id')->all(),
            ])
            ->assertRedirect();

        $ruta = RutaRecoleccion::firstOrFail();

        $this->actingAs($administrador)
            ->get(route('administracion.rutas.horarios'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rutas/Horarios')
                ->has('rutas', 1)
                ->where('rutas.0.id', $ruta->id)
                ->where('rutas.0.horario_resumen', null));

        $this->actingAs($administrador)
            ->patch(route('administracion.rutas.horario', $ruta), [
                'hora_inicio' => '07:00',
                'hora_fin' => '11:30',
                'dias_recoleccion' => [1, 3, 5],
                'horario_nota' => 'Salida desde la base municipal',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $ruta->refresh();
        $this->assertSame('07:00', $ruta->hora_inicio);
        $this->assertSame('11:30', $ruta->hora_fin);
        $this->assertSame([1, 3, 5], $ruta->dias_recoleccion);
        $this->assertSame('Lun, Mié, Vie · 07:00–11:30', $ruta->horario_resumen);

        $this->actingAs($personal[1])
            ->get(route('limpieza.rutas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Limpieza/Recorrido')
                ->where('rutaAsignada.horario_resumen', 'Lun, Mié, Vie · 07:00–11:30')
                ->where('rutaAsignada.horario_nota', 'Salida desde la base municipal'));

        $this->actingAs($administrador)
            ->patch(route('administracion.rutas.cancelar', $ruta))
            ->assertRedirect();

        $this->actingAs($administrador)
            ->patch(route('administracion.rutas.horario', $ruta), [
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
                'dias_recoleccion' => [1],
            ])
            ->assertStatus(422);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create(['activo' => false]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    /**
     * @return void
     */
    private function fakeOsrmTrip(): void
    {
        Http::fake([
            'https://router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'waypoints' => [
                    ['waypoint_index' => 0, 'trips_index' => 0],
                    ['waypoint_index' => 1, 'trips_index' => 0],
                    ['waypoint_index' => 2, 'trips_index' => 0],
                ],
                'trips' => [[
                    'distance' => 4300,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [-75.2215, -12.0504],
                            [-75.2210, -12.0508],
                            [-75.2200, -12.0500],
                            [-75.2300, -12.0600],
                        ],
                    ],
                    'legs' => [
                        ['distance' => 1800],
                        ['distance' => 2500],
                    ],
                ]],
            ], 200),
        ]);
    }
}
