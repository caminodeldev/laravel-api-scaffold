<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Generators\TestGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class TestGeneratorTest extends TestCase
{
    public function test_it_generates_feature_test_with_named_route(): void
    {
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new TestGenerator(new StubRenderer()))->generate($names);

        $this->assertStringContainsString("\$this->getJson(route('solicitudes.index'))", $contents);
        $this->assertStringNotContainsString("'/v1/solicituds'", $contents);
        $this->assertStringNotContainsString("'/v1/solicitudes'", $contents);
    }
}
