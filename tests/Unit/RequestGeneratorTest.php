<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Generators\RequestGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class RequestGeneratorTest extends TestCase
{
    public function test_it_generates_index_rules_for_safe_filters_sort_search_and_pagination(): void
    {
        config()->set('api-scaffold.pagination.max_per_page', 50);
        config()->set('api-scaffold.security.excluded_columns', ['password']);
        config()->set('api-scaffold.security.hidden_columns', []);

        $table = $this->solicitudesTable();
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new RequestGenerator(new StubRenderer()))->generateIndex($table, $names);

        $this->assertStringContainsString("'per_page' => ['nullable', 'integer', 'min:1', 'max:50']", $contents);
        $this->assertStringContainsString("'perPage' => ['nullable', 'integer', 'min:1', 'max:50']", $contents);
        $this->assertStringContainsString("'search' => ['nullable', 'string', 'max:255']", $contents);
        $this->assertStringContainsString("'sort' => ['nullable', 'string', 'max:255']", $contents);
        $this->assertStringContainsString("'estado' => ['sometimes', 'string', 'in:ingresada,cerrada']", $contents);
        $this->assertStringContainsString("'filter.estado' => ['sometimes', 'string', 'in:ingresada,cerrada']", $contents);
        $this->assertStringNotContainsString("'password' =>", $contents);
    }

    public function test_it_generates_smarter_write_rules_from_column_metadata(): void
    {
        $table = $this->solicitudesTable();
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $store = (new RequestGenerator(new StubRenderer()))->generateStore($table, $names);
        $update = (new RequestGenerator(new StubRenderer()))->generateUpdate($table, $names);

        $this->assertStringContainsString("'folio' => ['required', 'string', 'max:30', 'unique:solicitudes,folio']", $store);
        $this->assertStringContainsString("'estado' => ['required', 'string', 'in:ingresada,cerrada']", $store);
        $this->assertStringContainsString("'requiere_revision_manual' => ['required', 'boolean']", $store);
        $this->assertStringContainsString("'metadata' => ['nullable', 'array']", $store);

        $this->assertStringContainsString("'folio' => ['sometimes', 'string', 'max:30']", $update);
        $this->assertStringNotContainsString('unique:solicitudes,folio', $update);
    }

    private function solicitudesTable(): TableDefinition
    {
        return new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('folio', 'varchar', length: 30, nullable: false, unique: true),
                new ColumnDefinition('estado', 'enum', nullable: false, allowedValues: ['ingresada', 'cerrada']),
                new ColumnDefinition('requiere_revision_manual', 'tinyint', length: 1, nullable: false),
                new ColumnDefinition('metadata', 'json', nullable: true),
                new ColumnDefinition('password', 'varchar', length: 255, nullable: false),
            ],
        );
    }
}
