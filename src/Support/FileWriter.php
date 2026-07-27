<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final readonly class FileWriter
{
    public function __construct(
        private Filesystem $files
    ) {
    }

    public function write(string $path, string $contents, bool $force = false, bool $dryRun = false): string
    {
        if ($dryRun) {
            return "DRY-RUN: {$path}";
        }

        if ($this->files->exists($path) && ! $force) {
            throw new RuntimeException("File already exists: {$path}. Use --force to overwrite it.");
        }

        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $contents);

        return $path;
    }

    public function appendOnce(string $path, string $needle, string $contents, bool $dryRun = false): bool
    {
        if ($dryRun) {
            return true;
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, "<?php\n\n");
        }

        $current = $this->files->get($path);

        if (str_contains($current, $needle)) {
            return false;
        }

        $this->files->append($path, PHP_EOL . $contents . PHP_EOL);

        return true;
    }
}
