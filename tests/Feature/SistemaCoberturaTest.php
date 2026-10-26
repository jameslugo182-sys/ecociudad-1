<?php

namespace Tests\Feature;

use App\Models\AreaUnidad;
use App\Models\Cargo;
use App\Models\Contrato;
use App\Models\Reporte;
use App\Models\Tacho;
use App\Models\User;
use App\Notifications\ReporteAtendido;
use App\Notifications\TachoNivelAlerta;
use App\Providers\AppServiceProvider;
use App\Services\NivelTachoService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class SistemaCoberturaTest extends TestCase
{
    use RefreshDatabase;

    private const IOT_TACHOS_LECTURAS = 'iot.tachos.lecturas';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------------------------------------------------------------
    // Lecturas IoT y servicio de niveles
    // ---------------------------------------------------------------

    public function test_device_can_authenticate_with_the_x_device_token_header(): void
    {
        $tacho = $this->crearTacho();
        $token = $tacho->generarToken();
        $tacho->save();

        $this->postJson(route(self::IOT_TACHOS_LECTURAS, $tacho), ['porcentaje' => 30], ['X-Device-Token' => $token])
            ->assertOk()
            ->assertJsonPath('nivel_porcentaje', 30)
            ->assertJsonPath('alerta', false);

        $this->postJson(route(self::IOT_TACHOS_LECTURAS, $tacho), ['porcentaje' => 30])
            ->assertUnauthorized();
    }

    public function test_inactive_bin_does_not_accept_readings(): void
    {
        $tacho = $this->crearTacho(['activo' => false]);
        $token = $tacho->generarToken();
        $tacho->save();

        $this->postJson(route(self::IOT_TACHOS_LECTURAS, $tacho), ['porcentaje' => 30], ['X-Device-Token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tacho');
    }

    public function test_level_service_requires_data_and_a_valid_calibration(): void
    {
        $servicio = app(NivelTachoService::class);
        $tacho = $this->crearTacho();

        try {
            $servicio->registrar($tacho, null, null);
            $this->fail('Debió exigir distancia o porcentaje.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('distancia_cm', $excepcion->errors());
        }

        $this->assertNull($servicio->calcularPorcentaje($tacho, null));

        $descalibrado = new Tacho(['distancia_vacio_cm' => 20, 'distancia_lleno_cm' => 20]);
        $this->assertNull($servicio->calcularPorcentaje($descalibrado, 10));
        $this->assertSame(0, $servicio->calcularPorcentaje($tacho, 200));
        $this->assertSame(100, $servicio->calcularPorcentaje($tacho, 1));
    }

    public function test_alert_stays_active_until_the_level_drops_below_the_release_margin(): void
    {
        $servicio = app(NivelTachoService::class);
        $tacho = $this->crearTacho(['creado_por' => User::factory()->create(['rol' => 'administrador'])->id]);

        $servicio->registrar($tacho, null, 85);
        $this->assertSame(Tacho::ESTADO_ALERTA, $tacho->fresh()->estado);

        $servicio->registrar($tacho->fresh(), null, 75);
        $this->assertSame(Tacho::ESTADO_ALERTA, $tacho->fresh()->estado);
        $this->assertTrue($tacho->fresh()->alerta_activa);

        $servicio->registrar($tacho->fresh(), null, 50);
        $this->assertSame(Tacho::ESTADO_OPERATIVO, $tacho->fresh()->estado);
    }

    public function test_incident_author_falls_back_to_an_active_administrator_or_fails(): void
    {
        $servicio = app(NivelTachoService::class);
        $tacho = $this->crearTacho(['nivel_porcentaje' => 96]);

        try {
            $servicio->abrirIncidencia($tacho);
            $this->fail('Sin autor disponible no debe abrirse la incidencia.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('tacho', $excepcion->errors());
        }

        $admin = User::factory()->create(['rol' => 'administrador']);
        $reporte = $servicio->abrirIncidencia($tacho);

        $this->assertSame($admin->id, $reporte->user_id);
        $this->assertSame(1, $reporte->prioridad);
        $this->assertSame($reporte->id, $tacho->incidenciaAbierta()?->id);

        $tacho->load('incidenciaAbiertaRelacion');
        $this->assertSame($reporte->id, $tacho->incidenciaAbierta()?->id);
    }

    public function test_notifications_build_their_mail_messages(): void
    {
        $usuario = User::factory()->create(['name' => 'Marta', 'rol' => 'administrador']);
        $tacho = $this->crearTacho(['nivel_porcentaje' => 90]);
        $reporte = Reporte::create([
            'user_id' => $usuario->id,
            'foto_path' => 'reportes_fotos/a.jpg',
            'latitud' => -12.05,
            'longitud' => -75.22,
            'descripcion' => 'Basura acumulada',
            'estado' => 'Atendido',
        ]);

        $alerta = new TachoNivelAlerta($tacho);
        $this->assertSame(['mail'], $alerta->via($usuario));
        $correo = $alerta->toMail($usuario);
        $this->assertSame('EcoCiudad: tacho TCH001 al 90%', $correo->subject);
        $this->assertSame('Hola, Marta', $correo->greeting);

        $atendido = new ReporteAtendido($reporte);
        $this->assertSame(['mail'], $atendido->via($usuario));
        $correo = $atendido->toMail($usuario);
        $this->assertSame("EcoCiudad: reporte #{$reporte->id} atendido", $correo->subject);
        $this->assertContains('Basura acumulada', $correo->introLines);
    }

    // ---------------------------------------------------------------
    // Panel y reportes ciudadanos
    // ---------------------------------------------------------------

    public function test_dashboard_lists_the_modules_of_each_role(): void
    {
        $casos = [
            'ciudadano' => 1,
            'area_limpieza' => 1,
            'personal_municipal' => 3,
            'administrador' => 6,
            'rol_inexistente' => 0,
        ];

        foreach ($casos as $rol => $modulos) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))
                ->get(route('dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Panel')->has('modulos', $modulos));
        }
    }

    public function test_citizen_can_open_the_new_report_form(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'ciudadano']))
            ->get(route('reportes.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reportes/Create'));
    }

    // ---------------------------------------------------------------
    // Autenticación
    // ---------------------------------------------------------------

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        Event::fake([Lockout::class]);
        $usuario = User::factory()->create();

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post(route('login'), ['email' => $usuario->email, 'password' => 'incorrecta'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        Event::assertDispatched(Lockout::class);
    }

    public function test_verified_users_skip_the_verification_screens(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($usuario)
            ->post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($usuario)
            ->get($this->urlVerificacion($usuario))
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_unverified_users_can_request_a_new_verification_link(): void
    {
        $usuario = User::factory()->unverified()->create();

        $this->actingAs($usuario)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_password_reset_reports_unknown_accounts_and_invalid_tokens(): void
    {
        $this->post(route('password.email'), ['email' => 'nadie@ecociudad.pe'])
            ->assertSessionHasErrors('email');

        $usuario = User::factory()->create();

        $this->post(route('password.store'), [
            'token' => 'token-invalido',
            'email' => $usuario->email,
            'password' => 'Password-2026',
            'password_confirmation' => 'Password-2026',
        ])->assertSessionHasErrors('email');
    }

    // ---------------------------------------------------------------
    // Contratos, modelos y proveedor de servicios
    // ---------------------------------------------------------------

    public function test_updating_a_contract_replaces_its_document(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);
        $cargo = Cargo::where('nombre', 'Recolector')->firstOrFail()->id;
        $area = AreaUnidad::where('nombre', 'Limpieza pública')->firstOrFail()->id;

        $this->actingAs($admin)
            ->post(route('administracion.contratos.store'), $this->payloadContrato($cargo, $area, [
                'archivo_contrato' => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
            ]))
            ->assertSessionHasNoErrors();

        $contrato = Contrato::firstOrFail();
        $anterior = $contrato->documento_path;
        $this->assertStringEndsWith('/storage/'.$anterior, $contrato->documento_url);

        $this->actingAs($admin)
            ->post(route('administracion.contratos.update', $contrato), $this->payloadContrato($cargo, $area, [
                'archivo_contrato' => UploadedFile::fake()->create('nuevo.pdf', 50, 'application/pdf'),
            ]))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($contrato->fresh()->documento_path);
    }

    public function test_attachment_urls_are_null_without_files(): void
    {
        $this->assertNull((new Reporte)->foto_url);
        $this->assertNull((new Reporte)->evidencia_url);
        $this->assertNull((new Contrato)->documento_url);
        $this->assertStringEndsWith('/storage/a/b.jpg', (new Reporte(['foto_path' => '/a/b.jpg']))->foto_url);
    }

    public function test_every_model_relationship_is_declared(): void
    {
        $revisadas = 0;

        foreach (glob(app_path('Models/*.php')) as $archivo) {
            $clase = 'App\\Models\\'.basename($archivo, '.php');
            $reflexion = new ReflectionClass($clase);

            if (! $reflexion->isSubclassOf(Model::class) && $clase !== User::class) {
                continue;
            }

            $modelo = new $clase;

            foreach ($reflexion->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
                $tipo = $metodo->getReturnType();

                if ($metodo->class !== $clase || $metodo->isStatic() || $metodo->getNumberOfParameters() > 0
                    || ! $tipo instanceof \ReflectionNamedType || $tipo->isBuiltin()
                    || ! is_subclass_of($tipo->getName(), Relation::class)) {
                    continue;
                }

                $this->assertInstanceOf(Relation::class, $modelo->{$metodo->getName()}(), "{$clase}::{$metodo->getName()}");
                $revisadas++;
            }
        }

        $this->assertGreaterThan(30, $revisadas);
    }

    public function test_inspire_console_command_prints_a_quote(): void
    {
        $this->artisan('inspire')->assertSuccessful();
    }

    public function test_https_application_url_forces_the_secure_scheme(): void
    {
        config(['app.url' => 'https://ecociudad.test']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/dashboard'));
    }

    // ---------------------------------------------------------------
    // Ayudas
    // ---------------------------------------------------------------

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

        return $tacho->refresh();
    }
}
