<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class ResourceGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generate(TableDefinition $table, array $names): string
    {
        $security = ColumnSecurity::fromConfig();

        $fields = array_map(
            static fn ($column): string => "            '{$column->name}' => \$this->resource->{$column->name},",
            $security->visibleColumns($table->columns)
        );

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/resource.stub', [
            'namespace' => config('api-scaffold.namespaces.resources'),
            'class' => $names['resource'],
            'fields' => implode(PHP_EOL, $fields),
        ]);
    }
}
