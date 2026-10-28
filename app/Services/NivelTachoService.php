<?php

namespace App\Services;

use App\Models\LecturaTacho;
use App\Models\Reporte;
use App\Models\Tacho;
use App\Models\User;
use App\Notifications\TachoNivelAlerta;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class NivelTachoService
{
    public function registrar(Tacho $tacho, ?float $distanciaCm, ?int $porcentajeDirecto): LecturaTacho
    {
        if (! $tacho->activo) {
            throw ValidationException::withMessages([
                'tacho' => 'Este tacho está inactivo y no acepta lecturas.',
            ]);
        }

        $porcentaje = $porcentajeDirecto ?? $this->calcularPorcentaje($tacho, $distanciaCm);

        if ($porcentaje === null) {
            throw ValidationException::withMessages([
                'distancia_cm' => 'Envía distancia_cm (sensor ultrasónico) o porcentaje.',
            ]);
        }

        $alertaPrevia = $tacho->alerta_activa;
        $estado = $this->estadoDesdeNivel($tacho, $porcentaje, $alertaPrevia);

        $lectura = $tacho->lecturas()->create([
            'distancia_cm' => $distanciaCm,
            'nivel_porcentaje' => $porcentaje,
        ]);

        $tacho->update([
            'distancia_cm' => $distanciaCm,
            'nivel_porcentaje' => $porcentaje,
            'estado' => $tacho->activo ? $estado : Tacho::ESTADO_INACTIVO,
            'alerta_activa' => in_array($estado, [Tacho::ESTADO_ALERTA, Tacho::ESTADO_LLENO], true),
            'ultima_lectura_at' => now(),
        ]);

        if (! $alertaPrevia && $tacho->alerta_activa) {
            $this->abrirIncidencia($tacho);
            $this->avisarAdministradores($tacho);
        }

        return $lectura;
    }

    public function calcularPorcentaje(Tacho $tacho, ?float $distanciaCm): ?int
    {
        if ($distanciaCm === null) {
            return null;
        }

        $vacio = (float) $tacho->distancia_vacio_cm;
        $lleno = (float) $tacho->distancia_lleno_cm;
        $rango = $vacio - $lleno;

        if ($rango <= 0) {
            return null;
        }

        $porcentaje = (int) round(100 * (($vacio - $distanciaCm) / $rango));

        return max(0, min(100, $porcentaje));
    }

    public function abrirIncidencia(Tacho $tacho): Reporte
    {
        $abierta = $tacho->incidenciaAbierta();

        if ($abierta) {
            $abierta->update([
                'descripcion' => $this->descripcionIncidencia($tacho),
                'latitud' => $tacho->latitud,
                'longitud' => $tacho->longitud,
                'prioridad' => $tacho->nivel_porcentaje >= 95 ? 1 : 2,
            ]);

            return $abierta;
        }

        $autorId = $tacho->creado_por
            ?? User::query()->where('rol', 'administrador')->where('activo', true)->value('id');

        if (! $autorId) {
            throw ValidationException::withMessages([
                'tacho' => 'No hay un usuario autor para abrir la incidencia del tacho.',
            ]);
        }

        return $tacho->reportes()->create([
            'user_id' => $autorId,
            'latitud' => $tacho->latitud,
            'longitud' => $tacho->longitud,
            'descripcion' => $this->descripcionIncidencia($tacho),
            'tipo_incidencia' => Tacho::TIPO_INCIDENCIA,
            'estado' => 'Pendiente',
            'prioridad' => $tacho->nivel_porcentaje >= 95 ? 1 : 2,
        ]);
    }

    private function estadoDesdeNivel(Tacho $tacho, int $porcentaje, bool $alertaPrevia): string
    {
        $umbral = $tacho->umbral_alerta;
        $liberar = max(0, $umbral - 10);

        if ($porcentaje >= 95) {
            return Tacho::ESTADO_LLENO;
        }

        $enAlerta = $porcentaje >= $umbral || ($alertaPrevia && $porcentaje >= $liberar);

        return $enAlerta ? Tacho::ESTADO_ALERTA : Tacho::ESTADO_OPERATIVO;
    }

    private function descripcionIncidencia(Tacho $tacho): string
    {
        return "Contenedor IoT {$tacho->codigo} ({$tacho->nombre}) al {$tacho->nivel_porcentaje}% de llenado.";
    }

    private function avisarAdministradores(Tacho $tacho): void
    {
        $admins = User::query()
            ->where('rol', 'administrador')
            ->where('activo', true)
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new TachoNivelAlerta($tacho->fresh()));
        }
    }
}
