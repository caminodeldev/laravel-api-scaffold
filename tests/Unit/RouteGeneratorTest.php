<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Generators\RouteGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class RouteGeneratorTest extends TestCase
{
    public function test_it_uses_table_based_resource_uri_and_explicit_route_parameter_mapping(): void
    {
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new RouteGenerator(new StubRenderer()))->generate($names, crud: true, withDelete: true);

        $this->assertStringContainsString('// <laravel-api-scaffold resource="solicitudes">', $contents);
        $this->assertStringContainsString("Route::apiResource('solicitudes'", $contents);
        $this->assertStringContainsString("->parameters(['solicitudes' => 'solicitud'])", $contents);
        $this->assertStringContainsString("->only(['index', 'show', 'store', 'update', 'destroy'])", $contents);
    }
}
