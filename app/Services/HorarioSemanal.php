<?php

namespace App\Services;

class HorarioSemanal
{
    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $dias
     */
    public static function totalHoras(array $dias): float
    {
        $total = 0.0;

        foreach ($dias as $turnosDelDia) {
            if (! is_array($turnosDelDia)) {
                continue;
            }

            foreach ($turnosDelDia as $turno) {
                if (! is_array($turno)) {
                    continue;
                }

                $total += self::horasEntre($turno['inicio'] ?? null, $turno['fin'] ?? null);
            }
        }

        return round($total, 2);
    }

    public static function horasEntre(?string $inicio, ?string $fin): float
    {
        if (! $inicio || ! $fin) {
            return 0.0;
        }

        $minutosInicio = self::aMinutos($inicio);
        $minutosFin = self::aMinutos($fin);

        if ($minutosInicio === null || $minutosFin === null) {
            return 0.0;
        }

        $diferencia = $minutosFin - $minutosInicio;
        if ($diferencia < 0) {
            $diferencia += 24 * 60;
        }

        return round($diferencia / 60, 2);
    }

    /**
     * @param  array<string, mixed>|null  $turno
     */
    public static function rango(?array $turno): string
    {
        if (! $turno) {
            return '—';
        }

        $inicio = self::formatearHora($turno['inicio'] ?? null);
        $fin = self::formatearHora($turno['fin'] ?? null);

        if ($inicio === '—' || $fin === '—') {
            return '—';
        }

        return "{$inicio} – {$fin}";
    }

    public static function formatearHora(?string $hora): string
    {
        $hora = substr((string) $hora, 0, 5);

        return preg_match('/^\d{2}:\d{2}$/', $hora) ? $hora : '—';
    }

    /**
     * @param  array<int|string, mixed>  $dias
     * @return array<string, mixed>|null
     */
    public static function celda(array $dias, int $diaId, string $clave): ?array
    {
        $delDia = $dias[$diaId] ?? $dias[(string) $diaId] ?? null;

        if (! is_array($delDia)) {
            return null;
        }

        $turno = $delDia[$clave] ?? null;

        return is_array($turno) ? $turno : null;
    }

    private static function aMinutos(string $hora): ?int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', $hora, $partes)) {
            return null;
        }

        return ((int) $partes[1] * 60) + (int) $partes[2];
    }
}
