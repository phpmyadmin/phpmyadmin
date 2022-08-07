<?php

declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * @psalm-immutable
 */
final class EditField
{
    /** @var string $columnName */
    public $columnName;
    /** @var string $value */
    public $value;
    /** @var string $type */
    public $type;
    /** @var bool $autoIncrement */
    public $autoIncrement;
    /** @var bool $isNull */
    public $isNull;
    /** @var bool $wasPreviouslyNull */
    public $wasPreviouslyNull;
    /** @var string $function */
    public $function;
    /** @var string|null $salt */
    public $salt;
    /** @var string|null $previousValue */
    public $previousValue;
    /** @var bool $isUploaded */
    public $isUploaded;

    public function __construct(
        string $columnName,
        string $value,
        string $type,
        bool $autoIncrement,
        bool $isNull,
        bool $wasPreviouslyNull,
        string $function,
        ?string $salt,
        ?string $previousValue,
        bool $isUploaded
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
