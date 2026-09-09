<?php

namespace App\Http\Middleware;

use App\Models\RolSistema;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'roleName' => fn () => match ($request->user()?->rol) {
                    'administrador' => 'Administrador',
                    'ciudadano' => 'Ciudadano',
                    null => null,
                    default => RolSistema::where('codigo', $request->user()->rol)->value('nombre')
                        ?? str($request->user()->rol)->replace('_', ' ')->title()->toString(),
                },
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
