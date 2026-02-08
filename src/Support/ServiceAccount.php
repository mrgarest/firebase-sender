<?php

namespace Garest\FirebaseSender\Support;

use Garest\FirebaseSender\DTO\ServiceAccountData;
use Garest\FirebaseSender\Exceptions\ServiceAccountException;

class ServiceAccount
{
    /**
     * Get service account settings by name.
     *
     * @param string $name
     * @return ServiceAccountData
     * @throws ServiceAccountException
     */
    public function getByName(string $name): ServiceAccountData
    {
        $account = config("firebase-sender.service_accounts.{$name}");

        if (!isset($account['project_id'], $account['private_key'], $account['client_email'])) {
            throw new ServiceAccountException();
        }

        return ServiceAccountData::fromArray($account);
    }
}
