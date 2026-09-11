<?php

namespace App\Services;

use App\Models\Reporte;

class PlanificadorFlotaService
{
    public function __construct(private TrazadoRutaService $trazado) {}

    /**
     * Reparte incidencias en recorridos geográficamente eficientes, sin persistir.
     *
     * @param  array<int, Reporte>  $reportes
     * @return array<int, array<string, mixed>>
     */
    public function proponer(array $reportes, int $vehiculos): array
    {
        $reportes = array_values($reportes);
        $grupos = $this->agrupar($reportes, max(1, min($vehiculos, count($reportes))));
        $propuestas = [];

        foreach ($grupos as $indice => $grupo) {
            if ($grupo === []) {
                continue;
            }

            $trazado = $this->trazado->trazar($grupo);
            $paradas = [];

            foreach (array_values($trazado['paradas']) as $orden => $punto) {
                $paradas[] = [
                    'id' => $punto['reporte']->id,
                    'descripcion' => $punto['reporte']->descripcion,
                    'tipo_incidencia' => $punto['reporte']->tipo_incidencia,
                    'latitud' => (float) $punto['reporte']->latitud,
                    'longitud' => (float) $punto['reporte']->longitud,
                    'orden' => $orden + 1,
                    'distancia' => $punto['distancia'],
                ];
            }

            $propuestas[] = [
                'clave' => 'propuesta-'.($indice + 1),
                'indice' => $indice + 1,
                'distancia_km' => $trazado['distancia_km'],
                'por_vias' => $trazado['por_vias'],
                'geometria' => $trazado['geometria'],
                'paradas' => $paradas,
                'reporte_ids' => array_column($paradas, 'id'),
            ];
        }

        return $propuestas;
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array<int, array<int, Reporte>>
     */
    public function agrupar(array $reportes, int $grupos): array
    {
        $reportes = array_values($reportes);
        $k = max(1, min($grupos, count($reportes)));

        if ($k <= 1) {
            return [$reportes];
        }

        $centroides = $this->semillasMasAlejadas($reportes, $k);

        for ($iteracion = 0; $iteracion < 12; $iteracion++) {
            $asignados = $this->asignarACentroides($reportes, $centroides);
            $asignados = $this->repararVacios($asignados);
            $siguientes = array_map(fn (array $grupo) => $this->centroide($grupo), $asignados);

            if ($siguientes === $centroides) {
                break;
            }

            $centroides = $siguientes;
        }

        return array_values(array_filter(
            $this->repararVacios($this->asignarACentroides($reportes, $centroides)),
            fn (array $grupo) => $grupo !== []
        ));
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array<int, array{0: float, 1: float}>
     */
    private function semillasMasAlejadas(array $reportes, int $k): array
    {
        $elegidos = [];
        $primero = $reportes[0];
        $maxima = -1.0;

        foreach ($reportes as $reporte) {
            $distancia = $this->distancia(
                TrazadoRutaService::BASE_LATITUD,
                TrazadoRutaService::BASE_LONGITUD,
                (float) $reporte->latitud,
                (float) $reporte->longitud
            );

            if ($distancia > $maxima) {
                $maxima = $distancia;
                $primero = $reporte;
            }
        }

        $elegidos[] = $primero;

        while (count($elegidos) < $k) {
            $siguiente = $reportes[0];
            $mejor = -1.0;

            foreach ($reportes as $candidato) {
                if (in_array($candidato, $elegidos, true)) {
                    continue;
                }

                $minima = PHP_FLOAT_MAX;
                foreach ($elegidos as $elegido) {
                    $minima = min($minima, $this->distancia(
                        (float) $candidato->latitud,
                        (float) $candidato->longitud,
                        (float) $elegido->latitud,
                        (float) $elegido->longitud
                    ));
                }

                if ($minima > $mejor) {
                    $mejor = $minima;
                    $siguiente = $candidato;
                }
            }

            $elegidos[] = $siguiente;
        }

        return array_map(
            fn (Reporte $reporte) => [(float) $reporte->latitud, (float) $reporte->longitud],
            $elegidos
        );
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @param  array<int, array{0: float, 1: float}>  $centroides
     * @return array<int, array<int, Reporte>>
     */
    private function asignarACentroides(array $reportes, array $centroides): array
    {
        $grupos = array_fill(0, count($centroides), []);

        foreach ($reportes as $reporte) {
            $grupos[$this->indiceMasCercano($reporte, $centroides)][] = $reporte;
        }

        return $grupos;
    }

    /**
     * @param  array<int, array<int, Reporte>>  $grupos
     * @return array<int, array<int, Reporte>>
     */
    private function repararVacios(array $grupos): array
    {
        foreach ($grupos as $indice => $grupo) {
            if ($grupo !== []) {
                continue;
            }

            $origen = $this->indiceGrupoMasGrande($grupos);
            if ($origen === null || count($grupos[$origen]) < 2) {
                continue;
            }

            $movido = array_pop($grupos[$origen]);
            $grupos[$indice][] = $movido;
        }

        return $grupos;
    }

    /**
     * @param  array<int, array<int, Reporte>>  $grupos
     */
    private function indiceGrupoMasGrande(array $grupos): ?int
    {
        $indice = null;
        $tamano = 0;

        foreach ($grupos as $actual => $grupo) {
            if (count($grupo) > $tamano) {
                $tamano = count($grupo);
                $indice = $actual;
            }
        }

        return $indice;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $centroides
     */
    private function indiceMasCercano(Reporte $reporte, array $centroides): int
    {
        $indice = 0;
        $minima = PHP_FLOAT_MAX;

        foreach ($centroides as $actual => $centroide) {
            $distancia = $this->distancia(
                (float) $reporte->latitud,
                (float) $reporte->longitud,
                $centroide[0],
                $centroide[1]
            );

            if ($distancia < $minima) {
                $minima = $distancia;
                $indice = $actual;
            }
        }

        return $indice;
    }

    /**
     * @param  array<int, Reporte>  $grupo
     * @return array{0: float, 1: float}
     */
    private function centroide(array $grupo): array
    {
        if ($grupo === []) {
            return [TrazadoRutaService::BASE_LATITUD, TrazadoRutaService::BASE_LONGITUD];
        }

        $lat = 0.0;
        $lng = 0.0;

        foreach ($grupo as $reporte) {
            $lat += (float) $reporte->latitud;
            $lng += (float) $reporte->longitud;
        }

        $total = count($grupo);

        return [$lat / $total, $lng / $total];
    }

    private function distancia(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radioTierra = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
