<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Database;

final readonly class ColumnDefinition
{
    public function __construct(
        public string $name,
        public string $type,
        public ?int $length = null,
        public bool $nullable = false,
        public bool $primary = false,
        public bool $autoIncrement = false,
        public bool $unique = false,
        public mixed $default = null,
    ) {
    }

    public function isTimestampColumn(): bool
    {
        return in_array($this->name, ['created_at', 'updated_at', 'deleted_at'], true);
    }

    public function isPrimaryKey(): bool
    {
        return $this->primary;
    }

    public function isWritable(): bool
    {
        return ! $this->primary
            && ! $this->autoIncrement
            && ! $this->isTimestampColumn();
    }
}
