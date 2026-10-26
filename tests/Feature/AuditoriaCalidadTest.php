<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RolSistema;
use App\Models\RutaRecoleccion;
use App\Models\Tacho;
use App\Models\User;
use App\Notifications\ReporteAtendido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditoriaCalidadTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_REPORTES_INDEX = 'admin.reportes.index';

    private const ADMINISTRACION_TACHOS_INDEX = 'administracion.tachos.index';

    private const TACHO_MERCADO_CENTRAL = 'Tacho Mercado Central';

    private const ADMINISTRACION_VEHICULOS_INDEX = 'administracion.vehiculos.index';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_metrics_board_uses_aggregates_and_rejects_citizens(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $this->crearReporte($ciudadano, [
            'estado' => 'Atendido',
            'tipo_incidencia' => 'Residuos',
            'atendido_at' => now(),
        ]);
        $this->crearReporte($ciudadano, ['estado' => 'Pendiente']);

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Metricas')
                ->where('resumen.total', 2)
                ->where('resumen.atendidos', 1)
                ->where('resumen.tasaAtencion', 50));

        $this->actingAs($ciudadano)
            ->get(route('administracion.reportes.index'))
            ->assertForbidden();

        $this->actingAs($ciudadano)
            ->get(route('admin.metricas.index'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_filters_and_map_share_route_names(): void
    {
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $this->crearReporte($ciudadano, ['estado' => 'Pendiente', 'descripcion' => 'Bolsa abandonada']);
        $this->crearReporte($ciudadano, ['estado' => 'Atendido', 'descripcion' => 'Lodo en vereda', 'atendido_at' => now()]);

        $this->actingAs($municipal)
            ->get(route(self::ADMIN_REPORTES_INDEX, ['estado' => 'Pendiente', 'buscar' => 'Bolsa']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('reportes.data', 1)
                ->where('filters.estado', 'Pendiente')
                ->where('filters.buscar', 'Bolsa'));

        $this->actingAs($municipal)
            ->get(route('admin.mapa'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Mapa')
                ->has('reportes', 2)
                ->where('routeNames.mapa', 'admin.mapa'));
    }

    public function test_nearest_neighbor_route_is_generated_only_with_pending_reports(): void
    {
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);

        $this->actingAs($municipal)
            ->from(route(self::ADMIN_REPORTES_INDEX))
            ->post(route('admin.rutas.generar'), ['assigned_to' => $limpieza->id])
            ->assertRedirect(route(self::ADMIN_REPORTES_INDEX))
            ->assertSessionHas('error');

        $cerca = $this->crearReporte($ciudadano, [
            'assigned_to' => $limpieza->id,
            'estado' => 'Asignado',
            'latitud' => -12.0500,
            'longitud' => -75.2200,
        ]);
        $lejos = $this->crearReporte($ciudadano, [
            'assigned_to' => $limpieza->id,
            'estado' => 'Asignado',
            'latitud' => -12.0800,
            'longitud' => -75.2500,
        ]);

        $this->actingAs($municipal)
            ->from(route(self::ADMIN_REPORTES_INDEX))
            ->post(route('admin.rutas.generar'), ['assigned_to' => $limpieza->id])
            ->assertRedirect(route(self::ADMIN_REPORTES_INDEX))
            ->assertSessionHas('success');

        $this->assertSame(1, $cerca->fresh()->ruta_orden);
        $this->assertSame(2, $lejos->fresh()->ruta_orden);
    }

    public function test_tacho_can_be_updated_deactivated_and_shown_on_map(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $tacho = $this->crearTacho($administrador);

        $this->actingAs($administrador)
            ->from(route(self::ADMINISTRACION_TACHOS_INDEX))
            ->put(route('administracion.tachos.update', $tacho), [
                'nombre' => self::TACHO_MERCADO_CENTRAL,
                'direccion' => 'Jr. Real',
                'latitud' => -12.0464,
                'longitud' => -75.2195,
                'distancia_vacio_cm' => 90,
                'distancia_lleno_cm' => 10,
                'umbral_alerta' => 85,
                'activo' => false,
            ])
            ->assertRedirect(route(self::ADMINISTRACION_TACHOS_INDEX))
            ->assertSessionHasNoErrors();

        $tacho->refresh();
        $this->assertSame(self::TACHO_MERCADO_CENTRAL, $tacho->nombre);
        $this->assertSame(Tacho::ESTADO_INACTIVO, $tacho->estado);
        $this->assertFalse($tacho->activo);

        $this->actingAs($administrador)
            ->from(route(self::ADMINISTRACION_TACHOS_INDEX))
            ->put(route('administracion.tachos.update', $tacho), [
                'nombre' => self::TACHO_MERCADO_CENTRAL,
                'direccion' => 'Jr. Real',
                'latitud' => -12.0464,
                'longitud' => -75.2195,
                'distancia_vacio_cm' => 90,
                'distancia_lleno_cm' => 10,
                'umbral_alerta' => 85,
                'activo' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Tacho::ESTADO_OPERATIVO, $tacho->fresh()->estado);

        $this->actingAs($administrador)
            ->get(route('administracion.tachos.mapa'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tachos/Mapa')
                ->has('tachos', 1)
                ->where('tachos.0.codigo', $tacho->codigo));

        $this->actingAs($administrador)
            ->from(route(self::ADMINISTRACION_TACHOS_INDEX))
            ->post(route('administracion.tachos.token', $tacho))
            ->assertRedirect(route(self::ADMINISTRACION_TACHOS_INDEX))
            ->assertSessionHas('device_token');
    }

    public function test_iot_reading_requires_distance_or_percentage(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $tacho = $this->crearTacho($administrador);
        $token = $tacho->generarToken();
        $tacho->save();

        $this->postJson(route('iot.tachos.lecturas', $tacho), [], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['distancia_cm', 'porcentaje']);
    }

    public function test_cleaning_staff_cannot_start_or_close_invalid_states(): void
    {
        Storage::fake('public');
        Notification::fake();

        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $pendiente = $this->crearReporte($ciudadano, [
            'assigned_to' => $limpieza->id,
            'estado' => 'Pendiente',
        ]);
        $atendido = $this->crearReporte($ciudadano, [
            'assigned_to' => $limpieza->id,
            'estado' => 'Atendido',
            'atendido_at' => now(),
        ]);
        $asignado = $this->crearReporte($ciudadano, [
            'assigned_to' => $limpieza->id,
            'estado' => 'Asignado',
        ]);

        $this->actingAs($limpieza)
            ->patch(route('limpieza.reportes.iniciar', $pendiente))
            ->assertStatus(422);

        $this->actingAs($limpieza)
            ->post(route('limpieza.reportes.evidencia', $atendido), [
                'evidencia' => $this->imagenFalsa(),
            ])
            ->assertStatus(422);

        $this->actingAs($limpieza)
            ->patch(route('limpieza.reportes.iniciar', $asignado))
            ->assertRedirect();

        $this->assertSame('En atención', $asignado->fresh()->estado);

        $this->actingAs($limpieza)
            ->get(route('limpieza.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Limpieza/Dashboard')
                ->has('reportes', 3));

        Notification::assertNothingSent();
        Notification::assertNotSentTo($ciudadano, ReporteAtendido::class);
    }

    public function test_truck_with_routes_cannot_be_deleted(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $camion = Camion::create([
            'codigo' => 'VH099',
            'placa' => 'AUD-099',
            'estado' => 'Disponible',
        ]);
        RutaRecoleccion::create([
            'camion_id' => $camion->id,
            'generado_por' => $administrador->id,
            'fecha' => today(),
            'estado' => 'Planificada',
        ]);

        $this->actingAs($administrador)
            ->from(route(self::ADMINISTRACION_VEHICULOS_INDEX))
            ->delete(route('administracion.vehiculos.destroy', $camion))
            ->assertRedirect(route(self::ADMINISTRACION_VEHICULOS_INDEX))
            ->assertSessionHasErrors('camion');

        $this->assertDatabaseHas('camiones', ['id' => $camion->id]);

        $libre = Camion::create([
            'codigo' => 'VH100',
            'placa' => 'AUD-100',
            'estado' => 'Disponible',
        ]);

        $this->actingAs($administrador)
            ->from(route(self::ADMINISTRACION_VEHICULOS_INDEX))
            ->delete(route('administracion.vehiculos.destroy', $libre))
            ->assertRedirect(route(self::ADMINISTRACION_VEHICULOS_INDEX))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('camiones', ['id' => $libre->id]);
    }

    public function test_custom_role_only_sees_granted_dashboard_modules(): void
    {
        RolSistema::create([
            'nombre' => 'Auditor de calidad',
            'codigo' => 'auditor_calidad',
            'descripcion' => 'Rol de prueba para cobertura',
            'modulos' => ['reportes', 'tachos'],
            'protegido' => false,
            'activo' => true,
        ]);

        $auditor = User::factory()->create(['rol' => 'auditor_calidad']);

        $this->actingAs($auditor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panel')
                ->has('modulos', 2)
                ->where('modulos.0.nombre', 'Reportes')
                ->where('modulos.1.nombre', 'Tachos IoT'));

        $this->actingAs($auditor)
            ->get(route('administracion.contratos.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function crearReporte(User $ciudadano, array $attributes = []): Reporte
    {
        return Reporte::create(array_merge([
            'user_id' => $ciudadano->id,
            'foto_path' => 'reportes_fotos/prueba.jpg',
            'latitud' => -12.056,
            'longitud' => -75.221,
            'descripcion' => 'Acumulación de residuos en la vía pública.',
            'estado' => 'Pendiente',
        ], $attributes));
    }

    private function crearTacho(User $administrador): Tacho
    {
        $this->actingAs($administrador)
            ->from(route(self::ADMINISTRACION_TACHOS_INDEX))
            ->post(route('administracion.tachos.store'), [
                'nombre' => 'Tacho Plaza Huamanmarca',
                'direccion' => 'Plaza Huamanmarca',
                'latitud' => -12.0464,
                'longitud' => -75.2195,
                'distancia_vacio_cm' => 80,
                'distancia_lleno_cm' => 8,
                'umbral_alerta' => 80,
            ])
            ->assertSessionHasNoErrors();

        return Tacho::query()->firstOrFail();
    }

    private function imagenFalsa(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'cierre.png',
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
            )
        );
    }
}
