<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build XLS dumps of tables
 *
 * @version $Id$
 */

/**
 *
 */
// Check if we have native MS Excel export using PEAR class Spreadsheet_Excel_Writer
if (!empty($GLOBALS['cfg']['TempDir'])) {
    @include_once 'Spreadsheet/Excel/Writer.php';
    if (class_exists('Spreadsheet_Excel_Writer')) {
        $xls = TRUE;
    } else {
        $xls = FALSE;
    }
} else {
    $xls = FALSE;
}

if ($xls) {

    if (isset($plugin_list)) {
        $plugin_list['xls'] = array(
            'text' => 'strStrucNativeExcel',
            'extension' => 'xls',
            'mime_type' => 'application/vnd.ms-excel',
            'force_file' => true,
            'options' => array(
                array('type' => 'text', 'name' => 'null', 'text' => 'strReplaceNULLBy'),
                array('type' => 'text', 'name' => 'columns', 'text' => 'strPutColNames'),
                array('type' => 'hidden', 'name' => 'data'),
                ),
            'options_text' => 'strOptions',
            );
    } else {

/**
 * Set of functions used to build MS Excel dumps of tables
 */

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text)
{
    return TRUE;
}

/**
 * Outputs export footer
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportFooter()
{
    global $workbook;
    global $tmp_filename;

    $res = $workbook->close();
    if (PEAR::isError($res)) {
        echo $res->getMessage();
        return FALSE;
    }
    if (!PMA_exportOutputHandler(file_get_contents($tmp_filename))) {
        return FALSE;
    }
    unlink($tmp_filename);

    return TRUE;
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader()
{
    global $workbook;
    global $tmp_filename;

    if (empty($GLOBALS['cfg']['TempDir'])) {
        return FALSE;
    }
    $tmp_filename = tempnam(realpath($GLOBALS['cfg']['TempDir']), 'pma_xls_');
    $workbook = new Spreadsheet_Excel_Writer($tmp_filename);

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
function PMA_exportDBHeader($db)
{
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
function PMA_exportDBFooter($db)
{
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
function PMA_exportDBCreate($db)
{
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
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
{
    global $what;
    global $workbook;

    $worksheet =& $workbook->addWorksheet($table);
    $workbook->setTempDir(realpath($GLOBALS['cfg']['TempDir']));

    // Gets the data from the database
    $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);
    $col         = 0;

    // If required, get fields name at the first line
    if (isset($GLOBALS['xls_columns']) && $GLOBALS['xls_columns'] == 'yes') {
        $schema_insert = '';
        for ($i = 0; $i < $fields_cnt; $i++) {
            $worksheet->write(0, $i, stripslashes(PMA_DBI_field_name($result, $i)));
        } // end for
        $col++;
    } // end if

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $schema_insert = '';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j]) || is_null($row[$j])) {
                $worksheet->write($col, $j, $GLOBALS['xls_null']);
            } elseif ($row[$j] == '0' || $row[$j] != '') {
                /**
                 * @todo we should somehow handle character set here!
                 */
                $worksheet->write($col, $j, $row[$j]);
            } else {
                $worksheet->write($col, $j, '');
            }
        } // end for
        $col++;
    } // end while
    PMA_DBI_free_result($result);

    return TRUE;
}

    }
}
?>
