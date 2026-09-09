<?php

namespace Tests\Feature;

use App\Models\Reporte;
use App\Models\User;
use App\Notifications\ReporteAtendido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EcoCiudadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_only_access_its_own_module(): void
    {
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);

        $this->actingAs($ciudadano)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($ciudadano)->get(route('limpieza.dashboard'))->assertForbidden();
        $this->actingAs($municipal)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($limpieza)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($limpieza)->get(route('reportes.index'))->assertForbidden();
    }

    public function test_citizen_only_sees_their_own_reports(): void
    {
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $otroCiudadano = User::factory()->create(['rol' => 'ciudadano']);
        $propio = $this->crearReporte($ciudadano);
        $this->crearReporte($otroCiudadano);

        $this->actingAs($ciudadano)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Index')
                ->has('reportes', 1)
                ->where('reportes.0.id', $propio->id));
    }

    public function test_municipal_staff_can_assign_a_report_to_cleaning_staff(): void
    {
        $municipal = User::factory()->create(['rol' => 'personal_municipal']);
        $limpieza = User::factory()->create(['rol' => 'area_limpieza']);
        $reporte = $this->crearReporte(User::factory()->create(['rol' => 'ciudadano']));

        $this->actingAs($municipal)
            ->patch(route('admin.reportes.asignar', $reporte), [
                'assigned_to' => $limpieza->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reportes', [
            'id' => $reporte->id,
            'assigned_to' => $limpieza->id,
            'estado' => 'Asignado',
        ]);
    }

    public function test_only_assigned_cleaning_staff_can_close_with_evidence(): void
    {
        Storage::fake('public');
        Notification::fake();

        $asignado = User::factory()->create(['rol' => 'area_limpieza']);
        $otroOperario = User::factory()->create(['rol' => 'area_limpieza']);
        $ciudadano = User::factory()->create(['rol' => 'ciudadano']);
        $reporte = $this->crearReporte(
            $ciudadano,
            ['assigned_to' => $asignado->id, 'estado' => 'Asignado']
        );

        $this->actingAs($otroOperario)
            ->post(route('limpieza.reportes.evidencia', $reporte), [
                'evidencia' => $this->imagenFalsa(),
            ])
            ->assertForbidden();

        $this->actingAs($asignado)
            ->post(route('limpieza.reportes.evidencia', $reporte), [
                'evidencia' => $this->imagenFalsa(),
            ])
            ->assertRedirect();

        $reporte->refresh();

        $this->assertSame('Atendido', $reporte->estado);
        $this->assertNotNull($reporte->atendido_at);
        Storage::disk('public')->assertExists($reporte->evidencia_path);
        Notification::assertSentTo($ciudadano, ReporteAtendido::class);
    }

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
