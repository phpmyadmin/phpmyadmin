<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used to build dumps of tables
 */

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text) {
    return PMA_exportOutputHandler('% ' . $text . $GLOBALS['crlf']);
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
    
    $head  =  '% phpMyAdmin LaTeX Dump' . $crlf
           .  '% version ' . PMA_VERSION . $crlf
           .  '% http://www.phpmyadmin.net' . $crlf
           .  '%' . $crlf
           .  '% ' . $GLOBALS['strHost'] . ': ' . $cfg['Server']['host'];
    if (!empty($cfg['Server']['port'])) {
         $head .= ':' . $cfg['Server']['port'];
    }
    $head .= $crlf
           .  '% ' . $GLOBALS['strGenTime'] . ': ' . PMA_localisedDate() . $crlf
           .  '% ' . $GLOBALS['strServerVersion'] . ': ' . substr(PMA_MYSQL_INT_VERSION, 0, 1) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 1, 2) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 3) . $crlf
           .  '% ' . $GLOBALS['strPHPVersion'] . ': ' . phpversion() . $crlf;
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
    $head = '% ' . $crlf
          . '% ' . $GLOBALS['strDatabase'] . ': ' . (isset($GLOBALS['use_backquotes']) ? PMA_backquote($db) : '\'' . $db . '\''). $crlf
          . '% ' . $crlf;
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
 * Outputs the content of a table in LaTeX table/sideways table environment
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
    $tex_escape = array("$", "%", "{", "}",  "&",  "#", "_", "^");

    $result      = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $error_url);

    $columns_cnt = mysql_num_fields($result);
    for ($i = 0; $i < $columns_cnt; $i++) {
        $columns[$i] = mysql_field_name($result, $i);
    }
    unset($i);

    $buffer      = $crlf . '%' . $crlf . '% ' . $GLOBALS['strData'] . $crlf . '%' . $crlf
                 . '\\begin{table} ' . $crlf
                 . ' \\begin{longtable}{|';

    for($index=0;$index<$columns_cnt;$index++) {
       $buffer .= 'c|';
    }
    $buffer .= '} ' . $crlf ;

    $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
    if (!PMA_exportOutputHandler($buffer)) return FALSE;
    
    // show column names
    if (isset($GLOBALS['latex_showcolumns'])) {
        $local_buffer = stripslashes(implode("\000", $columns));
        for($k=0;$k<count($tex_escape);$k++) {
            $local_buffer = str_replace($tex_escape[$k], '\\' . $tex_escape[$k], $local_buffer);
        }
        $buffer = str_replace("\000", ' & ', $local_buffer);
        unset($local_buffer);
        $buffer .= ' \\\\ \\hline \\hline' . $crlf;
        if (!PMA_exportOutputHandler($buffer)) return FALSE;
    }

    // print the whole table
    while ($record = PMA_mysql_fetch_array($result, MYSQL_ASSOC)) {

        $buffer = '';
        // print each row
        for($i = 0; $i < $columns_cnt; $i++) {
            if ( isset($record[$columns[$i]]) && (!function_exists('is_null') || !is_null($record[$columns[$i]]))) {
                $column_value = stripslashes($record[$columns[$i]]);

                //    $ % { } & # _ ^
                // escaping special characters
                for($k=0;$k<count($tex_escape);$k++) {
                    $column_value = str_replace($tex_escape[$k], '\\' . $tex_escape[$k], $column_value);
                }
            } else {
                $column_value = $GLOBALS['latex_replace_null'];
            }

            // last column ... no need for & character
            if($i == ($columns_cnt - 1)) {
                $buffer .= $column_value;
            } else {
                $buffer .= $column_value . " & ";
            }
        }
        $buffer .= ' \\\\ \\hline ' . $crlf;
        if (!PMA_exportOutputHandler($buffer)) return FALSE;
    }

    $buffer = ' \\end{longtable} \\end{table}' . $crlf;
    if (!PMA_exportOutputHandler($buffer)) return FALSE;

    mysql_free_result($result);
    return TRUE;

} // end getTableLaTeX

/**
 * Returns $table's structure as LaTeX
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   string   the url to go back in case of error
 * @param   boolean  whether to include relation comments
 * @param   boolean  whether to include column comments
 * @param   boolean  whether to include mime comments
 *
 * @return  bool     Whether it suceeded
 *
 * @access  public
 */
