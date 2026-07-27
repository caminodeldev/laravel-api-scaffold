<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Support\FileWriter;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class FileWriterTest extends TestCase
{
    private Filesystem $files;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laravel-api-scaffold-' . bin2hex(random_bytes(8));

        $this->files->makeDirectory($this->temporaryDirectory, 0755, true);
    }

    protected function tearDown(): void
    {
        if ($this->files->isDirectory($this->temporaryDirectory)) {
            $this->files->deleteDirectory($this->temporaryDirectory);
        }

        parent::tearDown();
    }

    public function test_write_creates_parent_directories_and_file(): void
    {
        $writer = new FileWriter($this->files);
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'app/Http/Controllers/UserController.php';

        $result = $writer->write($path, '<?php // generated');

        $this->assertSame($path, $result);
        $this->assertTrue($this->files->exists($path));
        $this->assertSame('<?php // generated', $this->files->get($path));
    }

    public function test_write_dry_run_does_not_create_directories_or_file(): void
    {
        $writer = new FileWriter($this->files);
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'app/Http/Controllers/UserController.php';

        $result = $writer->write($path, '<?php // generated', dryRun: true);

        $this->assertSame("DRY-RUN: {$path}", $result);
        $this->assertFalse($this->files->exists($path));
        $this->assertFalse($this->files->isDirectory(dirname($path)));
    }

    public function test_write_refuses_to_overwrite_existing_file_without_force(): void
    {
        $writer = new FileWriter($this->files);
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'UserController.php';

        $this->files->put($path, 'existing');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Use --force to overwrite it.');

        $writer->write($path, 'new');
    }

    public function test_write_overwrites_existing_file_when_force_is_enabled(): void
    {
        $writer = new FileWriter($this->files);
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'UserController.php';

        $this->files->put($path, 'existing');

        $result = $writer->write($path, 'new', force: true);

        $this->assertSame($path, $result);
        $this->assertSame('new', $this->files->get($path));
    }

    public function test_append_once_creates_file_and_appends_content_once(): void
    {
        $writer = new FileWriter($this->files);
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'routes/api.php';

        $firstAppend = $writer->appendOnce(
            $path,
            '// <laravel-api-scaffold routes>',
            '// <laravel-api-scaffold routes>' . PHP_EOL . 'require __DIR__ . "/scaffolded-api.php";'
        );

        $secondAppend = $writer->appendOnce(
            $path,
            '// <laravel-api-scaffold routes>',
            '// <laravel-api-scaffold routes>' . PHP_EOL . 'require __DIR__ . "/scaffolded-api.php";'
        );

        $this->assertTrue($firstAppend);
        $this->assertFalse($secondAppend);
        $this->assertSame(1, substr_count($this->files->get($path), '// <laravel-api-scaffold routes>'));
    }

    public function test_append_once_dry_run_does_not_create_file(): void
    {
        $writer = new FileWriter($this->files);
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'routes/api.php';

        $result = $writer->appendOnce(
            $path,
            '// <laravel-api-scaffold routes>',
            '// <laravel-api-scaffold routes>' . PHP_EOL . 'require __DIR__ . "/scaffolded-api.php";',
            dryRun: true
        );

        $this->assertTrue($result);
        $this->assertFalse($this->files->exists($path));
    }
}
