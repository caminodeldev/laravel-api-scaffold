<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database;

final readonly class TableDefinition
{
    /**
     * @param array<int, ColumnDefinition> $columns
     */
    public function __construct(
        public string $connection,
        public string $driver,
        public string $table,
        public array $columns,
    ) {
    }

    public function primaryKey(): ?ColumnDefinition
    {
        foreach ($this->columns as $column) {
            if ($column->primary) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return array<int, ColumnDefinition>
     */
    public function writableColumns(): array
    {
        return array_values(array_filter(
            $this->columns,
            static fn (ColumnDefinition $column): bool => $column->isWritable()
        ));
    }

    public function hasColumn(string $name): bool
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return true;
            }
        }

        return false;
    }

    public function usesSoftDeletes(): bool
    {
        return $this->hasColumn('deleted_at');
    }

    public function usesTimestamps(): bool
    {
        return $this->hasColumn('created_at') && $this->hasColumn('updated_at');
    }
}
