<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
use CaminoDelDev\LaravelApiScaffold\Support\QueryColumnResolver;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class RequestGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generateIndex(TableDefinition $table, array $names): string
    {
        $queryColumns = QueryColumnResolver::fromConfig();
        $filterable = array_flip($queryColumns->filterable($table));
        $rules = [];

        foreach ($table->columns as $column) {
            if (! array_key_exists($column->name, $filterable)) {
                continue;
            }

            $columnRules = $this->rulesForColumn($column, $table, partial: true, includeUnique: false);
            $rules[] = "            '{$column->name}' => {$columnRules},";
            $rules[] = "            'filter.{$column->name}' => {$columnRules},";
        }

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/request.index.stub', [
            'namespace' => config('api-scaffold.namespaces.requests') . '\\' . $names['model'],
            'class' => $names['indexRequest'],
            'maxPerPage' => (string) config('api-scaffold.pagination.max_per_page', config('api-scaffold.security.max_per_page', 100)),
            'filterRules' => implode(PHP_EOL, $rules),
        ]);
    }

    /**
     * @param array<string, string> $names
     */
    public function generateStore(TableDefinition $table, array $names): string
    {
        return $this->generateWriteRequest($table, $names, $names['storeRequest'], partial: false);
    }

    /**
     * @param array<string, string> $names
     */
    public function generateUpdate(TableDefinition $table, array $names): string
    {
        return $this->generateWriteRequest($table, $names, $names['updateRequest'], partial: true);
    }

    /**
     * @param array<string, string> $names
     */
    private function generateWriteRequest(TableDefinition $table, array $names, string $class, bool $partial): string
    {
        $security = ColumnSecurity::fromConfig();
        $rules = [];

        foreach ($security->fillableColumns($table->writableColumns()) as $column) {
            $rules[] = "            '{$column->name}' => " . $this->rulesForColumn($column, $table, $partial, includeUnique: ! $partial) . ',';
        }

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/request.write.stub', [
            'namespace' => config('api-scaffold.namespaces.requests') . '\\' . $names['model'],
            'class' => $class,
            'rules' => implode(PHP_EOL, $rules),
        ]);
    }

    private function rulesForColumn(
        ColumnDefinition $column,
        TableDefinition $table,
        bool $partial,
        bool $includeUnique
    ): string {
        $rules = [];

        if ($partial) {
            $rules[] = 'sometimes';
        } elseif (! $column->nullable && $column->default === null) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = match (strtolower($column->type)) {
            'bigint', 'int', 'integer', 'mediumint', 'smallint' => 'integer',
            'tinyint' => $column->length === 1 ? 'boolean' : 'integer',
            'decimal', 'double', 'float' => 'numeric',
            'boolean', 'bool' => 'boolean',
            'uuid' => 'uuid',
            'date' => 'date',
            'datetime', 'timestamp', 'time' => 'date',
            'json' => 'array',
            default => 'string',
        };

        if ($column->length !== null && in_array('string', $rules, true)) {
            $rules[] = 'max:' . $column->length;
        }

        if ($column->allowedValues !== []) {
            $rules[] = 'in:' . implode(',', $column->allowedValues);
        }

        if ($includeUnique && $column->unique && ! $column->isPrimaryKey()) {
            $rules[] = "unique:{$table->table},{$column->name}";
        }

        return '[' . implode(', ', array_map(static fn (string $rule): string => "'{$rule}'", $rules)) . ']';
    }
}
