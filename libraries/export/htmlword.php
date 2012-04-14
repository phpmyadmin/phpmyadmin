<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to export a set of queries to a MS Word document
 *
 * @package PhpMyAdmin-Export
 * @subpackage HTMLWord
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
     * Outputs export footer
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportFooter()
    {
        return PMA_exportOutputHandler('</body></html>');
    }

    /**
     * Outputs export header
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportHeader()
    {
        global $charset_of_file;
        return PMA_exportOutputHandler(
            '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset=' . (isset($charset_of_file) ? $charset_of_file : 'utf-8') . '" />
            </head>
            <body>'
        );
    }

    /**
     * Outputs database header
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBHeader($db)
    {
        return PMA_exportOutputHandler('<h1>' . __('Database') . ' ' . htmlspecialchars($db) . '</h1>');
    }

    /**
     * Outputs database footer
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBFooter($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBCreate($db)
    {
        return true;
    }

    /**
     * Outputs the content of a table in HTML (Microsoft Word) format
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $crlf       the end of line sequence
     * @param string  $error_url  the url to go back in case of error
     * @param string  $sql_query  SQL query for obtaining data
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        global $what;

        if (! PMA_exportOutputHandler('<h2>' . __('Dumping data for table') . ' ' . htmlspecialchars($table) . '</h2>')) {
            return false;
        }
        if (! PMA_exportOutputHandler('<table class="width100" cellspacing="1">')) {
            return false;
        }

        // Gets the data from the database
        $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $fields_cnt  = PMA_DBI_num_fields($result);

        // If required, get fields name at the first line
        if (isset($GLOBALS['htmlword_columns'])) {
            $schema_insert = '<tr class="print-category">';
            for ($i = 0; $i < $fields_cnt; $i++) {
                $schema_insert .= '<td class="print"><strong>' . htmlspecialchars(stripslashes(PMA_DBI_field_name($result, $i))) . '</strong></td>';
            } // end for
            $schema_insert .= '</tr>';
            if (! PMA_exportOutputHandler($schema_insert)) {
                return false;
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
                return false;
            }
        } // end while
        PMA_DBI_free_result($result);
        if (! PMA_exportOutputHandler('</table>')) {
            return false;
        }

        return true;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db   the database name
     * @param string $view the view name
     * @param string $crlf the end of line sequence
     *
     * @return string resulting definition
     *
     * @access public
     */
    function PMA_getTableDefStandIn($db, $view, $crlf)
    {
        $schema_insert = '<table class="width100" cellspacing="1">';

        $columns_cnt = 4;
        $schema_insert .= '<tr class="print-category">';
        $schema_insert .= '<th class="print">' . __('Column') . '</th>';
        $schema_insert .= '<td class="print"><strong>' . __('Type') . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>' . __('Null') . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>' . __('Default') . '</strong></td>';
        $schema_insert .= '</tr>';


        $columns = PMA_DBI_get_columns($db, $view);
        foreach ($columns as $column) {
            $schema_insert .= '<tr class="print-category">';

            $extracted_fieldspec = PMA_extractFieldSpec($column['Type']);
            $type = htmlspecialchars($extracted_fieldspec['print_type']);
            if (empty($type)) {
                $type     = '&nbsp;';
            }

            if (! isset($column['Default'])) {
                if ($column['Null'] != 'NO') {
                    $column['Default'] = 'NULL';
                }
            }

            $fmt_pre = '';
            $fmt_post = '';
            if (in_array($column['Field'], $unique_keys)) {
                $fmt_pre = '<strong>' . $fmt_pre;
                $fmt_post = $fmt_post . '</strong>';
            }
            if ($column['Key'] == 'PRI') {
                $fmt_pre = '<em>' . $fmt_pre;
                $fmt_post = $fmt_post . '</em>';
            }
            $schema_insert .= '<td class="print">' . $fmt_pre . htmlspecialchars($column['Field']) . $fmt_post . '</td>';
            $schema_insert .= '<td class="print">' . htmlspecialchars($type) . '</td>';
            $schema_insert .= '<td class="print">' . (($column['Null'] == '' || $column['Null'] == 'NO') ? __('No') : __('Yes')) . '</td>';
            $schema_insert .= '<td class="print">' . htmlspecialchars(isset($column['Default']) ? $column['Default'] : '') . '</td>';
            $schema_insert .= '</tr>';
        }

        $schema_insert .= '</table>';
        return $schema_insert;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string $db            the database name
     * @param string $table         the table name
     * @param string $crlf          the end of line sequence
     * @param string $error_url     the url to go back in case of error
     * @param bool   $do_relation   whether to include relation comments
     * @param bool   $do_comments   whether to include the pmadb-style column comments
     *                                as comments in the structure; this is deprecated
     *                                but the parameter is left here because export.php
     *                                calls PMA_exportStructure() also for other export
     *                                types which use this parameter
     * @param bool   $do_mime       whether to include mime comments
     * @param bool   $show_dates    whether to include creation/update/check dates
     * @param bool   $add_semicolon whether to add semicolon and end-of-line at the end
     * @param bool   $view          whether we're handling a view
     *
     * @return string resulting schema
     *
     * @access public
     */
    function PMA_getTableDef($db, $table, $crlf, $error_url, $do_relation, $do_comments, $do_mime, $show_dates = false, $add_semicolon = true, $view = false)
    {
        global $cfgRelation;

        $schema_insert = '';

        /**
         * Get the unique keys in the table
         */
        $unique_keys = array();
        $keys        = PMA_DBI_get_table_indexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] == 0) {
                $unique_keys[] = $key['Column_name'];
            }
        }

        /**
         * Gets fields properties
         */
        PMA_DBI_select_db($db);

        // Check if we can use Relations
        if ($do_relation && ! empty($cfgRelation['relation'])) {
            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = PMA_getForeigners($db, $table);

            if ($res_rel && count($res_rel) > 0) {
                $have_rel = true;
            } else {
                $have_rel = false;
            }
        } else {
               $have_rel = false;
        } // end if

        /**
         * Displays the table structure
         */
        $schema_insert .= '<table class="width100" cellspacing="1">';

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

        $schema_insert .= '<tr class="print-category">';
        $schema_insert .= '<th class="print">' . __('Column') . '</th>';
        $schema_insert .= '<td class="print"><strong>' . __('Type') . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>' . __('Null') . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>' . __('Default') . '</strong></td>';
        if ($do_relation && $have_rel) {
            $schema_insert .= '<td class="print"><strong>' . __('Links to') . '</strong></td>';
        }
        if ($do_comments) {
            $schema_insert .= '<td class="print"><strong>' . __('Comments') . '</strong></td>';
            $comments = PMA_getComments($db, $table);
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $schema_insert .= '<td class="print"><strong>' . htmlspecialchars('MIME') . '</strong></td>';
            $mime_map = PMA_getMIME($db, $table, true);
        }
        $schema_insert .= '</tr>';

        $columns = PMA_DBI_get_columns($db, $table);
        foreach ($columns as $column) {

            $schema_insert .= '<tr class="print-category">';

            $extracted_fieldspec = PMA_extractFieldSpec($column['Type']);
            $type = htmlspecialchars($extracted_fieldspec['print_type']);
            if (empty($type)) {
                $type     = '&nbsp;';
            }

            if (! isset($column['Default'])) {
                if ($column['Null'] != 'NO') {
                    $column['Default'] = 'NULL';
                }
            }

            $fmt_pre = '';
            $fmt_post = '';
            if (in_array($column['Field'], $unique_keys)) {
                $fmt_pre = '<strong>' . $fmt_pre;
                $fmt_post = $fmt_post . '</strong>';
            }
            if ($column['Key'] == 'PRI') {
                $fmt_pre = '<em>' . $fmt_pre;
                $fmt_post = $fmt_post . '</em>';
            }
            $schema_insert .= '<td class="print">' . $fmt_pre . htmlspecialchars($column['Field']) . $fmt_post . '</td>';
            $schema_insert .= '<td class="print">' . htmlspecialchars($type) . '</td>';
            $schema_insert .= '<td class="print">' . (($column['Null'] == '' || $column['Null'] == 'NO') ? __('No') : __('Yes')) . '</td>';
            $schema_insert .= '<td class="print">' . htmlspecialchars(isset($column['Default']) ? $column['Default'] : '') . '</td>';

            $field_name = $column['Field'];

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
        } // end while

        $schema_insert .= '</table>';
        return $schema_insert;
    } // end of the 'PMA_getTableDef()' function

    /**
     * Outputs triggers
     *
     * @param string $db     database name
     * @param string $table  table name
     * @return string        Formatted triggers list
     *
     * @access public
     */
    function PMA_getTriggers($db, $table)
    {
        $dump = '<table class="width100" cellspacing="1">';
        $dump .= '<tr class="print-category">';
        $dump .= '<th class="print">' . __('Name') . '</th>';
        $dump .= '<td class="print"><strong>' . __('Time') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Event') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Definition') . '</strong></td>';
        $dump .= '</tr>';

        $triggers = PMA_DBI_get_triggers($db, $table);

        foreach($triggers as $trigger) {
            $dump .= '<tr class="print-category">';
            $dump .= '<td class="print">' . htmlspecialchars($trigger['name']) . '</td>';
            $dump .= '<td class="print">' . htmlspecialchars($trigger['action_timing']) . '</td>';
            $dump .= '<td class="print">' . htmlspecialchars($trigger['event_manipulation']) . '</td>';
            $dump .= '<td class="print">' . htmlspecialchars($trigger['definition']) . '</td>';
            $dump .= '</tr>';
        }

        $dump .= '</table>';
        return $dump;
    }

    /**
     * Outputs table's structure
     *
     * @param string  $db           database name
     * @param string  $table        table name
     * @param string  $crlf         the end of line sequence
     * @param string  $error_url    the url to go back in case of error
     * @param bool    $do_relation  whether to include relation comments
     * @param bool    $do_comments  whether to include the pmadb-style column comments
     *                                as comments in the structure; this is deprecated
     *                                but the parameter is left here because export.php
     *                                calls PMA_exportStructure() also for other export
     *                                types which use this parameter
     * @param bool    $do_mime      whether to include mime comments
     * @param bool    $dates        whether to include creation/update/check dates
     * @param string  $export_mode  'create_table', 'triggers', 'create_view', 'stand_in'
     * @param string  $export_type  'server', 'database', 'table'
     * @return  bool      Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportStructure($db, $table, $crlf, $error_url, $do_relation = false, $do_comments = false, $do_mime = false, $dates = false, $export_mode, $export_type)
    {
        $dump = '';

        switch($export_mode) {
        case 'create_table':
            $dump .= '<h2>' . __('Table structure for table') . ' ' . htmlspecialchars($table) . '</h2>';
            $dump .= PMA_getTableDef($db, $table, $crlf, $error_url, $do_relation, $do_comments, $do_mime, $dates);
            break;
        case 'triggers':
            $dump = '';
            $triggers = PMA_DBI_get_triggers($db, $table);
            if ($triggers) {
                $dump .= '<h2>' . __('Triggers') . ' ' . htmlspecialchars($table) . '</h2>';
                $dump .= PMA_getTriggers($db, $table);
            }
            break;
        case 'create_view':
            $dump .= '<h2>' . __('Structure for view') . ' ' . htmlspecialchars($table) . '</h2>';
            $dump .= PMA_getTableDef($db, $table, $crlf, $error_url, $do_relation, $do_comments, $do_mime, $dates, true, true);
            break;
        case 'stand_in':
            $dump .=  '<h2>' . __('Stand-in structure for view') . ' ' . htmlspecialchars($table) . '</h2>';
            // export a stand-in definition to resolve view dependencies
            $dump .= PMA_getTableDefStandIn($db, $table, $crlf);
        } // end switch

        return PMA_exportOutputHandler($dump);
    }

}
?>
