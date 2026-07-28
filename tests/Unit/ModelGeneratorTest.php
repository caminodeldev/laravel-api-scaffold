<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Generators\ModelGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\PhpArrayRenderer;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    public function test_it_casts_tinyint_one_columns_as_boolean(): void
    {
        $table = new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('requiere_revision_manual', 'tinyint', length: 1),
                new ColumnDefinition('dias_habiles_estimados', 'tinyint', length: 3),
            ],
        );

        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new ModelGenerator(new StubRenderer(), new PhpArrayRenderer()))->generate($table, $names);

        $this->assertStringContainsString("'requiere_revision_manual' => 'boolean'", $contents);
        $this->assertStringContainsString("'dias_habiles_estimados' => 'integer'", $contents);
    }
}
