<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database;

use CaminoDelDev\LaravelApiScaffold\Database\Drivers\MySqlTableInspector;
use CaminoDelDev\LaravelApiScaffold\Database\Drivers\PostgresTableInspector;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

final readonly class TableInspectorFactory
{
    public function __construct(
        private DatabaseManager $database,
    ) {
    }

    public function inspect(string $table, ?string $connection = null): TableDefinition
    {
        return $this->make($connection)->inspect($table, $connection);
    }

    public function make(?string $connection = null): TableInspector
    {
        $connectionName = $connection ?: $this->database->getDefaultConnection();
        $driver = $this->database->connection($connectionName)->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => new MySqlTableInspector($this->database),
            'pgsql' => new PostgresTableInspector($this->database),
            default => throw new RuntimeException(sprintf(
                'Unsupported database driver [%s] for connection [%s]. Supported drivers: mysql, mariadb, pgsql.',
                $driver,
                $connectionName
            )),
        };
    }
}
