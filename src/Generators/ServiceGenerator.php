<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class ServiceGenerator
{
    public function __construct(
        private StubRenderer $stubRenderer,
    ) {
    }

    /**
     * @param array<string, string> $names
     */
    public function generate(array $names, bool $crud = false, bool $withDelete = false): string
    {
        return $this->stubRenderer->render(__DIR__ . '/../../stubs/service.stub', [
            'namespace' => config('api-scaffold.namespaces.services'),
            'modelNamespace' => config('api-scaffold.namespaces.models'),
            'class' => $names['service'],
            'model' => $names['model'],
            'modelVariable' => $names['modelVariable'],
            'crudMethods' => $crud ? $this->crudMethods($names, $withDelete) : '',
        ]);
    }

    /**
     * @param array<string, string> $names
     */
    private function crudMethods(array $names, bool $withDelete): string
    {
        $delete = $withDelete ? <<<PHP

    /**
     * Deletes an existing record.
     */
    public function delete({$names['model']} \${$names['modelVariable']}): bool
    {
        return (bool) \${$names['modelVariable']}->delete();
    }
PHP : '';

        return <<<PHP

    /**
     * Creates a new record from validated data.
     *
     * @param array<string, mixed> \$data
     */
    public function create(array \$data): {$names['model']}
    {
        return {$names['model']}::query()->create(\$data);
    }

    /**
     * Updates an existing record from validated data.
     *
     * @param array<string, mixed> \$data
     */
    public function update({$names['model']} \${$names['modelVariable']}, array \$data): {$names['model']}
    {
        \${$names['modelVariable']}->fill(\$data);
        \${$names['modelVariable']}->save();

        return \${$names['modelVariable']}->refresh();
    }
{$delete}
PHP;
    }
}
