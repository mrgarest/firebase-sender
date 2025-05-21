<?php

namespace MrGarest\FirebaseSender\Exceptions;


final class MessageEmptyException extends \Exception
{
    protected $message = 'The message cannot be empty';

}
