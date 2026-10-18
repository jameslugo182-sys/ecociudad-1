<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payloadContrato(int $cargoId, int $areaId, array $overrides = []): array
    {
        return array_merge([
            'documento' => '72345678',
            'nombres' => 'Operario',
            'apellido_paterno' => 'Municipal',
            'apellido_materno' => 'Huamán',
            'sexo' => 'Masculino',
            'nacionalidad' => 'Peruana',
            'telefono' => '987654321',
            'direccion' => 'Jr. Real 120, El Tambo',
            'nivel_educativo' => 'Secundaria',
            'numero' => 'CON-2026-001',
            'cargo_id' => $cargoId,
            'tipo' => 'CAS',
            'fecha_inicio' => now()->subDay()->toDateString(),
            'fecha_fin' => now()->addYear()->toDateString(),
            'remuneracion' => 1900,
            'jornada_horas' => 48,
            'area_id' => $areaId,
            'estado' => 'Vigente',
        ], $overrides);
    }
}
