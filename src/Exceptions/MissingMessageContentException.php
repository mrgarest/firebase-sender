<?php

namespace Garest\FirebaseSender\Exceptions;

final class MissingMessageContentException extends \Exception
{
    public function __construct(int $index)
    {
        parent::__construct("Message group [{$index}] has a recipient but no content (notification or data)");
    }
}
