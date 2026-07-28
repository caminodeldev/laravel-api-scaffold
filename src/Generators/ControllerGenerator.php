<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Generators;

use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;

final readonly class ControllerGenerator
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
        return $this->stubRenderer->render(__DIR__ . '/../../stubs/controller.stub', [
            'namespace' => config('api-scaffold.namespaces.controllers'),
            'requestNamespace' => config('api-scaffold.namespaces.requests') . '\\' . $names['model'],
            'resourceNamespace' => config('api-scaffold.namespaces.resources'),
            'modelNamespace' => config('api-scaffold.namespaces.models'),
            'serviceNamespace' => config('api-scaffold.namespaces.services'),
            'class' => $names['controller'],
            'model' => $names['model'],
            'modelVariable' => $names['modelVariable'],
            'modelPluralVariable' => $names['modelPluralVariable'],
            'service' => $names['service'],
            'resource' => $names['resource'],
            'indexRequest' => $names['indexRequest'],
            'storeRequest' => $names['storeRequest'],
            'updateRequest' => $names['updateRequest'],
            'crudMethods' => $crud ? $this->crudMethods($names, $withDelete) : '',
            'storeUse' => $crud ? "use {$this->requestNamespace($names)}\\{$names['storeRequest']};\n" : '',
            'updateUse' => $crud ? "use {$this->requestNamespace($names)}\\{$names['updateRequest']};\n" : '',
        ]);
    }

    /**
     * @param array<string, string> $names
     */
    private function requestNamespace(array $names): string
    {
        return config('api-scaffold.namespaces.requests') . '\\' . $names['model'];
    }

    /**
     * @param array<string, string> $names
     */
    private function crudMethods(array $names, bool $withDelete): string
    {
        $delete = $withDelete ? <<<PHP

    public function destroy({$names['model']} \${$names['modelVariable']}): JsonResponse
    {
        try {
            \$this->{$names['modelVariable']}Service->delete(\${$names['modelVariable']});

            return response()->json([
                config('api-scaffold.envelope.keys.code', 'code') => 200,
                config('api-scaffold.envelope.keys.message', 'message') => config('api-scaffold.envelope.messages.destroy_success', 'Record deleted successfully.'),
                config('api-scaffold.envelope.keys.data', 'data') => null,
            ]);
        } catch (Throwable \$throwable) {
            Log::error('Error deleting record.', [
                'controller' => self::class,
                'method' => __METHOD__,
                'error' => \$throwable->getMessage(),
            ]);

            return response()->json([
                config('api-scaffold.envelope.keys.code', 'code') => 500,
                config('api-scaffold.envelope.keys.message', 'message') => config('api-scaffold.envelope.messages.internal_error', 'Internal server error.'),
                config('api-scaffold.envelope.keys.data', 'data') => null,
            ], 500);
        }
    }
PHP : '';

        return <<<PHP

    public function store({$names['storeRequest']} \$request): JsonResponse
    {
        try {
            \${$names['modelVariable']} = \$this->{$names['modelVariable']}Service->create(\$request->validated());

            return response()->json([
                config('api-scaffold.envelope.keys.code', 'code') => 201,
                config('api-scaffold.envelope.keys.message', 'message') => config('api-scaffold.envelope.messages.store_success', 'Record created successfully.'),
                config('api-scaffold.envelope.keys.data', 'data') => new {$names['resource']}(\${$names['modelVariable']}),
            ], 201);
        } catch (Throwable \$throwable) {
            Log::error('Error creating record.', [
                'controller' => self::class,
                'method' => __METHOD__,
                'error' => \$throwable->getMessage(),
            ]);

            return response()->json([
                config('api-scaffold.envelope.keys.code', 'code') => 500,
                config('api-scaffold.envelope.keys.message', 'message') => config('api-scaffold.envelope.messages.internal_error', 'Internal server error.'),
                config('api-scaffold.envelope.keys.data', 'data') => null,
            ], 500);
        }
    }

    public function update({$names['updateRequest']} \$request, {$names['model']} \${$names['modelVariable']}): JsonResponse
    {
        try {
            \${$names['modelVariable']} = \$this->{$names['modelVariable']}Service->update(
                \${$names['modelVariable']},
                \$request->validated()
            );

            return response()->json([
                config('api-scaffold.envelope.keys.code', 'code') => 200,
                config('api-scaffold.envelope.keys.message', 'message') => config('api-scaffold.envelope.messages.update_success', 'Record updated successfully.'),
                config('api-scaffold.envelope.keys.data', 'data') => new {$names['resource']}(\${$names['modelVariable']}),
            ]);
        } catch (Throwable \$throwable) {
            Log::error('Error updating record.', [
                'controller' => self::class,
                'method' => __METHOD__,
                'error' => \$throwable->getMessage(),
            ]);

            return response()->json([
                config('api-scaffold.envelope.keys.code', 'code') => 500,
                config('api-scaffold.envelope.keys.message', 'message') => config('api-scaffold.envelope.messages.internal_error', 'Internal server error.'),
                config('api-scaffold.envelope.keys.data', 'data') => null,
            ], 500);
        }
    }
{$delete}
PHP;
    }
}
