<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Sample export to Texy! text.
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['texytext'] = array(
        'text' => 'strTexyText',
        'extension' => 'txt',
        'mime_type' => 'text/plain',
        'options' => array(
            array('type' => 'bool',
                'name' => 'structure',
                'text' => 'strStructure',
                'force' => 'data'),
            array('type' => 'bgroup',
                'name' => 'data',
                'text' => 'strData',
                'force' => 'structure'),
            array('type' => 'text',
                'name' => 'null',
                'text' => 'strReplaceNULLBy'),
            array('type' => 'bool',
                'name' => 'columns',
                'text' => 'strPutColNames'),
            array('type' => 'egroup'),
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
    return true;
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader() {
    return true;
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
    return PMA_exportOutputHandler('===' . $GLOBALS['strDatabase'] . ' ' . $db . "\n\n");
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
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
{
    global $what;

    if (!PMA_exportOutputHandler('== ' . $GLOBALS['strDumpingData'] . ' ' . $table . "\n\n")) {
        return FALSE;
    }

    // Gets the data from the database
    $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);

    // If required, get fields name at the first line
    if (isset($GLOBALS[$what . '_columns'])) {
        $text_output = "|------\n";
        for ($i = 0; $i < $fields_cnt; $i++) {
            $text_output .= '|' . htmlspecialchars(stripslashes(PMA_DBI_field_name($result, $i)));
        } // end for
        $text_output .= "\n|------\n";
        if (!PMA_exportOutputHandler($text_output)) {
            return FALSE;
        }
    } // end if

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $text_output = '';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j]) || is_null($row[$j])) {
                $value = $GLOBALS[$what . '_null'];
            } elseif ($row[$j] == '0' || $row[$j] != '') {
                $value = $row[$j];
            } else {
                $value = ' ';
            }
            $text_output .= '|' . htmlspecialchars($value);
        } // end for
        $text_output .= "\n";
        if (!PMA_exportOutputHandler($text_output)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);

    return TRUE;
}

function PMA_exportStructure($db, $table, $crlf, $error_url, $do_relation = false, $do_comments = false, $do_mime = false, $dates = false, $dummy)
{
    global $cfgRelation;

    if (!PMA_exportOutputHandler('== ' . $GLOBALS['strTableStructure'] . ' ' .$table . "\n\n")) {
        return FALSE;
    }

    /**
     * Get the unique keys in the table
     */
    $keys_query     = 'SHOW KEYS FROM ' . PMA_backquote($table) . ' FROM '. PMA_backquote($db);
    $keys_result    = PMA_DBI_query($keys_query);
    $unique_keys    = array();
    while ($key = PMA_DBI_fetch_assoc($keys_result)) {
        if ($key['Non_unique'] == 0) {
            $unique_keys[] = $key['Column_name'];
        }
    }
    PMA_DBI_free_result($keys_result);

    /**
     * Gets fields properties
     */
    PMA_DBI_select_db($db);
    $local_query = 'SHOW FIELDS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
    $result      = PMA_DBI_query($local_query);
    $fields_cnt  = PMA_DBI_num_rows($result);

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
    } else {
           $have_rel = FALSE;
    } // end if

    /**
     * Displays the table structure
     */

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

    $text_output = "|------\n";
    $text_output .= '|' . htmlspecialchars($GLOBALS['strField']);
    $text_output .= '|' . htmlspecialchars($GLOBALS['strType']);
    $text_output .= '|' . htmlspecialchars($GLOBALS['strNull']);
    $text_output .= '|' . htmlspecialchars($GLOBALS['strDefault']);
    if ($do_relation && $have_rel) {
        $text_output .= '|' . htmlspecialchars($GLOBALS['strLinksTo']);
    }
    if ($do_comments) {
        $text_output .= '|' . htmlspecialchars($GLOBALS['strComments']);
        $comments = PMA_getComments($db, $table);
    }
    if ($do_mime && $cfgRelation['mimework']) {
        $text_output .= '|' . htmlspecialchars('MIME');
        $mime_map = PMA_getMIME($db, $table, true);
    }
    $text_output .= "\n|------\n";

    if (!PMA_exportOutputHandler($text_output)) {
        return FALSE;
    }

    while ($row = PMA_DBI_fetch_assoc($result)) {

        $text_output = '';
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

            $binary       = eregi('BINARY', $row['Type']);
            $unsigned     = eregi('UNSIGNED', $row['Type']);
            $zerofill     = eregi('ZEROFILL', $row['Type']);
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

        $fmt_pre = '';
        $fmt_post = '';
        if (in_array($row['Field'], $unique_keys)) {
            $fmt_pre = '**' . $fmt_pre;
            $fmt_post = $fmt_post . '**';
        }
        if ($row['Key']=='PRI') {
            $fmt_pre = '//' . $fmt_pre;
            $fmt_post = $fmt_post . '//';
        }
        $text_output .= '|' . $fmt_pre . htmlspecialchars($row['Field']) . $fmt_post;
        $text_output .= '|' . htmlspecialchars($type);
        $text_output .= '|' . htmlspecialchars($row['Null'] == '' ? $GLOBALS['strNo'] : $GLOBALS['strYes']);
        $text_output .= '|' . htmlspecialchars(isset($row['Default']) ? $row['Default'] : '');

        $field_name = $row['Field'];

        if ($do_relation && $have_rel) {
            $text_output .= '|' . (isset($res_rel[$field_name]) ? htmlspecialchars($res_rel[$field_name]['foreign_table'] . ' (' . $res_rel[$field_name]['foreign_field'] . ')') : '');
        }
        if ($do_comments && $cfgRelation['commwork']) {
            $text_output .= '|' . (isset($comments[$field_name]) ? htmlspecialchars($comments[$field_name]) : '');
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $text_output .= '|' . (isset($mime_map[$field_name]) ? htmlspecialchars(str_replace('_', '/', $mime_map[$field_name]['mimetype'])) : '');
        }

        $text_output .= "\n";

        if (!PMA_exportOutputHandler($text_output)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);

    return true;
}

}
?>
