<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Naming;

use Illuminate\Support\Str;

final class NameResolver
{
    /**
     * Resolves naming conventions from a database table name.
     *
     * @return array<string, string>
     */
    public function resolve(string $table, ?string $model = null, ?string $routeResource = null): array
    {
        $table = trim($table);
        $baseTable = $this->baseTableName($table);
        $modelName = $model ?: Str::studly(Str::singular($baseTable));
        $modelVariable = Str::camel($modelName);
        $route = $this->normalizeRouteResource($routeResource ?: $baseTable);

        return [
            'table' => $table,
            'model' => $modelName,
            'modelVariable' => $modelVariable,
            'modelPluralVariable' => Str::camel(Str::pluralStudly($modelName)),
            'controller' => "{$modelName}Controller",
            'service' => "{$modelName}Service",
            'resource' => "{$modelName}Resource",
            'indexRequest' => "Index{$modelName}Request",
            'storeRequest' => "Store{$modelName}Request",
            'updateRequest' => "Update{$modelName}Request",
            'route' => $route,
            'routeName' => str_replace('/', '.', $route),
            'routeParameterResource' => $this->lastRouteSegment($route),
            'routeParameter' => $modelVariable,
            'test' => "{$modelName}ApiTest",
        ];
    }

    private function baseTableName(string $table): string
    {
        if (! str_contains($table, '.')) {
            return $table;
        }

        $segments = explode('.', $table);

        return end($segments) ?: $table;
    }

    private function normalizeRouteResource(string $routeResource): string
    {
        $routeResource = trim($routeResource);
        $routeResource = trim($routeResource, '/');
        $routeResource = preg_replace('#/+#', '/', $routeResource) ?? $routeResource;

        $segments = array_map(
            static fn (string $segment): string => Str::kebab($segment),
            explode('/', $routeResource)
        );

        return implode('/', array_values(array_filter(
            $segments,
            static fn (string $segment): bool => $segment !== ''
        )));
    }

    private function lastRouteSegment(string $route): string
    {
        $segments = explode('/', $route);

        return end($segments) ?: $route;
    }
}
