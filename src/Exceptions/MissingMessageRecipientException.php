<?php

namespace Garest\FirebaseSender\Exceptions;

final class MissingMessageRecipientException extends \Exception
{
    public function __construct(int $index)
    {
        parent::__construct("There is no recipient in the group of messages with index [$index].");
    }
}
