<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldApiCommand;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;
use ReflectionMethod;

class ScaffoldApiCommandTest extends TestCase
{
    public function test_it_replaces_existing_managed_route_block_for_same_resource(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'scaffolded-api-');

        $this->assertIsString($temporaryFile);

        file_put_contents($temporaryFile, <<<PHP
<?php

use Illuminate\Support\Facades\Route;

// manual route must stay
Route::get('health', fn () => 'ok');

// <laravel-api-scaffold resource="solicitudes">
Route::prefix('v1')->group(function (): void {
    Route::apiResource('solicitudes', \App\Http\Controllers\frontend\v1\SolicitudController::class)
        ->only(['index', 'show']);
});
// </laravel-api-scaffold resource="solicitudes">
PHP);

        $newRoute = <<<PHP
// <laravel-api-scaffold resource="solicitudes">
Route::prefix('v1')->group(function (): void {
    Route::apiResource('solicitudes', \App\Http\Controllers\frontend\v1\SolicitudController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);
});
// </laravel-api-scaffold resource="solicitudes">
PHP;

        try {
            $merged = $this->invokeMergeRouteFile($temporaryFile, $newRoute, 'solicitudes');

            $this->assertStringContainsString("Route::get('health'", $merged);
            $this->assertStringContainsString("->only(['index', 'show', 'store', 'update', 'destroy'])", $merged);
            $this->assertStringNotContainsString("->only(['index', 'show']);", $merged);
            $this->assertSame(1, substr_count($merged, '// <laravel-api-scaffold resource="solicitudes">'));
        } finally {
            @unlink($temporaryFile);
        }
    }

    private function invokeMergeRouteFile(string $routeFile, string $newRoute, string $routeResource): string
    {
        $method = new ReflectionMethod(ScaffoldApiCommand::class, 'mergeRouteFile');
        $method->setAccessible(true);

        return (string) $method->invoke(new ScaffoldApiCommand(), $routeFile, $newRoute, $routeResource);
    }
}
