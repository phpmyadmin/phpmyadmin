<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Microsoft Office Excel 97-2003 XLS import plugin for phpMyAdmin
 *
 * @todo    Pretty much everything
 * @version $Id$
 * @package phpMyAdmin-Import
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The possible scopes for $plugin_param are: 'table', 'database', and 'server'
 */

if (isset($plugin_list)) {
    $plugin_list['xls'] = array(
        'text' => 'strImportXLS',
        'extension' => 'xls',
        'options' => array(
                array('type' => 'bool', 'name' => 'col_names', 'text' => 'strImportColNames'),
            ),
        'options_text' => 'strOptions',
        );
    /* We do not define function when plugin is just queried for information above */
    return;
}

ini_set('memory_limit', '256M');
set_time_limit(120);

/* Append the PHPExcel directory to the include path variable */
set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/libraries/PHPExcel/');

require_once './libraries/PHPExcel/PHPExcel.php';
require_once './libraries/PHPExcel/PHPExcel/Reader/Excel5.php';

$objReader = new PHPExcel_Reader_Excel5();
$objReader->setReadDataOnly(true);
$objReader->setLoadAllSheets();
$objPHPExcel = $objReader->load($import_file);

$sheet_names = $objPHPExcel->getSheetNames();
$num_sheets = count($sheet_names);

$tables = array();
$tempRow = array();
$rows = array();
$col_names = array();

for ($s = 0; $s < $num_sheets; ++$s) {
    $current_sheet = $objPHPExcel->getSheet($s);
    
    $num_rows = $current_sheet->getHighestRow();
    $num_cols = PMA_getColumnNumberFromName($current_sheet->getHighestColumn());
    
    if ($num_rows != 1 && $num_cols != 1) {
        for ($r = 1; $r <= $num_rows; ++$r) {
            for ($c = 0; $c < $num_cols; ++$c) {
                $cell = $current_sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue();
                
                if (! strcmp($cell, '')) {
                    $cell = 'NULL';
                }
                
                $tempRow[] = $cell;
            }
            
            $rows[] = $tempRow;
            $tempRow = array();
        }
        
        if ($_REQUEST['xls_col_names']) {
            $col_names = array_splice($rows, 0, 1);
            $col_names = $col_names[0];
            for ($j = 0; $j < $num_cols; ++$j) {
                if (! strcmp('NULL', $col_names[$j])) {
                    $col_names[$j] = PMA_getColumnAlphaName($j + 1);
                }
            }
        } else {
            for ($n = 0; $n < $num_cols; ++$n) {
                $col_names[] = PMA_getColumnAlphaName($n + 1);
            }
        }
        
        $tables[] = array($sheet_names[$s], $col_names, $rows);
        
        $col_names = array();
        $rows = array();
    }
}

unset($objPHPExcel);
unset($objReader);
unset($rows);
unset($tempRow);
unset($col_names);

/* Obtain the best-fit MySQL types for each column */
$analyses = array();

$len = count($tables);
for ($i = 0; $i < $len; ++$i) {
    $analyses[] = PMA_analyzeTable($tables[$i]);
}

/**
 * string $db_name (no backquotes)
 *
 * array $table = array(table_name, array() column_names, array()() rows)
 * array $tables = array of "$table"s
 *
 * array $analysis = array(array() column_types, array() column_sizes)
 * array $analyses = array of "$analysis"s
 *
 * array $create = array of SQL strings
 *
 * array $options = an associative array of options
 */

/* Set database name to the currently selected one, if applicable */
if (strlen($db)) {
    $db_name = $db;
    $options = array('create_db' => false);
} else {
    $db_name = 'XLS_DB';
    $options = NULL;
}

/* Non-applicable parameters */
$create = NULL;

/* Created and execute necessary SQL statements from data */
PMA_buildSQL($db_name, $tables, $analyses, $create, $options);

unset($tables);
unset($analyses);

$finished = true;
$error = false;

/* Commit any possible data in buffers */
PMA_importRunQuery();
?>
