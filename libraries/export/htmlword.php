<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used to build CSV dumps of tables
 */

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
    ?>
</table>
</div>
</body>
</html>
    <?php
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
    ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" content="text/html;charset=<?php echo isset($charset_of_file) ? $charset_of_file : $charset; ?>" />
</head>
<body>

<table class="width100" cellspacing="1">
<?php

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
    $result      = PMA_DBI_query($sql_query, NULL, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);

    // If required, get fields name at the first line
    if (isset($GLOBALS[$what . '_shownames']) && $GLOBALS[$what . '_shownames'] == 'yes') {
        $schema_insert = '<tr class="print-category">';
        for ($i = 0; $i < $fields_cnt; $i++) {
            $schema_insert .= '<td class="print"><b>' . htmlspecialchars(stripslashes(PMA_DBI_field_name($result, $i))) . '</b></td>';
        } // end for
        $schema_insert .= '</tr>';
        if (!PMA_exportOutputHandler($schema_insert)) return FALSE;
    } // end if

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $schema_insert = '<tr class="print-category">';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j]) || is_null($row[$j])) {
                $value = $GLOBALS[$what . '_replace_null'];
            } else if ($row[$j] == '0' || $row[$j] != '') {
                $value = $row[$j];
            } else {
                $value = '';
            }
            $schema_insert .= '<td class="print">' . htmlspecialchars($value) . '</td>';
        } // end for
        $schema_insert .= '</tr>';
        if (!PMA_exportOutputHandler($schema_insert)) return FALSE;
    } // end while
    PMA_DBI_free_result($result);

    return TRUE;
}
?>
