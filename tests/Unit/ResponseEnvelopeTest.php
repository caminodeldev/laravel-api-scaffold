<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Support\ResponseEnvelope;
use CaminoDelDev\LaravelApiScaffold\Tests\Fixtures\ExternalResponseHelper;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class ResponseEnvelopeTest extends TestCase
{
    public function test_it_builds_default_envelope(): void
    {
        $response = ResponseEnvelope::make(200, 'OK', ['id' => 1]);

        $this->assertSame([
            'code' => 200,
            'message' => 'OK',
            'data' => ['id' => 1],
        ], $response);
    }

    public function test_it_uses_configured_envelope_keys(): void
    {
        config()->set('api-scaffold.envelope.keys.code', 'codigoRetorno');
        config()->set('api-scaffold.envelope.keys.message', 'glosaRetorno');
        config()->set('api-scaffold.envelope.keys.data', 'respuesta');

        $response = ResponseEnvelope::make(201, 'Creado', ['id' => 10]);

        $this->assertSame([
            'codigoRetorno' => 201,
            'glosaRetorno' => 'Creado',
            'respuesta' => ['id' => 10],
        ], $response);
    }

    public function test_it_can_include_timestamp_when_enabled(): void
    {
        config()->set('api-scaffold.response.include_timestamp', true);

        $response = ResponseEnvelope::make(200, 'OK');

        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString($response['timestamp']);
        $this->assertNotFalse(date_create_immutable($response['timestamp']));
    }

    public function test_it_delegates_to_configured_external_helper(): void
    {
        config()->set('api-scaffold.response.helper', ExternalResponseHelper::class);
        config()->set('api-scaffold.response.method', 'returnResponse');

        $response = ResponseEnvelope::make(200, 'OK', ['id' => 5]);

        $this->assertSame([
            'codigoRetorno' => 200,
            'glosaRetorno' => 'OK',
            'timestamp' => 'external-helper',
            'respuesta' => ['id' => 5],
        ], $response);
    }

    public function test_it_falls_back_to_default_envelope_when_configured_helper_is_missing(): void
    {
        config()->set('api-scaffold.response.helper', 'App\\Helpers\\MissingResponseHelper');

        $response = ResponseEnvelope::make(404, 'Missing', null);

        $this->assertSame([
            'code' => 404,
            'message' => 'Missing',
            'data' => null,
        ], $response);
    }
}
