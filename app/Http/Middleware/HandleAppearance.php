<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hands the persisted theme preference to the root blade view so the `dark`
 * class is rendered server-side and the page does not flash on load.
 */
class HandleAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance');

        View::share(
            'appearance',
            in_array($appearance, ['light', 'dark', 'system'], true) ? $appearance : 'system',
        );

        return $next($request);
    }
}
