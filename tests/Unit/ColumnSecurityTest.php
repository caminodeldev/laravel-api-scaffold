<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class ColumnSecurityTest extends TestCase
{
    public function test_it_excludes_sensitive_columns(): void
    {
        $security = new ColumnSecurity(
            excludedColumns: ['password'],
            hiddenColumns: ['remember_token']
        );

        $columns = [
            new ColumnDefinition('id', 'bigint', primary: true),
            new ColumnDefinition('name', 'varchar'),
            new ColumnDefinition('password', 'varchar'),
            new ColumnDefinition('remember_token', 'varchar'),
        ];

        $visible = array_map(
            static fn (ColumnDefinition $column): string => $column->name,
            $security->visibleColumns($columns)
        );

        $this->assertSame(['id', 'name'], $visible);
    }
}
