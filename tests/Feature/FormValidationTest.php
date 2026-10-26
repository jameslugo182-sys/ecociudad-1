<?php

namespace Tests\Feature;

use App\Models\AreaUnidad;
use App\Models\Camion;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\TipoMantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormValidationTest extends TestCase
{
    use RefreshDatabase;

    private const ADMINISTRACION_VEHICULOS_STORE = 'administracion.vehiculos.store';

    private const ABC_123 = 'ABC-123';

    private const ADMINISTRACION_CONTRATOS_STORE = 'administracion.contratos.store';

    private const REPORTES_STORE = 'reportes.store';

    private const ADMINISTRACION_VEHICULOS_MAESTROS_TIPOS_STORE = 'administracion.vehiculos.maestros.tipos.store';

    public function test_vehicle_cannot_be_registered_with_only_plate_and_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_STORE), [
                'placa' => self::ABC_123,
                'estado' => 'Disponible',
                'foto' => $this->imagenFalsa(),
            ])
            ->assertSessionHasErrors(['marca', 'modelo', 'anio', 'capacidad_kg']);

        $this->assertDatabaseCount('camiones', 0);
    }

    public function test_vehicle_rejects_invalid_plate_and_requires_unique_plate(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_STORE), $this->payloadVehiculo([
                'placa' => 'PLACAINVALIDA',
            ]))
            ->assertSessionHasErrors('placa');

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_STORE), $this->payloadVehiculo())
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_STORE), $this->payloadVehiculo([
                'placa' => self::ABC_123,
            ]))
            ->assertSessionHasErrors('placa');
    }

    public function test_complete_vehicle_can_be_registered_and_updated_without_new_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_STORE), $this->payloadVehiculo())
            ->assertSessionHasNoErrors();

        $camion = Camion::where('placa', self::ABC_123)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.update', $camion), [
                'placa' => self::ABC_123,
                'marca' => 'Volvo',
                'modelo' => 'VM 270',
                'anio' => 2023,
                'capacidad_kg' => 14000,
                'estado' => 'Disponible',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('camiones', [
            'id' => $camion->id,
            'modelo' => 'VM 270',
            'capacidad_kg' => 14000,
        ]);
    }

    public function test_contract_rejects_incomplete_personal_data_and_duplicate_document(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $cargo = Cargo::where('nombre', 'Recolector')->firstOrFail();
        $area = AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail();

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_CONTRATOS_STORE), [
                'documento' => 'ABC',
                'nombres' => 'Luis',
                'numero' => 'CON-INCOMPLETO',
                'cargo_id' => $cargo->id,
                'tipo' => 'CAS',
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => now()->addYear()->toDateString(),
                'area_id' => $area->id,
                'estado' => 'Vigente',
            ])
            ->assertSessionHasErrors([
                'documento',
                'apellido_paterno',
                'apellido_materno',
                'sexo',
                'nacionalidad',
                'telefono',
                'direccion',
                'remuneracion',
                'jornada_horas',
            ]);

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_CONTRATOS_STORE), $this->payloadContrato($cargo->id, $area->id))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_CONTRATOS_STORE), $this->payloadContrato($cargo->id, $area->id, [
                'numero' => 'CON-2026-DUP',
            ]))
            ->assertSessionHasErrors('fecha_inicio');

        $this->assertDatabaseCount('contratos', 1);
    }

    public function test_citizen_report_requires_photo_gps_type_and_description(): void
    {
        Storage::fake('public');
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);

        $this->actingAs($ciudadano)
            ->post(route(self::REPORTES_STORE), [])
            ->assertSessionHasErrors(['foto', 'latitud', 'longitud', 'descripcion', 'tipo_incidencia']);

        $this->actingAs($ciudadano)
            ->post(route(self::REPORTES_STORE), [
                'foto' => $this->imagenFalsa(),
                'latitud' => -12.068,
                'longitud' => -75.210,
                'descripcion' => 'Basura en la vereda.',
                'tipo_incidencia' => 'Tipo inventado',
            ])
            ->assertSessionHasErrors('tipo_incidencia');

        $this->actingAs($ciudadano)
            ->post(route(self::REPORTES_STORE), [
                'foto' => $this->imagenFalsa(),
                'latitud' => -12.068,
                'longitud' => -75.210,
                'descripcion' => 'Basura en la vereda.',
                'tipo_incidencia' => 'Contenedor desbordado',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reportes', [
            'user_id' => $ciudadano->id,
            'tipo_incidencia' => 'Contenedor desbordado',
        ]);
    }

    public function test_masters_and_roles_reject_empty_or_duplicate_names(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.cargos.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.cargos.store'), ['nombre' => 'A'])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.roles.store'), [
                'nombre' => 'Supervisor',
                'codigo' => 'supervisor_limpieza',
                'modulos' => [],
            ])
            ->assertSessionHasErrors('modulos');

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_MAESTROS_TIPOS_STORE), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_MAESTROS_TIPOS_STORE), ['nombre' => 'Alineación de dirección'])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_VEHICULOS_MAESTROS_TIPOS_STORE), ['nombre' => 'Alineación de dirección'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_collaborator_account_requires_password_confirmation_and_role(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $cargo = Cargo::where('nombre', 'Recolector')->firstOrFail();
        $area = AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail();

        $this->actingAs($admin)
            ->post(route(self::ADMINISTRACION_CONTRATOS_STORE), $this->payloadContrato($cargo->id, $area->id, [
                'documento' => '45556677',
                'numero' => 'CON-CTA-001',
            ]))
            ->assertSessionHasNoErrors();

        $colaborador = Colaborador::where('documento', '45556677')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.usuarios.store'), [
                'colaborador_id' => $colaborador->id,
                'email' => 'cuenta@ecociudad.pe',
            ])
            ->assertSessionHasErrors(['rol', 'password']);
    }

    public function test_maintenance_and_collection_schedule_require_core_fields(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $camion = Camion::create([
            'codigo' => 'VH-VAL',
            'placa' => 'VAL-100',
            'marca' => 'Hino',
            'modelo' => '500',
            'anio' => 2022,
            'capacidad_kg' => 10000,
            'estado' => 'Disponible',
        ]);
        $tipo = TipoMantenimiento::create(['nombre' => 'Frenos', 'activo' => true]);

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.mantenimiento.store'), [
                'camion_id' => $camion->id,
            ])
            ->assertSessionHasErrors(['tipo_mantenimiento_id', 'motivo']);

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.mantenimiento.store'), [
                'camion_id' => $camion->id,
                'tipo_mantenimiento_id' => $tipo->id,
                'motivo' => 'Ruidos en el eje trasero al frenar.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('administracion.rutas.store'), [])
            ->assertSessionHasErrors(['camion_id', 'fecha', 'reporte_ids']);
    }

    public function test_team_assignment_requires_one_driver_and_three_collectors(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $camion = Camion::create([
            'codigo' => 'VH-EQ',
            'placa' => 'EQP-101',
            'marca' => 'Volvo',
            'modelo' => 'VM',
            'anio' => 2021,
            'capacidad_kg' => 12000,
            'estado' => 'Disponible',
        ]);
        $conductor = User::factory()->create(['rol' => 'area_limpieza']);

        $this->actingAs($admin)
            ->put(route('administracion.vehiculos.equipo', $camion), [
                'conductor_id' => $conductor->id,
                'recolector_ids' => [],
            ])
            ->assertSessionHasErrors('recolector_ids');
    }

    public function test_unauthenticated_user_cannot_submit_protected_forms(): void
    {
        $this->post(route(self::ADMINISTRACION_VEHICULOS_STORE), [])->assertRedirect();
        $this->post(route(self::REPORTES_STORE), [])->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadVehiculo(array $overrides = []): array
    {
        return array_merge([
            'placa' => self::ABC_123,
            'marca' => 'Mercedes-Benz',
            'modelo' => 'Atego',
            'anio' => 2022,
            'capacidad_kg' => 12000,
            'estado' => 'Disponible',
            'foto' => $this->imagenFalsa(),
        ], $overrides);
    }

    private function imagenFalsa(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'unidad.png',
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
            )
        );
    }
}
