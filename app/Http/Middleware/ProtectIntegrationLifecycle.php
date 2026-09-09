<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectIntegrationLifecycle
{
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
}
