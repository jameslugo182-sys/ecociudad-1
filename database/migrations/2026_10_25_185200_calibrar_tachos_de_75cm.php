<?php

use App\Models\Tacho;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Tacho::query()
            ->where('distancia_vacio_cm', 80)
            ->where('distancia_lleno_cm', 8)
            ->each(function (Tacho $tacho) {
                $tacho->distancia_vacio_cm = 75;
                $tacho->distancia_lleno_cm = 5;

                if ($tacho->distancia_cm !== null && $tacho->activo) {
                    $porcentaje = (int) round(100 * ((75 - (float) $tacho->distancia_cm) / 70));
                    $porcentaje = max(0, min(100, $porcentaje));
                    $umbral = (int) $tacho->umbral_alerta;

                    if ($porcentaje >= 95) {
                        $tacho->estado = Tacho::ESTADO_LLENO;
                        $tacho->alerta_activa = true;
                    } elseif ($porcentaje >= $umbral || ($tacho->alerta_activa && $porcentaje >= max(0, $umbral - 10))) {
                        $tacho->estado = Tacho::ESTADO_ALERTA;
                        $tacho->alerta_activa = true;
                    } else {
                        $tacho->estado = Tacho::ESTADO_OPERATIVO;
                        $tacho->alerta_activa = false;
                    }

                    $tacho->nivel_porcentaje = $porcentaje;
                }

                $tacho->save();
            });
    }

    public function down(): void
    {
        Tacho::query()
            ->where('distancia_vacio_cm', 75)
            ->where('distancia_lleno_cm', 5)
            ->update([
                'distancia_vacio_cm' => 80,
                'distancia_lleno_cm' => 8,
            ]);
    }
};
