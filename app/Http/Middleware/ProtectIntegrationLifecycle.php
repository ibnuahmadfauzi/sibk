<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;

class ProtectIntegrationLifecycle
{
    public static function redactForExceptionReporting(Request $request): void
    {
        $isJson = $request->isJson();
        $input = $isJson ? $request->json()->all() : $request->request->all();
        $redactedInput = self::redact($input);

        $request->initialize(
            self::redact($request->query->all()),
            $redactedInput,
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            '',
        );

        if ($isJson) {
            $request->setJson(new InputBag($redactedInput));
        }
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isIntegrationRequest($request)) {
            return $next($request);
        }

        return $this->noStore($next($request));
    }

    private function isIntegrationRequest(Request $request): bool
    {
        return $request->is('data-master/integrations', 'data-master/integrations/*');
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function redact(array $input): array
    {
        $input = array_intersect_key($input, array_flip(['dapodik', 'etatib']));

        foreach (['dapodik', 'etatib'] as $provider) {
            if (! array_key_exists($provider, $input)) {
                continue;
            }

            if (! is_array($input[$provider])) {
                unset($input[$provider]);

                continue;
            }

            $input[$provider] = array_intersect_key($input[$provider], array_flip([
                'base_url',
                'expected_source_identifier',
                'remove_api_key',
                'timeout_seconds',
            ]));
        }

        return $input;
    }
}
