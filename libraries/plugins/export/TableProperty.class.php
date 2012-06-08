<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TableProperty class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CodeGen
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

class TableProperty
{
    public $name;
    public $type;
    public $nullable;
    public $key;
    public $defaultValue;
    public $ext;

    function __construct($row)
    {
        $this->name = trim($row[0]);
        $this->type = trim($row[1]);
        $this->nullable = trim($row[2]);
        $this->key = trim($row[3]);
        $this->defaultValue = trim($row[4]);
        $this->ext = trim($row[5]);
    }

    function getPureType()
    {
        $pos=strpos($this->type, "(");
        if ($pos > 0) {
            return substr($this->type, 0, $pos);
        }
        return $this->type;
    }

    function isNotNull()
    {
        return $this->nullable == "NO" ? "true" : "false";
    }

    function isUnique()
    {
        return $this->key == "PRI" || $this->key == "UNI" ? "true" : "false";
    }

    function getDotNetPrimitiveType()
    {
        if (strpos($this->type, "int") === 0) {
            return "int";
        }
        if (strpos($this->type, "long") === 0) {
            return "long";
        }
        if (strpos($this->type, "char") === 0) {
            return "string";
        }
        if (strpos($this->type, "varchar") === 0) {
            return "string";
        }
        if (strpos($this->type, "text") === 0) {
            return "string";
        }
        if (strpos($this->type, "longtext") === 0) {
            return "string";
        }
        if (strpos($this->type, "tinyint") === 0) {
            return "bool";
        }
        if (strpos($this->type, "datetime") === 0) {
            return "DateTime";
        }
        return "unknown";
    }

    function getDotNetObjectType()
    {
        if (strpos($this->type, "int") === 0) {
            return "Int32";
        }
        if (strpos($this->type, "long") === 0) {
            return "Long";
        }
        if (strpos($this->type, "char") === 0) {
            return "String";
        }
        if (strpos($this->type, "varchar") === 0) {
            return "String";
        }
        if (strpos($this->type, "text") === 0) {
            return "String";
        }
        if (strpos($this->type, "longtext") === 0) {
            return "String";
        }
        if (strpos($this->type, "tinyint") === 0) {
            return "Boolean";
        }
        if (strpos($this->type, "datetime") === 0) {
            return "DateTime";
        }
        return "Unknown";
    }

    function getIndexName()
    {
        if (strlen($this->key) > 0) {
            return "index=\""
                . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8')
                . "\"";
        }
        return "";
    }

    function isPK()
    {
        return $this->key=="PRI";
    }

    function formatCs($text)
    {
        $text = str_replace(
            "#name#",
            ExportCodegen::cgMakeIdentifier($this->name, false),
            $text
        );
        return $this->format($text);
    }

    function formatXml($text)
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

    function format($text)
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
?>