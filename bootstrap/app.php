<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->prepend(HandleCors::class);  // pastikan ini PREPEND bukan append
    
    // Tambah ini — handle OPTIONS preflight request
    $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
    
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    $middleware->appendToGroup('api', [
        \App\Http\Middleware\SanitizeInput::class,
    ]);
    $middleware->alias([
        'role'              => \App\Http\Middleware\CheckRole::class,
        'account.status'    => \App\Http\Middleware\CheckAccountStatus::class,
        'log.activity'      => \App\Http\Middleware\LogActivity::class,
        'integration.auth'  => \App\Http\Middleware\IntegrationAuth::class,
        'brute.force'       => \App\Http\Middleware\PreventBruteForce::class,
    ]);
})

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        });
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        });
    })
    ->create();