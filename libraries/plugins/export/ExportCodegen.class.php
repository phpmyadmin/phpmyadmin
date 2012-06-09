<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build NHibernate dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CodeGen
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once "libraries/plugins/ExportPlugin.class.php";
/* Get the table property class */
require_once "libraries/plugins/export/TableProperty.class.php";

/**
 * Handles the export for the CodeGen class
 *
 * @todo add descriptions for all vars/methods
 * @package PhpMyAdmin-Export
 */
class ExportCodegen extends ExportPlugin
{
    /**
     *
     *
     * @var type array
     */
    private $_CG_FORMATS;

    /**
     *
     *
     * @var type array
     */
    private $_CG_HANDLERS;

    /**
     * Constructor
     */
    public function __construct()
    {
        // initialize the specific export codegen variables
        $this->initLocalVariables();

        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export CodeGen
     *
     * @return void
     */
    private function initLocalVariables()
    {
        $this->setCG_FORMATS(
            array(
                "NHibernate C# DO",
                "NHibernate XML"
            )
        );

        $this->setCG_HANDLERS(
            array(
                "handleNHibernateCSBody",
                "handleNHibernateXMLBody"
            )
        );
    }

    /**
     * Sets the export XML properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $this->properties = array(
            'text' => 'CodeGen',
            'extension' => 'cs',
            'mime_type' => 'text/cs',
            'options' => array(),
            'options_text' => __('Options')
        );

        $this->properties['options'] = array(
            array(
                'type' => 'begin_group',
                'name' => 'general_opts'
            ),
            array(
                'type' => 'hidden',
                'name' => 'structure_or_data'
            ),
            array(
                'type' => 'select',
                'name' => 'format',
                'text' => __('Format:'),
                'values' => $this->getCG_FORMATS()
            ),
            array(
                'type' => 'end_group'
            )
        );
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader ()
    {
        // initialize the general export variables
        $this->initExportCommonVariables();

        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter ()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader ($db)
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter ($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db)
    {
        return true;
    }
    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    public function exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        $CG_FORMATS = $this->getCG_FORMATS();
        $CG_HANDLERS = $this->getCG_HANDLERS();

        $format = $GLOBALS['codegen_format'];
        if (isset($CG_FORMATS[$format])) {
            return PMA_exportOutputHandler(
                $this->$CG_HANDLERS[$format]($db, $table, $crlf)
            );
        }
        return PMA_exportOutputHandler(sprintf("%s is not supported.", $format));
    }

    public static function cgMakeIdentifier($str, $ucfirst = true)
    {
        // remove unsafe characters
        $str = preg_replace('/[^\p{L}\p{Nl}_]/u', '', $str);
        // make sure first character is a letter or _
        if (! preg_match('/^\pL/u', $str)) {
            $str = '_' . $str;
        }
        if ($ucfirst) {
            $str = ucfirst($str);
        }
        return $str;
    }

    private function handleNHibernateCSBody($db, $table, $crlf)
    {
        $lines = array();
        $result = PMA_DBI_query(
            sprintf('DESC %s.%s', PMA_backquote($db), PMA_backquote($table))
        );
        if ($result) {
            $tableProperties = array();
            while ($row = PMA_DBI_fetch_row($result)) {
                $tableProperties[] = new TableProperty($row);
            }
            PMA_DBI_free_result($result);
            $lines[] = 'using System;';
            $lines[] = 'using System.Collections;';
            $lines[] = 'using System.Collections.Generic;';
            $lines[] = 'using System.Text;';
            $lines[] = 'namespace ' . ExportCodegen::cgMakeIdentifier($db);
            $lines[] = '{';
            $lines[] = '    #region ' . ExportCodegen::cgMakeIdentifier($table);
            $lines[] = '    public class ' . ExportCodegen::cgMakeIdentifier($table);
            $lines[] = '    {';
            $lines[] = '        #region Member Variables';
            foreach ($tableProperties as $tableProperty) {
                $lines[] = $tableProperty->formatCs(
                    '        protected #dotNetPrimitiveType# _#name#;'
                );
            }
            $lines[] = '        #endregion';
            $lines[] = '        #region Constructors';
            $lines[] = '        public ' . ExportCodegen::cgMakeIdentifier($table).'() { }';
            $temp = array();
            foreach ($tableProperties as $tableProperty) {
                if (! $tableProperty->isPK()) {
                    $temp[] = $tableProperty->formatCs(
                        '#dotNetPrimitiveType# #name#'
                    );
                }
            }
            $lines[] = '        public '
                . ExportCodegen::cgMakeIdentifier($table)
                . '('
                . implode(', ', $temp)
                . ')';
            $lines[] = '        {';
            foreach ($tableProperties as $tableProperty) {
                if (! $tableProperty->isPK()) {
                    $lines[] = $tableProperty->formatCs(
                        '            this._#name#=#name#;'
                    );
                }
            }
            $lines[] = '        }';
            $lines[] = '        #endregion';
            $lines[] = '        #region Public Properties';
            foreach ($tableProperties as $tableProperty) {
                $lines[] = $tableProperty->formatCs(
                    '        public virtual #dotNetPrimitiveType# #ucfirstName#'
                    . "\n"
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
            . 'namespace="' . ExportCodegen::cgMakeIdentifier($db) . '" '
            . 'assembly="' . ExportCodegen::cgMakeIdentifier($db) . '">';
        $lines[] = '    <class '
            . 'name="' . ExportCodegen::cgMakeIdentifier($table) . '" '
            . 'table="' . ExportCodegen::cgMakeIdentifier($table) . '">';
        $result = PMA_DBI_query(
            sprintf("DESC %s.%s", PMA_backquote($db), PMA_backquote($table))
        );
        if ($result) {
            while ($row = PMA_DBI_fetch_row($result)) {
                $tableProperty = new TableProperty($row);
                if ($tableProperty->isPK()) {
                    $lines[] = $tableProperty->formatXml(
                        '        <id name="#ucfirstName#" type="#dotNetObjectType#"'
                        . ' unsaved-value="0">' . "\n"
                        . '            <column name="#name#" sql-type="#type#"'
                        . ' not-null="#notNull#" unique="#unique#"'
                        . ' index="PRIMARY"/>' . "\n"
                        . '            <generator class="native" />' . "\n"
                        . '        </id>'
                    );
                } else {
                    $lines[] = $tableProperty->formatXml(
                        '        <property name="#ucfirstName#"'
                        . ' type="#dotNetObjectType#">' . "\n"
                        . '            <column name="#name#" sql-type="#type#"'
                        . ' not-null="#notNull#" #indexName#/>' . "\n"
                        . '        </property>'
                    );
                }
            }
            PMA_DBI_free_result($result);
        }
        $lines[] = '    </class>';
        $lines[] = '</hibernate-mapping>';
        return implode("\n", $lines);
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    public function getCG_FORMATS()
    {
        return $this->_CG_FORMATS;
    }

    public function setCG_FORMATS($CG_FORMATS)
    {
        $this->_CG_FORMATS = $CG_FORMATS;
    }

    public function getCG_HANDLERS()
    {
        return $this->_CG_HANDLERS;
    }

    public function setCG_HANDLERS($CG_HANDLERS)
    {
        $this->_CG_HANDLERS = $CG_HANDLERS;
    }
}
?>