<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\Drivers\PostgresTableInspector;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

class PostgresTableInspectorTest extends TestCase
{
    public function test_it_inspects_postgres_columns_with_enum_unique_and_identity_metadata(): void
    {
        config()->set('api-scaffold.database.default_schema', 'public');

        $columns = [
            (object) [
                'name' => 'id',
                'data_type' => 'bigint',
                'udt_name' => 'int8',
                'length_value' => null,
                'nullable_value' => 'NO',
                'default_value' => "nextval('solicitudes_id_seq'::regclass)",
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'uuid',
                'data_type' => 'uuid',
                'udt_name' => 'uuid',
                'length_value' => null,
                'nullable_value' => 'NO',
                'default_value' => null,
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'folio',
                'data_type' => 'character varying',
                'udt_name' => 'varchar',
                'length_value' => 30,
                'nullable_value' => 'NO',
                'default_value' => null,
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'estado',
                'data_type' => 'USER-DEFINED',
                'udt_name' => 'solicitud_estado',
                'length_value' => null,
                'nullable_value' => 'NO',
                'default_value' => "'ingresada'::solicitud_estado",
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'metadata',
                'data_type' => 'jsonb',
                'udt_name' => 'jsonb',
                'length_value' => null,
                'nullable_value' => 'YES',
                'default_value' => null,
                'identity_value' => 'NO',
            ],
        ];

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('pgsql');
        $database->method('connection')->with('pgsql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('pgsql');
        $connection->method('select')->willReturnCallback(
            function (string $query, array $bindings) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')) {
                    $this->assertSame(['public', 'solicitudes'], $bindings);

                    return $columns;
                }

                if (str_contains($query, 'CONSTRAINT_TYPE')) {
                    return [(object) ['column_name' => 'id']];
                }

                if (str_contains($query, 'I.INDISUNIQUE')) {
                    return [(object) ['column_name' => 'uuid'], (object) ['column_name' => 'folio']];
                }

                if (str_contains($query, 'PG_ENUM') && $bindings === ['public', 'solicitud_estado']) {
                    return [
                        (object) ['value' => 'ingresada'],
                        (object) ['value' => 'observada'],
                        (object) ['value' => 'cerrada'],
                    ];
                }

                return [];
            }
        );

        $table = (new PostgresTableInspector($database))->inspect('solicitudes');

