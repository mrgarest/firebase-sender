<?php

namespace Garest\FirebaseSender\DTO;

use Carbon\Carbon;

class GoogleAccessToken
{
    public function __construct(
        public string $accessToken,
        public Carbon $expiresAt,
        public string $tokenType
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['access_token'],
            expiresAt: Carbon::createFromTimestamp($data['expires_at']),
            tokenType: $data['token_type']
        );
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'expires_at' => $this->expiresAt->timestamp,
            'token_type' => $this->tokenType
        ];
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function isExpiringSoon(int $bufferSeconds = 10): bool
    {
        return $this->expiresAt->lessThanOrEqualTo(
            Carbon::now()->addSeconds($bufferSeconds)
        );
    }
}
