<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Support;

final class PhpArrayRenderer
{
    /**
     * @param array<int, string> $values
     */
    public function stringList(array $values, int $indent = 8): string
    {
        if ($values === []) {
            return '';
        }

        $spaces = str_repeat(' ', $indent);

        return implode(PHP_EOL, array_map(
            static fn (string $value): string => "{$spaces}'{$value}',",
            $values
        ));
    }

    /**
     * @param array<string, string> $values
     */
    public function associativeList(array $values, int $indent = 8): string
    {
        if ($values === []) {
            return '';
        }

        $spaces = str_repeat(' ', $indent);

        return implode(PHP_EOL, array_map(
            static fn (string $key, string $value): string => "{$spaces}'{$key}' => '{$value}',",
            array_keys($values),
            array_values($values)
        ));
    }
}
