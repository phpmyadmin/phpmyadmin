<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Stringable;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * @psalm-immutable
 */
final class DatabaseName implements Stringable
{
    /**
     * @see https://dev.mysql.com/doc/refman/en/identifier-length.html
     * @see https://mariadb.com/kb/en/identifier-names/#maximum-length
     */
    private const MAX_LENGTH = 64;

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $name;

    /**
     * @param mixed $name
     *
     * @throws InvalidDatabaseName
     */
    private function __construct($name)
    {
        try {
            Assert::stringNotEmpty($name);
        } catch (InvalidArgumentException $exception) {
            throw InvalidDatabaseName::fromEmptyName();
        }

        try {
            Assert::maxLength($name, self::MAX_LENGTH);
        } catch (InvalidArgumentException $exception) {
            throw InvalidDatabaseName::fromLongName(self::MAX_LENGTH);
        }

        try {
            Assert::notEndsWith($name, ' ');
        } catch (InvalidArgumentException $exception) {
            throw InvalidDatabaseName::fromNameWithTrailingSpace();
        }

        $this->name = $name;
    }

    /**
     * @param mixed $name
     *
     * @throws InvalidDatabaseName
     */
    public static function fromValue($name): self
    {
        return new self($name);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
