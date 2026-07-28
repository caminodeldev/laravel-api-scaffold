<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Fixtures;

final class ExternalResponseHelper
{
    public static function returnResponse(int $codigoRetorno, string $glosaRetorno, mixed $respuesta = []): array
    {
        return [
            'codigoRetorno' => $codigoRetorno,
            'glosaRetorno' => $glosaRetorno,
            'timestamp' => 'external-helper',
            'respuesta' => $respuesta,
        ];
    }
}
