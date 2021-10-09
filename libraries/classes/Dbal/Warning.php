<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use InvalidArgumentException;
use Stringable;
use Webmozart\Assert\Assert;

/**
 * @see https://mariadb.com/kb/en/show-warnings/
 * @see https://dev.mysql.com/doc/refman/en/show-warnings.html
 *
 * @psalm-immutable
 */
final class Warning implements Stringable
{
    /** @var string */
    public $level;

    /** @var int */
    public $code;

    /** @var string */
    public $message;

    private function __construct(string $level, int $code, string $message)
    {
        $this->level = $level;
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @param mixed[] $row
     *
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $row): self
    {
        Assert::keyExists($row, 'Level');
        Assert::keyExists($row, 'Code');
        Assert::keyExists($row, 'Message');
        Assert::stringNotEmpty($row['Level']);
        Assert::numeric($row['Code']);
        Assert::string($row['Message']);

        return new self($row['Level'], (int) $row['Code'], $row['Message']);
    }

    public function __toString(): string
    {
        return $this->level . ': #' . $this->code . ' ' . $this->message;
    }
}
