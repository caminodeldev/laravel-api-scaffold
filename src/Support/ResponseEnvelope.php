<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Support;

use DateTimeImmutable;
use ReflectionException;
use ReflectionMethod;

final class ResponseEnvelope
{
    public static function make(int $code, string $message, mixed $data = []): array
    {
        $helper = config('api-scaffold.response.helper');
        $method = config('api-scaffold.response.method', 'returnResponse');

        if (is_string($helper) && is_string($method) && self::canDelegateTo($helper, $method)) {
            return $helper::{$method}($code, $message, $data);
        }

        return self::default($code, $message, $data);
    }

    private static function default(int $code, string $message, mixed $data = []): array
    {
        $codeKey = config('api-scaffold.envelope.keys.code', 'code');
        $messageKey = config('api-scaffold.envelope.keys.message', 'message');
        $dataKey = config('api-scaffold.envelope.keys.data', 'data');
        $timestampKey = config('api-scaffold.envelope.keys.timestamp', 'timestamp');

        $response = [
            $codeKey => $code,
            $messageKey => $message,
            $dataKey => $data,
        ];

        if ((bool) config('api-scaffold.response.include_timestamp', false)) {
            $response[$timestampKey] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
        }

        return $response;
    }

    private static function canDelegateTo(string $helper, string $method): bool
    {
        if (! class_exists($helper) || ! method_exists($helper, $method)) {
            return false;
        }

        try {
            $reflection = new ReflectionMethod($helper, $method);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->isPublic() && $reflection->isStatic();
    }
}
