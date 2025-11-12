<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Routing;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'check.access' => \App\Http\Middleware\CheckUserAccess::class,
            'simple.rate.limit' => \App\Http\Middleware\SimpleRateLimiter::class,


    ]);
})
    ->withExceptions(function (Exceptions $exceptions) {
        // Always return JSON for API routes
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
        
        // Handle unauthenticated requests for API routes
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });
        
        // Handle validation exceptions
        $exceptions->render(function (\Illuminate\Validation\ValidationException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errors = $exception->errors();
                $firstError = collect($errors)->flatten()->first();
                
                return response()->json([
                    'success' => false,
                    'message' => $firstError ?? 'Validation failed',
                    'errors' => $errors
                ], 422);
            }
        });
        
        // Handle method not allowed exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $allowedMethods = $exception->getHeaders()['Allow'] ?? 'POST';
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed. This endpoint requires a POST request, but you used ' . $request->method() . '. Supported methods: ' . $allowedMethods,
                    'error' => $exception->getMessage()
                ], 405);
            }
        });
        
        // Handle not found exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found.',
                    'error' => 'The requested route does not exist.'
                ], 404);
            }
        });
        
        // Handle all other exceptions for API routes
        $exceptions->render(function (\Throwable $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('API Exception: ' . $exception->getMessage(), [
                    'exception' => $exception,
                    'request' => $request->all(),
                    'url' => $request->fullUrl(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your request.',
                    'error' => config('app.debug') ? $exception->getMessage() : 'Internal server error'
                ], 500);
            }
        });
    })->create();