function PMA_exportStructure($db, $table, $crlf, $error_url, $do_relation = false, $do_comments = false, $do_mime = false)
{
    global $cfgRelation;

    $tex_escape = array("$", "%", "{", "}",  "&",  "#", "_", "^");
    /**
     * Gets fields properties
     */
    PMA_mysql_select_db($db);
    $local_query = 'SHOW FIELDS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
    $fields_cnt  = mysql_num_rows($result);

    // Check if we can use Relations (Mike Beck)
    if ($do_relation && !empty($cfgRelation['relation'])) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if ($res_rel && count($res_rel) > 0) {
            $have_rel = TRUE;
        } else {
            $have_rel = FALSE;
        }
    }
    else {
           $have_rel = FALSE;
    } // end if

    /**
     * Displays the table structure
     */
    $buffer      = $crlf . '%' . $crlf . '% ' . $GLOBALS['strStructure'] . $crlf . '%' . $crlf
                 . '\\begin{table} ' . $crlf
                 . ' \\begin{longtable}{|';
    if (!PMA_exportOutputHandler($buffer)) return FALSE;

    $columns_cnt = 4;
    if ($do_relation && $have_rel) {
        $columns_cnt++;
    }
    if ($do_comments && $cfgRelation['commwork']) {
        $columns_cnt++;
    }
    if ($do_mime && $cfgRelation['mimework']) {
        $columns_cnt++;
    }
    $buffer = '';
    for($index=0;$index<$columns_cnt;$index++) {
       $buffer .= 'c|';
    }
    $buffer .= '} ' . $crlf ;

    $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
    
    $buffer .= $GLOBALS['strField'] . ' & ' . $GLOBALS['strType'] . ' & ' . $GLOBALS['strNull'] . ' & ' . $GLOBALS['strDefault'];
    if ($do_relation && $have_rel) {
        $buffer .= ' & ' . $GLOBALS['strLinksTo'];
    }
    if ($do_comments && $cfgRelation['commwork']) {
        $buffer .= ' & ' . $GLOBALS['strComments'];
        $comments = PMA_getComments($db, $table);
    }
    if ($do_mime && $cfgRelation['mimework']) {
        $buffer .= ' & MIME';
        $mime_map = PMA_getMIME($db, $table, true);
    }
    $buffer .= ' \\\\ \\hline \\hline ' . $crlf;
    if (!PMA_exportOutputHandler($buffer)) return FALSE;
    
    while ($row = PMA_mysql_fetch_array($result)) {

        $type             = $row['Type'];
        // reformat mysql query output - staybyte - 9. June 2001
        // loic1: set or enum types: slashes single quotes inside options
        if (eregi('^(set|enum)\((.+)\)$', $type, $tmp)) {
            $tmp[2]       = substr(ereg_replace('([^,])\'\'', '\\1\\\'', ',' . $tmp[2]), 1);
            $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
            $type_nowrap  = '';

            $binary       = 0;
            $unsigned     = 0;
            $zerofill     = 0;
        } else {
            $type_nowrap  = ' nowrap="nowrap"';
            $type         = eregi_replace('BINARY', '', $type);
            $type         = eregi_replace('ZEROFILL', '', $type);
            $type         = eregi_replace('UNSIGNED', '', $type);
            if (empty($type)) {
                $type     = '&nbsp;';
            }

            $binary       = eregi('BINARY', $row['Type'], $test);
            $unsigned     = eregi('UNSIGNED', $row['Type'], $test);
            $zerofill     = eregi('ZEROFILL', $row['Type'], $test);
        }
        $strAttribute     = '&nbsp;';
        if ($binary) {
            $strAttribute = 'BINARY';
        }
        if ($unsigned) {
            $strAttribute = 'UNSIGNED';
        }
        if ($zerofill) {
            $strAttribute = 'UNSIGNED ZEROFILL';
        }
        if (!isset($row['Default'])) {
            if ($row['Null'] != '') {
                $row['Default'] = 'NULL';
            }
        } else {
            $row['Default'] = $row['Default'];
        }
        $field_name = $row['Field'];

        $local_buffer = $field_name . "\000" . $type . "\000" . (($row['Null'] == '') ? $GLOBALS['strNo'] : $GLOBALS['strYes'])  . "\000" . (isset($row['Default']) ? $row['Default'] : '');

        if ($do_relation && $have_rel) {
            $local_buffer .= "\000";
            if (isset($res_rel[$field_name])) {
                $local_buffer .= $res_rel[$field_name]['foreign_table'] . ' (' . $res_rel[$field_name]['foreign_field'] . ')';
            }
        }
        if ($do_comments && $cfgRelation['commwork']) {
            $local_buffer .= "\000";
            if (isset($comments[$field_name])) {
                $local_buffer .= $comments[$field_name];
            }
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $local_buffer .= "\000";
            if (isset($mime_map[$field_name])) {
                $local_buffer .= str_replace('_', '/', $mime_map[$field_name]['mimetype']);
            }
        }
        for($k=0;$k<count($tex_escape);$k++) {
            $local_buffer = str_replace($tex_escape[$k], '\\' . $tex_escape[$k], $local_buffer);
        }
        $buffer = str_replace("\000", ' & ', $local_buffer);
        $buffer .= ' \\\\ \\hline ' . $crlf;
        if (!PMA_exportOutputHandler($buffer)) return FALSE;
    } // end while
    mysql_free_result($result);
    $buffer = ' \\end{longtable} \\end{table}' . $crlf;
    return PMA_exportOutputHandler($buffer);
} // end of the 'PMA_getTableStructureLaTeX()' function
?>
