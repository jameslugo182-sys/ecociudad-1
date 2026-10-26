<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\Tacho;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InformesOperativosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_administrator_can_open_every_operational_report(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $this->crearReporte($ciudadano, [
            'estado' => 'Atendido',
            'tipo_incidencia' => 'Acumulación de bolsas de basura',
            'atendido_at' => now(),
        ]);

        $paginas = [
            'administracion.reportes.index' => 'Reportes/Metricas',
            'administracion.reportes.incidencias' => 'Reportes/InformeIncidencias',
            'administracion.reportes.rutas' => 'Reportes/InformeRutas',
            'administracion.reportes.tachos' => 'Reportes/InformeTachos',
            'administracion.reportes.personal' => 'Reportes/InformePersonal',
        ];

        foreach ($paginas as $ruta => $componente) {
            $this->actingAs($administrador)
                ->get(route($ruta))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($componente)
                    ->has('periodo.fecha_desde')
                    ->has('pdfUrl'));

            $this->actingAs($ciudadano)
                ->get(route($ruta))
                ->assertForbidden();
        }
    }

    public function test_summary_only_counts_incidents_created_in_the_selected_period(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $delMes = $this->crearReporte($ciudadano, ['estado' => 'Pendiente']);
        $anterior = $this->crearReporte($ciudadano, ['estado' => 'Atendido', 'atendido_at' => now()->subMonths(2)]);
        $anterior->forceFill(['created_at' => now()->subMonths(2)])->save();

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.index', [
                'fecha_desde' => now()->startOfMonth()->toDateString(),
                'fecha_hasta' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Metricas')
                ->where('resumen.total', 1)
                ->where('resumen.atendidos', 0)
                ->where('resumen.tasaAtencion', 0));

        $this->assertNotSame($delMes->id, $anterior->id);
    }

    public function test_incidents_report_lists_live_rows_and_pdf_downloads(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano', 'name' => 'Rosa Quispe']);
        $this->crearReporte($ciudadano, [
            'descripcion' => 'Bolsas en Jr. Real',
            'tipo_incidencia' => 'Acumulación de bolsas de basura',
        ]);

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.incidencias'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/InformeIncidencias')
                ->has('incidencias.data', 1)
                ->where('incidencias.data.0.ciudadano', 'Rosa Quispe')
                ->where('incidencias.data.0.origen', 'Ciudadano'));

        $pdf = $this->actingAs($administrador)
            ->get(route('administracion.reportes.pdf', [
                'seccion' => 'resumen',
                'fecha_desde' => now()->startOfMonth()->toDateString(),
                'fecha_hasta' => now()->toDateString(),
            ]));

        $pdf->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $pdf->headers->get('content-type')));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_routes_and_bins_reports_use_registered_fleet_and_iot_data(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);
        $camion = Camion::create([
            'codigo' => 'VH010',
            'placa' => 'INF-010',
            'estado' => 'Disponible',
        ]);
        RutaRecoleccion::create([
            'camion_id' => $camion->id,
            'generado_por' => $administrador->id,
            'fecha' => today(),
            'estado' => 'Planificada',
            'distancia_estimada_km' => 4.5,
        ]);
        $tacho = new Tacho([
            'codigo' => 'TCH010',
            'nombre' => 'Plaza Huamanmarca',
            'latitud' => -12.0464,
            'longitud' => -75.2195,
            'distancia_vacio_cm' => 75,
            'distancia_lleno_cm' => 5,
            'umbral_alerta' => 80,
            'estado' => Tacho::ESTADO_OPERATIVO,
            'activo' => true,
            'creado_por' => $administrador->id,
            'nivel_porcentaje' => 20,
        ]);
        $tacho->generarToken();
        $tacho->save();

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.rutas'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/InformeRutas')
                ->where('resumenRutas.total', 1)
                ->where('resumenRutas.km', 4.5)
                ->has('flota', 1));

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.tachos'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/InformeTachos')
                ->where('resumenTachos.total', 1)
                ->where('tachos.0.codigo', 'TCH010'));

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.personal'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/InformePersonal')
                ->where('resumenPersonal.cuentas', 1)
                ->where('resumenPersonal.ciudadanos', 0));
    }

    public function test_inverted_dates_are_rejected(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->get(route('administracion.reportes.index', [
                'fecha_desde' => now()->toDateString(),
                'fecha_hasta' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('fecha_hasta');
    }

    public function test_unknown_pdf_section_is_not_found(): void
    {
        $administrador = User::factory()->create(['rol' => 'administrador']);

        $this->actingAs($administrador)
            ->get('/administracion/reportes/inventado/pdf')
            ->assertNotFound();
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
}
