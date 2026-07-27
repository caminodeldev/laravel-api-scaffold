<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class TestGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generate(array $names): string
    {
        return $this->stubRenderer->render(__DIR__ . '/../../stubs/feature-test.stub', [
            'namespace' => config('api-scaffold.namespaces.tests'),
            'class' => $names['test'],
            'routePrefix' => trim(config('api-scaffold.routes.prefix', 'v1'), '/'),
            'route' => $names['route'],
        ]);
    }
}
