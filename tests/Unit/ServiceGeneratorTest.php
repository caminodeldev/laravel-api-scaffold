<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Generators\ServiceGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\PhpArrayRenderer;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class ServiceGeneratorTest extends TestCase
{
    public function test_it_generates_safe_filters_search_sort_and_pagination(): void
    {
        config()->set('api-scaffold.security.excluded_columns', ['password', 'created_at']);
        config()->set('api-scaffold.security.hidden_columns', []);

        $table = new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('folio', 'varchar', length: 30),
                new ColumnDefinition('estado', 'enum', allowedValues: ['ingresada', 'cerrada']),
                new ColumnDefinition('observacion_ciudadana', 'text'),
                new ColumnDefinition('password', 'varchar'),
                new ColumnDefinition('created_at', 'timestamp'),
            ],
        );

        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new ServiceGenerator(new StubRenderer(), new PhpArrayRenderer()))
            ->generate($table, $names, crud: true, withDelete: true);

        $this->assertStringContainsString('private const FILTERABLE = [', $contents);
        $this->assertStringContainsString("        'folio',", $contents);
        $this->assertStringContainsString("        'estado',", $contents);
        $this->assertStringContainsString('private const SEARCHABLE = [', $contents);
        $this->assertStringContainsString("        'observacion_ciudadana',", $contents);
        $this->assertStringContainsString('private const SORTABLE = [', $contents);
        $this->assertStringContainsString("        'id',", $contents);
        $this->assertStringContainsString("        'created_at',", $contents);
        $this->assertStringNotContainsString("        'password',", $contents);

        $this->assertStringContainsString('$this->applyFilters($query, $filters);', $contents);
        $this->assertStringContainsString('$this->applySearch($query, $filters);', $contents);
        $this->assertStringContainsString('$this->applySort($query, $filters);', $contents);
        $this->assertStringContainsString('$filters[\'per_page\']', $contents);
        $this->assertStringContainsString('$filters[\'perPage\']', $contents);
    }

    public function test_it_generates_safe_query_lists_for_postgres_metadata(): void
    {
        config()->set('api-scaffold.security.excluded_columns', []);
        config()->set('api-scaffold.security.hidden_columns', []);

        $table = new TableDefinition(
            connection: 'pgsql',
            driver: 'pgsql',
            table: 'public.solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('uuid', 'uuid', unique: true),
                new ColumnDefinition('estado', 'enum', allowedValues: ['ingresada', 'cerrada']),
                new ColumnDefinition('nombre_tramite', 'varchar', length: 180),
                new ColumnDefinition('metadata', 'json', nullable: true),
                new ColumnDefinition('created_at', 'timestamp'),
            ],
        );

        $names = (new NameResolver())->resolve('public.solicitudes', 'Solicitud');

        $contents = (new ServiceGenerator(new StubRenderer(), new PhpArrayRenderer()))
            ->generate($table, $names, crud: true, withDelete: false);

        $this->assertStringContainsString("        'uuid',", $contents);
        $this->assertStringContainsString("        'estado',", $contents);
        $this->assertStringContainsString("        'nombre_tramite',", $contents);
        $this->assertStringNotContainsString("        'metadata',", $contents);
    }
}
