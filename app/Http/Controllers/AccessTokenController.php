<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AccessTokenController extends Controller
{
    /**
     * Generate access token for valid client
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAccessToken(Request $request)
    {
        Log::info("Generating access token");
        try {
            $clientId = $request->header('X-Client-Id');
            Log::info("Client ID: " . $clientId);
            if (!$clientId || $this->isClientNotExist($clientId)) {
                Log::error("Invalid Client ID");
                return $this->failedResponse('Invalid Client ID', 400);
            }
            Log::info("Client ID is valid");
            // Generate a random access token
            $accessToken = $this->generateRandomAccessToken();

            return $this->successResponse(['access_token' => $accessToken]);
        } catch (\Exception $exception) {
            Log::error("Failed to generate access token", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    /**
     * Check if the client does not exist
     *
     * @param string $clientId
     * @return bool
     */
    private function isClientNotExist($clientId)
    {
        $cacheKey = "CLIENT_" . $clientId;

        try {
            $cached = Redis::get($cacheKey);

            if ($cached !== null) {
                return $cached === "false";
            }
        } catch (\Exception $e) {
            Log::error("Redis get failed in isClientNotExist: " . $e->getMessage());
        }

        $clientExists = Client::find($clientId) !== null;

        try {
            Redis::setex($cacheKey, 86400, $clientExists ? "true" : "false");
        } catch (\Exception $e) {
            Log::warning("Redis setex failed in isClientNotExist: " . $e->getMessage());
        }

        return !$clientExists;
    }

    /**
     * Generate a random access token
     *
     * @return string
     */
    private function generateRandomAccessToken()
    {
        return bin2hex(random_bytes(16)); // 16 bytes = 128 bits
    }
}
