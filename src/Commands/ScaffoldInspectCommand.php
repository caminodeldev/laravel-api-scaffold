<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Commands;

use CaminoDelDev\LaravelApiScaffold\Database\TableInspectorFactory;
use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use Illuminate\Console\Command;

class ScaffoldInspectCommand extends Command
{
    protected $signature = 'scaffold:inspect
                            {table : Database table name}
                            {--connection= : Database connection name}';

    protected $description = 'Inspect a database table and print the detected structure.';

    public function handle(TableInspectorFactory $inspectorFactory): int
    {
        $table = (string) $this->argument('table');
        $connection = $this->option('connection') ? (string) $this->option('connection') : null;

        $definition = $inspectorFactory->inspect($table, $connection);

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

            $this->line(sprintf(
                '- %s %s %s',
                $column->name,
                $this->displayType($column),
                implode(' ', $flags)
            ));
        }

        return self::SUCCESS;
    }

    private function displayType(ColumnDefinition $column): string
    {
        if ($column->allowedValues !== []) {
            return sprintf('%s(%s)', $column->type, implode(', ', $column->allowedValues));
        }

        $length = $column->length !== null ? "({$column->length})" : '';

        return "{$column->type}{$length}";
    }
}
