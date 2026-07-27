<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
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
    public function generateIndex(array $names): string
    {
        return $this->stubRenderer->render(__DIR__ . '/../../stubs/request.index.stub', [
            'namespace' => config('api-scaffold.namespaces.requests') . '\\' . $names['model'],
            'class' => $names['indexRequest'],
            'maxPerPage' => (string) config('api-scaffold.security.max_per_page', 100),
        ]);
    }

    /**
     * @param array<string, string> $names
     */
    public function generateStore(TableDefinition $table, array $names): string
    {
        return $this->generateWriteRequest($table, $names, $names['storeRequest'], false);
    }

    /**
     * @param array<string, string> $names
     */
    public function generateUpdate(TableDefinition $table, array $names): string
    {
        return $this->generateWriteRequest($table, $names, $names['updateRequest'], true);
    }

    /**
     * @param array<string, string> $names
     */
    private function generateWriteRequest(TableDefinition $table, array $names, string $class, bool $partial): string
    {
        $security = ColumnSecurity::fromConfig();
        $rules = [];

        foreach ($security->fillableColumns($table->writableColumns()) as $column) {
            $rules[] = "            '{$column->name}' => " . $this->rulesForColumn($column, $partial) . ',';
        }

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/request.write.stub', [
            'namespace' => config('api-scaffold.namespaces.requests') . '\\' . $names['model'],
            'class' => $class,
            'rules' => implode(PHP_EOL, $rules),
        ]);
    }

    private function rulesForColumn(ColumnDefinition $column, bool $partial): string
    {
        $rules = [];

        if ($partial) {
            $rules[] = 'sometimes';
        } elseif (! $column->nullable && $column->default === null) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = match (strtolower($column->type)) {
            'bigint', 'int', 'integer', 'mediumint', 'smallint', 'tinyint' => 'integer',
            'decimal', 'double', 'float' => 'numeric',
            'boolean', 'bool' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'date',
            'json' => 'array',
            default => 'string',
        };

        if ($column->length !== null && in_array('string', $rules, true)) {
            $rules[] = 'max:' . $column->length;
        }

        return '[' . implode(', ', array_map(static fn (string $rule): string => "'{$rule}'", $rules)) . ']';
    }
}
