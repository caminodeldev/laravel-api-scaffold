<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database;

interface TableInspector
{
    public function inspect(string $table, ?string $connection = null): TableDefinition;
}
