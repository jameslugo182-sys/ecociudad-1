<?php

namespace Database\Seeders;

use App\Models\Camion;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoEcoCiudadSeeder extends Seeder
{
    public function run(): void
    {
        $ciudadanos = collect(range(1, 3))->map(fn (int $numero) => User::updateOrCreate(
            ['email' => "ciudadano.demo{$numero}@ecociudad.pe"],
            [
                'name' => "Ciudadano Demo {$numero}",
                'password' => Hash::make('Demo2026!'),
                'rol' => 'ciudadano',
                'activo' => true,
                'email_verified_at' => now(),
            ]
        ));

        $cargoConductor = Cargo::firstOrCreate(['nombre' => 'Conductor'], ['activo' => true]);
        $cargoRecolector = Cargo::firstOrCreate(['nombre' => 'Recolector'], ['activo' => true]);
        $personal = collect();

        foreach (range(1, 12) as $numero) {
            $esConductor = in_array($numero, [1, 5, 9], true);
            $colaborador = Colaborador::updateOrCreate(
                ['documento' => '8'.str_pad((string) $numero, 7, '0', STR_PAD_LEFT)],
                [
                    'nombres' => $esConductor ? "Conductor {$numero}" : "Recolector {$numero}",
                    'apellido_paterno' => 'Demo',
                    'apellido_materno' => 'EcoCiudad',
                    'telefono' => '98765'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT),
                    'email' => "operario.demo{$numero}@ecociudad.pe",
                    'direccion' => 'Distrito de El Tambo, Huancayo',
                    'activo' => true,
                ]
            );
            Contrato::updateOrCreate(
                ['numero' => 'DEMO-CON-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT)],
                [
                    'colaborador_id' => $colaborador->id,
                    'cargo_id' => $esConductor ? $cargoConductor->id : $cargoRecolector->id,
                    'tipo' => 'CAS',
                    'fecha_inicio' => today()->startOfYear(),
                    'fecha_fin' => today()->endOfYear(),
                    'remuneracion' => $esConductor ? 2400 : 1900,
                    'jornada_horas' => 48,
                    'area' => 'Limpieza pública',
                    'sede' => 'Base municipal El Tambo',
                    'estado' => 'Vigente',
                ]
            );
            $personal->push(User::updateOrCreate(
                ['email' => "operario.demo{$numero}@ecociudad.pe"],
                [
                    'colaborador_id' => $colaborador->id,
                    'name' => $colaborador->nombre_completo,
                    'password' => Hash::make('Demo2026!'),
                    'rol' => 'area_limpieza',
                    'activo' => true,
                    'email_verified_at' => now(),
                ]
            ));
        }

        $camiones = [
            ['codigo' => 'ECO-01', 'placa' => 'W5A-101', 'marca' => 'Volvo', 'modelo' => 'VM', 'anio' => 2022, 'capacidad_kg' => 12000],
            ['codigo' => 'ECO-02', 'placa' => 'W5A-102', 'marca' => 'Mercedes-Benz', 'modelo' => 'Atego', 'anio' => 2021, 'capacidad_kg' => 10000],
            ['codigo' => 'ECO-03', 'placa' => 'W5A-103', 'marca' => 'Hino', 'modelo' => '500', 'anio' => 2023, 'capacidad_kg' => 11000],
        ];

        foreach ($camiones as $indice => $datos) {
            $camion = Camion::updateOrCreate(['codigo' => $datos['codigo']], [...$datos, 'estado' => 'Disponible']);
            $inicio = $indice * 4;
            $camion->personal()->sync([
                $personal[$inicio]->id => ['puesto' => 'conductor'],
                $personal[$inicio + 1]->id => ['puesto' => 'recolector'],
                $personal[$inicio + 2]->id => ['puesto' => 'recolector'],
                $personal[$inicio + 3]->id => ['puesto' => 'recolector'],
            ]);
        }

        $fotoPath = 'reportes_fotos/demo-incidencia.svg';
        Storage::disk('public')->put($fotoPath, $this->imagenDemo());
        $coordenadas = [
            [-12.0478, -75.2184], [-12.0502, -75.2251], [-12.0534, -75.2147],
            [-12.0568, -75.2219], [-12.0601, -75.2282], [-12.0445, -75.2304],
            [-12.0637, -75.2176], [-12.0589, -75.2108], [-12.0416, -75.2227],
            [-12.0662, -75.2249], [-12.0521, -75.2335], [-12.0463, -75.2099],
            [-12.0694, -75.2138], [-12.0572, -75.2361], [-12.0389, -75.2162],
        ];
        $tipos = ['Contenedor desbordado', 'Acumulación de bolsas', 'Residuos en área verde', 'Desmonte en vía pública'];

        foreach ($coordenadas as $indice => [$latitud, $longitud]) {
            $numero = $indice + 1;
            Reporte::updateOrCreate(
                ['descripcion' => "[DEMO-{$numero}] Incidencia de residuos pendiente de atención."],
                [
                    'user_id' => $ciudadanos[$indice % $ciudadanos->count()]->id,
                    'foto_path' => $fotoPath,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'estado' => 'Pendiente',
                    'tipo_incidencia' => $tipos[$indice % count($tipos)],
                    'prioridad' => ($indice % 3) + 1,
                    'assigned_to' => null,
                    'ruta_orden' => null,
                    'assigned_at' => null,
                ]
            );
        }
    }

    private function imagenDemo(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500">
  <rect width="800" height="500" fill="#d1fae5"/>
  <rect y="330" width="800" height="170" fill="#64748b"/>
  <path d="M160 365h480l-55-115H220z" fill="#475569"/>
  <g fill="#166534">
    <rect x="245" y="215" width="95" height="135" rx="8"/>
    <rect x="355" y="180" width="105" height="170" rx="8"/>
    <rect x="475" y="230" width="90" height="120" rx="8"/>
  </g>
  <text x="400" y="90" text-anchor="middle" font-family="Arial" font-size="42" font-weight="700" fill="#065f46">INCIDENCIA ECOCIUDAD</text>
  <text x="400" y="140" text-anchor="middle" font-family="Arial" font-size="24" fill="#047857">Punto georreferenciado de demostración</text>
</svg>
SVG;
    }
}
