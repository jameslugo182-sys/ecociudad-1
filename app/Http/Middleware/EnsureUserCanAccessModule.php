<?php

namespace App\Http\Middleware;

use App\Models\RolSistema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        abort_unless($user?->activo, 403);

        if ($user->rol === 'administrador') {
            return $next($request);
        }

        $rol = RolSistema::where('codigo', $user->rol)
            ->where('activo', true)
            ->first();

        abort_unless($rol && in_array($module, $rol->modulos ?? [], true), 403);

        return $next($request);
    }
}
