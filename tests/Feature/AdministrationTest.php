<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($administrador)
            ->post(route('administracion.contratos.store'), [
                'documento' => '72345678',
                'nombres' => 'Operario',
                'apellido_paterno' => 'Municipal',
                'numero' => 'CON-2026-001',
                'cargo_id' => $cargo->id,
                'tipo' => 'CAS',
                'fecha_inicio' => today()->subDay()->toDateString(),
                'fecha_fin' => today()->addYear()->toDateString(),
                'area' => 'Limpieza pública',
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
        $this->assertDatabaseMissing('reportes', ['id' => $reportes[0]->id, 'estado' => 'Pendiente']);

        $this->actingAs($personal[1])
            ->patch(route('limpieza.reportes.iniciar', $reportes[0]))
            ->assertRedirect();
        $this->assertDatabaseHas('reportes', ['id' => $reportes[0]->id, 'estado' => 'En atención']);
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
}
