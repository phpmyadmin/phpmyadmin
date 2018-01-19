<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\plugins\export\TableProperty class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CodeGen
 */
namespace PMA\libraries\plugins\export;

/**
 * PMA\libraries\plugins\export\TableProperty class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CodeGen
 */
class TableProperty
{
    /**
     * Name
     *
     * @var string
     */
    public $name;

    /**
     * Type
     *
     * @var string
     */
    public $type;

    /**
     * Whether the key is nullable or not
     *
     * @var bool
     */
    public $nullable;

    /**
     * The key
     *
     * @var int
     */
    public $key;

    /**
     * Default value
     *
     * @var mixed
     */
    public $defaultValue;

    /**
     * Extension
     *
     * @var string
     */
    public $ext;

    /**
     * Constructor
     *
     * @param array $row table row
     */
    public function __construct($row)
    {
        $this->name = trim($row[0]);
        $this->type = trim($row[1]);
        $this->nullable = trim($row[2]);
        $this->key = trim($row[3]);
        $this->defaultValue = trim($row[4]);
        $this->ext = trim($row[5]);
    }

    /**
     * Gets the pure type
     *
     * @return string type
     */
    public function getPureType()
    {
        $pos = mb_strpos($this->type, "(");
        if ($pos > 0) {
            return mb_substr($this->type, 0, $pos);
        }
        return $this->type;
    }

    /**
     * Tells whether the key is null or not
     *
     * @return bool true if the key is not null, false otherwise
     */
    public function isNotNull()
    {
        return $this->nullable == "NO" ? "true" : "false";
    }

    /**
     * Tells whether the key is unique or not
     *
     * @return bool true if the key is unique, false otherwise
     */
    public function isUnique()
    {
        return $this->key == "PRI" || $this->key == "UNI" ? "true" : "false";
    }

     /**
     * Gets the .NET primitive type
     *
     * @return string type
     */
    public function getDotNetPrimitiveType()
    {
        if (mb_strpos($this->type, "int") === 0) {
            return "int";
        }
        if (mb_strpos($this->type, "longtext") === 0) {
            return "string";
        }
        if (mb_strpos($this->type, "long") === 0) {
            return "long";
        }
        if (mb_strpos($this->type, "char") === 0) {
            return "string";
        }
        if (mb_strpos($this->type, "varchar") === 0) {
            return "string";
        }
        if (mb_strpos($this->type, "text") === 0) {
            return "string";
        }
        if (mb_strpos($this->type, "tinyint") === 0) {
            return "bool";
        }
        if (mb_strpos($this->type, "datetime") === 0) {
            return "DateTime";
        }
        return "unknown";
    }

    /**
     * Gets the .NET object type
     *
     * @return string type
     */
    public function getDotNetObjectType()
    {
        if (mb_strpos($this->type, "int") === 0) {
            return "Int32";
        }
        if (mb_strpos($this->type, "longtext") === 0) {
            return "String";
        }
        if (mb_strpos($this->type, "long") === 0) {
            return "Long";
        }
        if (mb_strpos($this->type, "char") === 0) {
            return "String";
        }
        if (mb_strpos($this->type, "varchar") === 0) {
            return "String";
        }
        if (mb_strpos($this->type, "text") === 0) {
            return "String";
        }
        if (mb_strpos($this->type, "tinyint") === 0) {
            return "Boolean";
        }
        if (mb_strpos($this->type, "datetime") === 0) {
            return "DateTime";
        }
        return "Unknown";
    }

    /**
     * Gets the index name
     *
     * @return string containing the name of the index
     */
    public function getIndexName()
    {
        if (strlen($this->key) > 0) {
            return "index=\""
                . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8')
                . "\"";
        }
        return "";
    }

    /**
     * Tells whether the key is primary or not
     *
     * @return bool true if the key is primary, false otherwise
     */
    public function isPK()
    {
        return $this->key == "PRI";
    }

    /**
     * Formats a string for C#
     *
     * @param string $text string to be formatted
     *
     * @return string formatted text
     */
    public function formatCs($text)
    {
        $text = str_replace(
            "#name#",
            ExportCodegen::cgMakeIdentifier($this->name, false),
            $text
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
    public function formatXml($text)
    {
        $text = str_replace(
            "#name#",
            htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8'),
            $text
        );
        $text = str_replace(
            "#indexName#",
            $this->getIndexName(),
            $text
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
    public function format($text)
    {
        $text = str_replace(
            "#ucfirstName#",
            ExportCodegen::cgMakeIdentifier($this->name),
            $text
        );
        $text = str_replace(
            "#dotNetPrimitiveType#",
            $this->getDotNetPrimitiveType(),
            $text
        );
        $text = str_replace(
            "#dotNetObjectType#",
            $this->getDotNetObjectType(),
            $text
        );
        $text = str_replace(
            "#type#",
            $this->getPureType(),
            $text
        );
        $text = str_replace(
            "#notNull#",
            $this->isNotNull(),
            $text
        );
        $text = str_replace(
            "#unique#",
            $this->isUnique(),
            $text
        );
        return $text;
    }
}
