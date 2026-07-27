<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Commands;

use CaminoDelDev\LaravelApiScaffold\Database\Drivers\MySqlTableInspector;
use CaminoDelDev\LaravelApiScaffold\Generators\ModelGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\FileWriter;
use Illuminate\Console\Command;

class ScaffoldModelCommand extends Command
{
    protected $signature = 'scaffold:model
                            {table : Database table name}
                            {--connection= : Database connection name}
                            {--model= : Explicit model class name}
                            {--dry-run : Show what would be generated without writing files}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a safe Eloquent model from a database table.';

    public function handle(
        MySqlTableInspector $inspector,
        NameResolver $nameResolver,
        ModelGenerator $modelGenerator,
        FileWriter $fileWriter,
    ): int {
        $table = (string) $this->argument('table');
        $connection = $this->option('connection') ? (string) $this->option('connection') : null;
        $model = $this->option('model') ? (string) $this->option('model') : null;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $definition = $inspector->inspect($table, $connection);
        $names = $nameResolver->resolve($table, $model);

        $path = rtrim(config('api-scaffold.paths.models'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $names['model']
            . '.php';

        $contents = $modelGenerator->generate($definition, $names);

        $fileWriter->write($path, $contents, $force, $dryRun);

        $this->info(($dryRun ? 'Would generate' : 'Generated') . ": {$path}");

        return self::SUCCESS;
    }
}
