<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
use CaminoDelDev\LaravelApiScaffold\Support\QueryColumnResolver;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class QueryColumnResolverTest extends TestCase
{
    public function test_it_resolves_safe_filter_search_and_sort_columns(): void
    {
        $table = new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('folio', 'varchar', length: 30, unique: true),
                new ColumnDefinition('estado', 'enum', allowedValues: ['ingresada', 'cerrada']),
                new ColumnDefinition('metadata', 'json'),
                new ColumnDefinition('observacion_ciudadana', 'text'),
                new ColumnDefinition('password', 'varchar'),
                new ColumnDefinition('created_at', 'timestamp'),
            ],
        );

        $resolver = new QueryColumnResolver(new ColumnSecurity(
            excludedColumns: ['password', 'created_at'],
            hiddenColumns: [],
        ));

        $this->assertSame(['folio', 'estado'], $resolver->filterable($table));
        $this->assertSame(['folio', 'observacion_ciudadana'], $resolver->searchable($table));
        $this->assertSame(['id', 'folio', 'estado', 'created_at'], $resolver->sortable($table));
    }
}
