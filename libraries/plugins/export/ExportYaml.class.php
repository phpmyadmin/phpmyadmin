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
        PMA_exportOutputHandler(
            '%YAML 1.1' . $GLOBALS['crlf'] . '---' . $GLOBALS['crlf']
        );
        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter ()
    {
        PMA_exportOutputHandler('...' . $GLOBALS['crlf']);
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
     * Outputs the content of a table in JSON format
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
        $result = $GLOBALS['dbi']->query(
            $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );

        $columns_cnt = $GLOBALS['dbi']->numFields($result);
        for ($i = 0; $i < $columns_cnt; $i++) {
            $columns[$i] = stripslashes($GLOBALS['dbi']->fieldName($result, $i));
        }
        unset($i);

        $buffer = '';
        $record_cnt = 0;
        while ($record = $GLOBALS['dbi']->fetchRow($result)) {
            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt == 1) {
                $buffer = '# ' . $db . '.' . $table . $crlf;
                $buffer .= '-' . $crlf;
            } else {
                $buffer = '-' . $crlf;
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                if (! isset($record[$i])) {
                    continue;
                }

                $column = $columns[$i];

                if (is_null($record[$i])) {
                    $buffer .= '  ' . $column . ': null' . $crlf;
                    continue;
                }

                if (is_numeric($record[$i])) {
                    $buffer .= '  ' . $column . ': '  . $record[$i] . $crlf;
                    continue;
                }

                $record[$i] = str_replace(
                    array('\\', '"', "\n", "\r"),
                    array('\\\\', '\"', '\n', '\r'),
                    $record[$i]
                );
                $buffer .= '  ' . $column . ': "' . $record[$i] . '"' . $crlf;
            }

            if (! PMA_exportOutputHandler($buffer)) {
                return false;
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        return true;
    } // end getTableYAML
}
?>