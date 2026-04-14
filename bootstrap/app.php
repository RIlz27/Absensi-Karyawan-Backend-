<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    )
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'api/*', // Biar API nggak kena blokir CSRF
    ]);

    $middleware->statefulApi(); // Penting buat Sanctum

    // Enable CORS for API routes
    $middleware->api([\Illuminate\Http\Middleware\HandleCors::class]);
})
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }
    });
})->create();
