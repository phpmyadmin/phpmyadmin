<?php

declare(strict_types=1);

namespace PhpMyAdmin;

/** @psalm-immutable */
final class EditField
{
    public function __construct(
        public string $columnName,
        public string $value,
        public string $type,
        public bool $autoIncrement,
        public bool $isNull,
        public bool $wasPreviouslyNull,
        public string $function,
        public string|null $salt = null,
        public string|null $previousValue = null,
        public bool $isUploaded,
    ) {
    }
}
