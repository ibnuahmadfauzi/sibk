<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ProtectIntegrationLifecycle;
use App\Integrations\IntegrationBusyException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        // Cloudflare Tunnel lokal meneruskan request ke loopback.
        // Jangan mempercayai forwarded headers ini di production.
        if (env('APP_ENV', 'production') === 'local') {
            $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        }

        $middleware->prepend(ProtectIntegrationLifecycle::class);

        $middleware->alias([
            'account.active' => EnsureActiveUser::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'api_url',
            'dapodik.api_key',
            'dapodik.current_password',
            'etatib.api_key',
            'etatib.current_password',
        ]);
        $exceptions->reportable(static function (Throwable $exception): ?bool {
            $request = request();

            if (! $request->is('data-master/integrations', 'data-master/integrations/*', 'data-master/api-connections/*')) {
                return null;
            }

            if ($exception instanceof IntegrationBusyException) {
                return false;
            }

            ProtectIntegrationLifecycle::redactForExceptionReporting($request);
            Log::error('Kegagalan tak terduga pada konfigurasi koneksi.', [
                'exception_class' => $exception::class,
                'exception_code' => $exception->getCode(),
            ]);

            return false;
        });
        $exceptions->render(function (IntegrationBusyException $exception, Request $request): ?Response {
            $savedUrl = $request->routeIs('data-master.api-connections.update');
            $mapping = $request->routeIs('data-master.etatib.mappings.*', 'data-master.etatib.conflicts.automatic.apply');
            $duplicate = $request->routeIs('data-master.etatib.duplicates.destroy');
            if (! $savedUrl && ! $mapping && ! $duplicate) {
                return null;
            }

            $message = $savedUrl && $request->route('provider') === 'dapodik'
                ? 'Proses API Siswa sedang berjalan. Coba lagi setelah selesai.'
                : 'Sinkronisasi e-Tatib sedang berjalan. Coba lagi setelah selesai.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409)->header('Cache-Control', 'no-store');
            }

            if ($savedUrl) {
                $provider = (string) $request->route('provider');

                return redirect()->route('data-master.index', ['tab' => $provider])
                    ->withErrors(['api_url' => $message], 'api_connections_'.$provider)
                    ->header('Cache-Control', 'no-store');
            }

            return redirect()->route($mapping ? 'data-master.etatib.conflicts.index' : 'data-master.index',
                $mapping ? [] : ['tab' => 'etatib'])
                ->withErrors([$mapping ? 'etatib_mapping' : 'etatib_sync' => $message])
                ->header('Cache-Control', 'no-store');
        });
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->routeIs('data-master.integrations.*', 'data-master.api-connections.*')) {
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

            $oldInput = [];
            if ($request->routeIs('data-master.integrations.update')) {
                $provider = (string) $request->route('provider');
                $providerInput = $request->input($provider);
                if (is_array($providerInput)) {
                    $oldInput[$provider] = array_intersect_key($providerInput, array_flip([
                        'base_url',
                        'expected_source_identifier',
                        'remove_api_key',
                        'timeout_seconds',
                    ]));
                }
            }

            $destination = $request->routeIs('data-master.api-connections.*')
                ? route('data-master.index', ['tab' => (string) $request->route('provider')])
                : route('data-master.index', ['tab' => 'dapodik']).'#integration-'.(string) $request->route('provider');

            return redirect()
                ->to($destination)
                ->withInput($oldInput)
                ->withErrors($exception->errors(), $exception->errorBag)
                ->header('Cache-Control', 'no-store');
        });
        $exceptions->render(function (Throwable $exception, Request $request): ?Response {
            if (! $request->is('data-master/integrations', 'data-master/integrations/*', 'data-master/api-connections/*')
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
            if ($request->is('data-master/integrations', 'data-master/integrations/*', 'data-master/api-connections/*')) {
                $response->headers->set('Cache-Control', 'no-store');
            }

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
