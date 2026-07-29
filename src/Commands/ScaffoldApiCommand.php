<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Commands;

use CaminoDelDev\LaravelApiScaffold\Database\TableInspectorFactory;
use CaminoDelDev\LaravelApiScaffold\Generators\ControllerGenerator;
use CaminoDelDev\LaravelApiScaffold\Generators\ModelGenerator;
use CaminoDelDev\LaravelApiScaffold\Generators\RequestGenerator;
use CaminoDelDev\LaravelApiScaffold\Generators\ResourceGenerator;
use CaminoDelDev\LaravelApiScaffold\Generators\RouteGenerator;
use CaminoDelDev\LaravelApiScaffold\Generators\ServiceGenerator;
use CaminoDelDev\LaravelApiScaffold\Generators\TestGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\FileWriter;
use Illuminate\Console\Command;

class ScaffoldApiCommand extends Command
{
    private const ROUTE_IMPORT_START_MARKER = '// <laravel-api-scaffold routes>';

    private const ROUTE_IMPORT_END_MARKER = '// </laravel-api-scaffold routes>';

    protected $signature = 'scaffold:api
                            {table : Database table name}
                            {--connection= : Database connection name}
                            {--model= : Explicit model class name}
                            {--route-resource= : Custom API resource URI. Defaults to the table name}
                            {--read-only : Generate only index and show endpoints}
                            {--crud : Generate index, show, store and update endpoints}
                            {--with-delete : Also generate destroy endpoint. Requires --crud}
                            {--dry-run : Show what would be generated without writing files}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a clean, secure and configurable Laravel API scaffold from a database table.';

    public function handle(
        TableInspectorFactory $inspectorFactory,
        NameResolver $nameResolver,
        ModelGenerator $modelGenerator,
        ResourceGenerator $resourceGenerator,
        RequestGenerator $requestGenerator,
        ServiceGenerator $serviceGenerator,
        ControllerGenerator $controllerGenerator,
        RouteGenerator $routeGenerator,
        TestGenerator $testGenerator,
        FileWriter $fileWriter,
    ): int {
        $table = (string) $this->argument('table');
        $connection = $this->option('connection') ? (string) $this->option('connection') : null;
        $model = $this->option('model') ? (string) $this->option('model') : null;
        $routeResource = $this->option('route-resource') ? trim((string) $this->option('route-resource')) : null;
        $crud = (bool) $this->option('crud');
        $withDelete = (bool) $this->option('with-delete');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($routeResource === '') {
            $this->error('--route-resource cannot be empty.');

            return self::FAILURE;
        }

        if ($withDelete && ! $crud) {
            $this->error('--with-delete requires --crud.');

            return self::FAILURE;
        }

        if (! $crud) {
            $this->warn('Safe default enabled: generating read-only API endpoints only.');
        }

        if ($crud) {
            $this->warn('Write endpoints are being generated. Review authorization and business rules before production use.');
        }

        if ($withDelete) {
            $this->warn('Delete endpoint is being generated. Ensure authorization, auditing and rollback strategy are defined.');
        }

        $definition = $inspectorFactory->inspect($table, $connection);
        $names = $nameResolver->resolve($table, $model, $routeResource);

        $files = [];

        if (config('api-scaffold.generation.generate_model', true)) {
            $files[$this->path('models', "{$names['model']}.php")] = $modelGenerator->generate($definition, $names);
        }

        if (config('api-scaffold.generation.generate_resource', true)) {
            $files[$this->path('resources', "{$names['resource']}.php")] = $resourceGenerator->generate($definition, $names);
        }

        if (config('api-scaffold.generation.generate_form_requests', true)) {
            $requestBase = $this->path('requests', $names['model']);
            $files[$requestBase . DIRECTORY_SEPARATOR . "{$names['indexRequest']}.php"] = $requestGenerator->generateIndex($definition, $names);

            if ($crud) {
                $files[$requestBase . DIRECTORY_SEPARATOR . "{$names['storeRequest']}.php"] = $requestGenerator->generateStore($definition, $names);
                $files[$requestBase . DIRECTORY_SEPARATOR . "{$names['updateRequest']}.php"] = $requestGenerator->generateUpdate($definition, $names);
            }
        }

        if (config('api-scaffold.generation.generate_service', true)) {
            $files[$this->path('services', "{$names['service']}.php")] = $serviceGenerator->generate($definition, $names, $crud, $withDelete);
        }

        $files[$this->path('controllers', "{$names['controller']}.php")] = $controllerGenerator->generate($names, $crud, $withDelete);

        if (config('api-scaffold.routes.enabled', true)) {
            $routeFile = (string) config('api-scaffold.routes.file');
            $files[$routeFile] = $this->mergeRouteFile(
                $routeFile,
                $routeGenerator->generate($names, $crud, $withDelete),
                $names['route']
            );
        }

        if (config('api-scaffold.generation.generate_tests', true)) {
            $files[$this->path('tests', "{$names['test']}.php")] = $testGenerator->generate($names);
        }

        foreach ($files as $path => $contents) {
            $fileWriter->write($path, $contents, $force, $dryRun);
            $this->info(($dryRun ? 'Would generate' : 'Generated') . ": {$path}");
        }

        if (config('api-scaffold.routes.import_from_api_php', true)) {
            $this->ensureApiRoutesImport($fileWriter, $dryRun);
        }

        $this->newLine();
        $this->info('API scaffold completed. Review generated authorization, validation and exposed fields before production use.');

        return self::SUCCESS;
    }

