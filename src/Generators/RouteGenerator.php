<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class RouteGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generate(array $names, bool $crud = false, bool $withDelete = false): string
    {
        $only = $crud
            ? ($withDelete ? "['index', 'show', 'store', 'update', 'destroy']" : "['index', 'show', 'store', 'update']")
            : "['index', 'show']";

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/routes.stub', [
            'controllerNamespace' => config('api-scaffold.namespaces.controllers'),
            'controller' => $names['controller'],
            'route' => $names['route'],
            'routeParameterResource' => $names['routeParameterResource'],
            'routeParameter' => $names['routeParameter'],
            'only' => $only,
        ]);
    }
}
