<?php
/**
 * Value object class for a character set
 */

declare(strict_types=1);

namespace PhpMyAdmin\Charsets;

/**
 * Value object class for a character set
 */
final class Charset
{
    /**
     * @param string $name             The character set name
     * @param string $description      A description of the character set
     * @param string $defaultCollation The default collation for the character set
     * @param int    $maxLength        The maximum number of bytes required to store one character
     */
    private function __construct(
        private string $name,
        private string $description,
        private string $defaultCollation,
        private int $maxLength,
    ) {
    }

    /**
     * @param string[] $state State obtained from the database server
     * @psalm-param array{Charset?:string, Description?:string, 'Default collation'?:string, Maxlen?:string} $state
     */
    public static function fromServer(array $state): self
    {
        return new self(
            $state['Charset'] ?? '',
            $state['Description'] ?? '',
            $state['Default collation'] ?? '',
            (int) ($state['Maxlen'] ?? 0),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefaultCollation(): string
    {
        return $this->defaultCollation;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }
}
