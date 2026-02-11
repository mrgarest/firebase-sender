<?php

namespace Garest\FirebaseSender\Events;

class FirebaseMessageFailed
{
    public function __construct(
        public int $code,
        public string $serviceAccount,
        public string $address,
        public string $target,
        public ?string $status = null,
        public ?string $message = null
    ) {}
}
