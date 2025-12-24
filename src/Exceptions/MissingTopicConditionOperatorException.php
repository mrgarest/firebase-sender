<?php

namespace MrGarest\FirebaseSender\Exceptions;

final class MissingTopicConditionOperatorException extends \Exception
{
    protected $message = 'The topic condition operator is missing';
}
