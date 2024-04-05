<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class LogContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        $correlationId = Uuid::uuid4()->toString();
        Log::getLogger()->pushProcessor(function ($record) use ($correlationId) {
            $record['correlation_id'] = $correlationId;
            return $record;
        });

        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
