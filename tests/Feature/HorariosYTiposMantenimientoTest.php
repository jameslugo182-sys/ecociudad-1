<?php

namespace Tests\Feature;

use App\Models\AreaUnidad;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\HorarioPersonal;
use App\Models\PlantillaHorario;
use App\Models\TipoMantenimiento;
use App\Models\User;
use App\Services\HorarioSemanal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HorariosYTiposMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_schedule_templates_and_assignments_pages_render_and_template_can_be_deleted(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.horarios.plantillas.store'), $this->horario(['nombre' => 'Plantilla mañana']))
            ->assertSessionHasNoErrors();

        $plantilla = PlantillaHorario::where('nombre', 'Plantilla mañana')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('administracion.contratos.horarios.plantillas'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contratos/Horarios/Plantillas')
                ->has('plantillas', 1));

        $this->actingAs($admin)
            ->get(route('administracion.contratos.horarios.registro'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contratos/Horarios/Registro')
                ->has('horarios', 0)
                ->has('colaboradores', 0)
                ->has('plantillas', 1));

        $this->actingAs($admin)
            ->delete(route('administracion.contratos.horarios.plantillas.destroy', $plantilla))
            ->assertSessionHas('success', 'Plantilla eliminada.');

        $this->assertDatabaseMissing('plantillas_horario', ['id' => $plantilla->id]);
    }

    public function test_schedule_requires_at_least_one_loaded_shift_in_the_weekly_table(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.horarios.plantillas.store'), $this->horario([
                'nombre' => 'Vacía',
                'dias' => [
                    1 => ['manana' => ['inicio' => '', 'fin' => '']],
                    2 => ['manana' => ['inicio' => '07:00', 'fin' => '']],
                ],
            ]))
            ->assertSessionHasErrors('dias');

        $this->assertDatabaseCount('plantillas_horario', 0);
    }

    public function test_assignments_list_only_collaborators_with_a_current_contract(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato(
                Cargo::where('nombre', 'Recolector')->firstOrFail()->id,
                AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail()->id,
            ))
            ->assertSessionHasNoErrors();

        $colaborador = Colaborador::firstOrFail();

        $this->actingAs($admin)
            ->post(route('administracion.contratos.horarios.registro.store'), $this->horario([
                'colaborador_id' => $colaborador->id,
                'vigencia_inicio' => today()->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('administracion.contratos.horarios.registro'))
            ->assertInertia(fn (Assert $page) => $page->has('horarios', 1)->has('colaboradores', 1));

        $this->assertSame(1, HorarioPersonal::count());
    }

    public function test_weekly_schedule_helper_handles_invalid_and_overnight_ranges(): void
    {
        $this->assertSame(0.0, HorarioSemanal::horasEntre(null, '10:00'));
        $this->assertSame(0.0, HorarioSemanal::horasEntre('xx', '10:00'));
        $this->assertSame(0.0, HorarioSemanal::horasEntre('08:00', 'zz'));
        $this->assertSame(8.0, HorarioSemanal::horasEntre('22:00', '06:00'));

        $this->assertSame(6.5, HorarioSemanal::totalHoras([
            1 => ['a' => ['inicio' => '08:00', 'fin' => '14:30'], 'b' => 'no-es-turno'],
            2 => 'no-es-dia',
        ]));

        $this->assertSame('—', HorarioSemanal::rango(null));
        $this->assertSame('—', HorarioSemanal::rango(['inicio' => '08:00']));
        $this->assertSame('08:00 – 12:00', HorarioSemanal::rango(['inicio' => '08:00:00', 'fin' => '12:00']));
        $this->assertSame('—', HorarioSemanal::formatearHora('abc'));

        $dias = ['1' => ['a' => ['inicio' => '08:00', 'fin' => '09:00']], 2 => 'x'];
        $this->assertSame(['inicio' => '08:00', 'fin' => '09:00'], HorarioSemanal::celda($dias, 1, 'a'));
        $this->assertNull(HorarioSemanal::celda($dias, 1, 'b'));
        $this->assertNull(HorarioSemanal::celda($dias, 2, 'a'));
        $this->assertNull(HorarioSemanal::celda($dias, 3, 'a'));
    }

    public function test_maintenance_types_master_can_be_listed_created_edited_and_toggled(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.maestros.tipos.store'), [
                'nombre' => 'Cambio de frenos',
                'descripcion' => 'Pastillas y discos.',
            ])
            ->assertSessionHasNoErrors();

        $tipo = TipoMantenimiento::where('nombre', 'Cambio de frenos')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('administracion.vehiculos.maestros.tipos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administracion/Mantenimiento/Tipos')
                ->where('tipos', fn ($tipos) => collect($tipos)->contains('nombre', 'Cambio de frenos')));

        $this->actingAs($admin)
            ->put(route('administracion.vehiculos.maestros.tipos.update', $tipo), [
                'nombre' => 'Cambio de frenos y discos',
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tipos_mantenimiento', ['id' => $tipo->id, 'nombre' => 'Cambio de frenos y discos']);

        $this->actingAs($admin)
            ->patch(route('administracion.vehiculos.maestros.tipos.estado', $tipo), ['activo' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($tipo->fresh()->activo);

        $this->actingAs($admin)
            ->post(route('administracion.vehiculos.maestros.tipos.store'), ['nombre' => 'Cambio de frenos y discos'])
            ->assertSessionHasErrors('nombre');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function horario(array $overrides = []): array
    {
        return [
            'nombre' => 'Plantilla',
            'turnos' => [
                ['clave' => 'manana', 'nombre' => 'Turno mañana', 'inicio' => '07:00', 'fin' => '13:00'],
            ],
            'dias' => [
                1 => ['manana' => ['inicio' => '07:00', 'fin' => '13:00']],
            ],
            ...$overrides,
        ];
    }
}
