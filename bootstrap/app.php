<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\ProtectIntegrationLifecycle;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare Tunnel lokal meneruskan request ke loopback. Jangan percaya
        // forwarded headers dari setiap peer karena IP ini dipakai audit/throttle.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->prepend(ProtectIntegrationLifecycle::class);
        $middleware->alias([
            'account.active' => EnsureActiveUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'dapodik.api_key',
            'dapodik.current_password',
            'etatib.api_key',
            'etatib.current_password',
        ]);
        $exceptions->dontReportWhen(
            static fn (Throwable $exception): bool => request()->is('data-master/integrations', 'data-master/integrations/*'),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->routeIs('data-master.integrations.*')) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()
                    ->json([
                        'message' => 'Data konfigurasi koneksi tidak valid.',
                        'errors' => $exception->errors(),
                    ], 422)
                    ->header('Cache-Control', 'no-store');
            }

            return redirect()
                ->to(route('data-master.index').'#integration-'.(string) $request->route('provider'))
                ->withInput($request->except([
                    'dapodik.api_key',
                    'dapodik.current_password',
                    'etatib.api_key',
                    'etatib.current_password',
                ]))
                ->withErrors($exception->errors(), $exception->errorBag)
                ->header('Cache-Control', 'no-store');
        });
        $exceptions->render(function (Throwable $exception, Request $request): ?Response {
            if (! $request->is('data-master/integrations', 'data-master/integrations/*')
                || $exception instanceof AuthenticationException
                || $exception instanceof HttpResponseException
            ) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
            $message = $status === 500
                ? 'Terjadi kesalahan pada konfigurasi koneksi.'
                : 'Permintaan konfigurasi koneksi tidak dapat diproses.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], $status, $headers)
                : response($message, $status, $headers);
        });
        $exceptions->respond(static function (Response $response, Throwable $exception, Request $request): Response {
            if ($request->is('data-master/integrations', 'data-master/integrations/*')) {
                $response->headers->set('Cache-Control', 'no-store');
            }

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
