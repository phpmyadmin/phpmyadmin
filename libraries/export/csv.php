<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Set of functions used to build CSV dumps of tables
 */

if (isset($plugin_list)) {
    $plugin_list['csv'] = array(
        'text' => 'strStrucCSV',
        'extension' => 'csv',
        'mime_type' => 'text/comma-separated-values',
        'options' => array(
            array('type' => 'text', 'name' => 'separator', 'text' => 'strFieldsTerminatedBy'),
            array('type' => 'text', 'name' => 'enclosed', 'text' => 'strFieldsEnclosedBy'),
            array('type' => 'text', 'name' => 'escaped', 'text' => 'strFieldsEscapedBy'),
            array('type' => 'text', 'name' => 'terminated', 'text' => 'strLinesTerminatedBy'),
            array('type' => 'text', 'name' => 'null', 'text' => 'strReplaceNULLBy'),
            array('type' => 'bool', 'name' => 'columns', 'text' => 'strPutColNames'),
            array('type' => 'hidden', 'name' => 'data'),
            ),
        'options_text' => 'strOptions',
        );
} else {

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text) {
    return TRUE;
}

/**
 * Outputs export footer
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportFooter() {
    return TRUE;
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader() {
    global $what;
    global $csv_terminated;
    global $csv_separator;
    global $csv_enclosed;
    global $csv_escaped;

    // Here we just prepare some values for export
    if ($what == 'excel') {
        $csv_terminated      = "\015\012";
        $csv_separator          = isset($GLOBALS['excel_edition']) && $GLOBALS['excel_edition'] == 'mac' ? ';' : ',';
        $csv_enclosed           = '"';
        $csv_escaped            = '"';
        if (isset($GLOBALS['excel_columns'])) {
            $GLOBALS['csv_columns'] = 'yes';
        }
    } else {
        if (empty($csv_terminated) || strtolower($csv_terminated) == 'auto') {
            $csv_terminated  = $GLOBALS['crlf'];
        } else {
            $csv_terminated  = str_replace('\\r', "\015", $csv_terminated);
            $csv_terminated  = str_replace('\\n', "\012", $csv_terminated);
            $csv_terminated  = str_replace('\\t', "\011", $csv_terminated);
        } // end if
        $csv_separator          = str_replace('\\t', "\011", $csv_separator);
    }
    return TRUE;
}

/**
 * Outputs database header
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBHeader($db) {
    return TRUE;
}

/**
 * Outputs database footer
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBFooter($db) {
    return TRUE;
}

/**
 * Outputs create database database
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBCreate($db) {
    return TRUE;
}

/**
 * Outputs the content of a table in CSV format
 *
 * @param   string      the database name
 * @param   string      the table name
 * @param   string      the end of line sequence
 * @param   string      the url to go back in case of error
 * @param   string      SQL query for obtaining data
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query) {
    global $what;
    global $csv_terminated;
    global $csv_separator;
    global $csv_enclosed;
    global $csv_escaped;

    // Gets the data from the database
    $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);

    // If required, get fields name at the first line
    if (isset($GLOBALS['csv_columns'])) {
        $schema_insert = '';
        for ($i = 0; $i < $fields_cnt; $i++) {
            if ($csv_enclosed == '') {
                $schema_insert .= stripslashes(PMA_DBI_field_name($result, $i));
            } else {
                $schema_insert .= $csv_enclosed
                               . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, stripslashes(PMA_DBI_field_name($result, $i)))
                               . $csv_enclosed;
            }
            $schema_insert     .= $csv_separator;
        } // end for
        $schema_insert  =trim(substr($schema_insert, 0, -1));
        if (!PMA_exportOutputHandler($schema_insert . $csv_terminated)) {
            return FALSE;
        }
    } // end if

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $schema_insert = '';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j]) || is_null($row[$j])) {
                $schema_insert .= $GLOBALS[$what . '_null'];
            } elseif ($row[$j] == '0' || $row[$j] != '') {
                // loic1 : always enclose fields
                if ($what == 'excel') {
                    $row[$j]       = ereg_replace("\015(\012)?", "\012", $row[$j]);
                }
                if ($csv_enclosed == '') {
                    $schema_insert .= $row[$j];
                } else {
                    // also double the escape string if found in the data
                    if ('csv' == $what) {
                        $schema_insert .= $csv_enclosed
                                   . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, str_replace($csv_escaped, $csv_escaped . $csv_escaped, $row[$j]))
                                   . $csv_enclosed;
                    } else {
                        // for excel, avoid a problem when a field contains
                        // double quotes
                        $schema_insert .= $csv_enclosed
                                   . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $row[$j])
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

        if (!PMA_exportOutputHandler($schema_insert . $csv_terminated)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);

    return TRUE;
} // end of the 'PMA_getTableCsv()' function

}
?>
