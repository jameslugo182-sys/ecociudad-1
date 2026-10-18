<?php

namespace Tests\Unit;

use App\Models\Tacho;
use App\Services\NivelTachoService;
use Tests\TestCase;

class NivelTachoServiceTest extends TestCase
{
    public function test_ultrasonic_distance_maps_to_fill_percentage(): void
    {
        $servicio = new NivelTachoService;
        $tacho = new Tacho([
            'distancia_vacio_cm' => 80,
            'distancia_lleno_cm' => 8,
        ]);

        $this->assertSame(0, $servicio->calcularPorcentaje($tacho, 80));
        $this->assertSame(100, $servicio->calcularPorcentaje($tacho, 8));
        $this->assertSame(50, $servicio->calcularPorcentaje($tacho, 44));
        $this->assertSame(100, $servicio->calcularPorcentaje($tacho, 2));
        $this->assertSame(0, $servicio->calcularPorcentaje($tacho, 120));
        $this->assertNull($servicio->calcularPorcentaje($tacho, null));
    }
}
