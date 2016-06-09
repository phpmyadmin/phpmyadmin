<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of methods used to build dumps of tables as JSON
 *
 * @package    PhpMyAdmin-Export
 * @subpackage JSON
 */
namespace PMA\libraries\plugins\export;

use PMA\libraries\properties\options\items\BoolPropertyItem;
use PMA\libraries\plugins\ExportPlugin;
use PMA\libraries\properties\plugins\ExportPluginProperties;
use PMA\libraries\properties\options\items\HiddenPropertyItem;
use PMA\libraries\properties\options\groups\OptionsPropertyMainGroup;
use PMA\libraries\properties\options\groups\OptionsPropertyRootGroup;
use PMA;

/**
 * Handles the export for the JSON format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage JSON
 */
class ExportJson extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the export JSON properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('JSON');
        $exportPluginProperties->setExtension('json');
        $exportPluginProperties->setMimeType('text/plain');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup(
            "Format Specific Options"
        );

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup("general_opts");
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem("structure_or_data");
        $generalOptions->addProperty($leaf);

        // JSON_PRETTY_PRINT is available since 5.4.0
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $leaf = new BoolPropertyItem(
                'pretty_print',
                __('Output pretty-printed JSON (Use human-readable formatting)')
            );
            $generalOptions->addProperty($leaf);
        }

        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader()
    {
        PMA_exportOutputHandler(
            '/**' . $GLOBALS['crlf']
            . ' Export to JSON plugin for PHPMyAdmin' . $GLOBALS['crlf']
            . ' @version ' . PMA_VERSION . $GLOBALS['crlf']
            . ' */' . $GLOBALS['crlf'] . $GLOBALS['crlf']
        );

        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db       Database name
     * @param string $db_alias Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader($db, $db_alias = '')
    {
        if (empty($db_alias)) {
            $db_alias = $db;
        }
        PMA_exportOutputHandler(
            '// Database \'' . $db_alias . '\'' . $GLOBALS['crlf']
        );

        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db          Database name
     * @param string $export_type 'server', 'database', 'table'
     * @param string $db_alias    Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db, $export_type, $db_alias = '')
    {
        return true;
    }

    /**
     * Outputs the content of a table in JSON format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     * @param array  $aliases   Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $error_url,
        $sql_query,
        $aliases = array()
    ) {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $result = $GLOBALS['dbi']->query(
            $sql_query,
            null,
            PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED
        );
        $columns_cnt = $GLOBALS['dbi']->numFields($result);

        $columns = array();
        for ($i = 0; $i < $columns_cnt; $i++) {
            $col_as = $GLOBALS['dbi']->fieldName($result, $i);
            if (!empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }
            $columns[$i] = stripslashes($col_as);
        }

        $record_cnt = 0;
        while ($record = $GLOBALS['dbi']->fetchRow($result)) {

            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt == 1) {
                $buffer = $crlf . '// ' . $db_alias . '.' . $table_alias
                    . $crlf . $crlf;
                $buffer .= '[';
            } else {
                $buffer = ', ';
            }

            if (!PMA_exportOutputHandler($buffer)) {
                return false;
            }

            $data = array();

            for ($i = 0; $i < $columns_cnt; $i++) {
                $data[$columns[$i]] = $record[$i];
            }

            if (isset($GLOBALS['json_pretty_print'])
                && $GLOBALS['json_pretty_print']
            ) {
                $encoded = json_encode($data, JSON_PRETTY_PRINT);
            } else {
                $encoded = json_encode($data);
            }

            if (!PMA_exportOutputHandler($encoded)) {
                return false;
            }
        }

        if ($record_cnt) {
            if (!PMA_exportOutputHandler(']' . $crlf)) {
                return false;
            }
        }

        $GLOBALS['dbi']->freeResult($result);

        return true;
    }
}
