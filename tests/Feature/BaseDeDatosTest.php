<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\Tacho;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BaseDeDatosTest extends TestCase
{
    use RefreshDatabase;

    private const CORREO_ADMIN = 'admin.seed@ecociudad.pe';

    protected function tearDown(): void
    {
        foreach (['ADMIN_EMAIL', 'ADMIN_PASSWORD', 'ADMIN_NAME'] as $variable) {
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }

        parent::tearDown();
    }

    public function test_admin_seeder_requires_credentials_in_the_environment(): void
    {
        $this->definirVariable('ADMIN_EMAIL', '');
        $this->definirVariable('ADMIN_PASSWORD', '');
        $antes = User::count();

        $this->seed(AdminUserSeeder::class);

        $this->assertSame($antes, User::count());
    }

    public function test_admin_seeder_creates_or_updates_the_administrator(): void
    {
        $this->definirVariable('ADMIN_EMAIL', self::CORREO_ADMIN);
        $this->definirVariable('ADMIN_PASSWORD', 'Clave-Segura-2026');

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', self::CORREO_ADMIN)->firstOrFail();
        $this->assertSame(1, User::where('email', self::CORREO_ADMIN)->count());
        $this->assertSame('administrador', $admin->rol);
        $this->assertTrue($admin->activo);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('Clave-Segura-2026', $admin->password));
    }

    public function test_database_seeder_loads_the_demo_data_without_duplicating_it(): void
    {
        Storage::fake('public');
        $this->definirVariable('ADMIN_EMAIL', self::CORREO_ADMIN);
        $this->definirVariable('ADMIN_PASSWORD', 'Clave-Segura-2026');

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::where('rol', 'ciudadano')->count());
        $this->assertSame(12, User::where('rol', 'area_limpieza')->count());
        $this->assertSame(3, Camion::count());
        $this->assertSame(15, Reporte::count());
        $this->assertTrue(Camion::with('personal')->get()->every(fn (Camion $camion) => $camion->tieneEquipoCompleto()));
        Storage::disk('public')->assertExists('reportes_fotos/demo-incidencia.svg');
    }

    public function test_bin_calibration_migration_recalculates_levels_for_the_75_cm_container(): void
    {
        $migracion = require database_path('migrations/2026_10_25_185200_calibrar_tachos_de_75cm.php');

        $lleno = $this->crearTacho('TCH001', ['distancia_cm' => 5]);
        $alerta = $this->crearTacho('TCH002', ['distancia_cm' => 18]);
        $histeresis = $this->crearTacho('TCH003', ['distancia_cm' => 22.5, 'alerta_activa' => true]);
        $operativo = $this->crearTacho('TCH004', ['distancia_cm' => 60]);
        $sinLectura = $this->crearTacho('TCH005');
        $inactivo = $this->crearTacho('TCH006', ['distancia_cm' => 5, 'activo' => false]);

        $migracion->up();

        $this->assertSame([75, 5], [$sinLectura->fresh()->distancia_vacio_cm, $sinLectura->fresh()->distancia_lleno_cm]);
        $this->assertSame(Tacho::ESTADO_LLENO, $lleno->fresh()->estado);
        $this->assertTrue($lleno->fresh()->alerta_activa);
        $this->assertSame(Tacho::ESTADO_ALERTA, $alerta->fresh()->estado);
        $this->assertSame(Tacho::ESTADO_ALERTA, $histeresis->fresh()->estado);
        $this->assertSame(75, $histeresis->fresh()->nivel_porcentaje);
        $this->assertSame(Tacho::ESTADO_OPERATIVO, $operativo->fresh()->estado);
        $this->assertFalse($operativo->fresh()->alerta_activa);
        $this->assertSame(Tacho::ESTADO_OPERATIVO, $inactivo->fresh()->estado);

        $migracion->down();

        $this->assertSame([80, 8], [$lleno->fresh()->distancia_vacio_cm, $lleno->fresh()->distancia_lleno_cm]);
    }

    public function test_every_migration_can_be_rolled_back_and_applied_again(): void
    {
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--force' => true]));
        $this->assertFalse(Schema::hasTable('reportes'));
        $this->assertFalse(Schema::hasTable('tachos'));

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertTrue(Schema::hasTable('reportes'));
        $this->assertTrue(Schema::hasTable('tachos'));
    }

    private function definirVariable(string $nombre, string $valor): void
    {
        putenv("{$nombre}={$valor}");
        $_ENV[$nombre] = $valor;
        $_SERVER[$nombre] = $valor;
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function crearTacho(string $codigo, array $atributos = []): Tacho
    {
        $tacho = new Tacho(array_merge([
            'codigo' => $codigo,
            'nombre' => "Tacho {$codigo}",
            'latitud' => -12.0464,
            'longitud' => -75.2195,
            'distancia_vacio_cm' => 80,
            'distancia_lleno_cm' => 8,
            'umbral_alerta' => 80,
            'estado' => Tacho::ESTADO_OPERATIVO,
            'activo' => true,
        ], $atributos));
        $tacho->generarToken();
        $tacho->save();

        return $tacho->refresh();
    }
}
