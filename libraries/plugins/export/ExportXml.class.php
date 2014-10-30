<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build XML dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage XML
 */
if (! defined('PHPMYADMIN')) {
    exit;
}
if (! strlen($GLOBALS['db'])) { /* Can't do server export */
    $GLOBALS['skip_import'] = true;
    return;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

/**
 * Handles the export for the XML class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage XML
 */
class ExportXml extends ExportPlugin
{
    /**
     * Table name
     *
     * @var string
     */
    private $_table;

    /**
     * Table names
     *
     * @var array
     */
    private $_tables;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export PDF
     *
     * @return void
     */
    protected function initSpecificVariables()
    {
        global $table, $tables;
        $this->_setTable($table);
        $this->_setTables($tables);
    }

    /**
     * Sets the export XML properties
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
        include_once "$props/options/items/BoolPropertyItem.class.php";

        // create the export plugin property item
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('XML');
        $exportPluginProperties->setExtension('xml');
        $exportPluginProperties->setMimeType('text/xml');
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
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // export structure main group
        $structure = new OptionsPropertyMainGroup();
        $structure->setName("structure");
        $structure->setText(__('Object creation options (all are recommended)'));
        // create primary items and add them to the group
        if (! PMA_DRIZZLE) {
            $leaf = new BoolPropertyItem();
            $leaf->setName("export_events");
            $leaf->setText(__('Events'));
            $structure->addProperty($leaf);
            $leaf = new BoolPropertyItem();
            $leaf->setName("export_functions");
            $leaf->setText(__('Functions'));
            $structure->addProperty($leaf);
            $leaf = new BoolPropertyItem();
            $leaf->setName("export_procedures");
            $leaf->setText(__('Procedures'));
            $structure->addProperty($leaf);
        }
        $leaf = new BoolPropertyItem();
        $leaf->setName("export_tables");
        $leaf->setText(__('Tables'));
        $structure->addProperty($leaf);
        if (! PMA_DRIZZLE) {
            $leaf = new BoolPropertyItem();
            $leaf->setName("export_triggers");
            $leaf->setText(__('Triggers'));
            $structure->addProperty($leaf);
            $leaf = new BoolPropertyItem();
            $leaf->setName("export_views");
            $leaf->setText(__('Views'));
            $structure->addProperty($leaf);
        }
        $exportSpecificOptions->addProperty($structure);

        // data main group
        $data = new OptionsPropertyMainGroup();
        $data->setName("data");
        $data->setText(__('Data dump options'));
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName("export_contents");
        $leaf->setText(__('Export contents'));
        $data->addProperty($leaf);
        $exportSpecificOptions->addProperty($data);

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
     * Outputs export header. It is the first method to be called, so all
     * the required variables are initialized here.
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader ()
    {
        $this->initSpecificVariables();
        global $crlf, $cfg, $db;
        $table = $this->_getTable();
        $tables = $this->_getTables();

        $export_struct = isset($GLOBALS['xml_export_functions'])
            || isset($GLOBALS['xml_export_procedures'])
            || isset($GLOBALS['xml_export_tables'])
            || isset($GLOBALS['xml_export_triggers'])
            || isset($GLOBALS['xml_export_views']);
        $export_data = isset($GLOBALS['xml_export_contents']) ? true : false;

        if ($GLOBALS['output_charset_conversion']) {
            $charset = $GLOBALS['charset_of_file'];
        } else {
            $charset = 'utf-8';
        }

        $head  =  '<?xml version="1.0" encoding="' . $charset . '"?>' . $crlf
               .  '<!--' . $crlf
               .  '- phpMyAdmin XML Dump' . $crlf
               .  '- version ' . PMA_VERSION . $crlf
               .  '- http://www.phpmyadmin.net' . $crlf
               .  '-' . $crlf
               .  '- ' . __('Host:') . ' ' . $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
             $head .= ':' . $cfg['Server']['port'];
        }
        $head .= $crlf
            . '- ' . __('Generation Time:') . ' '
            . PMA_Util::localisedDate() . $crlf
            . '- ' . __('Server version:') . ' ' . PMA_MYSQL_STR_VERSION . $crlf
            . '- ' . __('PHP Version:') . ' ' . phpversion() . $crlf
            . '-->' . $crlf . $crlf;

        $head .= '<pma_xml_export version="1.0"'
            . (($export_struct)
            ? ' xmlns:pma="http://www.phpmyadmin.net/some_doc_url/"'
            : '')
            . '>' . $crlf;

        if ($export_struct) {
            if (PMA_DRIZZLE) {
                $result = $GLOBALS['dbi']->fetchResult(
                    "SELECT
                        'utf8' AS DEFAULT_CHARACTER_SET_NAME,
                        DEFAULT_COLLATION_NAME
                    FROM data_dictionary.SCHEMAS
                    WHERE SCHEMA_NAME = '"
                    . PMA_Util::sqlAddSlashes($db) . "'"
                );
            } else {
                $result = $GLOBALS['dbi']->fetchResult(
                    'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
                    . ' FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME`'
                    . ' = \'' . PMA_Util::sqlAddSlashes($db) . '\' LIMIT 1'
                );
            }
            $db_collation = $result[0]['DEFAULT_COLLATION_NAME'];
            $db_charset = $result[0]['DEFAULT_CHARACTER_SET_NAME'];

            $head .= '    <!--' . $crlf;
            $head .= '    - Structure schemas' . $crlf;
            $head .= '    -->' . $crlf;
            $head .= '    <pma:structure_schemas>' . $crlf;
            $head .= '        <pma:database name="' . htmlspecialchars($db)
                . '" collation="' . $db_collation . '" charset="' . $db_charset
                . '">' . $crlf;

            if (count($tables) == 0) {
                $tables[] = $table;
            }

            foreach ($tables as $table) {
                // Export tables and views
                $result = $GLOBALS['dbi']->fetchResult(
                    'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.'
                    . PMA_Util::backquote($table),
                    0
                );
                $tbl =  $result[$table][1];

                $is_view = PMA_Table::isView($db, $table);

                if ($is_view) {
                    $type = 'view';
                } else {
                    $type = 'table';
                }

                if ($is_view && ! isset($GLOBALS['xml_export_views'])) {
                    continue;
                }

                if (! $is_view && ! isset($GLOBALS['xml_export_tables'])) {
                    continue;
                }

                $head .= '            <pma:' . $type . ' name="' . $table . '">'
                    . $crlf;

                $tbl = "                " . htmlspecialchars($tbl);
                $tbl = str_replace("\n", "\n                ", $tbl);

                $head .= $tbl . ';' . $crlf;
                $head .= '            </pma:' . $type . '>' . $crlf;

                if (isset($GLOBALS['xml_export_triggers'])
                    && $GLOBALS['xml_export_triggers']
                ) {
                    // Export triggers
                    $triggers = $GLOBALS['dbi']->getTriggers($db, $table);
                    if ($triggers) {
                        foreach ($triggers as $trigger) {
                            $code = $trigger['create'];
                            $head .= '            <pma:trigger name="'
                                . $trigger['name'] . '">' . $crlf;

                            // Do some formatting
                            $code = substr(rtrim($code), 0, -3);
                            $code = "                " . htmlspecialchars($code);
                            $code = str_replace("\n", "\n                ", $code);

                            $head .= $code . $crlf;
                            $head .= '            </pma:trigger>' . $crlf;
                        }

                        unset($trigger);
                        unset($triggers);
                    }
                }
            }

            if (isset($GLOBALS['xml_export_functions'])
                && $GLOBALS['xml_export_functions']
            ) {
                // Export functions
                $functions = $GLOBALS['dbi']->getProceduresOrFunctions(
                    $db, 'FUNCTION'
                );
                if ($functions) {
                    foreach ($functions as $function) {
                        $head .= '            <pma:function name="'
                            . $function . '">' . $crlf;

                        // Do some formatting
                        $sql = $GLOBALS['dbi']->getDefinition(
                            $db, 'FUNCTION', $function
                        );
                        $sql = rtrim($sql);
                        $sql = "                " . htmlspecialchars($sql);
                        $sql = str_replace("\n", "\n                ", $sql);

                        $head .= $sql . $crlf;
                        $head .= '            </pma:function>' . $crlf;
                    }

                    unset($function);
                    unset($functions);
                }
            }

            if (isset($GLOBALS['xml_export_procedures'])
                && $GLOBALS['xml_export_procedures']
            ) {
                // Export procedures
                $procedures = $GLOBALS['dbi']->getProceduresOrFunctions(
                    $db, 'PROCEDURE'
                );
                if ($procedures) {
                    foreach ($procedures as $procedure) {
                        $head .= '            <pma:procedure name="'
                            . $procedure . '">' . $crlf;

                        // Do some formatting
                        $sql = $GLOBALS['dbi']->getDefinition(
                            $db, 'PROCEDURE', $procedure
                        );
                        $sql = rtrim($sql);
                        $sql = "                " . htmlspecialchars($sql);
                        $sql = str_replace("\n", "\n                ", $sql);

                        $head .= $sql . $crlf;
                        $head .= '            </pma:procedure>' . $crlf;
                    }

                    unset($procedure);
                    unset($procedures);
                }
            }

            if (isset($GLOBALS['xml_export_events'])
                && $GLOBALS['xml_export_events']
            ) {
                if (PMA_MYSQL_INT_VERSION > 50100) {
                    // Export events
                    $events = $GLOBALS['dbi']->fetchResult(
                        "SELECT EVENT_NAME FROM information_schema.EVENTS "
                        . "WHERE EVENT_SCHEMA='" . PMA_Util::sqlAddslashes($db) . "'"
                    );
                    if ($events) {
                        foreach ($events as $event) {
                            $head .= '            <pma:event name="'
                                . $event . '">' . $crlf;

                            $sql = $GLOBALS['dbi']->getDefinition(
                                $db, 'EVENT', $event
                            );
                            $sql = rtrim($sql);
                            $sql = "                " . htmlspecialchars($sql);
                            $sql = str_replace("\n", "\n                ", $sql);

                            $head .= $sql . $crlf;
                            $head .= '            </pma:event>' . $crlf;
                        }

                        unset($event);
                        unset($events);
                    }
                }
            }

            unset($result);

            $head .= '        </pma:database>' . $crlf;
            $head .= '    </pma:structure_schemas>' . $crlf;

            if ($export_data) {
                $head .= $crlf;
            }
        }

        return PMA_exportOutputHandler($head);
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter ()
    {
        $foot = '</pma_xml_export>';

        return PMA_exportOutputHandler($foot);
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
        global $crlf;

        if (isset($GLOBALS['xml_export_contents'])
            && $GLOBALS['xml_export_contents']
        ) {
            $head = '    <!--' . $crlf
                  . '    - ' . __('Database:') . ' ' .  '\'' . $db . '\'' . $crlf
                  . '    -->' . $crlf
                  . '    <database name="' . htmlspecialchars($db) . '">' . $crlf;

            return PMA_exportOutputHandler($head);
        } else {
            return true;
        }
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
        global $crlf;

        if (isset($GLOBALS['xml_export_contents'])
            && $GLOBALS['xml_export_contents']
        ) {
            return PMA_exportOutputHandler('    </database>' . $crlf);
        } else {
            return true;
        }
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
     * Outputs the content of a table in XML format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     */
    public function exportData ($db, $table, $crlf, $error_url, $sql_query)
    {
        if (isset($GLOBALS['xml_export_contents'])
            && $GLOBALS['xml_export_contents']
        ) {
            $result = $GLOBALS['dbi']->query(
                $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
            );

            $columns_cnt = $GLOBALS['dbi']->numFields($result);
            $columns = array();
            for ($i = 0; $i < $columns_cnt; $i++) {
                $columns[$i] = stripslashes($GLOBALS['dbi']->fieldName($result, $i));
            }
            unset($i);

            $buffer = '        <!-- ' . __('Table') . ' ' . $table . ' -->' . $crlf;
            if (! PMA_exportOutputHandler($buffer)) {
                return false;
            }

            while ($record = $GLOBALS['dbi']->fetchRow($result)) {
                $buffer = '        <table name="'
                    . htmlspecialchars($table) . '">' . $crlf;
                for ($i = 0; $i < $columns_cnt; $i++) {
                    // If a cell is NULL, still export it to preserve
                    // the XML structure
                    if (! isset($record[$i]) || is_null($record[$i])) {
                        $record[$i] = 'NULL';
                    }
                    $buffer .= '            <column name="'
                        . htmlspecialchars($columns[$i]) . '">'
                        . htmlspecialchars((string)$record[$i])
                        .  '</column>' . $crlf;
                }
                $buffer     .= '        </table>' . $crlf;

                if (! PMA_exportOutputHandler($buffer)) {
                    return false;
                }
            }
            $GLOBALS['dbi']->freeResult($result);
        }

        return true;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the table name
     *
     * @return string
     */
    private function _getTable()
    {
        return $this->_table;
    }

    /**
     * Sets the table name
     *
     * @param string $table table name
     *
     * @return void
     */
    private function _setTable($table)
    {
        $this->_table = $table;
    }

    /**
     * Gets the table names
     *
     * @return array
     */
    private function _getTables()
    {
        return $this->_tables;
    }

    /**
     * Sets the table names
     *
     * @param array $tables table names
     *
     * @return void
     */
    private function _setTables($tables)
    {
        $this->_tables = $tables;
    }
}
?>
