<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * CSV export code
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CSV
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once "libraries/plugins/ExportPlugin.class.php";

/**
 * Handles the export for the CSV format
 *
 * @todo add descriptions for all vars/methods
 * @package PhpMyAdmin-Export
 */
class ExportCsv extends ExportPlugin
{
    /**
     *
     *
     * @var type String
     */
    protected $_what;

    /**
     *
     *
     * @var type String
     */
    protected $_csvTerminated;

    /**
     *
     *
     * @var type String
     */
    protected $_csvSeparator;

    /**
     *
     *
     * @var type String
     */
    protected $_csvEnclosed;

    /**
     *
     *
     * @var type String
     */
    protected $_csvEscaped;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export CSV
     *
     * @return void
     */
    private function initLocalVariables()
    {
        global $what;
        global $csv_terminated;
        global $csv_separator;
        global $csv_enclosed;
        global $csv_escaped;
        $this->setWhat($what);
        $this->setCsvTerminated($csv_terminated);
        $this->setCsvSeparator($csv_separator);
        $this->setCsvEnclosed($csv_enclosed);
        $this->setCsvEscaped($csv_escaped);
    }

    /**
     * Sets the export CSV properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $this->properties = array(
            'text' => __('CSV'),
            'extension' => 'csv',
            'mime_type' => 'text/comma-separated-values',
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
                'name' => 'separator',
                'text' => __('Columns separated with:')
            ),
            array(
                'type' => 'text',
                'name' => 'enclosed',
                'text' => __('Columns enclosed with:')
            ),
            array(
                'type' => 'text',
                'name' => 'escaped',
                'text' => __('Columns escaped with:')
            ),
            array(
                'type' => 'text',
                'name' => 'terminated',
                'text' => __('Lines terminated with:')
            ),
            array(
                'type' => 'text',
                'name' => 'null',
                'text' => __('Replace NULL with:')
            ),
            array(
                'type' => 'bool',
                'name' => 'removeCRLF',
                'text' => __(
                    'Remove carriage return/line feed characters within columns'
                )
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
        // initialize the general export variables
        $this->initExportCommonVariables();

        // initialize the specific export sql variables
        $this->initLocalVariables();

        $what = $this->getWhat();
        $csv_terminated = $this->getCsvTerminated();
        $csv_separator = $this->getCsvSeparator();
        $csv_enclosed = $this->getCsvEnclosed();
        $csv_escaped = $this->getCsvEscaped();


        // Here we just prepare some values for export
        if ($what == 'excel') {
            $csv_terminated = "\015\012";
            switch($GLOBALS['excel_edition']) {
            case 'win':
                // as tested on Windows with Excel 2002 and Excel 2007
                $csv_separator = ';';
                break;
            case 'mac_excel2003':
                $csv_separator = ';';
                break;
            case 'mac_excel2008':
                $csv_separator = ',';
                break;
            }
            $csv_enclosed = '"';
            $csv_escaped  = '"';
            if (isset($GLOBALS['excel_columns'])) {
                $GLOBALS['csv_columns'] = 'yes';
            }
        } else {
            if (empty($csv_terminated) || strtolower($csv_terminated) == 'auto') {
                $csv_terminated = $GLOBALS['crlf'];
            } else {
                $csv_terminated = str_replace('\\r', "\015", $csv_terminated);
                $csv_terminated = str_replace('\\n', "\012", $csv_terminated);
                $csv_terminated = str_replace('\\t', "\011", $csv_terminated);
            } // end if
            $csv_separator = str_replace('\\t', "\011", $csv_separator);
        }

        // remember the modifications
        $this->setCsvTerminated($csv_terminated);
        $this->setCsvSeparator($csv_separator);
        $this->setCsvEnclosed($csv_enclosed);
        $this->setCsvEscaped($csv_escaped);

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
    public function exportDBHeader ($db) {
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
     * Outputs the content of a table in CSV format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    public function exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        $what = $this->getWhat();
        $csv_terminated = $this->getCsvTerminated();
        $csv_separator = $this->getCsvSeparator();
        $csv_enclosed = $this->getCsvEnclosed();
        $csv_escaped = $this->getCsvEscaped();

        // Gets the data from the database
        $result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $fields_cnt = PMA_DBI_num_fields($result);

        // If required, get fields name at the first line
        if (isset($GLOBALS['csv_columns'])) {
            $schema_insert = '';
            for ($i = 0; $i < $fields_cnt; $i++) {
                if ($csv_enclosed == '') {
                    $schema_insert .= stripslashes(PMA_DBI_field_name($result, $i));
                } else {
                    $schema_insert .= $csv_enclosed
                        . str_replace(
                            $csv_enclosed,
                            $csv_escaped . $csv_enclosed,
                            stripslashes(PMA_DBI_field_name($result, $i))
                        )
                        .  $csv_enclosed;
                }
                $schema_insert .= $csv_separator;
            } // end for
            $schema_insert = trim(substr($schema_insert, 0, -1));
            if (! PMA_exportOutputHandler($schema_insert . $csv_terminated)) {
                return false;
            }
        } // end if

        // Format the data
        while ($row = PMA_DBI_fetch_row($result)) {
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (! isset($row[$j]) || is_null($row[$j])) {
                    $schema_insert .= $GLOBALS[$what . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    // always enclose fields
                    if ($what == 'excel') {
                        $row[$j] = preg_replace("/\015(\012)?/", "\012", $row[$j]);
                    }
                    // remove CRLF characters within field
                    if (isset($GLOBALS[$what . '_removeCRLF'])
                        && $GLOBALS[$what . '_removeCRLF']
                    ) {
                        $row[$j] = str_replace(
                            "\n",
                            "",
                            str_replace(
                                "\r",
                                "",
                                $row[$j]
                            )
                        );
                    }
                    if ($csv_enclosed == '') {
                        $schema_insert .= $row[$j];
                    } else {
                        // also double the escape string if found in the data
                        if ($csv_escaped != $csv_enclosed) {
                            $schema_insert .= $csv_enclosed
                                . str_replace(
                                    $csv_enclosed,
                                    $csv_escaped . $csv_enclosed,
                                    str_replace(
                                        $csv_escaped,
                                        $csv_escaped . $csv_escaped,
                                        $row[$j]
                                    )
                                )
                                . $csv_enclosed;
                        } else {
                            // avoid a problem when escape string equals enclose
                            $schema_insert .= $csv_enclosed
                            . str_replace(
                                $csv_enclosed,
                                $csv_escaped . $csv_enclosed,
                                $row[$j]
                            )
                            . $csv_enclosed;
                        }
                    }
                } else {
                    $schema_insert .= '';
                }
                if ($j < $fields_cnt-1) {
                    $schema_insert .= $csv_separator;
                }
            } // end for

            if (! PMA_exportOutputHandler($schema_insert . $csv_terminated)) {
                return false;
            }
        } // end while
        PMA_DBI_free_result($result);

        return true;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    public function getWhat()
    {
        return $this->_what;
    }

    public function setWhat($_what)
    {
        $this->_what = $_what;
    }

    public function getCsvTerminated()
    {
        return $this->_csvTerminated;
    }

    public function setCsvTerminated($_csvTerminated)
    {
        $this->_csvTerminated = $_csvTerminated;
    }

    public function getCsvSeparator()
    {
        return $this->_csvSeparator;
    }

    public function setCsvSeparator($_csvSeparator)
    {
        $this->_csvSeparator = $_csvSeparator;
    }

    public function getCsvEnclosed()
    {
        return $this->_csvEnclosed;
    }

    public function setCsvEnclosed($_csvEnclosed)
    {
        $this->_csvEnclosed = $_csvEnclosed;
    }

    public function getCsvEscaped()
    {
        return $this->_csvEscaped;
    }

    public function setCsvEscaped($_csvEscaped)
    {
        $this->_csvEscaped = $_csvEscaped;
    }
}
?>