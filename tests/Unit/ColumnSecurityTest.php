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

    public function test_it_normalizes_sensitive_column_names_before_matching(): void
    {
        $security = new ColumnSecurity(
            excludedColumns: ['api_key', 'client_secret'],
            hiddenColumns: []
        );

        $this->assertTrue($security->isSensitive('apiKey'));
        $this->assertTrue($security->isSensitive('API-KEY'));
        $this->assertTrue($security->isSensitive('clientSecret'));
    }

    public function test_it_uses_patterns_for_common_secret_names(): void
    {
        $security = new ColumnSecurity(
            excludedColumns: [],
            hiddenColumns: [],
            sensitiveNamePatterns: [
                '/(^|_)(password|passwd|pwd)(_|$)/i',
                '/(^|_)(client|api|access|secret|private|encryption|signing|webhook)_(key|secret|token)(_|$)/i',
                '/(^|_)(token|jwt|bearer)(_|$)/i',
            ]
        );

        $this->assertTrue($security->isSensitive('password_hash'));
        $this->assertTrue($security->isSensitive('signing_key'));
        $this->assertTrue($security->isSensitive('accessToken'));
        $this->assertFalse($security->isSensitive('foreign_key_id'));
        $this->assertFalse($security->isSensitive('keyboard_layout'));
    }

    public function test_fillable_columns_never_include_sensitive_writable_columns(): void
    {
        $security = new ColumnSecurity(
            excludedColumns: ['password', 'remember_token'],
            hiddenColumns: []
        );

        $columns = [
            new ColumnDefinition('id', 'bigint', primary: true),
            new ColumnDefinition('name', 'varchar'),
            new ColumnDefinition('password', 'varchar'),
            new ColumnDefinition('remember_token', 'varchar'),
        ];

        $fillable = array_map(
            static fn (ColumnDefinition $column): string => $column->name,
            $security->fillableColumns($columns)
        );

        $this->assertSame(['name'], $fillable);
    }
}
