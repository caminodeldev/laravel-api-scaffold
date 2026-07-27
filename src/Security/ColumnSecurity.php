<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Security;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;

final class ColumnSecurity
{
    /**
     * @param array<int, string> $excludedColumns
     * @param array<int, string> $hiddenColumns
     */
    public function __construct(
        private readonly array $excludedColumns = [],
        private readonly array $hiddenColumns = [],
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            excludedColumns: config('api-scaffold.security.excluded_columns', []),
            hiddenColumns: config('api-scaffold.security.hidden_columns', []),
        );
    }

    public function isSensitive(string $column): bool
    {
        $normalized = strtolower($column);

        if (in_array($normalized, array_map('strtolower', $this->excludedColumns), true)) {
            return true;
        }

        foreach (['password', 'token', 'secret', 'key', 'credential'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function shouldHide(string $column): bool
    {
        $normalized = strtolower($column);

        return in_array($normalized, array_map('strtolower', $this->hiddenColumns), true)
            || $this->isSensitive($column);
    }

    /**
     * @param array<int, ColumnDefinition> $columns
     * @return array<int, ColumnDefinition>
     */
    public function visibleColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (ColumnDefinition $column): bool => ! $this->isSensitive($column->name)
        ));
    }

    /**
     * @param array<int, ColumnDefinition> $columns
     * @return array<int, ColumnDefinition>
     */
    public function fillableColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (ColumnDefinition $column): bool => $column->isWritable() && ! $this->isSensitive($column->name)
        ));
    }

    /**
     * @param array<int, ColumnDefinition> $columns
     * @return array<int, ColumnDefinition>
     */
    public function hiddenColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (ColumnDefinition $column): bool => $this->shouldHide($column->name)
        ));
    }
}
