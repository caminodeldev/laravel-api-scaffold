<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\Drivers\MySqlTableInspector;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

class MySqlTableInspectorTest extends TestCase
{
    public function test_it_maps_mysql_information_schema_columns_to_table_definition(): void
    {
        $columns = require __DIR__ . '/../Fixtures/mysql_users_columns.php';

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->expects($this->once())
            ->method('getDefaultConnection')
            ->willReturn('mysql');

        $database->expects($this->once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('getDriverName')
            ->willReturn('mysql');

        $connection->expects($this->once())
            ->method('getDatabaseName')
            ->willReturn('testing');

        $connection->method('select')->willReturnCallback(
            function (string $query, array $bindings) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')
                    && str_contains($query, 'COLUMN_NAME AS name')) {
                    $this->assertSame(['testing', 'users'], $bindings);

                    return $columns;
                }

                return [];
            }
        );

        $table = (new MySqlTableInspector($database))->inspect('users');

        $this->assertSame('mysql', $table->connection);
        $this->assertSame('mysql', $table->driver);
        $this->assertSame('users', $table->table);
        $this->assertCount(8, $table->columns);
        $this->assertTrue($table->usesTimestamps());
        $this->assertTrue($table->usesSoftDeletes());

        $primaryKey = $table->primaryKey();

        $this->assertNotNull($primaryKey);
        $this->assertSame('id', $primaryKey->name);
        $this->assertSame('bigint', $primaryKey->type);
        $this->assertTrue($primaryKey->primary);
        $this->assertTrue($primaryKey->autoIncrement);
        $this->assertTrue($primaryKey->unique);

        $email = $table->columns[2];

        $this->assertSame('email', $email->name);
        $this->assertSame('varchar', $email->type);
        $this->assertSame(255, $email->length);
        $this->assertFalse($email->nullable);
        $this->assertTrue($email->unique);

        $isActive = $table->columns[4];

        $this->assertSame('is_active', $isActive->name);
        $this->assertSame('1', $isActive->default);
    }

    public function test_it_uses_explicit_connection_when_provided(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->expects($this->never())
            ->method('getDefaultConnection');

        $database->expects($this->once())
            ->method('connection')
            ->with('tenant_mysql')
            ->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('getDatabaseName')->willReturn('tenant');
        $connection->method('select')->willReturnCallback(
            static function (string $query): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')
                    && str_contains($query, 'COLUMN_NAME AS name')) {
                    return require __DIR__ . '/../Fixtures/mysql_users_columns.php';
                }

                return [];
            }
        );

        $table = (new MySqlTableInspector($database))->inspect('users', 'tenant_mysql');

        $this->assertSame('tenant_mysql', $table->connection);
    }

    public function test_it_rejects_non_mysql_connections(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('pgsql');
        $database->method('connection')->with('pgsql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('pgsql');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only supports mysql or mariadb connections');

        (new MySqlTableInspector($database))->inspect('users');
    }

    public function test_it_throws_when_table_does_not_exist(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('mysql');
        $database->method('connection')->with('mysql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('getDatabaseName')->willReturn('testing');
        $connection->method('select')->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table [missing_table] was not found');

        (new MySqlTableInspector($database))->inspect('missing_table');
    }

    public function test_it_maps_mysql_enum_allowed_values(): void
    {
        $columns = [
            (object) [
                'name' => 'estado',
                'type' => 'enum',
                'column_type' => "enum('ingresada','en_revision','cerrada')",
                'length_value' => null,
                'nullable_value' => 'NO',
                'column_key' => '',
                'extra_value' => '',
                'default_value' => 'ingresada',
            ],
        ];

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('mysql');
        $database->method('connection')->with('mysql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('getDatabaseName')->willReturn('testing');
        $connection->method('select')->willReturnCallback(
            static function (string $query) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')
                    && str_contains($query, 'COLUMN_NAME AS name')) {
                    return $columns;
                }

                return [];
            }
        );

        $table = (new MySqlTableInspector($database))->inspect('solicitudes');

        $this->assertSame(['ingresada', 'en_revision', 'cerrada'], $table->columns[0]->allowedValues);
    }


    public function test_it_maps_mysql_numeric_display_length_from_column_type(): void
    {
        $columns = [
            (object) [
                'name' => 'requiere_revision_manual',
                'type' => 'tinyint',
                'column_type' => 'tinyint(1)',
                'length_value' => null,
                'nullable_value' => 'NO',
                'column_key' => '',
                'extra_value' => '',
                'default_value' => '0',
            ],
        ];

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('mysql');
        $database->method('connection')->with('mysql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('getDatabaseName')->willReturn('testing');
        $connection->method('select')->willReturnCallback(
            static function (string $query) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')
                    && str_contains($query, 'COLUMN_NAME AS name')) {
                    return $columns;
                }

                return [];
            }
        );

        $table = (new MySqlTableInspector($database))->inspect('solicitudes');

        $this->assertSame(1, $table->columns[0]->length);
    }

    public function test_it_maps_mysql_foreign_keys_and_inverse_references(): void
    {
        $columns = [
            (object) [
                'name' => 'id',
                'type' => 'bigint',
                'column_type' => 'bigint unsigned',
                'length_value' => null,
                'nullable_value' => 'NO',
                'column_key' => 'PRI',
                'extra_value' => 'auto_increment',
                'default_value' => null,
            ],
            (object) [
                'name' => 'scaffold_cliente_id',
                'type' => 'bigint',
                'column_type' => 'bigint unsigned',
                'length_value' => null,
                'nullable_value' => 'NO',
                'column_key' => 'MUL',
                'extra_value' => '',
                'default_value' => null,
            ],
        ];

        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('mysql');
        $database->method('connection')->with('mysql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('getDatabaseName')->willReturn('testing');
        $connection->method('select')->willReturnCallback(
            static function (string $query) use ($columns): array {
                if (str_contains($query, 'INFORMATION_SCHEMA.COLUMNS')
                    && str_contains($query, 'COLUMN_NAME AS name')) {
                    return $columns;
                }

                if (str_contains($query, 'KCU.TABLE_NAME = ?')) {
                    return [
                        (object) [
                            'constraint_name' => 'scaffold_transacciones_scaffold_cliente_id_foreign',
                            'local_table' => 'scaffold_transacciones',
                            'local_column' => 'scaffold_cliente_id',
                            'foreign_table' => 'scaffold_clientes',
                            'foreign_column' => 'id',
                            'nullable_value' => 'NO',
                        ],
                    ];
                }

                if (str_contains($query, 'KCU.REFERENCED_TABLE_NAME = ?')) {
                    return [
                        (object) [
                            'constraint_name' => 'scaffold_transacciones_scaffold_cliente_id_foreign',
                            'local_table' => 'scaffold_transacciones',
                            'local_column' => 'scaffold_cliente_id',
                            'foreign_table' => 'scaffold_clientes',
                            'foreign_column' => 'id',
                            'nullable_value' => 'NO',
                        ],
                    ];
                }

                return [];
            }
        );

        $table = (new MySqlTableInspector($database))->inspect('scaffold_transacciones');

        $this->assertCount(1, $table->foreignKeys);
        $this->assertSame('scaffold_cliente_id', $table->foreignKeys[0]->localColumn);
        $this->assertSame('scaffold_clientes', $table->foreignKeys[0]->foreignTable);
        $this->assertSame('id', $table->foreignKeys[0]->foreignColumn);

        $this->assertCount(1, $table->referencedBy);
    }

}