        $this->assertSame('pgsql', $table->connection);
        $this->assertSame('pgsql', $table->driver);
        $this->assertSame('solicitudes', $table->table);
        $this->assertTrue($table->columns[0]->primary);
        $this->assertTrue($table->columns[0]->autoIncrement);
        $this->assertTrue($table->columns[1]->unique);
        $this->assertSame('varchar', $table->columns[2]->type);
        $this->assertSame(30, $table->columns[2]->length);
        $this->assertSame('enum', $table->columns[3]->type);
        $this->assertSame(['ingresada', 'observada', 'cerrada'], $table->columns[3]->allowedValues);
        $this->assertSame('json', $table->columns[4]->type);
        $this->assertTrue($table->columns[4]->nullable);
    }

    public function test_it_supports_schema_qualified_table_names(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('pgsql');
        $database->method('connection')->with('pgsql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('pgsql');
        $connection->method('select')->willReturnCallback(
            function (string $query, array $bindings): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')) {
                    $this->assertSame(['tramites', 'solicitudes'], $bindings);

                    return [
                        (object) [
                            'name' => 'id',
                            'data_type' => 'bigint',
                            'udt_name' => 'int8',
                            'length_value' => null,
                            'nullable_value' => 'NO',
                            'default_value' => null,
                            'identity_value' => 'YES',
                        ],
                    ];
                }

                return [];
            }
        );

        $table = (new PostgresTableInspector($database))->inspect('tramites.solicitudes');

        $this->assertSame('tramites.solicitudes', $table->table);
        $this->assertTrue($table->columns[0]->autoIncrement);
    }

    public function test_it_only_marks_single_column_unique_indexes_as_unique(): void
    {
        $columns = [
            (object) [
                'name' => 'id',
                'data_type' => 'bigint',
                'udt_name' => 'int8',
                'length_value' => null,
                'nullable_value' => 'NO',
                'default_value' => "nextval('solicitudes_id_seq'::regclass)",
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'folio',
                'data_type' => 'character varying',
                'udt_name' => 'varchar',
                'length_value' => 30,
                'nullable_value' => 'NO',
                'default_value' => null,
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'codigo_tramite',
                'data_type' => 'character varying',
                'udt_name' => 'varchar',
                'length_value' => 50,
                'nullable_value' => 'NO',
                'default_value' => null,
                'identity_value' => 'NO',
            ],
        ];

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('pgsql');
        $database->method('connection')->with('pgsql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('pgsql');
        $connection->method('select')->willReturnCallback(
            function (string $query, array $bindings) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')) {
                    return $columns;
                }

                if (str_contains($query, 'CONSTRAINT_TYPE')) {
                    return [(object) ['column_name' => 'id']];
                }

                if (str_contains($query, 'I.INDISUNIQUE')) {
                    $this->assertStringContainsString("STRING_TO_ARRAY(I.INDKEY::TEXT, ' ')", $query);

                    return [(object) ['column_name' => 'folio']];
                }

                return [];
            }
        );

        $table = (new PostgresTableInspector($database))->inspect('solicitudes');

        $this->assertTrue($table->columns[1]->unique);
        $this->assertFalse($table->columns[2]->unique);
    }


    public function test_it_rejects_non_postgres_connections(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('mysql');
        $database->method('connection')->with('mysql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only supports pgsql connections');

        (new PostgresTableInspector($database))->inspect('users');
    }

    public function test_it_throws_when_table_does_not_exist(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('pgsql');
        $database->method('connection')->with('pgsql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('pgsql');
        $connection->method('select')->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table [missing_table] was not found');

        (new PostgresTableInspector($database))->inspect('missing_table');
    }
    public function test_it_maps_postgres_foreign_keys_and_inverse_references(): void
    {
        config()->set('api-scaffold.database.default_schema', 'public');

        $columns = [
            (object) [
                'name' => 'id',
                'data_type' => 'bigint',
                'udt_name' => 'int8',
                'length_value' => null,
                'nullable_value' => 'NO',
                'default_value' => "nextval('scaffold_transacciones_id_seq'::regclass)",
                'identity_value' => 'NO',
            ],
            (object) [
                'name' => 'scaffold_cliente_id',
                'data_type' => 'bigint',
                'udt_name' => 'int8',
                'length_value' => null,
                'nullable_value' => 'NO',
                'default_value' => null,
                'identity_value' => 'NO',
            ],
        ];

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('pgsql');
        $database->method('connection')->with('pgsql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('pgsql');
        $connection->method('select')->willReturnCallback(
            static function (string $query) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')) {
                    return $columns;
                }

                if (str_contains($query, 'CONSTRAINT_TYPE')) {
                    return [(object) ['column_name' => 'id']];
                }

                if (str_contains($query, 'I.INDISUNIQUE')) {
                    return [];
                }

                if (str_contains($query, 'CON.CONTYPE =')
                    && str_contains($query, 'SOURCE_NS.NSPNAME = ?')) {
                    return [
                        (object) [
                            'constraint_name' => 'scaffold_transacciones_scaffold_cliente_id_foreign',
                            'local_schema' => 'public',
                            'local_table' => 'scaffold_transacciones',
                            'local_column' => 'scaffold_cliente_id',
                            'foreign_schema' => 'public',
                            'foreign_table' => 'scaffold_clientes',
                            'foreign_column' => 'id',
                            'nullable_value' => 'NO',
                        ],
                    ];
                }

                if (str_contains($query, 'CON.CONTYPE =')
                    && str_contains($query, 'TARGET_NS.NSPNAME = ?')) {
                    return [
                        (object) [
                            'constraint_name' => 'scaffold_transacciones_scaffold_cliente_id_foreign',
                            'local_schema' => 'public',
                            'local_table' => 'scaffold_transacciones',
                            'local_column' => 'scaffold_cliente_id',
                            'foreign_schema' => 'public',
                            'foreign_table' => 'scaffold_clientes',
                            'foreign_column' => 'id',
                            'nullable_value' => 'NO',
                        ],
                    ];
                }

                return [];
            }
        );

        $table = (new PostgresTableInspector($database))->inspect('scaffold_transacciones');

        $this->assertCount(1, $table->foreignKeys);
        $this->assertSame('scaffold_cliente_id', $table->foreignKeys[0]->localColumn);
        $this->assertSame('scaffold_clientes', $table->foreignKeys[0]->foreignTable);
        $this->assertSame('id', $table->foreignKeys[0]->foreignColumn);

        $this->assertCount(1, $table->referencedBy);
    }

}
