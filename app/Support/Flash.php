<?php

namespace App\Support;

final class Flash
{
    /** Tạo 1 message dạng i18n key */
    public static function make(string $type, string $key, array $params = []): array
    {
        return [[
            'type'   => $type,
            'key'    => $key,
            'params' => $params,
        ]];
    }

    public static function success(string $key, array $params = []): array
    {
        return self::make('success', $key, $params);
    }

    public static function error(string $key, array $params = []): array
    {
        return self::make('error', $key, $params);
    }

    public static function warning(string $key, array $params = []): array
    {
        return self::make('warning', $key, $params);
    }

    public static function info(string $key, array $params = []): array
    {
        return self::make('info', $key, $params);
    }

    /** Gộp nhiều message trong 1 request */
    public static function many(array ...$bags): array
    {
        return array_merge(...$bags);
    }
}