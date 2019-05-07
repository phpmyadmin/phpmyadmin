<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of methods used to build dumps of tables as JSON
 *
 * @package    PhpMyAdmin-Export
 * @subpackage JSON
 */
namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;

/**
 * Handles the export for the JSON format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage JSON
 */
class ExportJson extends ExportPlugin
{
    private $first = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Encodes the data into JSON
     *
     * @param mixed $data Data to encode
     *
     * @return string
     */
    public function encode($data)
    {
        $options = 0;
        if (isset($GLOBALS['json_pretty_print'])
            && $GLOBALS['json_pretty_print']
        ) {
            $options |= JSON_PRETTY_PRINT;
        }
        if (isset($GLOBALS['json_unicode'])
            && $GLOBALS['json_unicode']
        ) {
            $options |= JSON_UNESCAPED_UNICODE;
        }
        return json_encode($data, $options);
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

        $leaf = new BoolPropertyItem(
            'pretty_print',
            __('Output pretty-printed JSON (Use human-readable formatting)')
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'unicode',
            __('Output unicode characters unescaped')
        );
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
        global $crlf;

        $meta = array(
            'type' => 'header',
            'version' => PMA_VERSION,
            'comment' => 'Export to JSON plugin for PHPMyAdmin',
        );

        return Export::outputHandler(
            '[' . $crlf . $this->encode($meta) . ',' . $crlf
        );
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        global $crlf;

        return Export::outputHandler(']' . $crlf);
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
        global $crlf;

        if (empty($db_alias)) {
            $db_alias = $db;
        }

        $meta = array(
            'type' => 'database',
            'name' => $db_alias
        );

        return Export::outputHandler(
            $this->encode($meta) . ',' . $crlf
        );
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

        if (! $this->first) {
            if (!Export::outputHandler(',')) {
                return false;
            }
        } else {
            $this->first = false;
        }

        $buffer = $this->encode(
            array(
                'type' => 'table',
                'name' => $table_alias,
                'database' => $db_alias,
                'data' => "@@DATA@@"
            )
        );
        list($header, $footer) = explode('"@@DATA@@"', $buffer);

        if (!Export::outputHandler($header . $crlf . '[' . $crlf)) {
            return false;
        }

        $result = $GLOBALS['dbi']->query(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_UNBUFFERED
        );
        $columns_cnt = $GLOBALS['dbi']->numFields($result);
        $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);

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
            if ($record_cnt > 1) {
                if (!Export::outputHandler(',' . $crlf)) {
                    return false;
                }
            }

            $data = array();

            for ($i = 0; $i < $columns_cnt; $i++) {
                if ($fields_meta[$i]->type === 'geometry') {
                    // export GIS types as hex
                    $record[$i] = '0x' . bin2hex($record[$i]);
                }
                $data[$columns[$i]] = $record[$i];
            }

            $encodedData = $this->encode($data);
            if (! $encodedData) {
                return false;
            }
            if (! Export::outputHandler($encodedData)) {
                return false;
            }
        }

        if (!Export::outputHandler($crlf . ']' . $crlf . $footer . $crlf)) {
            return false;
        }

        $GLOBALS['dbi']->freeResult($result);

        return true;
    }
}
