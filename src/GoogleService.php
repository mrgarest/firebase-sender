<?php

namespace MrGarest\FirebaseSender;

use Carbon\Carbon;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use MrGarest\FirebaseSender\DTO\GoogleAccessToken;

class GoogleService
{
    /**
     * Handles sending bulk messages via HTTP.
     *
     * @param int|string $projectId
     * @param string $accessToken
     * @param array $messages
     * 
     * @return array
     */
    public static function poolMessage(int|string $projectId, string $accessToken, array $messages): array
    {
        try {
            return Http::pool(function (Pool $pool) use ($projectId, $accessToken, $messages) {
                return array_map(function ($message) use ($pool, $projectId, $accessToken) {
                    return $pool->withToken($accessToken)
                        ->withHeaders([
                            'Content-Type' => 'application/json; UTF-8',
                        ])
                        ->post('https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send', ['message' => $message]);
                }, $messages);
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Receives authorization tokens
     *
     * @param array $serviceAccount
     * 
     * @return GoogleAccessToken|null
     */
    public static function getAccessToken(array $serviceAccount): ?GoogleAccessToken
    {
        $cashEnabled = (bool) config('firebase-sender.cache.googleAccessToken');

        $cacheKey = 'fcm_auth_token_' . md5($serviceAccount['project_id']);
        if (!$cashEnabled) {
            $data = Cache::get($cacheKey, null);
            if (!$data) return GoogleAccessToken::fromArray($data);
        }

        $credentials = new ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $serviceAccount);
        $auth = $credentials->fetchAuthToken();

        if (!isset($auth['access_token'])) return null;

        $data = new GoogleAccessToken(
            accessToken: $auth['access_token'],
            expiresAt: Carbon::now()->addSeconds($auth['expires_in']),
            tokenType: $auth['token_type']
        );

        if (!$cashEnabled) {
            Cache::put($cacheKey, $data->toArray(), $data->expiresAt->copy()->subSeconds(60));
        }

        return $data;
    }
}
