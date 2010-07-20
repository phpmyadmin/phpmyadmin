<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build CSV dumps of tables
 *
 * @package phpMyAdmin-Export-HTMLWord
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['htmlword'] = array(
        'text' => __('Microsoft Word 2000'),
        'extension' => 'doc',
        'mime_type' => 'application/vnd.ms-word',
        'force_file' => true,
        'options' => array(
            /* what to dump (structure/data/both) */
            array('type' => 'begin_group', 'name' => 'dump_what', 'text' => __('Dump table')),
            array('type' => 'radio', 'name' => 'structure_or_data', 'values' => array('structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data'))),
            array('type' => 'end_group'),
            /* data options */
            array('type' => 'begin_group', 'name' => 'data', 'text' => __('Data dump options'), 'force' => 'structure'),
            array('type' => 'text', 'name' => 'null', 'text' => __('Replace NULL with:')),
            array('type' => 'bool', 'name' => 'columns', 'text' => __('Put columns names in the first row')),
            array('type' => 'end_group'),
            ),
        'options_text' => __('Options'),
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
    return PMA_exportOutputHandler('</body></html>');
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
    return PMA_exportOutputHandler('<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-type" content="text/html;charset=' . (isset($charset_of_file) ? $charset_of_file : $charset) . '" />
</head>
<body>');
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
    return PMA_exportOutputHandler('<h1>' . __('Database') . ' ' . $db . '</h1>');
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

    if (! PMA_exportOutputHandler('<h2>' . __('Dumping data for table') . ' ' . $table . '</h2>')) {
        return FALSE;
    }
    if (! PMA_exportOutputHandler('<table class="width100" cellspacing="1">')) {
        return FALSE;
    }

    // Gets the data from the database
    $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    $fields_cnt  = PMA_DBI_num_fields($result);

    // If required, get fields name at the first line
    if (isset($GLOBALS['htmlword_columns'])) {
        $schema_insert = '<tr class="print-category">';
        for ($i = 0; $i < $fields_cnt; $i++) {
            $schema_insert .= '<td class="print"><b>' . htmlspecialchars(stripslashes(PMA_DBI_field_name($result, $i))) . '</b></td>';
        } // end for
        $schema_insert .= '</tr>';
        if (! PMA_exportOutputHandler($schema_insert)) {
            return FALSE;
        }
    } // end if

    // Format the data
    while ($row = PMA_DBI_fetch_row($result)) {
        $schema_insert = '<tr class="print-category">';
        for ($j = 0; $j < $fields_cnt; $j++) {
            if (! isset($row[$j]) || is_null($row[$j])) {
                $value = $GLOBALS[$what . '_null'];
            } elseif ($row[$j] == '0' || $row[$j] != '') {
                $value = $row[$j];
            } else {
                $value = '';
            }
            $schema_insert .= '<td class="print">' . htmlspecialchars($value) . '</td>';
        } // end for
        $schema_insert .= '</tr>';
        if (! PMA_exportOutputHandler($schema_insert)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);
    if (! PMA_exportOutputHandler('</table>')) {
        return FALSE;
    }

    return TRUE;
}

function PMA_exportStructure($db, $table, $crlf, $error_url, $do_relation = false, $do_comments = false, $do_mime = false, $dates = false, $dummy)
{
    global $cfgRelation;

    if (! PMA_exportOutputHandler('<h2>' . __('Table structure for table') . ' ' .$table . '</h2>')) {
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
    if ($do_relation && ! empty($cfgRelation['relation'])) {
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
    if (! PMA_exportOutputHandler('<table class="width100" cellspacing="1">')) {
        return FALSE;
    }

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

    $schema_insert = '<tr class="print-category">';
    $schema_insert .= '<th class="print">' . htmlspecialchars(__('Column')) . '</th>';
    $schema_insert .= '<td class="print"><b>' . htmlspecialchars(__('Type')) . '</b></td>';
    $schema_insert .= '<td class="print"><b>' . htmlspecialchars(__('Null')) . '</b></td>';
    $schema_insert .= '<td class="print"><b>' . htmlspecialchars(__('Default')) . '</b></td>';
    if ($do_relation && $have_rel) {
        $schema_insert .= '<td class="print"><b>' . htmlspecialchars(__('Links to')) . '</b></td>';
    }
    if ($do_comments) {
        $schema_insert .= '<td class="print"><b>' . htmlspecialchars(__('Comments')) . '</b></td>';
        $comments = PMA_getComments($db, $table);
    }
    if ($do_mime && $cfgRelation['mimework']) {
        $schema_insert .= '<td class="print"><b>' . htmlspecialchars('MIME') . '</b></td>';
        $mime_map = PMA_getMIME($db, $table, true);
    }
    $schema_insert .= '</tr>';

    if (! PMA_exportOutputHandler($schema_insert)) {
        return FALSE;
    }

    while ($row = PMA_DBI_fetch_assoc($result)) {

        $schema_insert = '<tr class="print-category">';
        $type          = $row['Type'];
        // reformat mysql query output
        // set or enum types: slashes single quotes inside options
        if (preg_match('/^(set|enum)\((.+)\)$/i', $type, $tmp)) {
            $tmp[2]       = substr(preg_replace('/([^,])\'\'/', '\\1\\\'', ',' . $tmp[2]), 1);
            $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
            $type_nowrap  = '';

            $binary       = 0;
            $unsigned     = 0;
            $zerofill     = 0;
        } else {
            $type_nowrap  = ' nowrap="nowrap"';
            $type         = preg_replace('/BINARY/i', '', $type);
            $type         = preg_replace('/ZEROFILL/i', '', $type);
            $type         = preg_replace('/UNSIGNED/i', '', $type);
            if (empty($type)) {
                $type     = '&nbsp;';
            }

            $binary       = preg_match('/BINARY/i', $row['Type']);
            $unsigned     = preg_match('/UNSIGNED/i', $row['Type']);
            $zerofill     = preg_match('/ZEROFILL/i', $row['Type']);
        }
        $attribute     = '&nbsp;';
        if ($binary) {
            $attribute = 'BINARY';
        }
        if ($unsigned) {
            $attribute = 'UNSIGNED';
        }
        if ($zerofill) {
            $attribute = 'UNSIGNED ZEROFILL';
        }
        if (! isset($row['Default'])) {
            if ($row['Null'] != 'NO') {
                $row['Default'] = 'NULL';
            }
        } else {
            $row['Default'] = $row['Default'];
        }

        $fmt_pre = '';
        $fmt_post = '';
        if (in_array($row['Field'], $unique_keys)) {
            $fmt_pre = '<b>' . $fmt_pre;
            $fmt_post = $fmt_post . '</b>';
        }
        if ($row['Key'] == 'PRI') {
            $fmt_pre = '<i>' . $fmt_pre;
            $fmt_post = $fmt_post . '</i>';
        }
        $schema_insert .= '<td class="print">' . $fmt_pre . htmlspecialchars($row['Field']) . $fmt_post . '</td>';
        $schema_insert .= '<td class="print">' . htmlspecialchars($type) . '</td>';
        $schema_insert .= '<td class="print">' . htmlspecialchars(($row['Null'] == '' || $row['Null'] == 'NO') ? __('No') : __('Yes')) . '</td>';
        $schema_insert .= '<td class="print">' . htmlspecialchars(isset($row['Default']) ? $row['Default'] : '') . '</td>';

        $field_name = $row['Field'];

        if ($do_relation && $have_rel) {
            $schema_insert .= '<td class="print">' . (isset($res_rel[$field_name]) ? htmlspecialchars($res_rel[$field_name]['foreign_table'] . ' (' . $res_rel[$field_name]['foreign_field'] . ')') : '') . '</td>';
        }
        if ($do_comments && $cfgRelation['commwork']) {
            $schema_insert .= '<td class="print">' . (isset($comments[$field_name]) ? htmlspecialchars($comments[$field_name]) : '') . '</td>';
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $schema_insert .= '<td class="print">' . (isset($mime_map[$field_name]) ? htmlspecialchars(str_replace('_', '/', $mime_map[$field_name]['mimetype'])) : '') . '</td>';
        }

        $schema_insert .= '</tr>';

        if (! PMA_exportOutputHandler($schema_insert)) {
            return FALSE;
        }
    } // end while
    PMA_DBI_free_result($result);

    return PMA_exportOutputHandler('</table>');
}

}
?>
