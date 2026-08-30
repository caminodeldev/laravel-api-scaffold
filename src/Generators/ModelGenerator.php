<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Database\ColumnDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\ForeignKeyDefinition;
use CaminoDelDev\LaravelApiScaffold\Database\TableDefinition;
use CaminoDelDev\LaravelApiScaffold\Security\ColumnSecurity;
use CaminoDelDev\LaravelApiScaffold\Support\PhpArrayRenderer;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use Illuminate\Support\Str;

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

        $generateRelationships = (bool) config('api-scaffold.models.generate_relationships', true);
        $generatePhpDoc = (bool) config('api-scaffold.models.generate_phpdoc', true);

        $relationshipMethods = $generateRelationships ? $this->relationshipMethods($table, $names) : '';
        $imports = $this->imports($table, $generateRelationships, $generatePhpDoc);
        $phpDoc = $generatePhpDoc ? $this->phpDoc($table, $names, $generateRelationships) : '';

        return $this->stubRenderer->render(__DIR__ . '/../../stubs/model.stub', [
            'namespace' => config('api-scaffold.namespaces.models'),
            'class' => $names['model'],
            'phpDoc' => $phpDoc,
            'imports' => $imports,
            'table' => $table->table,
            'primaryKey' => $primaryKey,
            'fillable' => $this->arrayRenderer->stringList($fillable),
            'hidden' => $this->arrayRenderer->stringList($hidden),
            'casts' => $this->arrayRenderer->associativeList($casts),
            'softDeletesTrait' => $table->usesSoftDeletes() ? "    use SoftDeletes;\n\n" : '',
            'timestamps' => $table->usesTimestamps() ? 'true' : 'false',
            'relationships' => $relationshipMethods,
        ]);
    }

    private function castForColumn(ColumnDefinition $column): ?string
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
            'json', 'jsonb' => 'array',
            default => null,
        };
    }

    private function imports(TableDefinition $table, bool $generateRelationships, bool $generatePhpDoc): string
    {
        $imports = [];

        if ($table->usesSoftDeletes()) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\SoftDeletes';
        }

        if ($generateRelationships && $table->foreignKeys !== []) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo';
        }

        if ($generateRelationships && $table->referencedBy !== []) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\HasMany';
        }

        if ($generatePhpDoc && $this->phpDocUsesCarbon($table)) {
            $imports[] = 'Illuminate\\Support\\Carbon';
        }

        if ($generatePhpDoc && $generateRelationships && $table->referencedBy !== []) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Collection';
        }

        sort($imports);

        return implode('', array_map(
            static fn (string $import): string => "use {$import};\n",
            array_unique($imports)
        ));
    }

    private function relationshipMethods(TableDefinition $table, array $names): string
    {
        $methods = [];

        foreach ($this->uniqueBelongsToRelations($table->foreignKeys) as $relation) {
            /** @var ForeignKeyDefinition $foreignKey */
            $foreignKey = $relation['foreignKey'];
            $relatedModel = $this->modelClassFromTable($foreignKey->foreignTable);

            $methods[] = implode(PHP_EOL, [
                "    public function {$relation['method']}(): BelongsTo",
                '    {',
                "        return \$this->belongsTo({$relatedModel}::class, '{$foreignKey->localColumn}', '{$foreignKey->foreignColumn}');",
                '    }',
            ]);
        }

        foreach ($this->uniqueHasManyRelations($table->referencedBy) as $relation) {
            /** @var ForeignKeyDefinition $foreignKey */
            $foreignKey = $relation['foreignKey'];
            $relatedModel = $this->modelClassFromTable($foreignKey->localTable);
            $localKey = $table->primaryKey()?->name ?? $foreignKey->foreignColumn;

            $methods[] = implode(PHP_EOL, [
                "    public function {$relation['method']}(): HasMany",
                '    {',
                "        return \$this->hasMany({$relatedModel}::class, '{$foreignKey->localColumn}', '{$localKey}');",
                '    }',
            ]);
        }

        if ($methods === []) {
            return '';
        }

        return PHP_EOL . PHP_EOL . implode(PHP_EOL . PHP_EOL, $methods);
    }

    /**
     * @param array<int, ForeignKeyDefinition> $foreignKeys
     * @return array<int, array{method: string, foreignKey: ForeignKeyDefinition}>
     */
    private function uniqueBelongsToRelations(array $foreignKeys): array
    {
        $used = [];
        $relations = [];

        foreach ($foreignKeys as $foreignKey) {
            $baseMethod = $this->belongsToMethodName($foreignKey);
            $method = $this->uniqueMethodName($baseMethod, $used, $foreignKey->localColumn);

            $relations[] = [
                'method' => $method,
                'foreignKey' => $foreignKey,
            ];
        }

        return $relations;
    }

    /**
     * @param array<int, ForeignKeyDefinition> $foreignKeys
     * @return array<int, array{method: string, foreignKey: ForeignKeyDefinition}>
     */
    private function uniqueHasManyRelations(array $foreignKeys): array
    {
        $used = [];
        $relations = [];

        foreach ($foreignKeys as $foreignKey) {
            $baseMethod = Str::camel(Str::plural($this->baseTableName($foreignKey->localTable)));
            $method = $this->uniqueMethodName($baseMethod, $used, $foreignKey->localColumn);

            $relations[] = [
                'method' => $method,
                'foreignKey' => $foreignKey,
            ];
        }

        return $relations;
    }

    /**
     * @param array<string, bool> $used
     */
    private function uniqueMethodName(string $baseMethod, array &$used, string $fallbackColumn): string
    {
        if (! isset($used[$baseMethod])) {
            $used[$baseMethod] = true;

            return $baseMethod;
        }

        $fallback = Str::camel($this->columnNameWithoutIdSuffix($fallbackColumn));
        $method = "{$baseMethod}By" . Str::studly($fallback);

        if (! isset($used[$method])) {
            $used[$method] = true;

            return $method;
        }

        $counter = 2;
        while (isset($used["{$method}{$counter}"])) {
            $counter++;
        }

        $used["{$method}{$counter}"] = true;

        return "{$method}{$counter}";
    }

    private function belongsToMethodName(ForeignKeyDefinition $foreignKey): string
    {
        return Str::camel($this->columnNameWithoutIdSuffix($foreignKey->localColumn));
    }

    private function columnNameWithoutIdSuffix(string $column): string
    {
        if (str_ends_with($column, '_id')) {
            return substr($column, 0, -3);
        }

        return $column;
    }

    private function modelClassFromTable(string $table): string
    {
        return Str::studly(Str::singular($this->baseTableName($table)));
    }

    private function baseTableName(string $table): string
    {
        if (! str_contains($table, '.')) {
            return $table;
        }

        $segments = explode('.', $table);

        return end($segments) ?: $table;
    }

    private function phpDoc(TableDefinition $table, array $names, bool $generateRelationships): string
    {
        $lines = [
            '/**',
            " * Class {$names['model']}",
            ' *',
        ];

        foreach ($table->columns as $column) {
            $lines[] = sprintf(
                ' * @property %s $%s',
                $this->phpDocColumnType($column),
                $column->name
            );
        }

        $relationshipLines = $generateRelationships ? $this->phpDocRelationshipLines($table) : [];

        if ($relationshipLines !== []) {
            $lines[] = ' *';

            foreach ($relationshipLines as $line) {
                $lines[] = $line;
            }
        }

        $package = (string) config('api-scaffold.namespaces.models', 'App\\Models');

        $lines[] = ' *';
        $lines[] = " * @package {$package}";
        $lines[] = ' */';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @return array<int, string>
     */
    private function phpDocRelationshipLines(TableDefinition $table): array
    {
        $lines = [];

        foreach ($this->uniqueBelongsToRelations($table->foreignKeys) as $relation) {
            /** @var ForeignKeyDefinition $foreignKey */
            $foreignKey = $relation['foreignKey'];
            $type = $this->modelClassFromTable($foreignKey->foreignTable) . ($foreignKey->nullable ? '|null' : '');

            $lines[] = sprintf(
                ' * @property %s $%s',
                $type,
                $relation['method']
            );
        }

        foreach ($this->uniqueHasManyRelations($table->referencedBy) as $relation) {
            /** @var ForeignKeyDefinition $foreignKey */
            $foreignKey = $relation['foreignKey'];
            $type = sprintf('Collection<int, %s>', $this->modelClassFromTable($foreignKey->localTable));

            $lines[] = sprintf(
                ' * @property %s $%s',
                $type,
                $relation['method']
            );
        }

        return $lines;
    }

    private function phpDocColumnType(ColumnDefinition $column): string
    {
        $type = match (strtolower($column->type)) {
            'bigint', 'int', 'integer', 'mediumint', 'smallint', 'tinyint' => $this->integerOrBooleanPhpDocType($column),
            'decimal' => (string) config('api-scaffold.models.phpdoc_decimal_type', 'string'),
            'double', 'float' => 'float',
            'boolean', 'bool' => 'bool',
            'date', 'datetime', 'timestamp' => 'Carbon',
            'json', 'jsonb' => 'array',
            default => 'string',
        };

        if ($column->nullable && ! str_contains($type, 'null')) {
            return "{$type}|null";
        }

        return $type;
    }

    private function integerOrBooleanPhpDocType(ColumnDefinition $column): string
    {
        if (strtolower($column->type) === 'tinyint' && $column->length === 1) {
            return 'bool';
        }

        return 'int';
    }

    private function phpDocUsesCarbon(TableDefinition $table): bool
    {
        foreach ($table->columns as $column) {
            if (in_array(strtolower($column->type), ['date', 'datetime', 'timestamp'], true)) {
                return true;
            }
        }

        return false;
    }
}
