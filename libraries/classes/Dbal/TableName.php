<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Stringable;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/** @psalm-immutable */
final class TableName implements Stringable
{
    /**
     * @see https://dev.mysql.com/doc/refman/en/identifier-length.html
     * @see https://mariadb.com/kb/en/identifier-names/#maximum-length
     */
    private const MAX_LENGTH = 64;

    /** @psalm-var non-empty-string */
    private string $name;

    /** @throws InvalidTableName */
    private function __construct(mixed $name)
    {
        try {
            Assert::stringNotEmpty($name);
        } catch (InvalidArgumentException) {
            throw InvalidTableName::fromEmptyName();
        }

        try {
            Assert::maxLength($name, self::MAX_LENGTH);
        } catch (InvalidArgumentException) {
            throw InvalidTableName::fromLongName(self::MAX_LENGTH);
        }

        try {
            Assert::notEndsWith($name, ' ');
        } catch (InvalidArgumentException) {
            throw InvalidTableName::fromNameWithTrailingSpace();
        }

        $this->name = $name;
    }

    /** @throws InvalidTableName */
    public static function fromValue(mixed $name): self
    {
        return new self($name);
    }

    public static function tryFromValue(mixed $name): self|null
    {
        try {
            return new self($name);
        } catch (InvalidTableName) {
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
