<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build OpenDocument Spreadsheet dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage ODS
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export;
use PhpMyAdmin\OpenDocument;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

/**
 * Handles the export for the ODS class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage ODS
 */
class ExportOds extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['ods_buffer'] = '';
        $this->setProperties();
    }

    /**
     * Sets the export ODS properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('OpenDocument Spreadsheet');
        $exportPluginProperties->setExtension('ods');
        $exportPluginProperties->setMimeType(
            'application/vnd.oasis.opendocument.spreadsheet'
        );
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
        $leaf = new TextPropertyItem(
            "null",
            __('Replace NULL with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            "columns",
            __('Put columns names in the first row')
        );
        $generalOptions->addProperty($leaf);
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
        $GLOBALS['ods_buffer'] .= '<?xml version="1.0" encoding="utf-8"?' . '>'
            . '<office:document-content '
            . OpenDocument::NS . ' office:version="1.0">'
            . '<office:automatic-styles>'
            . '<number:date-style style:name="N37"'
            . ' number:automatic-order="true">'
            . '<number:month number:style="long"/>'
            . '<number:text>/</number:text>'
            . '<number:day number:style="long"/>'
            . '<number:text>/</number:text>'
            . '<number:year/>'
            . '</number:date-style>'
            . '<number:time-style style:name="N43">'
            . '<number:hours number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:minutes number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:seconds number:style="long"/>'
            . '<number:text> </number:text>'
            . '<number:am-pm/>'
            . '</number:time-style>'
            . '<number:date-style style:name="N50"'
            . ' number:automatic-order="true"'
            . ' number:format-source="language">'
            . '<number:month/>'
            . '<number:text>/</number:text>'
            . '<number:day/>'
            . '<number:text>/</number:text>'
            . '<number:year/>'
            . '<number:text> </number:text>'
            . '<number:hours number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:minutes number:style="long"/>'
            . '<number:text> </number:text>'
            . '<number:am-pm/>'
            . '</number:date-style>'
            . '<style:style style:name="DateCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N37"/>'
            . '<style:style style:name="TimeCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N43"/>'
            . '<style:style style:name="DateTimeCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N50"/>'
            . '</office:automatic-styles>'
            . '<office:body>'
            . '<office:spreadsheet>';

        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        $GLOBALS['ods_buffer'] .= '</office:spreadsheet>'
            . '</office:body>'
            . '</office:document-content>';

        return $this->export->outputHandler(
            OpenDocument::create(
                'application/vnd.oasis.opendocument.spreadsheet',
                $GLOBALS['ods_buffer']
            )
        );
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
     * Outputs the content of a table in NHibernate format
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
        array $aliases = []
    ) {
        global $what;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        // Gets the data from the database
        $result = $GLOBALS['dbi']->query(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_UNBUFFERED
        );
        $fields_cnt = $GLOBALS['dbi']->numFields($result);
        $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
        $field_flags = [];
        for ($j = 0; $j < $fields_cnt; $j++) {
            $field_flags[$j] = $GLOBALS['dbi']->fieldFlags($result, $j);
        }

        $GLOBALS['ods_buffer']
            .= '<table:table table:name="' . htmlspecialchars($table_alias) . '">';

        // If required, get fields name at the first line
        if (isset($GLOBALS[$what . '_columns'])) {
            $GLOBALS['ods_buffer'] .= '<table:table-row>';
            for ($i = 0; $i < $fields_cnt; $i++) {
                $col_as = $GLOBALS['dbi']->fieldName($result, $i);
                if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }
                $GLOBALS['ods_buffer']
                    .= '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars(
                        stripslashes($col_as)
                    )
                    . '</text:p>'
                    . '</table:table-cell>';
            } // end for
            $GLOBALS['ods_buffer'] .= '</table:table-row>';
        } // end if

        // Format the data
        while ($row = $GLOBALS['dbi']->fetchRow($result)) {
            $GLOBALS['ods_buffer'] .= '<table:table-row>';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if ($fields_meta[$j]->type === 'geometry') {
                    // export GIS types as hex
                    $row[$j] = '0x' . bin2hex($row[$j]);
                }
                if (! isset($row[$j]) || $row[$j] === null) {
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($GLOBALS[$what . '_null'])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif (false !== stripos($field_flags[$j], 'BINARY')
                    && $fields_meta[$j]->blob
                ) {
                    // ignore BLOB
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->type == "date") {
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="date"'
                        . ' office:date-value="'
                        . date("Y-m-d", strtotime($row[$j]))
                        . '" table:style-name="DateCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->type == "time") {
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="time"'
                        . ' office:time-value="'
                        . date("\P\TH\Hi\Ms\S", strtotime($row[$j]))
                        . '" table:style-name="TimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->type == "datetime") {
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="date"'
                        . ' office:date-value="'
                        . date("Y-m-d\TH:i:s", strtotime($row[$j]))
                        . '" table:style-name="DateTimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif (($fields_meta[$j]->numeric
                    && $fields_meta[$j]->type != 'timestamp'
                    && ! $fields_meta[$j]->blob)
                    || $fields_meta[$j]->type == 'real'
                ) {
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="float"'
                        . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['ods_buffer']
                        .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            } // end for
            $GLOBALS['ods_buffer'] .= '</table:table-row>';
        } // end while
        $GLOBALS['dbi']->freeResult($result);

        $GLOBALS['ods_buffer'] .= '</table:table>';

        return true;
    }
}
