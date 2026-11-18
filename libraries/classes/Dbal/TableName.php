<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Stringable;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;
use function sprintf;

/**
 * @psalm-immutable
 */
final class TableName implements Stringable
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
     * @throws InvalidArgumentException
     */
    private function __construct($name)
    {
        try {
            Assert::stringNotEmpty($name);
        } catch (InvalidArgumentException $exception) {
            $message = __('The table name must be a non-empty string.');

            throw new InvalidArgumentException($message, $exception->getCode(), $exception);
        }

        try {
            Assert::maxLength($name, self::MAX_LENGTH);
        } catch (InvalidArgumentException $exception) {
            $message = sprintf(__('The table name cannot be longer than %d characters.'), self::MAX_LENGTH);

            throw new InvalidArgumentException($message, $exception->getCode(), $exception);
        }

        try {
            Assert::notEndsWith($name, ' ');
        } catch (InvalidArgumentException $exception) {
            $message = __('The table name cannot end with a space character.');

            throw new InvalidArgumentException($message, $exception->getCode(), $exception);
        }

        $this->name = $name;
    }

    /**
     * @param mixed $name
     *
     * @throws InvalidArgumentException
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
