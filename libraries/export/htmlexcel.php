<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build CSV dumps of tables
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['htmlexcel'] = array(
        'text' => 'strHTMLExcel',
        'extension' => 'xls',
        'mime_type' => 'application/vnd.ms-excel',
        'force_file' => true,
        'options' => array(
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
    if (!PMA_exportOutputHandler('
</table>
</div>
</body>
</html>
')) {
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
    global $charset, $charset_of_file;
    if (!PMA_exportOutputHandler('
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html;charset=' . (isset($charset_of_file) ? $charset_of_file : $charset) .'" />
<style id="Classeur1_16681_Styles">
</style>

</head>
<body>

<div id="Classeur1_16681" align=center x:publishsource="Excel">

<table x:str border=0 cellpadding=0 cellspacing=0 width=100% style="border-collapse: collapse">
')) {
        return FALSE;
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

    // Gets the data from the database
    $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);

    // If required, get fields name at the first line
    if (isset($GLOBALS[$what . '_columns'])) {
        $schema_insert = '<tr>';
        for ($i = 0; $i < $fields_cnt; $i++) {
            $schema_insert .= '<td class=xl2216681 nowrap><b>' . htmlspecialchars(stripslashes(PMA_DBI_field_name($result, $i))) . '</b></td>';
        } // end for
        $schema_insert .= '</tr>';
        if (!PMA_exportOutputHandler($schema_insert)) {
            return FALSE;
        }
    } // end if

    $fields_meta = PMA_DBI_get_fields_meta($result);

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $schema_insert = '<tr>';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j]) || is_null($row[$j])) {
                $value = $GLOBALS[$what . '_null'];
            } elseif ($row[$j] == '0' || $row[$j] != '') {
                $value = $row[$j];
            } else {
                $value = '';
            }
            $schema_insert .= '<td class=xl2216681 nowrap';
            if ('1' == $fields_meta[$j]->numeric) {
                $schema_insert .= ' x:num ';
            }
            $schema_insert .= '>' . htmlspecialchars($value) . '</td>';
        } // end for
        $schema_insert .= '</tr>';
        if (!PMA_exportOutputHandler($schema_insert)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);

    return TRUE;
}

}
?>
