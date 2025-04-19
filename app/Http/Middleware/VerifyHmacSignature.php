<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class VerifyHmacSignature
{
    const HMAC_TIMESTAMP_TOLERANCE = 300; // 5 minutes
    const CLIENT_SECRET_CACHE_DURATION = 86400; // Cache for 24 hours

    public function handle(Request $request, Closure $next)
    {
        $accessToken = $request->bearerToken();
        $clientId = $request->header('X-Client-Id');
        $providedSignature = $request->header('X-HMAC-Signature');
        $timestamp = $request->header('X-HMAC-Timestamp');

        if (!$accessToken || !$clientId || !$providedSignature || !$timestamp) {
            return response()->json(['code' => '400', 'message' => 'Bad Request'], 400);
        }

        $clientSecret = $this->getClientSecret($clientId);
        if (!$clientSecret) {
            return response()->json(['code' => '401', 'message' => 'Invalid Client ID'], 401);
        }

        if (!$this->validateHmacSignature($request, $clientSecret, $providedSignature)) {
            return response()->json(['code' => '401', 'message' => 'Invalid Signature'], 401);
        }

        if (!$this->validateHmacTimestamp($timestamp)) {
            return response()->json(['code' => '401', 'message' => 'Invalid Signature'], 401);
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

    private function validateHmacSignature(Request $request, $clientSecret, $providedSignature)
    {
        $method = $request->getMethod();
        $url = '/' . $request->path();
        $accessToken = $request->bearerToken();
        $requestBody = $request->getContent();
        $timestamp = $request->header('X-HMAC-Timestamp');

        $minifiedBody = $this->minifyJson($requestBody);
        $hashedBody = hash('sha256', $minifiedBody);
        $stringToSign = sprintf('%s:%s:%s:%s:%s', strtoupper($method), $url, $accessToken, strtolower($hashedBody), $timestamp);
        $expectedSignature = base64_encode(hash_hmac('sha512', $stringToSign, $clientSecret, true));

        return hash_equals($providedSignature, $expectedSignature);
    }

    private function validateHmacTimestamp($hmacTimestamp)
    {
        $timestamp = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $hmacTimestamp, 'UTC');
        $currentTimestamp = Carbon::now('UTC');
        $timeDifference = $currentTimestamp->diffInSeconds($timestamp);

        return $timeDifference <= self::HMAC_TIMESTAMP_TOLERANCE;
    }

    private function minifyJson($jsonStr)
    {
        return json_encode(json_decode($jsonStr), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
