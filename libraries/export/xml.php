<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used to build XML dumps of tables
 */

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text) {
    return PMA_exportOutputHandler('<!-- ' . $text . ' -->' . $GLOBALS['crlf']);
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader() {
    global $crlf;
    global $cfg;
    
    if ($GLOBALS['output_charset_conversion']) {
        $charset = $GLOBALS['charset_of_file'];
    } else {
        $charset = $GLOBALS['charset'];
    }

    $head  =  '<?xml version="1.0" encoding="' . $charset . '" ?>' . $crlf
           .  '<!--' . $crlf
           .  '-' . $crlf
           .  '- phpMyAdmin XML Dump' . $crlf
           .  '- version ' . PMA_VERSION . $crlf
           .  '- http://www.phpmyadmin.net' . $crlf
           .  '-' . $crlf
           .  '- ' . $GLOBALS['strHost'] . ': ' . $cfg['Server']['host'];
    if (!empty($cfg['Server']['port'])) {
         $head .= ':' . $cfg['Server']['port'];
    }
    $head .= $crlf
           .  '- ' . $GLOBALS['strGenTime'] . ': ' . PMA_localisedDate() . $crlf
           .  '- ' . $GLOBALS['strServerVersion'] . ': ' . substr(PMA_MYSQL_INT_VERSION, 0, 1) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 1, 2) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 3) . $crlf
           .  '- ' . $GLOBALS['strPHPVersion'] . ': ' . phpversion() . $crlf
           .  '-->' . $crlf . $crlf;
    return PMA_exportOutputHandler($head);
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
    global $crlf;
    $head = '<!--' . $crlf
          . '- ' . $GLOBALS['strDatabase'] . ': ' . (isset($GLOBALS['use_backquotes']) ? PMA_backquote($db) : '\'' . $db . '\''). $crlf
          . '-->' . $crlf
          . '<' . $db . '>' . $crlf;
    return PMA_exportOutputHandler($head);
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
    global $crlf;
    return PMA_exportOutputHandler('</' . $db . '>' . $crlf);
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
 * Outputs the content of a table
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
    $result      = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $error_url);
    
    $columns_cnt = mysql_num_fields($result);
    for ($i = 0; $i < $columns_cnt; $i++) {
        $columns[$i] = stripslashes(mysql_field_name($result, $i));
    }
    unset($i);
    
    $buffer      = '  <!-- ' . $GLOBALS['strTable'] . ' ' . $table . ' -->' . $crlf;
    if (!PMA_exportOutputHandler($buffer)) return FALSE;
    
    while ($record = PMA_mysql_fetch_array($result, MYSQL_ASSOC)) {
        $buffer         = '    <' . $table . '>' . $crlf;
        for ($i = 0; $i < $columns_cnt; $i++) {
            // There is no way to dectect a "NULL" value with PHP3
            if ( isset($record[$columns[$i]]) && (!function_exists('is_null') || !is_null($record[$columns[$i]]))) {
                $buffer .= '        <' . $columns[$i] . '>' . htmlspecialchars(stripslashes($record[$columns[$i]]))
                        .  '</' . $columns[$i] . '>' . $crlf;
            }
        }
        $buffer         .= '    </' . $table . '>' . $crlf;
        
        if (!PMA_exportOutputHandler($buffer)) return FALSE;
    }
    mysql_free_result($result);

    return TRUE;
} // end of the 'PMA_getTableXML()' function
?>