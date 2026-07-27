<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Security;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;

final class ColumnSecurity
{
    /**
     * @param array<int, string> $excludedColumns
     * @param array<int, string> $hiddenColumns
     * @param array<int, string> $sensitiveNamePatterns
     */
    public function __construct(
        private readonly array $excludedColumns = [],
        private readonly array $hiddenColumns = [],
        private readonly array $sensitiveNamePatterns = [],
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            excludedColumns: config('api-scaffold.security.excluded_columns', []),
            hiddenColumns: config('api-scaffold.security.hidden_columns', []),
            sensitiveNamePatterns: config('api-scaffold.security.sensitive_name_patterns', []),
        );
    }

    public function isSensitive(string $column): bool
    {
        $normalized = $this->normalizeColumnName($column);

        if (in_array($normalized, $this->normalizeColumnList($this->excludedColumns), true)) {
            return true;
        }

        foreach ($this->sensitiveNamePatterns as $pattern) {
            if (@preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    public function shouldHide(string $column): bool
    {
        $normalized = $this->normalizeColumnName($column);

        return in_array($normalized, $this->normalizeColumnList($this->hiddenColumns), true)
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

    /**
     * @param array<int, string> $columns
     * @return array<int, string>
     */
    private function normalizeColumnList(array $columns): array
    {
        return array_map(
            fn (string $column): string => $this->normalizeColumnName($column),
            $columns
        );
    }

    private function normalizeColumnName(string $column): string
    {
        $snakeCase = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $column);
        $normalized = strtolower($snakeCase);
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '_', $normalized);

        return trim($normalized, '_');
    }
}
