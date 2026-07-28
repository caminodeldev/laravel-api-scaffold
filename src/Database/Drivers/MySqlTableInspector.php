<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database\Drivers;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableInspector;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

final readonly class MySqlTableInspector implements TableInspector
{
    public function __construct(
        private DatabaseManager $database
    ) {
    }

    public function inspect(string $table, ?string $connection = null): TableDefinition
    {
        $connectionName = $connection ?: $this->database->getDefaultConnection();
        $db = $this->database->connection($connectionName);

        if ($db->getDriverName() !== 'mysql') {
            throw new RuntimeException(sprintf(
                'The MySQL inspector only supports mysql connections. [%s] uses [%s].',
                $connectionName,
                $db->getDriverName()
            ));
        }

        $databaseName = (string) $db->getDatabaseName();

        $columns = $db->select(
            <<<SQL
            SELECT
                COLUMN_NAME AS name,
                DATA_TYPE AS type,
                COLUMN_TYPE AS column_type,
                CHARACTER_MAXIMUM_LENGTH AS length_value,
                IS_NULLABLE AS nullable_value,
                COLUMN_KEY AS column_key,
                EXTRA AS extra_value,
                COLUMN_DEFAULT AS default_value
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
            SQL,
            [$databaseName, $table]
        );

        if ($columns === []) {
            throw new RuntimeException(sprintf(
                'Table [%s] was not found on connection [%s].',
                $table,
                $connectionName
            ));
        }

        return new TableDefinition(
            connection: $connectionName,
            driver: 'mysql',
            table: $table,
            columns: array_map(
                fn (object $column): ColumnDefinition => new ColumnDefinition(
                    name: (string) $column->name,
                    type: (string) $column->type,
                    length: $column->length_value !== null ? (int) $column->length_value : null,
                    nullable: strtoupper((string) $column->nullable_value) === 'YES',
                    primary: (string) $column->column_key === 'PRI',
                    autoIncrement: str_contains(strtolower((string) $column->extra_value), 'auto_increment'),
                    unique: in_array((string) $column->column_key, ['UNI', 'PRI'], true),
                    default: $column->default_value,
                    allowedValues: $this->enumAllowedValues($column->column_type ?? null),
                ),
                $columns
            )
        );
    }

    /**
     * @return array<int, string>
     */
    private function enumAllowedValues(mixed $columnType): array
    {
        if (! is_string($columnType) || ! str_starts_with(strtolower($columnType), 'enum(')) {
            return [];
        }

        $rawValues = substr($columnType, 5, -1);

        if ($rawValues === false || $rawValues === '') {
            return [];
        }

        return array_map(
            static fn (string $value): string => $value,
            str_getcsv($rawValues, ',', "'", '\\')
        );
    }
}
