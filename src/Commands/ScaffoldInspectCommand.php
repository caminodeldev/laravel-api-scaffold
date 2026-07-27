<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Commands;

use CaminoDelDev\LaravelApiScaffold\Database\Drivers\MySqlTableInspector;
use Illuminate\Console\Command;

class ScaffoldInspectCommand extends Command
{
    protected $signature = 'scaffold:inspect
                            {table : Database table name}
                            {--connection= : Database connection name}';

    protected $description = 'Inspect a database table and print the detected structure.';

    public function handle(MySqlTableInspector $inspector): int
    {
        $table = (string) $this->argument('table');
        $connection = $this->option('connection') ? (string) $this->option('connection') : null;

        $definition = $inspector->inspect($table, $connection);

        $this->info("Table: {$definition->table}");
        $this->line("Connection: {$definition->connection}");
        $this->line("Driver: {$definition->driver}");
        $this->newLine();
        $this->line('Columns:');

        foreach ($definition->columns as $column) {
            $flags = [];

            if ($column->primary) {
                $flags[] = 'primary';
            }

            if ($column->autoIncrement) {
                $flags[] = 'auto_increment';
            }

            if ($column->nullable) {
                $flags[] = 'nullable';
            } else {
                $flags[] = 'not_null';
            }

            if ($column->unique) {
                $flags[] = 'unique';
            }

            $length = $column->length !== null ? "({$column->length})" : '';

            $this->line(sprintf(
                '- %s %s%s %s',
                $column->name,
                $column->type,
                $length,
                implode(' ', $flags)
            ));
        }

        return self::SUCCESS;
    }
}
