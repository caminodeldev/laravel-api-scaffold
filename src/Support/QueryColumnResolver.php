<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Support;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;

final readonly class QueryColumnResolver
{
    public function __construct(
        private ColumnSecurity $security,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(ColumnSecurity::fromConfig());
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
        return ! $this->security->shouldHide($column->name)
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
