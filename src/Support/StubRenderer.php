<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Support;

use RuntimeException;

final class StubRenderer
{
    /**
     * @param array<string, string> $replacements
     */
    public function render(string $stubPath, array $replacements): string
    {
        if (! is_file($stubPath)) {
            throw new RuntimeException("Stub not found: {$stubPath}");
        }

        $contents = file_get_contents($stubPath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read stub: {$stubPath}");
        }

        foreach ($replacements as $key => $value) {
            $contents = str_replace('{{ ' . $key . ' }}', $value, $contents);
        }

        return $contents;
    }
}
