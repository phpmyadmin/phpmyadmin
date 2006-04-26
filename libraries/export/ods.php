<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used to build CSV dumps of tables
 */

if (isset($plugin_list)) {
    $plugin_list['ods'] = array(
        'text' => 'strOpenDocumentSpreadsheet',
        'extension' => 'ods',
        'mime_type' => 'application/vnd.oasis.opendocument.spreadsheet',
        'force_file' => true,
        'options' => array(
            array('type' => 'text', 'name' => 'null', 'text' => 'strReplaceNULLBy'),
            array('type' => 'bool', 'name' => 'columns', 'text' => 'strPutColNames'),
            array('type' => 'hidden', 'name' => 'data'),
            ),
        'options_text' => 'strOpenDocumentSpreadsheetOptions',
        );
} else {

$GLOBALS['ods_buffer'] = '';
require_once('./libraries/opendocument.lib.php');

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
    $GLOBALS['ods_buffer'] .= '</office:spreadsheet>'
        . '</office:body>'
        . '</office:document-content>';
    if (!PMA_exportOutputHandler(PMA_createOpenDocument('application/vnd.oasis.opendocument.spreadsheet', $GLOBALS['ods_buffer']))) {
        return FALSE;
    }
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
    $GLOBALS['ods_buffer'] .= '<?xml version="1.0" encoding="UTF-8"?' . '>'
        . '<office:document-content '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
            . 'xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0">'
        . '<office:body>'
        . '<office:spreadsheet>';
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

    // Gets the data from the database
    $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);
    $fields_meta = PMA_DBI_get_fields_meta($result);
    $field_flags = array();
    for ($j = 0; $j < $fields_cnt; $j++) {
        $field_flags[$j] = PMA_DBI_field_flags($result, $j);
    }

    $GLOBALS['ods_buffer'] .= '<table:table table:name="' . htmlspecialchars($table) . '">';

    // If required, get fields name at the first line
    if (isset($GLOBALS[$what . '_columns'])) {
        $GLOBALS['ods_buffer'] .= '<table:table-row>';
        for ($i = 0; $i < $fields_cnt; $i++) {
            $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . htmlspecialchars(stripslashes(PMA_DBI_field_name($result, $i))) . '</text:p>'
                . '</table:table-cell>';
        } // end for
        $GLOBALS['ods_buffer'] .= '</table:table-row>';
    } // end if

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $GLOBALS['ods_buffer'] .= '<table:table-row>';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j]) || is_null($row[$j])) {
                $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                    . '<text:p>' . htmlspecialchars($GLOBALS[$what . '_null']) . '</text:p>'
                    . '</table:table-cell>';
            // ignore binary field
            // Note: with mysqli, under MySQL 4.1.3, we get the flag
            // "binary" for those field types (I don't know why)
            } elseif (stristr($field_flags[$j], 'BINARY')
                    && isset($GLOBALS['sql_hex_for_binary'])
                    && $fields_meta[$j]->type != 'datetime'
                    && $fields_meta[$j]->type != 'date'
                    && $fields_meta[$j]->type != 'time'
                    && $fields_meta[$j]->type != 'timestamp'
                   ) {
                $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                    . '<text:p></text:p>'
                    . '</table:table-cell>';
            } elseif ($fields_meta[$j]->numeric && $fields_meta[$j]->type != 'timestamp' && ! $fields_meta[$j]->blob) {
                $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="float" office:value="' . $row[$j] . '" >'
                    . '<text:p>' . htmlspecialchars($row[$j]) . '</text:p>'
                    . '</table:table-cell>';
            } else {
                $GLOBALS['ods_buffer'] .= '<table:table-cell office:value-type="string">'
                    . '<text:p>' . htmlspecialchars($row[$j]) . '</text:p>'
                    . '</table:table-cell>';
            }
        } // end for
        $GLOBALS['ods_buffer'] .= '</table:table-row>';
    } // end while
    PMA_DBI_free_result($result);

    $GLOBALS['ods_buffer'] .= '</table:table>';

    return TRUE;
}

}
?>
