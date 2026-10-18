<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tacho;
use App\Services\NivelTachoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LecturaIotController extends Controller
{
    public function store(Request $request, Tacho $tacho, NivelTachoService $niveles): JsonResponse
    {
        $token = $request->bearerToken()
            ?: (string) $request->header('X-Device-Token', '');

        if ($token === '' || ! $tacho->tokenValido($token)) {
            return response()->json([
                'ok' => false,
                'message' => 'Token de dispositivo inválido.',
            ], 401);
        }

        $validated = $request->validate([
            'distancia_cm' => ['nullable', 'numeric', 'min:2', 'max:400'],
            'porcentaje' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $lectura = $niveles->registrar(
            $tacho,
            isset($validated['distancia_cm']) ? (float) $validated['distancia_cm'] : null,
            isset($validated['porcentaje']) ? (int) $validated['porcentaje'] : null,
        );

        $tacho->refresh();

        return response()->json([
            'ok' => true,
            'codigo' => $tacho->codigo,
            'nivel_porcentaje' => $tacho->nivel_porcentaje,
            'capacidad_restante' => $tacho->capacidadRestante(),
            'estado' => $tacho->estado,
            'alerta' => $tacho->alerta_activa,
            'lectura_id' => $lectura->id,
        ]);
    }
}
