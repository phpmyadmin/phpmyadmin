<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 *
 * @package phpMyAdmin-Export-XLS
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['xls'] = array(
        'text' => 'strImportXLS',
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

/* Append the PHPExcel directory to the include path variable */
set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/libraries/PHPExcel/');

require_once './libraries/PHPExcel/PHPExcel.php';
require_once './libraries/PHPExcel/PHPExcel/Writer/Excel5.php';

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
    global $workbook;
    global $tmp_filename;
    
    $tmp_filename = tempnam(realpath($GLOBALS['cfg']['TempDir']), 'pma_xls_');
    
    $workbookWriter = new PHPExcel_Writer_Excel5($workbook);
    $workbookWriter->setTempDir(realpath($GLOBALS['cfg']['TempDir']));
    $workbookWriter->save($tmp_filename);
    
    if (!PMA_exportOutputHandler(file_get_contents($tmp_filename))) {
        return FALSE;
    }
    
    unlink($tmp_filename);
    
    unset($GLOBALS['workbook']);
    unset($GLOBALS['sheet_index']);

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
    global $db;
    
    /* Initialize the workbook */
    $GLOBALS['workbook'] = new PHPExcel();
    $GLOBALS['sheet_index'] = 0;
    global $workbook;
    
    $workbook->getProperties()->setCreator('phpMyAdmin ' . PMA_VERSION);
    $workbook->getProperties()->setLastModifiedBy('phpMyAdmin ' . PMA_VERSION);
    $workbook->getProperties()->setTitle($db);
    $workbook->getProperties()->setSubject('phpMyAdmin ' . PMA_VERSION . ' XLS Dump');
    
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
 * Outputs the content of a table in XLS format
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
    global $workbook;
    global $sheet_index;
    
    /**
     * Get the data from the database using the original query
     */
    $result      = PMA_DBI_fetch_result($sql_query);
    $row_cnt     = count($result);
    
    if ($row_cnt > 0) {
        $col_names = array_keys($result[0]);
        $fields_cnt = count($result[0]);
        $row_offset = 1;
        
        /* Only one sheet is created on workbook initialization */
        if ($sheet_index > 0) {
            $workbook->createSheet();
        }
        
        $workbook->setActiveSheetIndex($sheet_index);
        $workbook->getActiveSheet()->setTitle(substr($table, 0, 31));
        
        if (isset($GLOBALS['xls_columns']) && $GLOBALS['xls_columns']) {
            for ($i = 0; $i < $fields_cnt; ++$i) {
                $workbook->getActiveSheet()->setCellValueByColumnAndRow($i, $row_offset, $col_names[$i]);
            }
            $row_offset++;
        }
        
        for ($r = 0; ($r < 65536) && ($r < $row_cnt); ++$r) {
            for ($c = 0; $c < $fields_cnt; ++$c) {
                if (!isset($result[$r][$col_names[$c]]) || is_null($result[$r][$col_names[$c]])) {
                    $workbook->getActiveSheet()->setCellValueByColumnAndRow($c, ($r + $row_offset), $GLOBALS['xls_null']);
                } elseif ($result[$r][$col_names[$c]] == '0' || $result[$r][$col_names[$c]] != '') {
                    /**
                     * @todo we should somehow handle character set here!
                     */
                    $workbook->getActiveSheet()->setCellValueByColumnAndRow($c, ($r + $row_offset), $result[$r][$col_names[$c]]);
                } else {
                    $workbook->getActiveSheet()->setCellValueByColumnAndRow($c, ($r + $row_offset), '');
                }
            }
        }
        
        $sheet_index++;
    }
    
    return TRUE;
}

}
?>
