<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\MantenimientoVehiculo;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\Tacho;
use App\Models\TipoMantenimiento;
use App\Models\User;
use App\Notifications\ReporteAtendido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperacionesFlotaTest extends TestCase
{
    use RefreshDatabase;

    private const ABC_123 = 'ABC-123';

    private const VEHICULOS_VIEJA_PNG = 'vehiculos/vieja.png';

    private const ADMINISTRACION_VEHICULOS_EQUIPO = 'administracion.vehiculos.equipo';

    private const ADMINISTRACION_TACHOS_UPDATE = 'administracion.tachos.update';

    private const AAA_101 = 'AAA-101';

    private const ADMINISTRACION_TACHOS_RUTA = 'administracion.tachos.ruta';

    private const EVIDENCIAS_LIMPIEZA_ANTERIOR_PNG = 'evidencias_limpieza/anterior.png';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake(['*' => Http::response(['code' => 'NoRoute'], 200)]);
    }

    // ---------------------------------------------------------------
    // Vehículos
    // ---------------------------------------------------------------

    public function test_vehicle_list_filters_and_suggests_the_next_code(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $this->crearCamion('VH001', self::ABC_123);
        $this->crearCamion('VH002', 'XYZ-987', ['marca' => 'Scania', 'estado' => 'Inactivo']);

        $this->actingAs($admin)
            ->get(route('administracion.vehiculos.index', ['buscar' => 'ABC', 'estado' => 'Disponible']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Camiones')
                ->has('camiones.data', 1)
                ->where('siguienteCodigo', 'VH003')
                ->where('filters.buscar', 'ABC'));
    }

    public function test_vehicle_photo_is_replaced_on_update_and_removed_on_delete(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);
        Storage::disk('public')->put(self::VEHICULOS_VIEJA_PNG, 'x');
        $camion = $this->crearCamion('VH001', self::ABC_123, ['foto_path' => self::VEHICULOS_VIEJA_PNG]);

        $this->assertStringEndsWith('/storage/vehiculos/vieja.png', $camion->foto_url);

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.update', $camion), [
                'placa' => self::ABC_123,
                'marca' => 'Volvo',
                'modelo' => 'FMX',
                'anio' => 2023,
                'capacidad_kg' => 15000,
                'estado' => 'Disponible',
                'foto' => $this->imagenFalsa(),
            ])
            ->assertSessionHasNoErrors();

        $camion->refresh();
        Storage::disk('public')->assertMissing(self::VEHICULOS_VIEJA_PNG);
        Storage::disk('public')->assertExists($camion->foto_path);
        $this->assertSame('FMX', $camion->modelo);

        $rutaFoto = $camion->foto_path;
        $this->actingAs($admin)
            ->delete(route('administracion.vehiculos.destroy', $camion))
            ->assertSessionHas('success', 'Camión eliminado.');

        Storage::disk('public')->assertMissing($rutaFoto);
        $this->assertDatabaseMissing('camiones', ['id' => $camion->id]);
    }

    public function test_team_assignment_rejects_repeated_and_already_assigned_people(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $personal = User::factory(8)->create(['rol' => 'area_limpieza']);
        $primero = $this->crearCamion('VH001', self::ABC_123);
        $segundo = $this->crearCamion('VH002', 'ABC-124');

        $this->actingAs($admin)
            ->put(route(self::ADMINISTRACION_VEHICULOS_EQUIPO, $primero), [
                'conductor_id' => $personal[0]->id,
                'recolector_ids' => [$personal[0]->id, $personal[1]->id, $personal[2]->id],
            ])
            ->assertSessionHasErrors('equipo');

        $this->actingAs($admin)
            ->put(route(self::ADMINISTRACION_VEHICULOS_EQUIPO, $primero), [
                'conductor_id' => $personal[0]->id,
                'recolector_ids' => [$personal[1]->id, $personal[2]->id, $personal[3]->id],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->put(route(self::ADMINISTRACION_VEHICULOS_EQUIPO, $segundo), [
                'conductor_id' => $personal[0]->id,
                'recolector_ids' => [$personal[4]->id, $personal[5]->id, $personal[6]->id],
            ])
            ->assertSessionHasErrors('equipo');

        $this->assertTrue($primero->fresh()->tieneEquipoCompleto());
        $this->assertFalse($segundo->fresh()->tieneEquipoCompleto());
    }

    public function test_maintenance_cannot_start_twice_and_closing_restores_availability(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $tipo = TipoMantenimiento::create(['nombre' => 'Revisión general', 'activo' => true]);
        $camion = $this->crearCamion('VH001', self::ABC_123, ['estado' => 'Mantenimiento']);

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.mantenimiento.store'), [
                'camion_id' => $camion->id,
                'tipo_mantenimiento_id' => $tipo->id,
                'motivo' => 'Ruido en el motor.',
            ])
            ->assertSessionHasErrors('camion_id');

        $mantenimiento = MantenimientoVehiculo::create([
            'camion_id' => $camion->id,
            'tipo_mantenimiento_id' => $tipo->id,
            'solicitado_por' => $admin->id,
            'motivo' => 'Ruido en el motor.',
            'estado' => 'En curso',
            'estado_anterior' => 'Mantenimiento',
            'iniciado_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('administracion.vehiculos.mantenimiento.finalizar', $mantenimiento))
            ->assertSessionHasNoErrors();

        $this->assertSame('Disponible', $camion->fresh()->estado);
        $this->assertSame('Finalizado', $mantenimiento->fresh()->estado);
    }

    // ---------------------------------------------------------------
    // Tachos y rutas
    // ---------------------------------------------------------------

    public function test_bin_list_filters_and_map_render_and_activation_follows_the_form(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $tacho = $this->crearTacho(['nombre' => 'Plaza Mayor']);

        $this->actingAs($admin)
            ->get(route('administracion.tachos.index', ['buscar' => 'Plaza', 'estado' => Tacho::ESTADO_OPERATIVO]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tachos.data', 1)
                ->where('siguienteCodigo', 'TCH002'));

        $this->actingAs($admin)
            ->get(route('administracion.tachos.mapa'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Tachos/Mapa')->has('tachos', 1));

        $datos = [
            'nombre' => 'Plaza Mayor',
            'latitud' => -12.0464,
            'longitud' => -75.2195,
            'distancia_vacio_cm' => 80,
            'distancia_lleno_cm' => 8,
            'umbral_alerta' => 80,
        ];

        $this->actingAs($admin)
            ->put(route(self::ADMINISTRACION_TACHOS_UPDATE, $tacho), $datos)
            ->assertSessionHasNoErrors();
        $this->assertTrue($tacho->fresh()->activo);

        $this->actingAs($admin)
            ->put(route(self::ADMINISTRACION_TACHOS_UPDATE, $tacho), $datos + ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertSame(Tacho::ESTADO_INACTIVO, $tacho->fresh()->estado);

        $this->actingAs($admin)
            ->put(route(self::ADMINISTRACION_TACHOS_UPDATE, $tacho), $datos + ['activo' => true])
            ->assertSessionHasNoErrors();
        $this->assertSame(Tacho::ESTADO_OPERATIVO, $tacho->fresh()->estado);

        $this->actingAs($admin)
            ->post(route('administracion.tachos.token', $tacho))
            ->assertSessionHas('device_token');
    }

    public function test_full_bin_cannot_join_the_same_route_twice_or_two_active_routes(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $primerCamion = $this->camionConEquipo('VH101', self::AAA_101);
        $segundoCamion = $this->camionConEquipo('VH102', 'AAA-102');

        foreach ([$primerCamion, $segundoCamion] as $indice => $camion) {
            $reporte = $this->crearReporte($ciudadano, ['latitud' => -12.05 - ($indice / 100)]);
            $this->actingAs($admin)
                ->post(route('administracion.rutas.store'), [
                    'camion_id' => $camion->id,
                    'fecha' => today()->toDateString(),
                    'reporte_ids' => [$reporte->id],
                ])
                ->assertSessionHasNoErrors();
        }

        [$primera, $segunda] = RutaRecoleccion::orderBy('id')->get()->all();
        $tacho = $this->crearTacho([
            'nivel_porcentaje' => 97,
            'alerta_activa' => true,
            'estado' => Tacho::ESTADO_LLENO,
        ]);

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_TACHOS_RUTA, $tacho), ['ruta_id' => $primera->id])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_TACHOS_RUTA, $tacho), ['ruta_id' => $primera->id])
            ->assertSessionHasErrors(['ruta_id' => 'Este tacho ya está en esa ruta.']);

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_TACHOS_RUTA, $tacho), ['ruta_id' => $segunda->id])
            ->assertSessionHasErrors(['ruta_id' => 'El tacho ya está asignado a otra ruta activa.']);

        $this->actingAs($admin)
            ->get(route('administracion.tachos.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tachos.data.0.en_ruta', true)
                ->has('rutasActivas', 2));
    }

    public function test_routes_board_draws_missing_road_geometry_for_active_routes(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $camion = $this->camionConEquipo('VH101', self::AAA_101);
        $reporte = $this->crearReporte($ciudadano);
        $ruta = RutaRecoleccion::create([
            'camion_id' => $camion->id,
            'generado_por' => $admin->id,
            'fecha' => today(),
            'estado' => 'Planificada',
        ]);
        $ruta->reportes()->attach($reporte->id, ['orden' => 1, 'distancia_desde_anterior_km' => 1]);

        $this->assertEmpty($ruta->fresh()->geometria);

        $this->actingAs($admin)
            ->get(route('administracion.rutas.index'))
            ->assertOk();

        $this->assertNotEmpty($ruta->fresh()->geometria);
    }

    public function test_route_schedule_summary_names_weekdays_and_every_day(): void
    {
        $ruta = new RutaRecoleccion([
            'hora_inicio' => '08:00:00',
            'hora_fin' => '12:00:00',
            'dias_recoleccion' => [1, 2, 3, 4, 5],
        ]);
        $this->assertSame('Lun–Vie · 08:00–12:00', $ruta->horario_resumen);

        $ruta->dias_recoleccion = [1, 2, 3, 4, 5, 6, 7];
        $this->assertSame('Todos los días · 08:00–12:00', $ruta->horario_resumen);

        $ruta->dias_recoleccion = [6, 2];
        $this->assertSame('Mar, Sáb · 08:00–12:00', $ruta->horario_resumen);

        $ruta->dias_recoleccion = [];
        $this->assertNull($ruta->horario_resumen);
    }

    // ---------------------------------------------------------------
    // Equipo de limpieza
    // ---------------------------------------------------------------

    public function test_cleaning_team_closes_stops_and_the_route_finishes_with_the_last_one(): void
    {
        Storage::fake('public');
        Notification::fake();

        $admin = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $camion = $this->camionConEquipo('VH101', self::AAA_101);
        $conductor = $camion->personal->firstWhere('pivot.puesto', 'conductor');
        $primero = $this->crearReporte($ciudadano);
        $segundo = $this->crearReporte($ciudadano, ['latitud' => -12.07]);

        $this->actingAs($admin)
            ->post(route('administracion.rutas.store'), [
                'camion_id' => $camion->id,
                'fecha' => today()->toDateString(),
                'reporte_ids' => [$primero->id, $segundo->id],
            ])
            ->assertSessionHasNoErrors();

        $ruta = RutaRecoleccion::firstOrFail();

        $this->actingAs($conductor)
            ->get(route('limpieza.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Limpieza/Dashboard')
                ->where('resumen.pendientes', 2)
                ->where('resumen.conRuta', 2));

        $this->actingAs($conductor)
            ->get(route('limpieza.rutas.index'))
            ->assertOk();

        $this->actingAs($conductor)
            ->patch(route('limpieza.reportes.iniciar', $primero))
            ->assertSessionHasNoErrors();
        $this->assertSame('En atención', $primero->fresh()->estado);

        $this->actingAs($conductor)
            ->post(route('limpieza.reportes.evidencia', $primero), ['evidencia' => $this->imagenFalsa()])
            ->assertSessionHasNoErrors();
        $this->assertSame('En curso', $ruta->fresh()->estado);
        $this->assertNull($ruta->fresh()->finalizada_at);

        Storage::disk('public')->put(self::EVIDENCIAS_LIMPIEZA_ANTERIOR_PNG, 'x');
        $segundo->update(['evidencia_path' => self::EVIDENCIAS_LIMPIEZA_ANTERIOR_PNG]);

        $this->actingAs($conductor)
            ->post(route('limpieza.reportes.evidencia', $segundo), ['evidencia' => $this->imagenFalsa()])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing(self::EVIDENCIAS_LIMPIEZA_ANTERIOR_PNG);
        $this->assertSame('Finalizada', $ruta->fresh()->estado);
        $this->assertNotNull($ruta->fresh()->finalizada_at);
        $this->assertNotNull($segundo->fresh()->evidencia_url);
        Notification::assertSentToTimes($ciudadano, ReporteAtendido::class, 2);
    }

    // ---------------------------------------------------------------
    // Informes
    // ---------------------------------------------------------------

    public function test_every_report_section_can_be_exported_to_pdf(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $this->crearReporte($ciudadano);

        foreach (['resumen', 'incidencias', 'rutas', 'tachos', 'personal'] as $seccion) {
            $pdf = $this->actingAs($admin)
                ->get(route('administracion.reportes.pdf', ['seccion' => $seccion]));

            $pdf->assertOk();
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        }
    }

    public function test_bin_report_shows_the_last_reading_date(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $this->crearTacho(['nivel_porcentaje' => 40, 'ultima_lectura_at' => now()]);

        $this->actingAs($admin)
            ->get(route('administracion.reportes.tachos'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tachos.0.codigo', 'TCH001')
                ->where('tachos.0.ultimaLectura', now()->timezone(config('app.timezone'))->format('d/m/Y H:i')));
    }

    // ---------------------------------------------------------------
    // Ayudas
    // ---------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearCamion(string $codigo, string $placa, array $overrides = []): Camion
    {
        return Camion::create(array_merge([
            'codigo' => $codigo,
            'placa' => $placa,
            'marca' => 'Volvo',
            'modelo' => 'FM',
            'anio' => 2022,
            'capacidad_kg' => 12000,
            'estado' => 'Disponible',
        ], $overrides));
    }

    private function camionConEquipo(string $codigo, string $placa): Camion
    {
        $personal = User::factory(4)->create(['rol' => 'area_limpieza']);
        $camion = $this->crearCamion($codigo, $placa);
        $camion->personal()->attach([
            $personal[0]->id => ['puesto' => 'conductor'],
            $personal[1]->id => ['puesto' => 'recolector'],
            $personal[2]->id => ['puesto' => 'recolector'],
            $personal[3]->id => ['puesto' => 'recolector'],
        ]);

        return $camion->load('personal');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearTacho(array $overrides = []): Tacho
    {
        $tacho = new Tacho(array_merge([
            'codigo' => 'TCH001',
            'nombre' => 'Tacho de prueba',
            'latitud' => -12.0464,
            'longitud' => -75.2195,
            'distancia_vacio_cm' => 80,
            'distancia_lleno_cm' => 8,
            'umbral_alerta' => 80,
            'estado' => Tacho::ESTADO_OPERATIVO,
            'activo' => true,
        ], $overrides));
        $tacho->generarToken();
        $tacho->save();

        return $tacho;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearReporte(User $ciudadano, array $overrides = []): Reporte
    {
        return Reporte::create(array_merge([
            'user_id' => $ciudadano->id,
            'foto_path' => 'reportes_fotos/test.jpg',
            'latitud' => -12.05,
            'longitud' => -75.22,
            'descripcion' => 'Incidencia de prueba',
            'estado' => 'Pendiente',
        ], $overrides));
    }

    private function imagenFalsa(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'foto.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
    }
}
