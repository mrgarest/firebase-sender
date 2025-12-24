<?php

namespace MrGarest\FirebaseSender\DTO;

class MessageError
{
    public function __construct(
        public int $code,
        public ?string $status = null,
        public ?string $message = null
    ) {}
}
