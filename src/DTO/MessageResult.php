<?php

namespace MrGarest\FirebaseSender\DTO;

use Carbon\Carbon;

class MessageResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public string $target,
        public string $address,
        public ?Carbon $datetime = null,
        public ?MessageError $error = null
    ) {}
}
