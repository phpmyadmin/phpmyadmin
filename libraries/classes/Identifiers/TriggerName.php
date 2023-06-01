<?php

declare(strict_types=1);

namespace PhpMyAdmin\Identifiers;

use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/** @psalm-immutable */
final class TriggerName implements Identifier
{
    /**
     * @see https://dev.mysql.com/doc/refman/en/identifier-length.html
     * @see https://mariadb.com/kb/en/identifier-names/#maximum-length
     */
    private const MAX_LENGTH = 64;

    /** @psalm-var non-empty-string */
    private string $name;

    /** @throws InvalidTriggerName */
    private function __construct(mixed $name)
    {
        try {
            Assert::stringNotEmpty($name);
        } catch (InvalidArgumentException) {
            throw InvalidTriggerName::fromEmptyName();
        }

        try {
            Assert::maxLength($name, self::MAX_LENGTH);
        } catch (InvalidArgumentException) {
            throw InvalidTriggerName::fromLongName(self::MAX_LENGTH);
        }

        try {
            Assert::notEndsWith($name, ' ');
        } catch (InvalidArgumentException) {
            throw InvalidTriggerName::fromNameWithTrailingSpace();
        }

        $this->name = $name;
    }

    /**
     * @throws InvalidTriggerName
     *
     * @psalm-assert non-empty-string $name
     */
    public static function from(mixed $name): static
    {
        return new self($name);
    }

    public static function tryFrom(mixed $name): static|null
    {
        try {
            return new self($name);
        } catch (InvalidTriggerName) {
            return null;
        }
    }

    /** @psalm-return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @psalm-return non-empty-string */
    public function __toString(): string
    {
        return $this->name;
    }
}
