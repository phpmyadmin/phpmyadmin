<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

use PhpMyAdmin\Dbal\InvalidIdentifierName;

use function __;
use function sprintf;

final class InvalidTriggerName extends InvalidIdentifierName
{
    public static function fromEmptyName(): self
    {
        return new self(__('The trigger name must not be empty.'));
    }

    /** @psalm-param positive-int $length */
    public static function fromLongName(int $length): self
    {
        return new self(sprintf(__('The trigger name cannot be longer than %d characters.'), $length));
    }

    public static function fromNameWithTrailingSpace(): self
    {
        return new self(__('The trigger name cannot end with a space character.'));
    }
}
