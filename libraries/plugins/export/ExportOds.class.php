<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build OpenDocument Spreadsheet dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage ODS
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once "libraries/plugins/ExportPlugin.class.php";

$GLOBALS['ods_buffer'] = '';
include_once 'libraries/opendocument.lib.php';

/**
 * Handles the export for the ODS class
 *
 * @todo add descriptions for all vars/methods
 * @package PhpMyAdmin-Export
 */
class ExportOds extends ExportPlugin
{
    /**
     *
     *
     * @var type string
     */
    private $_what;

    /**
     * Constructor
     */
    public function __construct()
    {
        // initialize the specific export ODS variables
        $this->initLocalVariables();

        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export ODS
     *
     * @return void
     */
    private function initLocalVariables()
    {
        global $what;
        $this->setWhat($what);
    }

    /**
     * Sets the export ODS properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $this->properties = array(
            'text' => __('Open Document Spreadsheet'),
            'extension' => 'ods',
            'mime_type' => 'application/vnd.oasis.opendocument.spreadsheet',
            'force_file' => true,
            'options' => array(),
            'options_text' => __('Options')
        );

        $this->properties['options'] = array(
            array(
                'type' => 'begin_group',
                'name' => 'general_opts'
            ),
            array(
                'type' => 'text',
                'name' => 'null',
                'text' => __('Replace NULL with:')
            ),
            array(
                'type' => 'bool',
                'name' => 'columns',
                'text' => __('Put columns names in the first row')
            ),
            array(
                'type' => 'hidden',
                'name' => 'structure_or_data'
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
        $GLOBALS['ods_buffer'] .= '<?xml version="1.0" encoding="utf-8"?' . '>'
            . '<office:document-content '
                . $GLOBALS['OpenDocumentNS'] . 'office:version="1.0">'
            . '<office:automatic-styles>'
                . '<number:date-style style:name="N37"'
                    .' number:automatic-order="true">'
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
    public function exportFooter ()
    {
        $GLOBALS['ods_buffer'] .= '</office:spreadsheet>'
            . '</office:body>'
            . '</office:document-content>';
        if (! PMA_exportOutputHandler(
            PMA_createOpenDocument(
                'application/vnd.oasis.opendocument.spreadsheet',
                $GLOBALS['ods_buffer']
            )
        )) {
            return false;
        }
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
        $what = $this->getWhat();

        // Gets the data from the database
        $result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $fields_cnt = PMA_DBI_num_fields($result);
        $fields_meta = PMA_DBI_get_fields_meta($result);
        $field_flags = array();
        for ($j = 0; $j < $fields_cnt; $j++) {
            $field_flags[$j] = PMA_DBI_field_flags($result, $j);
        }

        $GLOBALS['ods_buffer'] .=
            '<table:table table:name="' . htmlspecialchars($table) . '">';

        // If required, get fields name at the first line
        if (isset($GLOBALS[$what . '_columns'])) {
            $GLOBALS['ods_buffer'] .= '<table:table-row>';
            for ($i = 0; $i < $fields_cnt; $i++) {
                $GLOBALS['ods_buffer'] .=
                    '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars(
                        stripslashes(PMA_DBI_field_name($result, $i))
                    )
                    . '</text:p>'
                    . '</table:table-cell>';
            } // end for
            $GLOBALS['ods_buffer'] .= '</table:table-row>';
        } // end if

        // Format the data
        while ($row = PMA_DBI_fetch_row($result)) {
            $GLOBALS['ods_buffer'] .= '<table:table-row>';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (! isset($row[$j]) || is_null($row[$j])) {
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($GLOBALS[$what . '_null'])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif (stristr($field_flags[$j], 'BINARY')
                    && $fields_meta[$j]->blob
                ) {
                    // ignore BLOB
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->type == "date") {
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="date"'
                            . ' office:date-value="'
                            . date("Y-m-d", strtotime($row[$j]))
                            . '" table:style-name="DateCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->type == "time") {
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="time"'
                            . ' office:time-value="'
                            . date("\P\TH\Hi\Ms\S", strtotime($row[$j]))
                            . '" table:style-name="TimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->type == "datetime") {
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="date"'
                            . ' office:date-value="'
                            . date("Y-m-d\TH:i:s", strtotime($row[$j]))
                            . '" table:style-name="DateTimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fields_meta[$j]->numeric
                    && $fields_meta[$j]->type != 'timestamp'
                    && ! $fields_meta[$j]->blob
                ) {
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="float"'
                            . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['ods_buffer'] .=
                        '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            } // end for
            $GLOBALS['ods_buffer'] .= '</table:table-row>';
        } // end while
        PMA_DBI_free_result($result);

        $GLOBALS['ods_buffer'] .= '</table:table>';

        return true;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    public function getWhat()
    {
        return $this->_what;
    }

    public function setWhat($what)
    {
        $this->_what = $what;
    }
}
?>