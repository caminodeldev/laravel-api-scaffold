<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\ForeignKeyDefinition;
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

    public function test_it_handles_postgres_boolean_json_and_schema_qualified_tables(): void
    {
        $table = new TableDefinition(
            connection: 'pgsql',
            driver: 'pgsql',
            table: 'public.solicitudes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('requiere_revision_manual', 'boolean'),
                new ColumnDefinition('metadata', 'json'),
            ],
        );

        $names = (new NameResolver())->resolve('public.solicitudes', 'Solicitud');

        $contents = (new ModelGenerator(new StubRenderer(), new PhpArrayRenderer()))->generate($table, $names);

        $this->assertStringContainsString('protected $table = \'public.solicitudes\';', $contents);
        $this->assertStringContainsString("'requiere_revision_manual' => 'boolean'", $contents);
        $this->assertStringContainsString("'metadata' => 'array'", $contents);
    }
    public function test_it_generates_belongs_to_and_has_many_relationships_from_foreign_key_metadata(): void
    {
        $table = new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'scaffold_transacciones',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('scaffold_cliente_id', 'bigint'),
                new ColumnDefinition('uuid', 'char', length: 36),
                new ColumnDefinition('fecha_operacion', 'date'),
                new ColumnDefinition('created_at', 'timestamp', nullable: true),
                new ColumnDefinition('updated_at', 'timestamp', nullable: true),
            ],
            foreignKeys: [
                new ForeignKeyDefinition(
                    name: 'scaffold_transacciones_scaffold_cliente_id_foreign',
                    localTable: 'scaffold_transacciones',
                    localColumn: 'scaffold_cliente_id',
                    foreignTable: 'scaffold_clientes',
                    foreignColumn: 'id',
                ),
            ],
        );

        $names = (new NameResolver())->resolve('scaffold_transacciones', 'ScaffoldTransaccion');

        $contents = (new ModelGenerator(new StubRenderer(), new PhpArrayRenderer()))->generate($table, $names);

        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Relations\BelongsTo;', $contents);
        $this->assertStringContainsString('public function scaffoldCliente(): BelongsTo', $contents);
        $this->assertStringContainsString("return \$this->belongsTo(ScaffoldCliente::class, 'scaffold_cliente_id', 'id');", $contents);
        $this->assertStringContainsString('@property ScaffoldCliente $scaffoldCliente', $contents);
    }

    public function test_it_generates_inverse_has_many_relationships_from_referenced_by_metadata(): void
    {
        $table = new TableDefinition(
            connection: 'pgsql',
            driver: 'pgsql',
            table: 'scaffold_clientes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('uuid', 'uuid'),
                new ColumnDefinition('nombre', 'varchar', length: 180),
                new ColumnDefinition('created_at', 'timestamp', nullable: true),
                new ColumnDefinition('updated_at', 'timestamp', nullable: true),
            ],
            referencedBy: [
                new ForeignKeyDefinition(
                    name: 'scaffold_transacciones_scaffold_cliente_id_foreign',
                    localTable: 'scaffold_transacciones',
                    localColumn: 'scaffold_cliente_id',
                    foreignTable: 'scaffold_clientes',
                    foreignColumn: 'id',
                ),
            ],
        );

        $names = (new NameResolver())->resolve('scaffold_clientes', 'ScaffoldCliente');

        $contents = (new ModelGenerator(new StubRenderer(), new PhpArrayRenderer()))->generate($table, $names);

        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Collection;', $contents);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Relations\HasMany;', $contents);
        $this->assertStringContainsString('public function scaffoldTransacciones(): HasMany', $contents);
        $this->assertStringContainsString("return \$this->hasMany(ScaffoldTransaccion::class, 'scaffold_cliente_id', 'id');", $contents);
        $this->assertStringContainsString('@property Collection<int, ScaffoldTransaccion> $scaffoldTransacciones', $contents);
    }

    public function test_it_generates_model_phpdoc_for_columns(): void
    {
        $table = new TableDefinition(
            connection: 'mysql',
            driver: 'mysql',
            table: 'scaffold_clientes',
            columns: [
                new ColumnDefinition('id', 'bigint', primary: true, autoIncrement: true),
                new ColumnDefinition('uuid', 'char', length: 36),
                new ColumnDefinition('activo', 'tinyint', length: 1),
                new ColumnDefinition('fecha_alta', 'date'),
                new ColumnDefinition('limite_credito', 'decimal'),
                new ColumnDefinition('preferencias', 'json', nullable: true),
                new ColumnDefinition('observacion', 'text', nullable: true),
            ],
        );

        $names = (new NameResolver())->resolve('scaffold_clientes', 'ScaffoldCliente');

        $contents = (new ModelGenerator(new StubRenderer(), new PhpArrayRenderer()))->generate($table, $names);

        $this->assertStringContainsString('/**', $contents);
        $this->assertStringContainsString(' * Class ScaffoldCliente', $contents);
        $this->assertStringContainsString('@property int $id', $contents);
        $this->assertStringContainsString('@property string $uuid', $contents);
        $this->assertStringContainsString('@property bool $activo', $contents);
        $this->assertStringContainsString('@property Carbon $fecha_alta', $contents);
        $this->assertStringContainsString('@property string $limite_credito', $contents);
        $this->assertStringContainsString('@property array|null $preferencias', $contents);
        $this->assertStringContainsString('@property string|null $observacion', $contents);
        $this->assertStringContainsString('use Illuminate\Support\Carbon;', $contents);
    }

}
