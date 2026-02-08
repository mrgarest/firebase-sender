<?php

namespace Garest\FirebaseSender\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Garest\FirebaseSender\DTO\ServiceAccountData getByName(string $name)
 *
 * @see \Garest\FirebaseSender\Support\ServiceAccount
 */
class ServiceAccount extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'fs.service_account';
    }
}
