<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database;

final readonly class ForeignKeyDefinition
{
    public function __construct(
        public string $name,
        public string $localTable,
        public string $localColumn,
        public string $foreignTable,
        public string $foreignColumn,
        public bool $nullable = false,
    ) {
    }
}
