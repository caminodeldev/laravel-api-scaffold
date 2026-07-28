<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Support\PhpArrayRenderer;
use CaminoDelDev\LaravelApiScaffold\Support\QueryColumnResolver;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class ServiceGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpArrayRenderer $arrayRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generate(TableDefinition $table, array $names, bool $crud = false, bool $withDelete = false): string
    {
        $queryColumns = QueryColumnResolver::fromConfig();
        $sortableColumns = $queryColumns->sortable($table);

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/service.stub', [
            'namespace' => config('api-scaffold.namespaces.services'),
            'modelNamespace' => config('api-scaffold.namespaces.models'),
            'class' => $names['service'],
            'model' => $names['model'],
            'modelVariable' => $names['modelVariable'],
            'filterableColumns' => $this->arrayRenderer->stringList($queryColumns->filterable($table), 8),
            'searchableColumns' => $this->arrayRenderer->stringList($queryColumns->searchable($table), 8),
            'sortableColumns' => $this->arrayRenderer->stringList($sortableColumns, 8),
            'defaultSort' => $this->defaultSort($table, $sortableColumns),
            'crudMethods' => $crud ? $this->crudMethods($names, $withDelete) : '',
        ]);
    }

    /**
     * @param array<int, string> $sortableColumns
     */
    private function defaultSort(TableDefinition $table, array $sortableColumns): string
    {
        $primaryKey = $table->primaryKey()?->name;

        if ($primaryKey !== null && in_array($primaryKey, $sortableColumns, true)) {
            return "-{$primaryKey}";
        }

        return $sortableColumns[0] ?? '';
    }

    /**
     * @param array<string, string> $names
     */
    private function crudMethods(array $names, bool $withDelete): string
    {
        $delete = $withDelete ? <<<PHP

    /**
     * Deletes an existing record.
     */
    public function delete({$names['model']} \${$names['modelVariable']}): bool
    {
        return (bool) \${$names['modelVariable']}->delete();
    }
PHP : '';

        return <<<PHP

    /**
     * Creates a new record from validated data.
     *
     * @param array<string, mixed> \$data
     */
    public function create(array \$data): {$names['model']}
    {
        return {$names['model']}::query()->create(\$data);
    }

    /**
     * Updates an existing record from validated data.
     *
     * @param array<string, mixed> \$data
     */
    public function update({$names['model']} \${$names['modelVariable']}, array \$data): {$names['model']}
    {
        \${$names['modelVariable']}->fill(\$data);
        \${$names['modelVariable']}->save();

        return \${$names['modelVariable']}->refresh();
    }
{$delete}
PHP;
    }
}