    private function path(string $key, string $file): string
    {
        return rtrim((string) config("api-scaffold.paths.{$key}"), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $file;
    }

    private function mergeRouteFile(string $routeFile, string $newRoute, string $routeResource): string
    {
        if (! is_file($routeFile)) {
            return "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n" . rtrim($newRoute) . PHP_EOL;
        }

        $current = file_get_contents($routeFile);

        if ($current === false) {
            return "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n" . rtrim($newRoute) . PHP_EOL;
        }

        $startMarker = $this->managedRouteStartMarker($routeResource);
        $endMarker = $this->managedRouteEndMarker($routeResource);
        $managedBlockPattern = sprintf(
            '/%s.*?%s/s',
            preg_quote($startMarker, '/'),
            preg_quote($endMarker, '/')
        );

        if (preg_match($managedBlockPattern, $current) === 1) {
            return preg_replace($managedBlockPattern, rtrim($newRoute), $current) ?? $current;
        }

        return rtrim($current) . PHP_EOL . PHP_EOL . rtrim($newRoute) . PHP_EOL;
    }

    private function managedRouteStartMarker(string $routeResource): string
    {
        return sprintf('// <laravel-api-scaffold resource="%s">', $routeResource);
    }

    private function managedRouteEndMarker(string $routeResource): string
    {
        return sprintf('// </laravel-api-scaffold resource="%s">', $routeResource);
    }

    private function ensureApiRoutesImport(FileWriter $fileWriter, bool $dryRun): void
    {
        $apiRoutePath = base_path('routes/api.php');
        $routeFile = (string) config('api-scaffold.routes.file', base_path('routes/scaffolded-api.php'));
        $relativeRoutePath = $this->relativeRoutePath($apiRoutePath, $routeFile);
        $escapedRelativeRoutePath = str_replace("'", "\\'", $relativeRoutePath);

        $import = implode(PHP_EOL, [
            self::ROUTE_IMPORT_START_MARKER,
            "if (file_exists(__DIR__ . '/{$escapedRelativeRoutePath}')) {",
            "    require __DIR__ . '/{$escapedRelativeRoutePath}';",
            '}',
            self::ROUTE_IMPORT_END_MARKER,
        ]);

        $fileWriter->appendOnce(
            $apiRoutePath,
            [
                self::ROUTE_IMPORT_START_MARKER,
                "require __DIR__ . '/{$escapedRelativeRoutePath}';",
            ],
            $import,
            $dryRun
        );

        $this->info(($dryRun ? 'Would ensure import in' : 'Ensured import in') . ": {$apiRoutePath}");
    }

    private function relativeRoutePath(string $apiRoutePath, string $routeFile): string
    {
        $apiRoutesDirectory = dirname($apiRoutePath);

        return $this->makeRelativePath($apiRoutesDirectory, $routeFile);
    }

    private function makeRelativePath(string $fromDirectory, string $toPath): string
    {
        $fromParts = $this->pathParts($fromDirectory);
        $toParts = $this->pathParts($toPath);

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativeParts = array_merge(
            array_fill(0, count($fromParts), '..'),
            $toParts
        );

        return implode('/', $relativeParts);
    }

    /**
     * @return array<int, string>
     */
    private function pathParts(string $path): array
    {
        $normalizedPath = str_replace('\\', '/', $path);

        return array_values(array_filter(
            explode('/', trim($normalizedPath, '/')),
            static fn (string $part): bool => $part !== ''
        ));
    }
}
