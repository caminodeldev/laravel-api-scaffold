<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\Drivers\MySqlTableInspector;
use CaminoDelDev\LaravelApiScaffold\Database\TableInspectorFactory;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

class TableInspectorFactoryTest extends TestCase
{
    public function test_it_resolves_mysql_inspector_from_connection_driver(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('mysql');
        $database->method('connection')->with('mysql')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mysql');

        $inspector = (new TableInspectorFactory($database))->make();

        $this->assertInstanceOf(MySqlTableInspector::class, $inspector);
    }

    public function test_it_resolves_mariadb_as_mysql_compatible_inspector(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('connection')->with('tenant_mariadb')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('mariadb');

        $inspector = (new TableInspectorFactory($database))->make('tenant_mariadb');

        $this->assertInstanceOf(MySqlTableInspector::class, $inspector);
    }

    public function test_it_rejects_unsupported_drivers(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->method('getDefaultConnection')->willReturn('sqlite');
        $database->method('connection')->with('sqlite')->willReturn($connection);

        $connection->method('getDriverName')->willReturn('sqlite');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database driver [sqlite]');

        (new TableInspectorFactory($database))->make();
    }
}
