<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build YAML dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage YAML
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

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
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/HiddenPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('YAML');
        $exportPluginProperties->setExtension('yml');
        $exportPluginProperties->setMimeType('text/yaml');
        $exportPluginProperties->setForceFile(true);
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

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }

    /**
     * Outputs export header
     *
     * @return array Error (if any) and header
     */
    public function exportHeader ()
    {
        return array(
            false,
            '%YAML 1.1' . $GLOBALS['crlf'] . '---' . $GLOBALS['crlf']
        );
    }

    /**
     * Outputs export footer
     *
     * @return array Error (if any) and footer
     */
    public function exportFooter ()
    {
        return array(false, '...' . $GLOBALS['crlf']);
    }

    /**
     * Outputs database header
     *
     * @param string $db       Database name
     * @param string $db_alias Aliases of db
     *
     * @return string DB header
     */
    public function exportDBHeader ($db, $db_alias = '')
    {
        return '';
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return array Error (if any) and DB footer
     */
    public function exportDBFooter ($db)
    {
        return array(false, '');
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db       Database name
     * @param string $db_alias Aliases of db
     *
     * @return string DB CREATE statement
     */
    public function exportDBCreate($db, $db_alias = '')
    {
        return '';
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
     * @return array Error (if any) and table's data
     */
    public function exportData(
        $db, $table, $crlf, $error_url, $sql_query, $aliases = array()
    ) {
        $db_alias = $db;
        $table_alias = $table;
        $error = false;
        $this->initAlias($aliases, $db_alias, $table_alias);
        $result = $GLOBALS['dbi']->tryQuery(
            $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );

        if ($error = $GLOBALS['dbi']->getError()) {
            return array($error, '');
        }

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
                if (! isset($record[$i])) {
                    continue;
                }

                if (is_null($record[$i])) {
                    $buffer .= '  ' . $columns[$i] . ': null' . $crlf;
                    continue;
                }

                if (is_numeric($record[$i])) {
                    $buffer .= '  ' . $columns[$i] . ': '  . $record[$i] . $crlf;
                    continue;
                }

                $record[$i] = str_replace(
                    array('\\', '"', "\n", "\r"),
                    array('\\\\', '\"', '\n', '\r'),
                    $record[$i]
                );
                $buffer .= '  ' . $columns[$i] . ': "' . $record[$i] . '"' . $crlf;
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        return array($error, $buffer);
    } // end getTableYAML
}
?>
