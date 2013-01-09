<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build LaTeX dumps of tables
 *
 * @package PhpMyAdmin-Export
 * @subpackage Latex
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Messages used in default captions */
$GLOBALS['strLatexContent'] = __('Content of table @TABLE@');
$GLOBALS['strLatexContinued'] = __('(continued)');
$GLOBALS['strLatexStructure'] = __('Structure of table @TABLE@');

/**
 *
 */
if (isset($plugin_list)) {
    $hide_structure = false;
    if ($plugin_param['export_type'] == 'table' && ! $plugin_param['single_table']) {
        $hide_structure = true;
    }
    $plugin_list['latex'] = array(
        'text' => __('LaTeX'),
        'extension' => 'tex',
        'mime_type' => 'application/x-tex',
        'options' => array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array('type' => 'bool', 'name' => 'caption', 'text' => __('Include table caption')),
            array('type' => 'end_group')
            ),
        'options_text' => __('Options'),
        );

    /* what to dump (structure/data/both) */
    $plugin_list['latex']['options'][]
        = array('type' => 'begin_group', 'name' => 'dump_what', 'text' => __('Dump table'));
    $plugin_list['latex']['options'][]
        = array('type' => 'radio', 'name' => 'structure_or_data', 'values' => array('structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')));
    $plugin_list['latex']['options'][] = array('type' => 'end_group');

    /* Structure options */
    if (! $hide_structure) {
        $plugin_list['latex']['options'][]
            = array('type' => 'begin_group', 'name' => 'structure', 'text' => __('Object creation options'), 'force' => 'data');
        $plugin_list['latex']['options'][]
            = array('type' => 'text', 'name' => 'structure_caption', 'text' => __('Table caption'), 'doc' => 'faq6_27');
        $plugin_list['latex']['options'][]
            = array('type' => 'text', 'name' => 'structure_continued_caption', 'text' => __('Table caption (continued)'), 'doc' => 'faq6_27');
        $plugin_list['latex']['options'][]
            = array('type' => 'text', 'name' => 'structure_label', 'text' => __('Label key'), 'doc' => 'faq6_27');
        if (! empty($GLOBALS['cfgRelation']['relation'])) {
            $plugin_list['latex']['options'][]
                = array('type' => 'bool', 'name' => 'relation', 'text' => __('Display foreign key relationships'));
        }
        $plugin_list['latex']['options'][]
            = array('type' => 'bool', 'name' => 'comments', 'text' => __('Display comments'));
        if (! empty($GLOBALS['cfgRelation']['mimework'])) {
            $plugin_list['latex']['options'][]
                = array('type' => 'bool', 'name' => 'mime', 'text' => __('Display MIME types'));
        }
        $plugin_list['latex']['options'][]
            = array('type' => 'end_group');
    }
    /* Data */
    $plugin_list['latex']['options'][]
        = array('type' => 'begin_group', 'name' => 'data', 'text' => __('Data dump options'), 'force' => 'structure');
    $plugin_list['latex']['options'][]
        = array('type' => 'bool', 'name' => 'columns', 'text' => __('Put columns names in the first row'));
    $plugin_list['latex']['options'][]
        = array('type' => 'text', 'name' => 'data_caption', 'text' => __('Table caption'), 'doc' => 'faq6_27');
    $plugin_list['latex']['options'][]
        = array('type' => 'text', 'name' => 'data_continued_caption', 'text' => __('Table caption (continued)'), 'doc' => 'faq6_27');
    $plugin_list['latex']['options'][]
        = array('type' => 'text', 'name' => 'data_label', 'text' => __('Label key'), 'doc' => 'faq6_27');
    $plugin_list['latex']['options'][]
        = array('type' => 'text', 'name' => 'null', 'text' => __('Replace NULL with:'));
    $plugin_list['latex']['options'][]
        = array('type' => 'end_group');
} else {

    /**
     * Escapes some special characters for use in TeX/LaTeX
     *
     * @param string $string the string to convert
     *
     * @return  string      the converted string with escape codes
     *
     * @access  private
     */
    function PMA_texEscape($string)
    {
        $escape = array('$', '%', '{', '}',  '&',  '#', '_', '^');
        $cnt_escape = count($escape);
        for ($k=0; $k < $cnt_escape; $k++) {
            $string = str_replace($escape[$k], '\\' . $escape[$k], $string);
        }
        return $string;
    }

    /**
     * Outputs export footer
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportFooter()
    {
        return true;
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    function PMA_exportHeader()
    {
        global $crlf;
        global $cfg;

        $head  =  '% phpMyAdmin LaTeX Dump' . $crlf
               .  '% version ' . PMA_VERSION . $crlf
               .  '% http://www.phpmyadmin.net' . $crlf
               .  '%' . $crlf
               .  '% ' . __('Host') . ': ' . $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
             $head .= ':' . $cfg['Server']['port'];
        }
        $head .= $crlf
               .  '% ' . __('Generation Time') . ': ' . PMA_localisedDate() . $crlf
               .  '% ' . __('Server version') . ': ' . PMA_MYSQL_STR_VERSION . $crlf
               .  '% ' . __('PHP Version') . ': ' . phpversion() . $crlf;
        return PMA_exportOutputHandler($head);
    }

    /**
     * Outputs database header
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    function PMA_exportDBHeader($db)
    {
        global $crlf;
        $head = '% ' . $crlf
              . '% ' . __('Database') . ': ' . '\'' . $db . '\'' . $crlf
              . '% ' . $crlf;
        return PMA_exportOutputHandler($head);
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    function PMA_exportDBFooter($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    function PMA_exportDBCreate($db)
    {
        return true;
    }

    /**
     * Outputs the content of a table in LaTeX table/sideways table environment
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        $result      = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);

        $columns_cnt = PMA_DBI_num_fields($result);
        for ($i = 0; $i < $columns_cnt; $i++) {
            $columns[$i] = PMA_DBI_field_name($result, $i);
        }
        unset($i);

        $buffer      = $crlf . '%' . $crlf . '% ' . __('Data') . ': ' . $table
            . $crlf . '%' . $crlf . ' \\begin{longtable}{|';

        for ($index = 0; $index < $columns_cnt; $index++) {
            $buffer .= 'l|';
        }
        $buffer .= '} ' . $crlf ;

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . PMA_expandUserString(
                    $GLOBALS['latex_data_caption'],
                    'PMA_texEscape',
                    array('table' => $table, 'database' => $db)
                )
                . '} \\label{'
                . PMA_expandUserString(
                    $GLOBALS['latex_data_label'],
                    null,
                    array('table' => $table, 'database' => $db)
                )
                . '} \\\\';
        }
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        // show column names
        if (isset($GLOBALS['latex_columns'])) {
            $buffer = '\\hline ';
            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= '\\multicolumn{1}{|c|}{\\textbf{'
                    . PMA_texEscape(stripslashes($columns[$i])) . '}} & ';
            }

            $buffer = substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
            if (! PMA_exportOutputHandler($buffer . ' \\endfirsthead ' . $crlf)) {
                return false;
            }
            if (isset($GLOBALS['latex_caption'])) {
                if (! PMA_exportOutputHandler(
                    '\\caption{'
                    . PMA_expandUserString(
                        $GLOBALS['latex_data_continued_caption'],
                        'PMA_texEscape',
                        array('table' => $table, 'database' => $db)
                    )
                    . '} \\\\ '
                )) {
                    return false;
                }
            }
            if (! PMA_exportOutputHandler($buffer . '\\endhead \\endfoot' . $crlf)) {
                return false;
            }
        } else {
            if (! PMA_exportOutputHandler('\\\\ \hline')) {
                return false;
            }
        }

        // print the whole table
        while ($record = PMA_DBI_fetch_assoc($result)) {

            $buffer = '';
            // print each row
            for ($i = 0; $i < $columns_cnt; $i++) {
                if (isset($record[$columns[$i]])
                    && (! function_exists('is_null') || ! is_null($record[$columns[$i]]))
                ) {
                    $column_value = PMA_texEscape(stripslashes($record[$columns[$i]]));
                } else {
                    $column_value = $GLOBALS['latex_null'];
                }

                // last column ... no need for & character
                if ($i == ($columns_cnt - 1)) {
                    $buffer .= $column_value;
                } else {
                    $buffer .= $column_value . " & ";
                }
            }
            $buffer .= ' \\\\ \\hline ' . $crlf;
            if (! PMA_exportOutputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . $crlf;
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        PMA_DBI_free_result($result);
        return true;

    } // end getTableLaTeX

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $error_url   the url to go back in case of error
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column comments
     *                            as comments in the structure; this is deprecated
     *                            but the parameter is left here because export.php
     *                            calls PMA_exportStructure() also for other export
     *                            types which use this parameter
     * @param bool   $do_mime     whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     * @param string $export_mode 'create_table', 'triggers', 'create_view', 'stand_in'
     * @param string $export_type 'server', 'database', 'table'
     *
     * @return bool Whether it succeeded
     *
     * @access public
     */
    function PMA_exportStructure($db, $table, $crlf, $error_url, $do_relation = false, $do_comments = false, $do_mime = false, $dates = false, $export_mode, $export_type)
    {
        global $cfgRelation;

        /* We do not export triggers */
        if ($export_mode == 'triggers') {
            return true;
        }

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
        $buffer      = $crlf . '%' . $crlf . '% ' . __('Structure') . ': ' . $table
            . $crlf . '%' . $crlf . ' \\begin{longtable}{';
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        $columns_cnt = 4;
        $alignment = '|l|c|c|c|';
        if ($do_relation && $have_rel) {
            $columns_cnt++;
            $alignment .= 'l|';
        }
        if ($do_comments) {
            $columns_cnt++;
            $alignment .= 'l|';
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $columns_cnt++;
            $alignment .='l|';
        }
        $buffer = $alignment . '} ' . $crlf ;

        $header = ' \\hline ';
        $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
        if ($do_relation && $have_rel) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
        }
        if ($do_comments) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
            $comments = PMA_getComments($db, $table);
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
            $mime_map = PMA_getMIME($db, $table, true);
        }

        // Table caption for first page and label
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . PMA_expandUserString(
                    $GLOBALS['latex_structure_caption'],
                    'PMA_texEscape',
                    array('table' => $table, 'database' => $db)
                )
                . '} \\label{'
                . PMA_expandUserString(
                    $GLOBALS['latex_structure_label'],
                    null,
                    array('table' => $table, 'database' => $db)
                )
                . '} \\\\' . $crlf;
        }
        $buffer .= $header . ' \\\\ \\hline \\hline' . $crlf . '\\endfirsthead' . $crlf;
        // Table caption on next pages
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . PMA_expandUserString(
                    $GLOBALS['latex_structure_continued_caption'],
                    'PMA_texEscape',
                    array('table' => $table, 'database' => $db)
                )
                . '} \\\\ ' . $crlf;
        }
        $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . $crlf;

        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        $fields = PMA_DBI_get_columns($db, $table);
        foreach ($fields as $row) {
            $extracted_fieldspec = PMA_extractFieldSpec($row['Type']);
            $type = $extracted_fieldspec['print_type'];
            if (empty($type)) {
                $type     = ' ';
            }

            if (! isset($row['Default'])) {
                if ($row['Null'] != 'NO') {
                    $row['Default'] = 'NULL';
                }
            }

            $field_name = $row['Field'];

            $local_buffer = $field_name . "\000" . $type . "\000"
                . (($row['Null'] == '' || $row['Null'] == 'NO') ? __('No') : __('Yes'))
                . "\000" . (isset($row['Default']) ? $row['Default'] : '');

            if ($do_relation && $have_rel) {
                $local_buffer .= "\000";
                if (isset($res_rel[$field_name])) {
                    $local_buffer .= $res_rel[$field_name]['foreign_table'] . ' ('
                        . $res_rel[$field_name]['foreign_field'] . ')';
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
            $local_buffer = PMA_texEscape($local_buffer);
            if ($row['Key']=='PRI') {
                $pos=strpos($local_buffer, "\000");
                $local_buffer = '\\textit{' . substr($local_buffer, 0, $pos) . '}' . substr($local_buffer, $pos);
            }
            if (in_array($field_name, $unique_keys)) {
                $pos=strpos($local_buffer, "\000");
                $local_buffer = '\\textbf{' . substr($local_buffer, 0, $pos) . '}' . substr($local_buffer, $pos);
            }
            $buffer = str_replace("\000", ' & ', $local_buffer);
            $buffer .= ' \\\\ \\hline ' . $crlf;

            if (! PMA_exportOutputHandler($buffer)) {
                return false;
            }
        } // end while

        $buffer = ' \\end{longtable}' . $crlf;
        return PMA_exportOutputHandler($buffer);
    } // end of the 'PMA_exportStructure' function

} // end else
?>
