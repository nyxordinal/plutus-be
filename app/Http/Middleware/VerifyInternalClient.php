<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class VerifyInternalClient
{
    const CLIENT_SECRET_CACHE_DURATION = 86400; // Cache for 24 hours

    public function handle(Request $request, Closure $next)
    {
        $clientId = $request->header('X-Client-Id');
        $clientToken = $request->header('X-Client-Token');

        if (!$clientId || !$clientToken) {
            return response()->json(['code' => '400', 'message' => 'Bad Request'], 400);
        }

        $clientSecret = $this->getClientSecret($clientId);
        if (!$clientSecret) {
            return response()->json(['code' => '401', 'message' => 'Invalid Client ID'], 401);
        }

        if ($clientToken != $clientSecret) {
            return response()->json(['code' => '401', 'message' => 'Invalid Token'], 401);
        }

        return $next($request);
    }

    private function getClientSecret($clientId)
    {
        $cacheKey = "CLIENT_SECRET_" . $clientId;
        $clientSecret = null;

        try {
            $cached = Redis::get($cacheKey);

            if ($cached !== null) {
                return $cached !== "NOT_FOUND" ? $cached : null;
            }
        } catch (\Exception $e) {
            Log::error('Redis get failed in getClientSecret: ' . $e->getMessage());
        }

        $client = Client::where('client_id', $clientId)->first(['client_secret']);
        $clientSecret = $client ? $client->client_secret : "NOT_FOUND";

        try {
            Redis::setex($cacheKey, self::CLIENT_SECRET_CACHE_DURATION, $clientSecret);
        } catch (\Exception $e) {
            Log::warning('Redis setex failed in getClientSecret: ' . $e->getMessage());
        }

        return $clientSecret !== "NOT_FOUND" ? $clientSecret : null;
    }
}
