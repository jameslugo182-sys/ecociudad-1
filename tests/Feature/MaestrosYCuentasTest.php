<?php

namespace Tests\Feature;

use App\Models\AreaUnidad;
use App\Models\Camion;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\RolSistema;
use App\Models\SedeTrabajo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaestrosYCuentasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_masters_index_redirects_to_roles_and_lists_render(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->get(route('administracion.contratos.maestros.index'))
            ->assertRedirect(route('administracion.contratos.maestros.roles.index'));

        $this->actingAs($admin)
            ->get(route('administracion.contratos.maestros.cargos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Contratos/Maestros/Cargos')->has('cargos'));

        $this->actingAs($admin)
            ->get(route('administracion.contratos.maestros.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contratos/Maestros/Roles')
                ->has('roles')
                ->has('modulos', 7));

        foreach (['areas' => 'Áreas / unidades', 'sedes' => 'Sedes de trabajo'] as $catalogo => $titulo) {
            $this->actingAs($admin)
                ->get(route("administracion.contratos.maestros.{$catalogo}.index"))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Contratos/Maestros/Catalogo')
                    ->where('titulo', $titulo)
                    ->has('items')
                    ->has('routeNames.store'));
        }
    }

    public function test_cargos_can_be_created_renamed_and_toggled(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.cargos.store'), ['nombre' => 'Supervisor de campo'])
            ->assertSessionHasNoErrors();

        $cargo = Cargo::where('nombre', 'Supervisor de campo')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('administracion.contratos.maestros.cargos.update', $cargo), [
                'nombre' => 'Supervisor general',
                'descripcion' => 'Coordina cuadrillas.',
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cargos', ['id' => $cargo->id, 'nombre' => 'Supervisor general']);

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.maestros.cargos.estado', $cargo), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($cargo->fresh()->activo);
    }

    public function test_areas_and_sedes_can_be_created_renamed_and_toggled(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.areas.store'), ['nombre' => 'Parques y jardines'])
            ->assertSessionHasNoErrors();
        $area = AreaUnidad::where('nombre', 'Parques y jardines')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('administracion.contratos.maestros.areas.update', $area), ['nombre' => 'Áreas verdes'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->patch(route('administracion.contratos.maestros.areas.estado', $area), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('areas_unidad', ['id' => $area->id, 'nombre' => 'Áreas verdes', 'activo' => false]);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.sedes.store'), ['nombre' => 'Sede norte'])
            ->assertSessionHasNoErrors();
        $sede = SedeTrabajo::where('nombre', 'Sede norte')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('administracion.contratos.maestros.sedes.update', $sede), ['nombre' => 'Sede sur'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->patch(route('administracion.contratos.maestros.sedes.estado', $sede), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sedes_trabajo', ['id' => $sede->id, 'nombre' => 'Sede sur', 'activo' => false]);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.sedes.store'), ['nombre' => 'Sede sur'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_roles_can_be_created_updated_and_toggled_with_protection_rules(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.maestros.roles.store'), [
                'nombre' => 'Supervisor',
                'codigo' => 'supervisor_limpieza',
                'modulos' => ['incidencias', 'reportes'],
            ])
            ->assertSessionHasNoErrors();

        $rol = RolSistema::where('codigo', 'supervisor_limpieza')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('administracion.contratos.maestros.roles.update', $rol), [
                'nombre' => 'Supervisor general',
                'codigo' => 'supervisor_general',
                'modulos' => ['incidencias'],
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('roles_sistema', ['id' => $rol->id, 'codigo' => 'supervisor_general']);

        // Un rol protegido conserva su código aunque se envíe otro distinto.
        $protegido = RolSistema::where('codigo', 'area_limpieza')->firstOrFail();
        $this->actingAs($admin)
            ->put(route('administracion.contratos.maestros.roles.update', $protegido), [
                'nombre' => 'Área de limpieza',
                'codigo' => 'otro_codigo',
                'modulos' => ['operaciones'],
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('area_limpieza', $protegido->fresh()->codigo);

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.maestros.roles.estado', $protegido), ['activo' => false])
            ->assertSessionHasErrors('rol');

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.maestros.roles.estado', $rol), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($rol->fresh()->activo);
    }

    public function test_a_role_with_active_accounts_cannot_be_deactivated(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $rol = RolSistema::create([
            'nombre' => 'Inspector',
            'codigo' => 'inspector',
            'modulos' => ['incidencias'],
        ]);
        User::factory()->create(['rol' => 'inspector', 'activo' => true]);

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.maestros.roles.estado', $rol), ['activo' => false])
            ->assertSessionHasErrors('rol');

        $this->assertTrue($rol->fresh()->activo);
    }

    public function test_accounts_page_lists_available_collaborators_and_existing_accounts(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        [$cargo, $area] = $this->catalogos();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('administracion.contratos.usuarios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contratos/Usuarios')
                ->has('disponibles', 1)
                ->has('cuentas', 0)
                ->has('roles'));

        $colaborador = Colaborador::firstOrFail();
        $this->actingAs($admin)
            ->post(route('administracion.contratos.usuarios.store'), [
                'colaborador_id' => $colaborador->id,
                'email' => 'cuenta.nueva@ecociudad.pe',
                'rol' => 'area_limpieza',
                'password' => 'Password-2026',
                'password_confirmation' => 'Password-2026',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('administracion.contratos.usuarios.index'))
            ->assertInertia(fn (Assert $page) => $page->has('disponibles', 0)->has('cuentas', 1));
    }

    public function test_account_can_be_updated_and_toggled_and_leaving_cleaning_releases_the_vehicle(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $operario = User::factory()->create([
            'rol' => 'area_limpieza',
            'colaborador_id' => Colaborador::create([
                'documento' => '70001234',
                'nombres' => 'Rosa',
                'apellido_paterno' => 'Ramos',
            ])->id,
        ]);
        $camion = Camion::create([
            'codigo' => 'VH001',
            'placa' => 'ABC-123',
            'marca' => 'Volvo',
            'modelo' => 'FM',
            'anio' => 2022,
            'capacidad_kg' => 12000,
            'estado' => 'Disponible',
        ]);
        $camion->personal()->attach($operario->id, ['puesto' => 'recolector']);

        $this->actingAs($admin)
            ->put(route('administracion.contratos.usuarios.update', $operario), [
                'email' => 'rosa.ramos@ecociudad.pe',
                'rol' => 'area_limpieza',
                'password' => 'Nueva-Clave-2026',
                'password_confirmation' => 'Nueva-Clave-2026',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('rosa.ramos@ecociudad.pe', $operario->fresh()->email);
        $this->assertSame(1, $operario->camiones()->count());

        $this->actingAs($admin)
            ->put(route('administracion.contratos.usuarios.update', $operario), [
                'email' => 'rosa.ramos@ecociudad.pe',
                'rol' => 'personal_municipal',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('personal_municipal', $operario->fresh()->rol);
        $this->assertSame(0, $operario->camiones()->count());

        $camion->personal()->attach($operario->id, ['puesto' => 'recolector']);
        $this->actingAs($admin)
            ->patch(route('administracion.contratos.usuarios.estado', $operario), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($operario->fresh()->activo);
        $this->assertSame(0, $operario->camiones()->count());

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.usuarios.estado', $operario), ['activo' => true])
            ->assertSessionHas('success', 'Cuenta activada.');
        $this->assertTrue($operario->fresh()->activo);
    }

    public function test_accounts_without_collaborator_cannot_be_managed_from_contracts(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $suelto = User::factory()->create(['rol' => 'ciudadano']);

        $this->actingAs($admin)
            ->put(route('administracion.contratos.usuarios.update', $suelto), [
                'email' => 'x@ecociudad.pe',
                'rol' => 'area_limpieza',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('administracion.contratos.usuarios.estado', $suelto), ['activo' => false])
            ->assertNotFound();
    }

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
}
