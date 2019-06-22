<?php
/**
 * Value object class for a collation
 * @package PhpMyAdmin\Charsets
 */
declare(strict_types=1);

namespace PhpMyAdmin\Charsets;

use PhpMyAdmin\Charsets;

/**
 * Value object class for a collation
 * @package PhpMyAdmin\Charsets
 */
final class Collation
{
    /**
     * The collation name
     * @var string
     */
    private $name;

    /**
     * A description of the collation
     * @var string
     */
    private $description;

    /**
     * The name of the character set with which the collation is associated
     * @var string
     */
    private $charset;

    /**
     * The collation ID
     * @var int
     */
    private $id;

    /**
     * Whether the collation is the default for its character set
     * @var bool
     */
    private $isDefault;

    /**
     * Whether the character set is compiled into the server
     * @var bool
     */
    private $isCompiled;

    /**
     * Used for determining the memory used to sort strings in this collation
     * @var int
     */
    private $sortLength;

    /**
     * The collation pad attribute
     * @var string
     */
    private $padAttribute;

    /**
     * @param string $name         Collation name
     * @param string $charset      Related charset
     * @param int    $id           Collation ID
     * @param bool   $isDefault    Whether is the default
     * @param bool   $isCompiled   Whether the charset is compiled
     * @param int    $sortLength   Sort length
     * @param string $padAttribute Pad attribute
     */
    private function __construct(
        string $name,
        string $charset,
        int $id,
        bool $isDefault,
        bool $isCompiled,
        int $sortLength,
        string $padAttribute
    ) {
        $this->name = $name;
        $this->charset = $charset;
        $this->id = $id;
        $this->isDefault = $isDefault;
        $this->isCompiled = $isCompiled;
        $this->sortLength = $sortLength;
        $this->padAttribute = $padAttribute;
        $this->description = Charsets::getCollationDescr($this->name);
    }

    /**
     * @param array $state State obtained from the database server
     * @return self
     */
    public static function fromServer(array $state): self
    {
        return new self(
            $state['Collation'] ?? '',
            $state['Charset'] ?? '',
            (int) $state['Id'] ?? 0,
            isset($state['Default']) && ($state['Default'] === 'Yes' || $state['Default'] === '1'),
            isset($state['Compiled']) && ($state['Compiled'] === 'Yes' || $state['Compiled'] === '1'),
            (int) $state['Sortlen'] ?? 0,
            $state['Pad_attribute'] ?? ''
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * @return bool
     */
    public function isCompiled(): bool
    {
        return $this->isCompiled;
    }

    /**
     * @return int
     */
    public function getSortLength(): int
    {
        return $this->sortLength;
    }

    /**
     * @return string
     */
    public function getPadAttribute(): string
    {
        return $this->padAttribute;
    }
}
