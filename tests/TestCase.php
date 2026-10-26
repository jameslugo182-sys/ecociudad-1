<?php

namespace Tests;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

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

    /**
     * URL firmada de verificación de correo, obtenida del propio mensaje de Laravel
     * (así las pruebas no calculan hashes por su cuenta).
     */
    protected function urlVerificacion(User $usuario): string
    {
        return (new VerifyEmail)->toMail($usuario)->actionUrl;
    }

    /**
     * URL firmada válida pero con un hash que no corresponde al correo del usuario.
     */
    protected function urlVerificacionConHashInvalido(User $usuario): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $usuario->id, 'hash' => 'hash-que-no-corresponde']
        );
    }
}
