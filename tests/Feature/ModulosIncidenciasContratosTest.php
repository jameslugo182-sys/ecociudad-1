<?php

namespace Tests\Feature;

use App\Models\AreaUnidad;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\Reporte;
use App\Models\User;
use App\Notifications\ReporteAtendido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Casos del plan de pruebas de los módulos "Incidencias Registradas" y
 * "Gestión de contratos" que no estaban cubiertos por otras clases.
 */
class ModulosIncidenciasContratosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------------------------------------------------------------
    // Incidencias Registradas
    // ---------------------------------------------------------------

    public function test_citizen_registers_incident_and_photo_is_stored(): void
    {
        Storage::fake('public');
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);

        $this->actingAs($ciudadano)
            ->post(route('reportes.store'), [
                'foto' => $this->imagenFalsa(),
                'latitud' => -12.0650,
                'longitud' => -75.2100,
                'descripcion' => 'Contenedor lleno frente al mercado.',
                'tipo_incidencia' => 'Contenedor desbordado',
            ])
            ->assertRedirect(route('reportes.index'))
            ->assertSessionHasNoErrors();

        $reporte = Reporte::query()->firstOrFail();

        $this->assertSame($ciudadano->id, $reporte->user_id);
        $this->assertSame('Pendiente', $reporte->estado);
        Storage::disk('public')->assertExists($reporte->foto_path);
    }

    public function test_report_rejects_non_image_files_and_out_of_range_coordinates(): void
    {
        Storage::fake('public');
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);

        $this->actingAs($ciudadano)
            ->post(route('reportes.store'), [
                'foto' => UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'),
                'latitud' => 95,
                'longitud' => -200,
                'descripcion' => 'Prueba de límites.',
                'tipo_incidencia' => 'Otros',
            ])
            ->assertSessionHasErrors(['foto', 'latitud', 'longitud']);

        $this->actingAs($ciudadano)
            ->post(route('reportes.store'), [
                'foto' => $this->imagenFalsa()->size(10241),
                'latitud' => -12.06,
                'longitud' => -75.21,
                'descripcion' => 'Imagen demasiado pesada.',
                'tipo_incidencia' => 'Otros',
            ])
            ->assertSessionHasErrors('foto');

        $this->assertDatabaseCount('reportes', 0);
    }

    public function test_assignment_rejects_users_that_are_not_active_cleaning_staff(): void
    {
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $inactivo = User::factory()->create(['rol' => 'area_limpieza', 'activo' => false]);
        $reporte = $this->crearReporte($ciudadano);

        foreach ([$ciudadano, $inactivo] as $destino) {
            $this->actingAs($municipal)
                ->patch(route('admin.reportes.asignar', $reporte), ['assigned_to' => $destino->id])
                ->assertSessionHasErrors('assigned_to');
        }

        $this->assertDatabaseHas('reportes', ['id' => $reporte->id, 'estado' => 'Pendiente', 'assigned_to' => null]);
    }

    public function test_attending_an_incident_notifies_the_citizen_and_reopening_clears_the_date(): void
    {
        Notification::fake();
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);
        $reporte = $this->crearReporte($ciudadano, ['estado' => 'Asignado', 'assigned_to' => $limpieza->id]);

        $this->actingAs($municipal)
            ->patch(route('admin.reportes.estado', $reporte), ['estado' => 'Atendido'])
            ->assertRedirect();

        $this->assertNotNull($reporte->fresh()->atendido_at);
        Notification::assertSentToTimes($ciudadano, ReporteAtendido::class, 1);

        $this->actingAs($municipal)
            ->patch(route('admin.reportes.estado', $reporte), ['estado' => 'En atención'])
            ->assertRedirect();

        $this->assertNull($reporte->fresh()->atendido_at);
        Notification::assertSentToTimes($ciudadano, ReporteAtendido::class, 1);

        $this->actingAs($municipal)
            ->patch(route('admin.reportes.estado', $reporte), ['estado' => 'Inventado'])
            ->assertSessionHasErrors('estado');
    }

    public function test_pending_incident_cannot_jump_to_attended_or_progress_without_a_responsible(): void
    {
        Notification::fake();
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $reporte = $this->crearReporte($ciudadano);

        foreach (['Atendido', 'En atención', 'Asignado'] as $estado) {
            $this->actingAs($administrador)
                ->patch(route('administracion.incidencias.estado', $reporte), ['estado' => $estado])
                ->assertSessionHasErrors('estado');
        }

        $this->assertDatabaseHas('reportes', ['id' => $reporte->id, 'estado' => 'Pendiente']);
        $this->assertNull($reporte->fresh()->atendido_at);
        Notification::assertNothingSent();
    }

    public function test_incident_follows_the_allowed_status_flow(): void
    {
        Notification::fake();
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);
        $reporte = $this->crearReporte($ciudadano, ['assigned_to' => $limpieza->id]);

        $recorrido = ['Asignado', 'En atención', 'Asignado', 'Pendiente', 'Asignado', 'En atención', 'En atención', 'Atendido', 'En atención'];

        foreach ($recorrido as $estado) {
            $this->actingAs($municipal)
                ->patch(route('admin.reportes.estado', $reporte), ['estado' => $estado])
                ->assertSessionHasNoErrors();

            $this->assertSame($estado, $reporte->fresh()->estado);
        }

        $this->actingAs($municipal)
            ->patch(route('admin.reportes.estado', $reporte), ['estado' => 'Atendido'])
            ->assertSessionHasNoErrors();

        foreach (['Asignado', 'Pendiente'] as $estado) {
            $this->actingAs($municipal)
                ->patch(route('admin.reportes.estado', $reporte), ['estado' => $estado])
                ->assertSessionHasErrors('estado');
        }

        $this->assertSame('Atendido', $reporte->fresh()->estado);
        Notification::assertSentToTimes($ciudadano, ReporteAtendido::class, 2);
    }

    public function test_attended_incident_cannot_be_reassigned_but_an_active_one_can(): void
    {
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $primero = User::factory()->create(['rol' => 'area_limpieza']);
        $segundo = User::factory()->create(['rol' => 'area_limpieza']);

        $activo = $this->crearReporte($ciudadano, ['estado' => 'En atención', 'assigned_to' => $primero->id]);
        $this->actingAs($municipal)
            ->patch(route('admin.reportes.asignar', $activo), ['assigned_to' => $segundo->id])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reportes', ['id' => $activo->id, 'estado' => 'Asignado', 'assigned_to' => $segundo->id]);

        $cerrado = $this->crearReporte($ciudadano, [
            'estado' => 'Atendido',
            'assigned_to' => $primero->id,
            'atendido_at' => now(),
        ]);
        $this->actingAs($municipal)
            ->patch(route('admin.reportes.asignar', $cerrado), ['assigned_to' => $segundo->id])
            ->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseHas('reportes', ['id' => $cerrado->id, 'estado' => 'Atendido', 'assigned_to' => $primero->id]);
    }

    // ---------------------------------------------------------------
    // Gestión de contratos
    // ---------------------------------------------------------------

    public function test_contract_number_must_be_unique_and_end_date_cannot_precede_start(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                'documento' => '81234567',
            ]))
            ->assertSessionHasErrors('numero');

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                'documento' => '81234567',
                'numero' => 'CON-2026-FECHAS',
                'fecha_inicio' => now()->addMonth()->toDateString(),
                'fecha_fin' => now()->toDateString(),
            ]))
            ->assertSessionHasErrors('fecha_fin');

        $this->assertDatabaseCount('contratos', 1);
    }

    public function test_contract_can_be_edited_keeping_its_own_number(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area))
            ->assertSessionHasNoErrors();

        $contrato = Contrato::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.update', $contrato), $this->payloadContrato($cargo, $area, [
                'remuneracion' => 2500,
                'telefono' => '999111222',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contratos', ['id' => $contrato->id, 'remuneracion' => 2500]);
        $this->assertDatabaseHas('colaboradores', ['id' => $contrato->colaborador_id, 'telefono' => '999111222']);
    }

    public function test_editing_a_contract_cannot_overlap_another_contract_of_the_same_collaborator(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area))
            ->assertSessionHasNoErrors();

        $vigente = Contrato::query()->firstOrFail();
        $anterior = Contrato::create([
            'colaborador_id' => $vigente->colaborador_id,
            'cargo_id' => $cargo,
            'area_id' => $area,
            'numero' => 'CON-2024-ANT',
            'tipo' => 'CAS',
            'fecha_inicio' => now()->subYears(3)->toDateString(),
            'fecha_fin' => now()->subYears(2)->toDateString(),
            'area' => 'Limpieza pública',
            'estado' => 'Finalizado',
        ]);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.update', $anterior), $this->payloadContrato($cargo, $area, [
                'numero' => 'CON-2024-ANT',
                'estado' => 'Finalizado',
                'fecha_inicio' => now()->subMonth()->toDateString(),
                'fecha_fin' => now()->addMonth()->toDateString(),
            ]))
            ->assertSessionHasErrors('fecha_inicio');
    }

    public function test_existing_collaborator_can_receive_a_renewal_contract(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                'numero' => 'CON-2026-RENOV',
                'telefono' => '999000111',
                'fecha_inicio' => now()->addYear()->addDay()->toDateString(),
                'fecha_fin' => now()->addYears(2)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('colaboradores', 1);
        $this->assertDatabaseCount('contratos', 2);

        $colaborador = Colaborador::query()->firstOrFail();
        $this->assertSame(2, $colaborador->contratos()->count());
        $this->assertSame('999000111', $colaborador->telefono);
    }

    public function test_new_contract_cannot_overlap_an_existing_one_of_the_same_collaborator(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area))
            ->assertSessionHasNoErrors();

        $solapado = $this->payloadContrato($cargo, $area, [
            'numero' => 'CON-2026-SOLAPE',
            'telefono' => '999888777',
            'fecha_inicio' => now()->addMonth()->toDateString(),
            'fecha_fin' => now()->addMonths(6)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $solapado)
            ->assertSessionHasErrors('fecha_inicio');

        $this->assertDatabaseCount('contratos', 1);
        $this->assertSame('987654321', Colaborador::query()->firstOrFail()->telefono);

        Contrato::query()->update(['estado' => 'Cancelado']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $solapado)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contratos', 2);
    }

    public function test_editing_a_contract_cannot_take_the_document_of_another_collaborator(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        foreach ([['70000021', 'CON-D-021'], ['70000022', 'CON-D-022']] as [$documento, $numero]) {
            $this->actingAs($admin)
                ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                    'documento' => $documento,
                    'numero' => $numero,
                ]))
                ->assertSessionHasNoErrors();
        }

        $segundo = Contrato::query()->where('numero', 'CON-D-022')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.update', $segundo), $this->payloadContrato($cargo, $area, [
                'documento' => '70000021',
                'numero' => 'CON-D-022',
            ]))
            ->assertSessionHasErrors('documento');
    }

    public function test_contract_document_must_be_a_pdf(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                'archivo_contrato' => UploadedFile::fake()->create('contrato.exe', 10, 'application/octet-stream'),
            ]))
            ->assertSessionHasErrors('archivo_contrato');

        $this->assertDatabaseCount('contratos', 0);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                'archivo_contrato' => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
            ]))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists(Contrato::query()->firstOrFail()->documento_path);
    }

    public function test_contract_list_filters_by_search_term_and_status(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
            'documento' => '70000001',
            'apellido_paterno' => 'Quispe',
            'numero' => 'CON-A-001',
        ]))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
            'documento' => '70000002',
            'apellido_paterno' => 'Mamani',
            'numero' => 'CON-B-002',
            'estado' => 'Suspendido',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('administracion.contratos.index', ['buscar' => 'QUISPE']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contratos/Registro')
                ->has('contratos.data', 1)
                ->where('contratos.data.0.numero', 'CON-A-001'));

        $this->actingAs($admin)
            ->get(route('administracion.contratos.index', ['estado' => 'Suspendido']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('contratos.data', 1)
                ->where('contratos.data.0.numero', 'CON-B-002'));

        $this->actingAs($admin)
            ->get(route('administracion.contratos.index', ['estado' => 'Inventado']))
            ->assertSessionHasErrors('estado');
    }

    public function test_account_rejects_duplicate_email_and_deactivation_blocks_login(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        foreach ([['70000011', 'CON-C-011'], ['70000012', 'CON-C-012']] as [$documento, $numero]) {
            $this->actingAs($admin)
                ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                    'documento' => $documento,
                    'numero' => $numero,
                ]))
                ->assertSessionHasNoErrors();
        }

        $primero = Colaborador::where('documento', '70000011')->firstOrFail();
        $segundo = Colaborador::where('documento', '70000012')->firstOrFail();
        $cuenta = fn (Colaborador $colaborador) => [
            'colaborador_id' => $colaborador->id,
            'email' => 'operario@ecociudad.pe',
            'rol' => 'area_limpieza',
            'password' => 'Password-2026',
            'password_confirmation' => 'Password-2026',
        ];

        $this->actingAs($admin)
            ->post(route('administracion.contratos.usuarios.store'), $cuenta($primero))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->post(route('administracion.contratos.usuarios.store'), $cuenta($segundo))
            ->assertSessionHasErrors('email');

        $usuario = User::where('email', 'operario@ecociudad.pe')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.usuarios.estado', $usuario), ['activo' => false])
            ->assertRedirect();
        $this->assertFalse($usuario->fresh()->activo);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'operario@ecociudad.pe', 'password' => 'Password-2026']);
        $this->assertGuest();
    }

    public function test_contracts_module_is_closed_to_guests_and_roles_without_the_module(): void
    {
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);

        $this->get(route('administracion.contratos.index'))->assertRedirect(route('login'));

        foreach ([$ciudadano, $limpieza, $municipal] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('administracion.contratos.index'))
                ->assertForbidden();
            $this->actingAs($usuario)
                ->post(route('administracion.contratos.store'), [])
                ->assertForbidden();
        }
    }

    // ---------------------------------------------------------------
    // Ayudas
    // ---------------------------------------------------------------

    /**
     * @return array{0: int, 1: int}
     */
    private function catalogos(): array
    {
        return [
            Cargo::where('nombre', 'Recolector')->firstOrFail()->id,
            AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail()->id,
        ];
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

    private function imagenFalsa(): File
    {
        return UploadedFile::fake()->createWithContent(
            'incidencia.png',
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
            )
        );
    }
}
