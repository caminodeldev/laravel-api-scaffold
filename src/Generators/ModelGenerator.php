<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
use CaminoDelDev\LaravelApiScaffold\Support\PhpArrayRenderer;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class ModelGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpArrayRenderer $arrayRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generate(TableDefinition $table, array $names): string
    {
        $security = ColumnSecurity::fromConfig();
        $primaryKey = $table->primaryKey()?->name ?? 'id';

        $fillable = array_map(
            static fn ($column): string => $column->name,
            $security->fillableColumns($table->columns)
        );

        $hidden = array_map(
            static fn ($column): string => $column->name,
            $security->hiddenColumns($table->columns)
        );

        $casts = [];
        foreach ($table->columns as $column) {
            $cast = $this->castForColumn($column);
            if ($cast !== null) {
                $casts[$column->name] = $cast;
            }
        }

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/model.stub', [
            'namespace' => config('api-scaffold.namespaces.models'),
            'class' => $names['model'],
            'table' => $table->table,
            'primaryKey' => $primaryKey,
            'fillable' => $this->arrayRenderer->stringList($fillable),
            'hidden' => $this->arrayRenderer->stringList($hidden),
            'casts' => $this->arrayRenderer->associativeList($casts),
            'softDeletesImport' => $table->usesSoftDeletes() ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '',
            'softDeletesTrait' => $table->usesSoftDeletes() ? "    use SoftDeletes;\n\n" : '',
            'timestamps' => $table->usesTimestamps() ? 'true' : 'false',
        ]);
    }

    private function castForColumn(\CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition $column): ?string
    {
        if (strtolower($column->type) === 'tinyint' && $column->length === 1) {
            return 'boolean';
        }

        return match (strtolower($column->type)) {
            'bigint', 'int', 'integer', 'mediumint', 'smallint', 'tinyint' => 'integer',
            'decimal', 'double', 'float' => 'decimal:2',
            'boolean', 'bool' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'json' => 'array',
            default => null,
        };
    }
}
