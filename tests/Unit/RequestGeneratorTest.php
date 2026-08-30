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
        $this->assertStringContainsString('private const FILTERABLE = [', $contents);
        $this->assertStringContainsString('private const SORTABLE = [', $contents);
        $this->assertStringContainsString('reject_invalid_filters', $contents);
        $this->assertStringContainsString('reject_invalid_sorts', $contents);
        $this->assertStringContainsString('validateFilterKeys', $contents);
        $this->assertStringContainsString('validateSortColumns', $contents);
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

        $this->assertStringContainsString("'uuid' => ['required', 'uuid', 'unique:solicitudes,uuid']", $store);
        $this->assertStringContainsString("'folio' => ['required', 'string', 'max:30', 'unique:solicitudes,folio']", $store);
        $this->assertStringContainsString("'estado' => ['required', 'string', 'in:ingresada,cerrada']", $store);
        $this->assertStringContainsString("'requiere_revision_manual' => ['required', 'boolean']", $store);
        $this->assertStringContainsString("'metadata' => ['nullable', 'array']", $store);

        $this->assertStringContainsString("'folio' => ['sometimes', 'string', 'max:30']", $update);
        $this->assertStringNotContainsString('unique:solicitudes,folio', $update);
    }


    public function test_it_generates_uuid_rule_for_mysql_uuid_like_string_columns(): void
    {
        foreach (['char', 'varchar', 'string'] as $type) {
            $table = new TableDefinition(
                connection: 'mysql',
                driver: 'mysql',
                table: 'solicitudes',
                columns: [
                    new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                    new ColumnDefinition('uuid', $type, length: 36, nullable: false, unique: true),
                    new ColumnDefinition('folio', 'varchar', length: 30, nullable: false),
                ],
            );

            $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

            $store = (new RequestGenerator(new StubRenderer()))->generateStore($table, $names);
            $update = (new RequestGenerator(new StubRenderer()))->generateUpdate($table, $names);

            $this->assertStringContainsString("'uuid' => ['required', 'uuid', 'unique:solicitudes,uuid']", $store);
            $this->assertStringNotContainsString("'uuid' => ['required', 'string', 'max:36'", $store);
            $this->assertStringContainsString("'uuid' => ['sometimes', 'uuid']", $update);
        }
    }

    public function test_it_keeps_regular_char_columns_as_strings(): void
    {
        $table = new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('tracking_code', 'char', length: 36, nullable: false),
            ],
        );

        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $store = (new RequestGenerator(new StubRenderer()))->generateStore($table, $names);

        $this->assertStringContainsString("'tracking_code' => ['required', 'string', 'max:36']", $store);
    }


    public function test_it_prefixes_connection_for_postgres_schema_qualified_unique_rules(): void
    {
        $table = new TableDefinition(
            connection: 'pgsql',
            driver: 'pgsql',
            table: 'public.solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('uuid', 'uuid', nullable: false, unique: true),
            ],
        );

        $names = (new NameResolver())->resolve('public.solicitudes', 'Solicitud');

        $store = (new RequestGenerator(new StubRenderer()))->generateStore($table, $names);

        $this->assertStringContainsString("'uuid' => ['required', 'uuid', 'unique:pgsql.public.solicitudes,uuid']", $store);
    }

    public function test_it_generates_in_rule_for_postgres_native_enum_metadata(): void
    {
        $table = new TableDefinition(
            connection: 'pgsql',
            driver: 'pgsql',
            table: 'public.solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition(
                    name: 'estado',
                    type: 'enum',
                    nullable: false,
                    allowedValues: ['ingresada', 'en_revision', 'cerrada'],
                ),
            ],
        );

        $names = (new NameResolver())->resolve('public.solicitudes', 'Solicitud');

        $store = (new RequestGenerator(new StubRenderer()))->generateStore($table, $names);

        $this->assertStringContainsString(
            "'estado' => ['required', 'string', 'in:ingresada,en_revision,cerrada']",
            $store
        );
    }



    public function test_it_generates_index_request_strict_validation_allowlists(): void
    {
        config()->set('api-scaffold.security.excluded_columns', ['password']);
        config()->set('api-scaffold.security.hidden_columns', []);

        $table = $this->solicitudesTable();
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new RequestGenerator(new StubRenderer()))->generateIndex($table, $names);

        $this->assertStringContainsString("        'uuid',", $contents);
        $this->assertStringContainsString("        'folio',", $contents);
        $this->assertStringContainsString("        'estado',", $contents);
        $this->assertStringContainsString("        'page',", $contents);
        $this->assertStringContainsString("        'filter',", $contents);
        $this->assertStringContainsString("The selected filter is not allowed.", $contents);
        $this->assertStringContainsString('The selected sort column [{$column}] is not allowed.', $contents);
        $this->assertStringNotContainsString("        'password',", $contents);
    }

    private function solicitudesTable(): TableDefinition
    {
        return new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('uuid', 'uuid', nullable: false, unique: true),
                new ColumnDefinition('folio', 'varchar', length: 30, nullable: false, unique: true),
                new ColumnDefinition('estado', 'enum', nullable: false, allowedValues: ['ingresada', 'cerrada']),
                new ColumnDefinition('requiere_revision_manual', 'tinyint', length: 1, nullable: false),
                new ColumnDefinition('metadata', 'json', nullable: true),
                new ColumnDefinition('password', 'varchar', length: 255, nullable: false),
            ],
        );
    }
}
