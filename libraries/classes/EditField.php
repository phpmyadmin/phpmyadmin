<?php

declare(strict_types=1);

namespace PhpMyAdmin;

/** @psalm-immutable */
final class EditField
{
    public string $columnName;
    public string $value;
    public string $type;
    public bool $autoIncrement;
    public bool $isNull;
    public bool $wasPreviouslyNull;
    public string $function;
    public string|null $salt = null;
    public string|null $previousValue = null;
    public bool $isUploaded;

    public function __construct(
        string $columnName,
        string $value,
        string $type,
        bool $autoIncrement,
        bool $isNull,
        bool $wasPreviouslyNull,
        string $function,
        string|null $salt,
        string|null $previousValue,
        bool $isUploaded,
    ) {
        $this->columnName = $columnName;
        $this->value = $value;
        $this->type = $type;
        $this->autoIncrement = $autoIncrement;
        $this->isNull = $isNull;
        $this->wasPreviouslyNull = $wasPreviouslyNull;
        $this->function = $function;
        $this->salt = $salt;
        $this->previousValue = $previousValue;
        $this->isUploaded = $isUploaded;
    }
}
