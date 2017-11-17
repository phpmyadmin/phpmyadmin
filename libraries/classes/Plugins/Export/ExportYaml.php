<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build YAML dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage YAML
 */

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;

/**
 * Handles the export for the YAML format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage YAML
 */
class ExportYaml extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the export YAML properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('YAML');
        $exportPluginProperties->setExtension('yml');
        $exportPluginProperties->setMimeType('text/yaml');
        $exportPluginProperties->setForceFile(true);
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
        Export::outputHandler(
            '%YAML 1.1' . $GLOBALS['crlf'] . '---' . $GLOBALS['crlf']
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
        Export::outputHandler('...' . $GLOBALS['crlf']);

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
        array $aliases = array()
    ) {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        $result = $GLOBALS['dbi']->query(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_UNBUFFERED
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

        $buffer = '';
        $record_cnt = 0;
        while ($record = $GLOBALS['dbi']->fetchRow($result)) {
            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt == 1) {
                $buffer = '# ' . $db_alias . '.' . $table_alias . $crlf;
                $buffer .= '-' . $crlf;
            } else {
                $buffer = '-' . $crlf;
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                if (!isset($record[$i])) {
                    continue;
                }

                if (is_null($record[$i])) {
                    $buffer .= '  ' . $columns[$i] . ': null' . $crlf;
                    continue;
                }

                if (is_numeric($record[$i])) {
                    $buffer .= '  ' . $columns[$i] . ': ' . $record[$i] . $crlf;
                    continue;
                }

                $record[$i] = str_replace(
                    array('\\', '"', "\n", "\r"),
                    array('\\\\', '\"', '\n', '\r'),
                    $record[$i]
                );
                $buffer .= '  ' . $columns[$i] . ': "' . $record[$i] . '"' . $crlf;
            }

            if (!Export::outputHandler($buffer)) {
                return false;
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        return true;
    } // end getTableYAML
}
