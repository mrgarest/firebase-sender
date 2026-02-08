<?php

namespace Garest\FirebaseSender\DTO;

class ServiceAccountData
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $privateKey,
        public readonly string $clientEmail,
    ) {}

    /**
     * Creating a DTO from an array configuration.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            projectId: $data['project_id'],
            privateKey: str_replace("\\n", "\n", $data['private_key']),
            clientEmail: $data['client_email']
        );
    }
}
