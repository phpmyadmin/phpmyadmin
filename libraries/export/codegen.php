<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build NHibernate dumps of tables
 *
 * @package PhpMyAdmin-Export
 * @subpackage Codegen
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This gets executed twice so avoid a notice
 */
if (! defined('CG_FORMAT_NHIBERNATE_CS')) {
    define("CG_FORMAT_NHIBERNATE_CS", "NHibernate C# DO");
    define("CG_FORMAT_NHIBERNATE_XML", "NHibernate XML");

    define("CG_HANDLER_NHIBERNATE_CS_BODY", "handleNHibernateCSBody");
    define("CG_HANDLER_NHIBERNATE_XML_BODY", "handleNHibernateXMLBody");
}

$CG_FORMATS = array(CG_FORMAT_NHIBERNATE_CS, CG_FORMAT_NHIBERNATE_XML);
$CG_HANDLERS = array(CG_HANDLER_NHIBERNATE_CS_BODY, CG_HANDLER_NHIBERNATE_XML_BODY);

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['codegen'] = array(
        'text' => 'CodeGen',
        'extension' => 'cs',
        'mime_type' => 'text/cs',
        'options' => array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array('type' => 'hidden', 'name' => 'structure_or_data'),
            array('type' => 'select', 'name' => 'format', 'text' => __('Format:'), 'values' => $CG_FORMATS),
            array('type' => 'end_group')
            ),
        'options_text' => __('Options'),
        );
} else {

    /**
     * Set of functions used to build exports of tables
     */

    /**
     * Outputs export footer
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportFooter()
    {
        return true;
    }

    /**
     * Outputs export header
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportHeader()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBHeader($db)
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBFooter($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBCreate($db)
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $crlf       the end of line sequence
     * @param string  $error_url  the url to go back in case of error
     * @param string  $sql_query  SQL query for obtaining data
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        global $CG_FORMATS, $CG_HANDLERS, $what;
        $format = $GLOBALS[$what . '_format'];
        if (isset($CG_FORMATS[$format])) {
            return PMA_exportOutputHandler($CG_HANDLERS[$format]($db, $table, $crlf));
        }
        return PMA_exportOutputHandler(sprintf("%s is not supported.", $format));
    }

    /**
     *
     * @package PhpMyAdmin-Export
     * @subpackage Codegen
     */
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
            if ($pos > 0)
                return substr($this->type, 0, $pos);
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
            if (strpos($this->type, "int") === 0) return "int";
            if (strpos($this->type, "long") === 0) return "long";
            if (strpos($this->type, "char") === 0) return "string";
            if (strpos($this->type, "varchar") === 0) return "string";
            if (strpos($this->type, "text") === 0) return "string";
            if (strpos($this->type, "longtext") === 0) return "string";
            if (strpos($this->type, "tinyint") === 0) return "bool";
            if (strpos($this->type, "datetime") === 0) return "DateTime";
            return "unknown";
        }
        function getDotNetObjectType()
        {
            if (strpos($this->type, "int") === 0) return "Int32";
            if (strpos($this->type, "long") === 0) return "Long";
            if (strpos($this->type, "char") === 0) return "String";
            if (strpos($this->type, "varchar") === 0) return "String";
            if (strpos($this->type, "text") === 0) return "String";
            if (strpos($this->type, "longtext") === 0) return "String";
            if (strpos($this->type, "tinyint") === 0) return "Boolean";
            if (strpos($this->type, "datetime") === 0) return "DateTime";
            return "Unknown";
        }
        function getIndexName()
        {
            if (strlen($this->key)>0)
                return "index=\"" . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8') . "\"";
            return "";
        }
        function isPK()
        {
            return $this->key=="PRI";
        }
        function formatCs($text)
        {
            $text=str_replace("#name#", cgMakeIdentifier($this->name, false), $text);
            return $this->format($text);
        }
        function formatXml($text)
        {
            $text=str_replace("#name#", htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8'), $text);
            $text=str_replace("#indexName#", $this->getIndexName(), $text);
            return $this->format($text);
        }
        function format($text)
        {
            $text=str_replace("#ucfirstName#", cgMakeIdentifier($this->name), $text);
            $text=str_replace("#dotNetPrimitiveType#", $this->getDotNetPrimitiveType(), $text);
            $text=str_replace("#dotNetObjectType#", $this->getDotNetObjectType(), $text);
            $text=str_replace("#type#", $this->getPureType(), $text);
            $text=str_replace("#notNull#", $this->isNotNull(), $text);
            $text=str_replace("#unique#", $this->isUnique(), $text);
            return $text;
        }
    }

    function cgMakeIdentifier($str, $ucfirst = true)
    {
        // remove unsafe characters
        $str = preg_replace('/[^\p{L}\p{Nl}_]/u', '', $str);
        // make sure first character is a letter or _
        if (!preg_match('/^\pL/u', $str)) {
            $str = '_' . $str;
        }
        if ($ucfirst) {
            $str = ucfirst($str);
        }
        return $str;
    }

    function handleNHibernateCSBody($db, $table, $crlf)
    {
        $lines=array();
        $result=PMA_DBI_query(sprintf('DESC %s.%s', PMA_backquote($db), PMA_backquote($table)));
        if ($result) {
            $tableProperties=array();
            while ($row = PMA_DBI_fetch_row($result)) {
                $tableProperties[] = new TableProperty($row);
            }
            PMA_DBI_free_result($result);
            $lines[] = 'using System;';
            $lines[] = 'using System.Collections;';
            $lines[] = 'using System.Collections.Generic;';
            $lines[] = 'using System.Text;';
            $lines[] = 'namespace ' . cgMakeIdentifier($db);
            $lines[] = '{';
            $lines[] = '    #region ' . cgMakeIdentifier($table);
            $lines[] = '    public class ' . cgMakeIdentifier($table);
            $lines[] = '    {';
            $lines[] = '        #region Member Variables';
            foreach ($tableProperties as $tablePropertie) {
                $lines[] = $tablePropertie->formatCs('        protected #dotNetPrimitiveType# _#name#;');
            }
            $lines[] = '        #endregion';
            $lines[] = '        #region Constructors';
            $lines[] = '        public ' . cgMakeIdentifier($table).'() { }';
            $temp = array();
            foreach ($tableProperties as $tablePropertie) {
                if (! $tablePropertie->isPK()) {
                    $temp[] = $tablePropertie->formatCs('#dotNetPrimitiveType# #name#');
                }
            }
            $lines[] = '        public ' . cgMakeIdentifier($table) . '(' . implode(', ', $temp) . ')';
            $lines[] = '        {';
            foreach ($tableProperties as $tablePropertie) {
                if (! $tablePropertie->isPK()) {
                    $lines[] = $tablePropertie->formatCs('            this._#name#=#name#;');
                }
            }
            $lines[] = '        }';
            $lines[] = '        #endregion';
            $lines[] = '        #region Public Properties';
            foreach ($tableProperties as $tablePropertie) {
                $lines[] = $tablePropertie->formatCs(''
                    . '        public virtual #dotNetPrimitiveType# #ucfirstName#' . "\n"
                    . '        {' . "\n"
                    . '            get {return _#name#;}' . "\n"
                    . '            set {_#name#=value;}' . "\n"
                    . '        }'
                    );
            }
            $lines[] = '        #endregion';
            $lines[] = '    }';
            $lines[] = '    #endregion';
            $lines[] = '}';
        }
        return implode("\n", $lines);
    }

    function handleNHibernateXMLBody($db, $table, $crlf)
    {
        $lines = array();
        $lines[] = '<?xml version="1.0" encoding="utf-8" ?' . '>';
        $lines[] = '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" '
            . 'namespace="' . cgMakeIdentifier($db) . '" '
            . 'assembly="' . cgMakeIdentifier($db) . '">';
        $lines[] = '    <class '
            . 'name="' . cgMakeIdentifier($table) . '" '
            . 'table="' . cgMakeIdentifier($table) . '">';
        $result = PMA_DBI_query(sprintf("DESC %s.%s", PMA_backquote($db), PMA_backquote($table)));
        if ($result) {
            while ($row = PMA_DBI_fetch_row($result)) {
                $tablePropertie = new TableProperty($row);
                if ($tablePropertie->isPK())
                    $lines[] = $tablePropertie->formatXml(''
                        . '        <id name="#ucfirstName#" type="#dotNetObjectType#" unsaved-value="0">' . "\n"
                        . '            <column name="#name#" sql-type="#type#" not-null="#notNull#" unique="#unique#" index="PRIMARY"/>' . "\n"
                        . '            <generator class="native" />' . "\n"
                        . '        </id>');
                else
                    $lines[] = $tablePropertie->formatXml(''
                        . '        <property name="#ucfirstName#" type="#dotNetObjectType#">' . "\n"
                        . '            <column name="#name#" sql-type="#type#" not-null="#notNull#" #indexName#/>' . "\n"
                        . '        </property>');
            }
            PMA_DBI_free_result($result);
        }
        $lines[] = '    </class>';
        $lines[] = '</hibernate-mapping>';
        return implode("\n", $lines);
    }
}
?>
