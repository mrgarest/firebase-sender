<?php

namespace Garest\FirebaseSender\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array poolMessage(int|string $projectId, string $accessToken, array $messages)
 * @method static \Garest\FirebaseSender\DTO\GoogleAccessToken|null getAccessToken(\Garest\FirebaseSender\DTO\ServiceAccountData $account)
 *
 * @see \Garest\FirebaseSender\Support\GoogleApi
 */
class GoogleApi extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'fs.google.api';
    }
}
