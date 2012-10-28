<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of methods used to build dumps of tables as JSON
 *
 * @package    PhpMyAdmin-Export
 * @subpackage JSON
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

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
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/HiddenPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('JSON');
        $exportPluginProperties->setExtension('json');
        $exportPluginProperties->setMimeType('text/plain');
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
            '/**' . $GLOBALS['crlf']
            . ' Export to JSON plugin for PHPMyAdmin' . $GLOBALS['crlf']
            . ' @version 0.1' . $GLOBALS['crlf']
            . ' */' . $GLOBALS['crlf'] . $GLOBALS['crlf']
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
        PMA_exportOutputHandler('// Database \'' . $db . '\'' . $GLOBALS['crlf']);
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
        $result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $columns_cnt = PMA_DBI_num_fields($result);

        // Get field information
        $fields_meta = PMA_DBI_get_fields_meta($result);

        for ($i = 0; $i < $columns_cnt; $i++) {
            $columns[$i] = stripslashes(PMA_DBI_field_name($result, $i));
        }
        unset($i);

        $buffer = '';
        $record_cnt = 0;
        while ($record = PMA_DBI_fetch_row($result)) {

            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt == 1) {
                $buffer .= '// ' . $db . '.' . $table . $crlf . $crlf;
                $buffer .= '[{';
            } else {
                $buffer .= ', {';
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                $isLastLine = ($i + 1 >= $columns_cnt);
                $column = $columns[$i];
                if (is_null($record[$i])) {
                    $buffer .= '"' . addslashes($column)
                        . '": null'
                        . (! $isLastLine ? ',' : '');
                } elseif ($fields_meta[$i]->numeric) {
                    $buffer .= '"' . addslashes($column)
                        . '": '
                        . $record[$i]
                        . (! $isLastLine ? ',' : '');
                } else {
                    $buffer .= '"' . addslashes($column)
                        . '": "'
                        . addslashes($record[$i])
                        . '"'
                        . (! $isLastLine ? ',' : '');
                }
            }

            $buffer .= '}';
        }

        if ($record_cnt) {
            $buffer .=  ']';
        }
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        PMA_DBI_free_result($result);
        return true;
    }
}
?>