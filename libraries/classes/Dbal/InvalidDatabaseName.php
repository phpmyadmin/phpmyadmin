<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use function __;
use function sprintf;

class InvalidDatabaseName extends InvalidIdentifierName
{
    public static function fromEmptyName(): self
    {
        return new self(__('The database name must be a non-empty string.'));
    }

    /**
     * @psalm-param positive-int $length
     */
    public static function fromLongName(int $length): self
    {
        return new self(sprintf(__('The database name cannot be longer than %d characters.'), $length));
    }

    public static function fromNameWithTrailingSpace(): self
    {
        return new self(__('The database name cannot end with a space character.'));
    }
}
