<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database\Drivers;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\ForeignKeyDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableInspector;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

final readonly class PostgresTableInspector implements TableInspector
{
    public function __construct(
        private DatabaseManager $database,
    ) {
    }

    public function inspect(string $table, ?string $connection = null): TableDefinition
    {
        $connectionName = $connection ?: $this->database->getDefaultConnection();
        $db = $this->database->connection($connectionName);

        if ($db->getDriverName() !== 'pgsql') {
            throw new RuntimeException(sprintf(
                'The PostgreSQL inspector only supports pgsql connections. [%s] uses [%s].',
                $connectionName,
                $db->getDriverName()
            ));
        }

        [$schema, $tableName] = $this->splitSchemaAndTable($table);

        $columns = $db->select(
            <<<SQL
            SELECT
                COLUMN_NAME AS name,
                DATA_TYPE AS data_type,
                UDT_NAME AS udt_name,
                CHARACTER_MAXIMUM_LENGTH AS length_value,
                IS_NULLABLE AS nullable_value,
                COLUMN_DEFAULT AS default_value,
                IS_IDENTITY AS identity_value
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
            SQL,
            [$schema, $tableName]
        );

        if ($columns === []) {
            throw new RuntimeException(sprintf(
                'Table [%s] was not found on connection [%s].',
                $table,
                $connectionName
            ));
        }

        $primaryColumns = array_flip($this->primaryColumns($schema, $tableName, $connectionName));
        $uniqueColumns = array_flip($this->singleColumnUniqueColumns($schema, $tableName, $connectionName));

        $columnDefinitions = array_map(
            function (object $column) use ($schema, $connectionName, $primaryColumns, $uniqueColumns): ColumnDefinition {
                $allowedValues = $this->enumAllowedValues($schema, (string) ($column->udt_name ?? ''), $connectionName);
                $name = (string) $column->name;
                $type = $this->normalizedType((string) $column->data_type, (string) ($column->udt_name ?? ''), $allowedValues);

                return new ColumnDefinition(
                    name: $name,
                    type: $type,
                    length: $this->columnLength($column->length_value ?? null),
                    nullable: strtoupper((string) $column->nullable_value) === 'YES',
                    primary: array_key_exists($name, $primaryColumns),
                    autoIncrement: $this->isAutoIncrement($column->default_value ?? null, $column->identity_value ?? null),
                    unique: array_key_exists($name, $primaryColumns) || array_key_exists($name, $uniqueColumns),
                    default: $column->default_value,
                    allowedValues: $allowedValues,
                );
            },
            $columns
        );

        $resolvedTable = str_contains($table, '.') ? $table : $tableName;

        return new TableDefinition(
            connection: $connectionName,
            driver: 'pgsql',
            table: $resolvedTable,
            columns: $columnDefinitions,
            foreignKeys: $this->foreignKeys($db, $schema, $tableName),
            referencedBy: $this->referencedBy($db, $schema, $tableName),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitSchemaAndTable(string $table): array
    {
        $table = trim($table);

        if (str_contains($table, '.')) {
            [$schema, $tableName] = explode('.', $table, 2);

            return [trim($schema, ' "'), trim($tableName, ' "')];
        }

        return [(string) config('api-scaffold.database.default_schema', 'public'), trim($table, ' "')];
    }


    /**
     * @return array<int, ForeignKeyDefinition>
     */
    private function foreignKeys(Connection $db, string $schema, string $table): array
    {
        $rows = $db->select(
            <<<SQL
            SELECT
                CON.CONNAME AS constraint_name,
                SOURCE_NS.NSPNAME AS local_schema,
                SOURCE.RELNAME AS local_table,
                SOURCE_ATT.ATTNAME AS local_column,
                TARGET_NS.NSPNAME AS foreign_schema,
                TARGET.RELNAME AS foreign_table,
                TARGET_ATT.ATTNAME AS foreign_column,
                COLS.IS_NULLABLE AS nullable_value
            FROM PG_CONSTRAINT CON
            JOIN PG_CLASS SOURCE ON SOURCE.OID = CON.CONRELID
            JOIN PG_NAMESPACE SOURCE_NS ON SOURCE_NS.OID = SOURCE.RELNAMESPACE
            JOIN PG_CLASS TARGET ON TARGET.OID = CON.CONFRELID
            JOIN PG_NAMESPACE TARGET_NS ON TARGET_NS.OID = TARGET.RELNAMESPACE
            JOIN UNNEST(CON.CONKEY) WITH ORDINALITY AS SOURCE_COLS(ATTNUM, ORD) ON TRUE
            JOIN UNNEST(CON.CONFKEY) WITH ORDINALITY AS TARGET_COLS(ATTNUM, ORD) ON TARGET_COLS.ORD = SOURCE_COLS.ORD
            JOIN PG_ATTRIBUTE SOURCE_ATT ON SOURCE_ATT.ATTRELID = SOURCE.OID AND SOURCE_ATT.ATTNUM = SOURCE_COLS.ATTNUM
            JOIN PG_ATTRIBUTE TARGET_ATT ON TARGET_ATT.ATTRELID = TARGET.OID AND TARGET_ATT.ATTNUM = TARGET_COLS.ATTNUM
            LEFT JOIN information_schema.columns COLS
              ON COLS.TABLE_SCHEMA = SOURCE_NS.NSPNAME
             AND COLS.TABLE_NAME = SOURCE.RELNAME
             AND COLS.COLUMN_NAME = SOURCE_ATT.ATTNAME
            WHERE CON.CONTYPE = 'f'
              AND SOURCE_NS.NSPNAME = ?
              AND SOURCE.RELNAME = ?
              AND ARRAY_LENGTH(CON.CONKEY, 1) = 1
            ORDER BY CON.CONNAME, SOURCE_COLS.ORD
            SQL,
            [$schema, $table]
        );

        return array_values(array_map(
            fn (object $row): ForeignKeyDefinition => new ForeignKeyDefinition(
                name: (string) $row->constraint_name,
                localTable: $this->schemaQualifiedTable((string) $row->local_schema, (string) $row->local_table),
                localColumn: (string) $row->local_column,
                foreignTable: $this->schemaQualifiedTable((string) $row->foreign_schema, (string) $row->foreign_table),
                foreignColumn: (string) $row->foreign_column,
                nullable: strtoupper((string) $row->nullable_value) === 'YES',
            ),
            $rows
        ));
    }

    /**
     * @return array<int, ForeignKeyDefinition>
     */
    private function referencedBy(Connection $db, string $schema, string $table): array
    {
        $rows = $db->select(
            <<<SQL
            SELECT
                CON.CONNAME AS constraint_name,
                SOURCE_NS.NSPNAME AS local_schema,
                SOURCE.RELNAME AS local_table,
                SOURCE_ATT.ATTNAME AS local_column,
                TARGET_NS.NSPNAME AS foreign_schema,
                TARGET.RELNAME AS foreign_table,
                TARGET_ATT.ATTNAME AS foreign_column,
                COLS.IS_NULLABLE AS nullable_value
            FROM PG_CONSTRAINT CON
            JOIN PG_CLASS SOURCE ON SOURCE.OID = CON.CONRELID
            JOIN PG_NAMESPACE SOURCE_NS ON SOURCE_NS.OID = SOURCE.RELNAMESPACE
            JOIN PG_CLASS TARGET ON TARGET.OID = CON.CONFRELID
            JOIN PG_NAMESPACE TARGET_NS ON TARGET_NS.OID = TARGET.RELNAMESPACE
            JOIN UNNEST(CON.CONKEY) WITH ORDINALITY AS SOURCE_COLS(ATTNUM, ORD) ON TRUE
            JOIN UNNEST(CON.CONFKEY) WITH ORDINALITY AS TARGET_COLS(ATTNUM, ORD) ON TARGET_COLS.ORD = SOURCE_COLS.ORD
            JOIN PG_ATTRIBUTE SOURCE_ATT ON SOURCE_ATT.ATTRELID = SOURCE.OID AND SOURCE_ATT.ATTNUM = SOURCE_COLS.ATTNUM
            JOIN PG_ATTRIBUTE TARGET_ATT ON TARGET_ATT.ATTRELID = TARGET.OID AND TARGET_ATT.ATTNUM = TARGET_COLS.ATTNUM
            LEFT JOIN information_schema.columns COLS
              ON COLS.TABLE_SCHEMA = SOURCE_NS.NSPNAME
             AND COLS.TABLE_NAME = SOURCE.RELNAME
             AND COLS.COLUMN_NAME = SOURCE_ATT.ATTNAME
            WHERE CON.CONTYPE = 'f'
              AND TARGET_NS.NSPNAME = ?
              AND TARGET.RELNAME = ?
              AND ARRAY_LENGTH(CON.CONKEY, 1) = 1
            ORDER BY SOURCE.RELNAME, CON.CONNAME, SOURCE_COLS.ORD
            SQL,
            [$schema, $table]
        );

        return array_values(array_map(
            fn (object $row): ForeignKeyDefinition => new ForeignKeyDefinition(
                name: (string) $row->constraint_name,
                localTable: $this->schemaQualifiedTable((string) $row->local_schema, (string) $row->local_table),
                localColumn: (string) $row->local_column,
                foreignTable: $this->schemaQualifiedTable((string) $row->foreign_schema, (string) $row->foreign_table),
                foreignColumn: (string) $row->foreign_column,
                nullable: strtoupper((string) $row->nullable_value) === 'YES',
            ),
            $rows
        ));
    }

    private function schemaQualifiedTable(string $schema, string $table): string
    {
        $defaultSchema = (string) config('api-scaffold.database.default_schema', 'public');

        return $schema === $defaultSchema ? $table : "{$schema}.{$table}";
    }

    /**
     * @return array<int, string>
     */
    private function primaryColumns(string $schema, string $table, string $connection): array
    {
        $rows = $this->database->connection($connection)->select(
            <<<SQL
            SELECT KCU.COLUMN_NAME AS column_name
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS TC
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE KCU
              ON KCU.CONSTRAINT_NAME = TC.CONSTRAINT_NAME
             AND KCU.TABLE_SCHEMA = TC.TABLE_SCHEMA
             AND KCU.TABLE_NAME = TC.TABLE_NAME
            WHERE TC.TABLE_SCHEMA = ?
              AND TC.TABLE_NAME = ?
              AND TC.CONSTRAINT_TYPE = 'PRIMARY KEY'
            SQL,
            [$schema, $table]
        );

        return array_values(array_map(
            static fn (object $row): string => (string) $row->column_name,
            $rows
        ));
    }

    /**
     * @return array<int, string>
     */
    private function singleColumnUniqueColumns(string $schema, string $table, string $connection): array
    {
        $rows = $this->database->connection($connection)->select(
            <<<SQL
            SELECT A.ATTNAME AS column_name
            FROM PG_INDEX I
            JOIN PG_CLASS C ON C.OID = I.INDRELID
            JOIN PG_NAMESPACE N ON N.OID = C.RELNAMESPACE
            JOIN PG_ATTRIBUTE A ON A.ATTRELID = C.OID AND A.ATTNUM = ANY(I.INDKEY)
            WHERE N.NSPNAME = ?
              AND C.RELNAME = ?
              AND I.INDISUNIQUE = TRUE
              AND ARRAY_LENGTH(STRING_TO_ARRAY(I.INDKEY::TEXT, ' '), 1) = 1
            SQL,
            [$schema, $table]
        );

        return array_values(array_map(
            static fn (object $row): string => (string) $row->column_name,
            $rows
        ));
    }

    /**
     * @return array<int, string>
     */
    private function enumAllowedValues(string $schema, string $typeName, string $connection): array
    {
        if ($typeName === '') {
            return [];
        }

        $rows = $this->database->connection($connection)->select(
            <<<SQL
            SELECT E.ENUMLABEL AS value
            FROM PG_TYPE T
            JOIN PG_ENUM E ON T.OID = E.ENUMTYPID
            JOIN PG_NAMESPACE N ON N.OID = T.TYPNAMESPACE
            WHERE N.NSPNAME = ?
              AND T.TYPNAME = ?
            ORDER BY E.ENUMSORTORDER
            SQL,
            [$schema, $typeName]
        );

        return array_values(array_map(
            static fn (object $row): string => (string) $row->value,
            $rows
        ));
    }

    /**
     * @param array<int, string> $allowedValues
     */
    private function normalizedType(string $dataType, string $udtName, array $allowedValues): string
    {
        $dataType = strtolower($dataType);
        $udtName = strtolower($udtName);

        if ($dataType === 'user-defined' && $allowedValues !== []) {
            return 'enum';
        }

        return match ($dataType) {
            'character varying' => 'varchar',
            'character' => 'char',
            'timestamp without time zone', 'timestamp with time zone' => 'timestamp',
            'time without time zone', 'time with time zone' => 'time',
            'double precision' => 'double',
            'real' => 'float',
            'numeric' => 'decimal',
            'jsonb' => 'json',
            'user-defined' => $udtName,
            default => $dataType,
        };
    }

    private function columnLength(mixed $lengthValue): ?int
    {
        return $lengthValue === null ? null : (int) $lengthValue;
    }

    private function isAutoIncrement(mixed $defaultValue, mixed $identityValue): bool
    {
        if (strtoupper((string) $identityValue) === 'YES') {
            return true;
        }

        return is_string($defaultValue) && str_contains(strtolower($defaultValue), 'nextval(');
    }
}
