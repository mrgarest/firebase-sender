<?php

namespace MrGarest\FirebaseSender;

class Utils
{
    /**
     * Converts all elements of the given array to strings.
     *
     * @param array $args
     */
    public static function toStrArg(array $args)
    {
        return array_map('strval', $args ?? []);
    }
}
