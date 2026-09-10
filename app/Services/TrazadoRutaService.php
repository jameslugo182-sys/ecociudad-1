<?php

namespace App\Services;

use App\Models\Reporte;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TrazadoRutaService
{
    public const BASE_LATITUD = -12.0504;

    public const BASE_LONGITUD = -75.2215;

    private const OSRM_BASE = 'https://router.project-osrm.org';

    /**
     * Ordena las paradas y obtiene el trazado por vías reales.
     *
     * @param  array<int, Reporte>  $reportes
     * @return array{
     *     paradas: array<int, array{reporte: Reporte, distancia: float}>,
     *     geometria: array<int, array{0: float, 1: float}>,
     *     distancia_km: float,
     *     por_vias: bool
     * }
     */
    public function trazar(array $reportes): array
    {
        $reportes = array_values($reportes);

        if ($reportes === []) {
            return [
                'paradas' => [],
                'geometria' => [$this->coordenadaBase()],
                'distancia_km' => 0.0,
                'por_vias' => false,
            ];
        }

        $porRedVial = $this->trazarConOsrmTrip($reportes)
            ?? $this->trazarConOsrmRuta($reportes);

        if ($porRedVial !== null) {
            return $porRedVial;
        }

        return $this->trazarEnLineaRecta($reportes);
    }

    /**
     * Conserva el orden de paradas y solo obtiene el trazado vial.
     *
     * @param  array<int, Reporte>  $reportes
     * @return array{
     *     paradas: array<int, array{reporte: Reporte, distancia: float}>,
     *     geometria: array<int, array{0: float, 1: float}>,
     *     distancia_km: float,
     *     por_vias: bool
     * }
     */
    public function trazarEnOrden(array $reportes): array
    {
        $reportes = array_values($reportes);

        if ($reportes === []) {
            return [
                'paradas' => [],
                'geometria' => [$this->coordenadaBase()],
                'distancia_km' => 0.0,
                'por_vias' => false,
            ];
        }

        return $this->consultarRutaParaOrden($reportes)
            ?? $this->geometriaRectaEnOrden($reportes);
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array{paradas: array<int, array{reporte: Reporte, distancia: float}>, geometria: array<int, array{0: float, 1: float}>, distancia_km: float, por_vias: bool}|null
     */
    private function trazarConOsrmTrip(array $reportes): ?array
    {
        $respuesta = $this->consultarOsrm('trip', $this->coordenadasConBase($reportes), [
            'source' => 'first',
            'destination' => 'any',
            'roundtrip' => 'false',
        ]);

        if ($respuesta === null) {
            return null;
        }

        $waypoints = $respuesta['waypoints'] ?? [];
        $viaje = $respuesta['trips'][0] ?? null;

        if ($viaje === null || (int) ($waypoints[0]['waypoint_index'] ?? -1) !== 0) {
            return null;
        }

        $ordenEntradaPorViaje = [];
        foreach ($waypoints as $indiceEntrada => $waypoint) {
            $ordenEntradaPorViaje[(int) $waypoint['waypoint_index']] = $indiceEntrada;
        }
        ksort($ordenEntradaPorViaje);

        $paradas = [];
        $tramos = $viaje['legs'] ?? [];
        $indiceTramo = 0;

        foreach (array_values($ordenEntradaPorViaje) as $indiceEntrada) {
            if ($indiceEntrada === 0) {
                continue;
            }

            if (! isset($reportes[$indiceEntrada - 1])) {
                return null;
            }

            $paradas[] = [
                'reporte' => $reportes[$indiceEntrada - 1],
                'distancia' => isset($tramos[$indiceTramo])
                    ? round(((float) $tramos[$indiceTramo]['distance']) / 1000, 2)
                    : 0.0,
            ];
            $indiceTramo++;
        }

        $geometria = $this->geojsonALeaflet($viaje['geometry']['coordinates'] ?? []);

        if (count($paradas) !== count($reportes) || count($geometria) < 2) {
            return null;
        }

        return [
            'paradas' => $paradas,
            'geometria' => $geometria,
            'distancia_km' => round(((float) ($viaje['distance'] ?? 0)) / 1000, 2),
            'por_vias' => true,
        ];
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array{paradas: array<int, array{reporte: Reporte, distancia: float}>, geometria: array<int, array{0: float, 1: float}>, distancia_km: float, por_vias: bool}|null
     */
    private function trazarConOsrmRuta(array $reportes): ?array
    {
        $vecinoMasCercano = $this->ordenarPorVecinoMasCercano($reportes);
        $ordenados = array_map(fn (array $punto) => $punto['reporte'], $vecinoMasCercano);

        return $this->consultarRutaParaOrden($ordenados);
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array{paradas: array<int, array{reporte: Reporte, distancia: float}>, geometria: array<int, array{0: float, 1: float}>, distancia_km: float, por_vias: bool}|null
     */
    private function consultarRutaParaOrden(array $reportes): ?array
    {
        $respuesta = $this->consultarOsrm('route', $this->coordenadasConBase($reportes));

        if ($respuesta === null) {
            return null;
        }

        $recorrido = $respuesta['routes'][0] ?? null;
        if ($recorrido === null) {
            return null;
        }

        $geometria = $this->geojsonALeaflet($recorrido['geometry']['coordinates'] ?? []);

        if (count($geometria) < 2) {
            return null;
        }

        $tramos = $recorrido['legs'] ?? [];
        $paradas = [];
        $actualLat = self::BASE_LATITUD;
        $actualLng = self::BASE_LONGITUD;

        foreach ($reportes as $indice => $reporte) {
            $paradas[] = [
                'reporte' => $reporte,
                'distancia' => isset($tramos[$indice])
                    ? round(((float) $tramos[$indice]['distance']) / 1000, 2)
                    : round($this->distanciaHaversine(
                        $actualLat,
                        $actualLng,
                        (float) $reporte->latitud,
                        (float) $reporte->longitud
                    ), 2),
            ];
            $actualLat = (float) $reporte->latitud;
            $actualLng = (float) $reporte->longitud;
        }

        return [
            'paradas' => $paradas,
            'geometria' => $geometria,
            'distancia_km' => round(((float) ($recorrido['distance'] ?? 0)) / 1000, 2),
            'por_vias' => true,
        ];
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array{paradas: array<int, array{reporte: Reporte, distancia: float}>, geometria: array<int, array{0: float, 1: float}>, distancia_km: float, por_vias: bool}
     */
    private function trazarEnLineaRecta(array $reportes): array
    {
        $paradas = $this->ordenarPorVecinoMasCercano($reportes);

        return [
            'paradas' => $paradas,
            'geometria' => $this->geometriaRectaDeParadas($paradas),
            'distancia_km' => round(collect($paradas)->sum('distancia'), 2),
            'por_vias' => false,
        ];
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array{paradas: array<int, array{reporte: Reporte, distancia: float}>, geometria: array<int, array{0: float, 1: float}>, distancia_km: float, por_vias: bool}
     */
    private function geometriaRectaEnOrden(array $reportes): array
    {
        $paradas = [];
        $actualLat = self::BASE_LATITUD;
        $actualLng = self::BASE_LONGITUD;

        foreach (array_values($reportes) as $reporte) {
            $distancia = round($this->distanciaHaversine(
                $actualLat,
                $actualLng,
                (float) $reporte->latitud,
                (float) $reporte->longitud
            ), 2);
            $paradas[] = ['reporte' => $reporte, 'distancia' => $distancia];
            $actualLat = (float) $reporte->latitud;
            $actualLng = (float) $reporte->longitud;
        }

        return [
            'paradas' => $paradas,
            'geometria' => $this->geometriaRectaDeParadas($paradas),
            'distancia_km' => round(collect($paradas)->sum('distancia'), 2),
            'por_vias' => false,
        ];
    }

    /**
     * @param  array<int, array{reporte: Reporte, distancia: float}>  $paradas
     * @return array<int, array{0: float, 1: float}>
     */
    private function geometriaRectaDeParadas(array $paradas): array
    {
        $geometria = [$this->coordenadaBase()];

        foreach ($paradas as $punto) {
            $geometria[] = [
                (float) $punto['reporte']->latitud,
                (float) $punto['reporte']->longitud,
            ];
        }

        return $geometria;
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array<int, array{reporte: Reporte, distancia: float}>
     */
    private function ordenarPorVecinoMasCercano(array $reportes): array
    {
        $pendientes = array_values($reportes);
        $actualLat = self::BASE_LATITUD;
        $actualLng = self::BASE_LONGITUD;
        $ruta = [];

        while ($pendientes !== []) {
            $indiceCercano = 0;
            $distanciaMinima = PHP_FLOAT_MAX;

            foreach ($pendientes as $indice => $reporte) {
                $distancia = $this->distanciaHaversine(
                    $actualLat,
                    $actualLng,
                    (float) $reporte->latitud,
                    (float) $reporte->longitud
                );

                if ($distancia < $distanciaMinima) {
                    $distanciaMinima = $distancia;
                    $indiceCercano = $indice;
                }
            }

            $siguiente = $pendientes[$indiceCercano];
            $ruta[] = ['reporte' => $siguiente, 'distancia' => round($distanciaMinima, 2)];
            $actualLat = (float) $siguiente->latitud;
            $actualLng = (float) $siguiente->longitud;
            array_splice($pendientes, $indiceCercano, 1);
        }

        return $ruta;
    }

    /**
     * @param  array<int, Reporte>  $reportes
     * @return array<int, array{0: float, 1: float}>
     */
    private function coordenadasConBase(array $reportes): array
    {
        return array_merge(
            [$this->coordenadaBase()],
            array_map(
                fn (Reporte $reporte) => [(float) $reporte->latitud, (float) $reporte->longitud],
                array_values($reportes)
            )
        );
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $coordenadas
     * @param  array<string, string>  $parametros
     * @return array<string, mixed>|null
     */
    private function consultarOsrm(string $servicio, array $coordenadas, array $parametros = []): ?array
    {
        $ruta = collect($coordenadas)
            ->map(fn (array $punto) => $punto[1].','.$punto[0])
            ->implode(';');

        $consulta = array_merge([
            'geometries' => 'geojson',
            'overview' => 'full',
            'steps' => 'false',
        ], $parametros);

        try {
            $respuesta = Http::timeout(12)
                ->acceptJson()
                ->get(self::OSRM_BASE."/{$servicio}/v1/driving/{$ruta}", $consulta);
        } catch (Throwable $excepcion) {
            Log::warning('No se pudo consultar el servicio de ruteo vial.', [
                'servicio' => $servicio,
                'mensaje' => $excepcion->getMessage(),
            ]);

            return null;
        }

        if (! $respuesta->successful() || ($respuesta->json('code') !== 'Ok')) {
            return null;
        }

        $datos = $respuesta->json();

        return is_array($datos) ? $datos : null;
    }

    /**
     * @param  array<int, array{0: float|int|string, 1: float|int|string}>  $coordenadas
     * @return array<int, array{0: float, 1: float}>
     */
    private function geojsonALeaflet(array $coordenadas): array
    {
        $puntos = [];

        foreach ($coordenadas as $par) {
            if (! is_array($par) || count($par) < 2) {
                continue;
            }

            $puntos[] = [(float) $par[1], (float) $par[0]];
        }

        return $puntos;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function coordenadaBase(): array
    {
        return [self::BASE_LATITUD, self::BASE_LONGITUD];
    }

    private function distanciaHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radioTierra = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
