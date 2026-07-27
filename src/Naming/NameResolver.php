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
    public function resolve(string $table, ?string $model = null): array
    {
        $modelName = $model ?: Str::studly(Str::singular($table));

        return [
            'table' => $table,
            'model' => $modelName,
            'modelVariable' => Str::camel($modelName),
            'modelPluralVariable' => Str::camel(Str::pluralStudly($modelName)),
            'controller' => "{$modelName}Controller",
            'service' => "{$modelName}Service",
            'resource' => "{$modelName}Resource",
            'indexRequest' => "Index{$modelName}Request",
            'storeRequest' => "Store{$modelName}Request",
            'updateRequest' => "Update{$modelName}Request",
            'route' => Str::kebab(Str::plural(Str::snake($modelName))),
            'test' => "{$modelName}ApiTest",
        ];
    }
}
