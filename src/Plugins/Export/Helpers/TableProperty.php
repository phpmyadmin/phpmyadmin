<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export\Helpers;

use PhpMyAdmin\Plugins\Export\ExportCodegen;

use function htmlspecialchars;
use function mb_strpos;
use function mb_substr;
use function str_replace;
use function str_starts_with;
use function trim;

use const ENT_COMPAT;

/**
 * PhpMyAdmin\Plugins\Export\Helpers\TableProperty class
 */
class TableProperty
{
    /**
     * Name
     */
    public string $name;

    /**
     * Type
     */
    public string $type;

    /**
     * Whether the key is nullable or not
     */
    public string $nullable;

    /**
     * The key
     */
    public string $key;

    /**
     * Default value
     */
    public mixed $defaultValue;

    /**
     * Extension
     */
    public string $ext;

    /** @param mixed[] $row table row */
    public function __construct(array $row)
    {
        $this->name = trim((string) $row[0]);
        $this->type = trim((string) $row[1]);
        $this->nullable = trim((string) $row[2]);
        $this->key = trim((string) $row[3]);
        $this->defaultValue = trim((string) $row[4]);
        $this->ext = trim((string) $row[5]);
    }

    /**
     * Gets the pure type
     *
     * @return string type
     */
    public function getPureType(): string
    {
        $pos = (int) mb_strpos($this->type, '(');
        if ($pos > 0) {
            return mb_substr($this->type, 0, $pos);
        }

        return $this->type;
    }

    /**
     * Tells whether the key is null or not
     *
     * @return string true if the key is not null, false otherwise
     */
    public function isNotNull(): string
    {
        return $this->nullable === 'NO' ? 'true' : 'false';
    }

    /**
     * Tells whether the key is unique or not
     *
     * @return string "true" if the key is unique, "false" otherwise
     */
    public function isUnique(): string
    {
        return $this->key === 'PRI' || $this->key === 'UNI' ? 'true' : 'false';
    }

    /**
     * Gets the .NET primitive type
     *
     * @return string type
     */
    public function getDotNetPrimitiveType(): string
    {
        if (str_starts_with($this->type, 'int')) {
            return 'int';
        }

        if (str_starts_with($this->type, 'longtext')) {
            return 'string';
        }

        if (str_starts_with($this->type, 'long')) {
            return 'long';
        }

        if (str_starts_with($this->type, 'char')) {
            return 'string';
        }

        if (str_starts_with($this->type, 'varchar')) {
            return 'string';
        }

        if (str_starts_with($this->type, 'text')) {
            return 'string';
        }

        if (str_starts_with($this->type, 'tinyint')) {
            return 'bool';
        }

        if (str_starts_with($this->type, 'datetime')) {
            return 'DateTime';
        }

        return 'unknown';
    }

    /**
     * Gets the .NET object type
     *
     * @return string type
     */
    public function getDotNetObjectType(): string
    {
        if (str_starts_with($this->type, 'int')) {
            return 'Int32';
        }

        if (str_starts_with($this->type, 'longtext')) {
            return 'String';
        }

        if (str_starts_with($this->type, 'long')) {
            return 'Long';
        }

        if (str_starts_with($this->type, 'char')) {
            return 'String';
        }

        if (str_starts_with($this->type, 'varchar')) {
            return 'String';
        }

        if (str_starts_with($this->type, 'text')) {
            return 'String';
        }

        if (str_starts_with($this->type, 'tinyint')) {
            return 'Boolean';
        }

        if (str_starts_with($this->type, 'datetime')) {
            return 'DateTime';
        }

        return 'Unknown';
    }

    /**
     * Gets the index name
     *
     * @return string containing the name of the index
     */
    public function getIndexName(): string
    {
        if ($this->key !== '') {
            return 'index="'
                . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8')
                . '"';
        }

        return '';
    }

    /**
     * Tells whether the key is primary or not
     */
    public function isPK(): bool
    {
        return $this->key === 'PRI';
    }

    /**
     * Formats a string for C#
     *
     * @param string $text string to be formatted
     *
     * @return string formatted text
     */
    public function formatCs(string $text): string
    {
        $text = str_replace(
            '#name#',
            ExportCodegen::cgMakeIdentifier($this->name, false),
            $text,
        );

        return $this->format($text);
    }

    /**
     * Formats a string for XML
     *
     * @param string $text string to be formatted
     *
     * @return string formatted text
     */
    public function formatXml(string $text): string
    {
        $text = str_replace(
            ['#name#', '#indexName#'],
            [htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8'), $this->getIndexName()],
            $text,
        );

        return $this->format($text);
    }

    /**
     * Formats a string
     *
     * @param string $text string to be formatted
     *
     * @return string formatted text
     */
    public function format(string $text): string
    {
        return str_replace(
            ['#ucfirstName#', '#dotNetPrimitiveType#', '#dotNetObjectType#', '#type#', '#notNull#', '#unique#'],
            [
                ExportCodegen::cgMakeIdentifier($this->name),
                $this->getDotNetPrimitiveType(),
                $this->getDotNetObjectType(),
                $this->getPureType(),
                $this->isNotNull(),
                $this->isUnique(),
            ],
            $text,
        );
    }
}
