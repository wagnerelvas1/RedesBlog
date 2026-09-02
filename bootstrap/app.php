<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Themed Inertia pages for the errors a visitor can actually hit, on
        // SPA visits and on direct page loads alike.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 500, 503], true)) {
                return $response;
            }

            // Server errors keep Laravel's stack-trace page while debugging,
            // unless the request came from Inertia (which cannot render it).
            if (config('app.debug') && $request->header('X-Inertia') === null
                && in_array($status, [500, 503], true)) {
                return $response;
            }

            $page = $status === 503 ? 500 : $status;

            return Inertia::render("errors/{$page}")
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
