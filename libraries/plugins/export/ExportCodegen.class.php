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
require_once 'libraries/plugins/ExportPlugin.class.php';
/* Get the table property class */
require_once 'libraries/plugins/export/TableProperty.class.php';

/**
 * Handles the export for the CodeGen class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CodeGen
 */
class ExportCodegen extends ExportPlugin
{
    /**
     * CodeGen Formats
     *
     * @var array
     */
    private $_cgFormats;

    /**
     * CodeGen Handlers
     *
     * @var array
     */
    private $_cgHandlers;

    /**
     * Constructor
     */
    public function __construct()
    {
        // initialize the specific export CodeGen variables
        $this->initSpecificVariables();
        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export CodeGen
     *
     * @return void
     */
    protected function initSpecificVariables()
    {
        $this->_setCgFormats(
            array(
                "NHibernate C# DO",
                "NHibernate XML"
            )
        );

        $this->_setCgHandlers(
            array(
                "_handleNHibernateCSBody",
                "_handleNHibernateXMLBody"
            )
        );
    }

    /**
     * Sets the export CodeGen properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/HiddenPropertyItem.class.php";
        include_once "$props/options/items/SelectPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CodeGen');
        $exportPluginProperties->setExtension('cs');
        $exportPluginProperties->setMimeType('text/cs');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem();
        $leaf->setName("structure_or_data");
        $generalOptions->addProperty($leaf);
        $leaf = new SelectPropertyItem();
        $leaf->setName("format");
        $leaf->setText(__('Format:'));
        $leaf->setValues($this->_getCgFormats());
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
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
     */
    public function exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        $CG_FORMATS = $this->_getCgFormats();
        $CG_HANDLERS = $this->_getCgHandlers();

        $format = $GLOBALS['codegen_format'];
        if (isset($CG_FORMATS[$format])) {
            return PMA_exportOutputHandler(
                $this->$CG_HANDLERS[$format]($db, $table, $crlf)
            );
        }
        return PMA_exportOutputHandler(sprintf("%s is not supported.", $format));
    }

    /**
     * Used to make identifiers (from table or database names)
     *
     * @param string $str     name to be converted
     * @param bool   $ucfirst whether to make the first character uppercase
     *
     * @return string identifier
     */
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

    /**
     * C# Handler
     *
     * @param string $db    database name
     * @param string $table table name
     * @param string $crlf  line separator
     *
     * @return string containing C# code lines, separated by "\n"
     */
    private function _handleNHibernateCSBody($db, $table, $crlf)
    {
        $lines = array();

        $result = $GLOBALS['dbi']->query(
            sprintf(
                'DESC %s.%s', PMA_Util::backquote($db),
                PMA_Util::backquote($table)
            )
        );
        if ($result) {
            $tableProperties = array();
            while ($row = $GLOBALS['dbi']->fetchRow($result)) {
                $tableProperties[] = new TableProperty($row);
            }
            $GLOBALS['dbi']->freeResult($result);
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
            $lines[] = '        public '
                . ExportCodegen::cgMakeIdentifier($table) . '() { }';
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

    /**
     * XML Handler
     *
     * @param string $db    database name
     * @param string $table table name
     * @param string $crlf  line separator
     *
     * @return string containing XML code lines, separated by "\n"
     */
    private function _handleNHibernateXMLBody($db, $table, $crlf)
    {
        $lines = array();
        $lines[] = '<?xml version="1.0" encoding="utf-8" ?' . '>';
        $lines[] = '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" '
            . 'namespace="' . ExportCodegen::cgMakeIdentifier($db) . '" '
            . 'assembly="' . ExportCodegen::cgMakeIdentifier($db) . '">';
        $lines[] = '    <class '
            . 'name="' . ExportCodegen::cgMakeIdentifier($table) . '" '
            . 'table="' . ExportCodegen::cgMakeIdentifier($table) . '">';
        $result = $GLOBALS['dbi']->query(
            sprintf(
                "DESC %s.%s", PMA_Util::backquote($db),
                PMA_Util::backquote($table)
            )
        );
        if ($result) {
            while ($row = $GLOBALS['dbi']->fetchRow($result)) {
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
            $GLOBALS['dbi']->freeResult($result);
        }
        $lines[] = '    </class>';
        $lines[] = '</hibernate-mapping>';
        return implode("\n", $lines);
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Getter for CodeGen formats
     *
     * @return array
     */
    private function _getCgFormats()
    {
        return $this->_cgFormats;
    }

    /**
     * Setter for CodeGen formats
     *
     * @param array $CG_FORMATS contains CodeGen Formats
     *
     * @return void
     */
    private function _setCgFormats($CG_FORMATS)
    {
        $this->_cgFormats = $CG_FORMATS;
    }

    /**
     * Getter for CodeGen handlers
     *
     * @return array
     */
    private function _getCgHandlers()
    {
        return $this->_cgHandlers;
    }

    /**
     * Setter for CodeGen handlers
     *
     * @param array $CG_HANDLERS contains CodeGen handler methods
     *
     * @return void
     */
    private function _setCgHandlers($CG_HANDLERS)
    {
        $this->_cgHandlers = $CG_HANDLERS;
    }
}
?>