<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->web(append: [
            \App\Http\Middleware\TrackPageViews::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->has('debug') || config('app.debug')) {
                return response(
                    '<div style="font-family:sans-serif;padding:24px;background:#fef2f2;color:#991b1b;border:1px solid #f87171;border-radius:8px;margin:20px;">' .
                    '<h2 style="margin-top:0;">' . htmlspecialchars(get_class($e)) . '</h2>' .
                    '<p style="font-size:16px;"><strong>Pesan:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>' .
                    '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</p>' .
                    '<pre style="background:#fff;padding:12px;border:1px solid #fed7aa;border-radius:4px;overflow:auto;font-size:12px;max-height:400px;color:#1f2937;">' .
                    htmlspecialchars($e->getTraceAsString()) .
                    '</pre></div>',
                    500,
                    ['Content-Type' => 'text/html; charset=utf-8']
                );
            }
        });
    })->create();

// Gunakan /tmp/storage untuk lingkungan serverless (Vercel) yang read-only
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || env('VERCEL')) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
