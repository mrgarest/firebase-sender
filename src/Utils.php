<?php

namespace MrGarest\FirebaseSender;

class Utils
{
    /**
     * Converts all elements of the given array to strings.
     *
     * @param array $args
     * @deprecated
     */
    public static function toStrArg(array $args)
    {
        return array_map('strval', $args ?? []);
    }

    /**
     * Deletes all elements of the array whose value is null.
     *
     * @param array $args
     * @return array|null
     */
    public static function nullFilter(array $array): array|null
    {
        $filtered = array_filter($array, function ($v) {
            return !is_null($v) && (!is_array($v) || count($v) > 0);
        });
        return empty($filtered) ? null : $filtered;
    }
}
