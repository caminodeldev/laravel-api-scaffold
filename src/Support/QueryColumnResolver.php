<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Support;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;

final readonly class QueryColumnResolver
{
    /**
     * @param array<int, string> $excludedColumns
     * @param array<int, string> $excludedPatterns
     */
    public function __construct(
        private ColumnSecurity $security,
        private array $excludedColumns = [],
        private array $excludedPatterns = [],
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            ColumnSecurity::fromConfig(),
            config('api-scaffold.query.excluded_columns', []),
            config('api-scaffold.query.excluded_patterns', []),
        );
    }

    /**
     * @return array<int, string>
     */
    public function filterable(TableDefinition $table): array
    {
        return $this->columnNames(array_filter(
            $table->columns,
            fn (ColumnDefinition $column): bool => $this->isSafeExactFilterColumn($column)
        ));
    }

    /**
     * @return array<int, string>
     */
    public function searchable(TableDefinition $table): array
    {
        return $this->columnNames(array_filter(
            $table->columns,
            fn (ColumnDefinition $column): bool => $this->isSafeSearchColumn($column)
        ));
    }

    /**
     * @return array<int, string>
     */
    public function sortable(TableDefinition $table): array
    {
        return $this->columnNames(array_filter(
            $table->columns,
            fn (ColumnDefinition $column): bool => $this->isSafeSortColumn($column)
        ));
    }

    private function isSafeExactFilterColumn(ColumnDefinition $column): bool
    {
        return ! $column->autoIncrement
            && ! $this->isQueryExcluded($column->name)
            && ! $this->security->shouldHide($column->name)
            && in_array(strtolower($column->type), [
                'bigint',
                'int',
                'integer',
                'mediumint',
                'smallint',
                'tinyint',
                'boolean',
                'bool',
                'uuid',
                'char',
                'varchar',
                'enum',
                'date',
                'datetime',
                'timestamp',
                'decimal',
                'double',
                'float',
            ], true);
    }

    private function isSafeSearchColumn(ColumnDefinition $column): bool
    {
        return ! $this->isQueryExcluded($column->name)
            && ! $this->security->shouldHide($column->name)
            && in_array(strtolower($column->type), [
                'char',
                'varchar',
                'text',
                'tinytext',
                'mediumtext',
                'longtext',
            ], true);
    }

    private function isSafeSortColumn(ColumnDefinition $column): bool
    {
        if ($this->isQueryExcluded($column->name)) {
            return false;
        }

        if ($this->security->shouldHide($column->name) && ! $this->isFrameworkTimestamp($column->name)) {
            return false;
        }

        return in_array(strtolower($column->type), [
            'bigint',
            'int',
            'integer',
            'mediumint',
            'smallint',
            'tinyint',
            'boolean',
            'bool',
            'uuid',
            'char',
            'varchar',
            'enum',
            'date',
            'datetime',
            'timestamp',
            'decimal',
            'double',
            'float',
        ], true);
    }

    private function isQueryExcluded(string $column): bool
    {
        $normalized = $this->normalizeColumnName($column);

        if (in_array($normalized, $this->normalizedExcludedColumns(), true)) {
            return true;
        }

        foreach ($this->excludedPatterns as $pattern) {
            if (@preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function normalizedExcludedColumns(): array
    {
        return array_map(
            fn (string $column): string => $this->normalizeColumnName($column),
            $this->excludedColumns
        );
    }

    private function normalizeColumnName(string $column): string
    {
        $snakeCase = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $column);
        $normalized = strtolower($snakeCase);
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '_', $normalized);

        return trim($normalized, '_');
    }

    private function isFrameworkTimestamp(string $column): bool
    {
        return in_array($column, ['created_at', 'updated_at', 'deleted_at'], true);
    }

    /**
     * @param array<int|string, ColumnDefinition> $columns
     * @return array<int, string>
     */
    private function columnNames(array $columns): array
    {
        return array_values(array_map(
            static fn (ColumnDefinition $column): string => $column->name,
            $columns
        ));
    }
}
