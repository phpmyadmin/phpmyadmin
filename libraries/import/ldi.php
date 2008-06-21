<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * CSV import plugin for phpMyAdmin
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if ($plugin_param !== 'table') {
    return;
}

if (isset($plugin_list)) {
    if ($GLOBALS['cfg']['Import']['ldi_local_option'] == 'auto') {
        $GLOBALS['cfg']['Import']['ldi_local_option'] = FALSE;

        if (PMA_MYSQL_INT_VERSION < 32349) {
                $GLOBALS['cfg']['Import']['ldi_local_option'] = TRUE;
        }

        if (PMA_MYSQL_INT_VERSION > 40003) {
            $result = PMA_DBI_try_query('SHOW VARIABLES LIKE \'local\\_infile\';');
            if ($result != FALSE && PMA_DBI_num_rows($result) > 0) {
                $tmp = PMA_DBI_fetch_row($result);
                if ($tmp[1] == 'ON') {
                    $GLOBALS['cfg']['Import']['ldi_local_option'] = TRUE;
                }
            }
            PMA_DBI_free_result($result);
            unset($result);
        }
    }
    $plugin_list['ldi'] = array(
        'text' => 'strLDI',
        'extension' => 'ldi', // This is nonsense, however we want to default to our parser for csv
        'options' => array(
            array('type' => 'bool', 'name' => 'replace', 'text' => 'strReplaceTable'),
            array('type' => 'bool', 'name' => 'ignore', 'text' => 'strIgnoreDuplicates'),
            array('type' => 'text', 'name' => 'terminated', 'text' => 'strFieldsTerminatedBy', 'size' => 2, 'len' => 2),
            array('type' => 'text', 'name' => 'enclosed', 'text' => 'strFieldsEnclosedBy', 'size' => 2, 'len' => 2),
            array('type' => 'text', 'name' => 'escaped', 'text' => 'strFieldsEscapedBy', 'size' => 2, 'len' => 2),
            array('type' => 'text', 'name' => 'new_line', 'text' => 'strLinesTerminatedBy', 'size' => 2),
            array('type' => 'text', 'name' => 'columns', 'text' => 'strColumnNames'),
            array('type' => 'bool', 'name' => 'local_option', 'text' => 'strLDILocal'),
            ),
        'options_text' => 'strOptions',
        );
    /* We do not define function when plugin is just queried for information above */
    return;
}

if ($import_file == 'none' || $compression != 'none' || $charset_conversion) {
    // We handle only some kind of data!
    $message = $strInvalidLDIImport;
    $show_error_header = TRUE;
    $error = TRUE;
    return;
}

$sql = 'LOAD DATA';
if (isset($ldi_local_option)) {
    $sql .= ' LOCAL';
}
$sql .= ' INFILE \'' . PMA_sqlAddslashes($import_file) . '\'';
if (isset($ldi_replace)) {
    $sql .= ' REPLACE';
} elseif (isset($ldi_ignore)) {
    $sql .= ' IGNORE';
}
$sql .= ' INTO TABLE ' . PMA_backquote($table);

if (strlen($ldi_terminated) > 0) {
    $sql .= ' FIELDS TERMINATED BY \'' . $ldi_terminated . '\'';
}
if (strlen($ldi_enclosed) > 0) {
    $sql .= ' ENCLOSED BY \'' . PMA_sqlAddslashes($ldi_enclosed) . '\'';
}
if (strlen($ldi_escaped) > 0) {
    $sql .= ' ESCAPED BY \'' . PMA_sqlAddslashes($ldi_escaped) . '\'';
}
if (strlen($ldi_new_line) > 0){
    if ($ldi_new_line == 'auto') {
        $ldi_new_line = PMA_whichCrlf() == "\n" ? '\n' : '\r\n';
    }
    $sql .= ' LINES TERMINATED BY \'' . $ldi_new_line . '\'';
}
if ($skip_queries > 0) {
    $sql .= ' IGNORE ' . $skip_queries . ' LINES';
    $skip_queries = 0;
}
if (strlen($ldi_columns) > 0) {
    $sql .= ' (';
    $tmp   = split(',( ?)', $ldi_columns);
    $cnt_tmp = count($tmp);
    for ($i = 0; $i < $cnt_tmp; $i++) {
        if ($i > 0) {
            $sql .= ', ';
        }
        $sql     .= PMA_backquote(trim($tmp[$i]));
    } // end for
    $sql .= ')';
}

PMA_importRunQuery($sql, $sql);
PMA_importRunQuery();
$finished = TRUE;
?>
