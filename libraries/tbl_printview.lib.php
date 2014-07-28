<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to show table print view
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * return html for tables' info
 *
 * @param array $the_tables selected tables
 *
 * @return string
 */
function PMA_getHtmlForTablesInfo($the_tables)
{
    $html = '';
    $multi_tables     = (count($the_tables) > 1);

    if ($multi_tables) {
        $tbl_list     = '';
        foreach ($the_tables as $table) {
            $tbl_list .= (empty($tbl_list) ? '' : ', ')
                      . PMA_Util::backquote($table);
        }
        $html .= '<strong>' .  __('Showing tables:') . ' '
            . htmlspecialchars($tbl_list) . '</strong>' . "\n";
        $html .= '<hr />' . "\n";
    } // end if

    return $html;
}


/**
 * return html for print view footer
 *
 * @return string
 */
function PMA_getHtmlForPrintViewFooter()
{
    $html  = PMA_Util::getButton();
    $html .= "<div id='PMA_disable_floating_menubar'></div>\n";

    return $html;
}

/**
 * return html for Print View Columns
 *
 * @param array  $columns      columns list
 * @param array  $analyzed_sql analyzed sql
 * @param array  $pk_array     primary key array
 * @param bool   $have_rel     have relation?
 * @param array  $res_rel      relations array
 * @param string $db           database name
 * @param string $table        table name
 * @param array  $cfgRelation  config from PMA_getRelationsParam
 *
 * @return string
 */
function PMA_getHtmlForPrintViewColumns(
    $columns, $analyzed_sql, $pk_array, $have_rel,
    $res_rel, $db, $table, $cfgRelation
) {
    $html = '';
    foreach ($columns as $row) {
        $extracted_columnspec = PMA_Util::extractColumnSpec($row['Type']);
        $type = $extracted_columnspec['print_type'];

        if (! isset($row['Default'])) {
            if ($row['Null'] != ''  && $row['Null'] != 'NO') {
                $row['Default'] = '<i>NULL</i>';
            }
        } else {
            $row['Default'] = htmlspecialchars($row['Default']);
        }
        $field_name = htmlspecialchars($row['Field']);

        // here, we have a TIMESTAMP that SHOW FULL COLUMNS reports as having the
        // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
        // the latter.
        /**
         * @todo merge this logic with the one in tbl_structure.php
         * or move it in a function similar to $GLOBALS['dbi']->getColumnsFull()
         * but based on SHOW CREATE TABLE because information_schema
         * cannot be trusted in this case (MySQL bug)
         */
        $analyzed_for_field = $analyzed_sql[0]['create_table_fields'][$field_name];
        if (! empty($analyzed_for_field['type'])
            && $analyzed_for_field['type'] == 'TIMESTAMP'
            && $analyzed_for_field['timestamp_not_null']
        ) {
            $row['Null'] = '';
        }

        $html .= "\n";
        $html .= '<tr><td>';

        if (isset($pk_array[$row['Field']])) {
            $html .= '    <u>' . $field_name . '</u>' . "\n";
        } else {
            $html .= '    ' . $field_name . "\n";
        }
        $html .= '</td>';
        $html .= '<td>' . $type . '<bdo dir="ltr"></bdo></td>';
        $html .= '<td>';
        $html .= (($row['Null'] == '' || $row['Null'] == 'NO')
            ? __('No')
            : __('Yes'));
        $html .= '&nbsp;</td>';
        $html .= '<td>';
        if (isset($row['Default'])) {
            $html .= $row['Default'];
        }
        $html .= '&nbsp;</td>';
        if ($have_rel) {
            $html .= '    <td>';
            if (isset($res_rel[$field_name])) {
                $html .= htmlspecialchars(
                    $res_rel[$field_name]['foreign_table']
                    . ' -> ' . $res_rel[$field_name]['foreign_field']
                );
            }
            $html .= '&nbsp;</td>' . "\n";
        }
        $html .= '    <td>';
        $comments = PMA_getComments($db, $table);
        if (isset($comments[$field_name])) {
            $html .= htmlspecialchars($comments[$field_name]);
        }
        $html .= '&nbsp;</td>' . "\n";
        if ($cfgRelation['mimework']) {
            $mime_map = PMA_getMIME($db, $table, true);

            $html .= '    <td>';
            if (isset($mime_map[$field_name])) {
                $html .= htmlspecialchars(
                    str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                );
            }
            $html .= '&nbsp;</td>' . "\n";
        }
        $html .= '</tr>';
    } // end foreach

    return $html;
}

/**
 * return html for Row Statistic
 *
 * @param array $showtable       showing table information
 * @param int   $cell_align_left cell align left
 * @param int   $avg_size        avg size
 * @param int   $avg_unit        avg unit
 * @param bool  $mergetable      is merge table?
 *
 * @return string
 */
function PMA_getHtmlForRowStatistics(
    $showtable, $cell_align_left, $avg_size, $avg_unit, $mergetable
) {
    $html  = '<td width="20">&nbsp;</td>';

    // Rows Statistic
    $html .= "\n";
    $html .= '<td class="vtop">';
    $html .= '<big>' . __('Row Statistics:') . '</big>';
    $html .= '<table width="100%">';
    if (isset($showtable['Row_format'])) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Format') . '</td>';
        $html .= '<td class="' . $cell_align_left . '">';
        if ($showtable['Row_format'] == 'Fixed') {
            $html .= __('static');
        } elseif ($showtable['Row_format'] == 'Dynamic') {
            $html .= __('dynamic');
        } else {
            $html .= $showtable['Row_format'];
        }
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Rows'])) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Rows') . '</td>';
        $html .= '<td class="right">';
        $html .= PMA_Util::formatNumber($showtable['Rows'], 0);
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Avg_row_length'])
        && $showtable['Avg_row_length'] > 0
    ) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Row length') . '&nbsp;&oslash;</td>';
        $html .= '<td>';
        $html .= PMA_Util::formatNumber(
            $showtable['Avg_row_length'], 0
        );
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Data_length'])
        && $showtable['Rows'] > 0
        && $mergetable == false
    ) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Row size') . '&nbsp;&oslash;</td>';
        $html .= '<td class="right">';
        $html .= $avg_size . ' ' . $avg_unit;
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Auto_increment'])) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Next autoindex') . ' </td>';
        $html .= '<td class="right">';
        $html .= PMA_Util::formatNumber(
            $showtable['Auto_increment'], 0
        );
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Create_time'])) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Creation') . '</td>';
        $html .= '<td class="right">';
        $html .= PMA_Util::localisedDate(
            strtotime($showtable['Create_time'])
        );
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Update_time'])) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Last update') . '</td>';
        $html .= '<td class="right">';
        $html .= PMA_Util::localisedDate(
            strtotime($showtable['Update_time'])
        );
        $html .= '</td>';
        $html .= '</tr>';
    }
    if (isset($showtable['Check_time'])) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td>' . __('Last check') . '</td>';
        $html .= '<td class="right">';
        $html .= PMA_Util::localisedDate(
            strtotime($showtable['Check_time'])
        );
        $html .= '</td>';
        $html .= '</tr>';
    }

    return $html;
}

/**
 * return html for Space Usage
 *
 * @param int  $data_size   data size
 * @param int  $data_unit   data unit
 * @param int  $index_size  index size
 * @param int  $index_unit  index unit
 * @param int  $free_size   free size
 * @param int  $free_unit   free unit
 * @param int  $effect_size effect size
 * @param int  $effect_unit effect unit
 * @param int  $tot_size    total size
 * @param int  $tot_unit    total unit
 * @param bool $mergetable  is merge table?
 *
 * @return string
 */
function PMA_getHtmlForSpaceUsage(
    $data_size, $data_unit, $index_size, $index_unit,
    $free_size, $free_unit, $effect_size, $effect_unit,
    $tot_size, $tot_unit, $mergetable
) {
    $html  = '<table cellspacing="0" cellpadding="0">';
    $html .= "\n";
    $html .= '<tr>';

    // Space usage
    $html .= '<td class="vtop">';
    $html .= '<big>' . __('Space usage:') . '</big>';
    $html .= '<table width="100%">';
    $html .= '<tr>';
    $html .= '<td style="padding-right: 10px">' . __('Data') . '</td>';
    $html .= '<td class="right">' . $data_size . '</td>';
    $html .= '<td>' . $data_unit . '</td>';
    $html .= '</tr>';
    if (isset($index_size)) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td style="padding-right: 10px">' . __('Index') . '</td>';
        $html .= '<td class="right">' . $index_size . '</td>';
        $html .= '<td>' . $index_unit . '</td>';
        $html .= '</tr>';
    }
    if (isset($free_size)) {
        $html .= "\n";
        $html .= '<tr style="color: #bb0000">';
        $html .= '<td style="padding-right: 10px">';
        $html .= __('Overhead');
        $html .= '</td>';
        $html .= '<td class="right">' . $free_size . '</td>';
        $html .= '<td>' . $free_unit . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td style="padding-right: 10px">';
        $html .= __('Effective');
        $html .= '</td>';
        $html .= '<td class="right">' . $effect_size . '</td>';
        $html .= '<td>' . $effect_unit . '</td>';
        $html .= '</tr>';
    }
    if (isset($tot_size) && $mergetable == false) {
        $html .= "\n";
        $html .= '<tr>';
        $html .= '<td style="padding-right: 10px">' . __('Total') . '</td>';
        $html .= '<td class="right">' . $tot_size . '</td>';
        $html .= '<td>' . $tot_unit . '</td>';
        $html .= '</tr>';
    }
    $html .= "\n";
    $html .= '</table>';

    return $html;
}
/**
 * return html for Space Usage And Row Statistic
 *
 * @param array  $showtable       showing table information
 * @param string $db              database
 * @param string $table           table
 * @param int    $cell_align_left cell align left
 *
 * @return string
 */
function PMA_getHtmlForSpaceUsageAndRowStatistics(
    $showtable, $db, $table, $cell_align_left
) {
    $html = '';
    $nonisam = false;
    if (isset($showtable['Type'])
        && ! preg_match('@ISAM|HEAP@i', $showtable['Type'])
    ) {
        $nonisam = true;
    }
    if ($nonisam == false) {
        // Gets some sizes

        $mergetable = PMA_Table::isMerge($db, $table);

        list($data_size, $data_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length']
        );
        if ($mergetable == false) {
            list($index_size, $index_unit)
                = PMA_Util::formatByteDown(
                    $showtable['Index_length']
                );
        }
        if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
            list($free_size, $free_unit)
                = PMA_Util::formatByteDown(
                    $showtable['Data_free']
                );
            list($effect_size, $effect_unit)
                = PMA_Util::formatByteDown(
                    $showtable['Data_length'] + $showtable['Index_length']
                    - $showtable['Data_free']
                );
        } else {
            unset($free_size);
            unset($free_unit);
            list($effect_size, $effect_unit)
                = PMA_Util::formatByteDown(
                    $showtable['Data_length'] + $showtable['Index_length']
                );
        }
        list($tot_size, $tot_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length']
        );
        $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
        if ($num_rows > 0) {
            list($avg_size, $avg_unit)
                = PMA_Util::formatByteDown(
                    ($showtable['Data_length'] + $showtable['Index_length'])
                    / $showtable['Rows'],
                    6,
                    1
                );
        }

        // Displays them
        $html .= '<br /><br />';
        $html .= PMA_getHtmlForSpaceUsage(
            $data_size, $data_unit,
            isset($index_size)? $index_size : null,
            isset($index_unit)? $index_unit : null,
            isset($free_size)? $free_size : null,
            isset($free_unit)? $free_unit : null,
            isset($effect_size)? $effect_size : null,
            isset($effect_unit)? $effect_unit : null,
            isset($tot_size)? $tot_size : null,
            isset($tot_unit)? $tot_unit : null,
            $mergetable
        );

        $html .= '</td>';
        $html .= PMA_getHtmlForRowStatistics(
            $showtable, $cell_align_left,
            isset($avg_size)? $avg_size: 0,
            isset($avg_unit)? $avg_unit: 0,
            $mergetable
        );
        $html .= "\n";
        $html .= '</table>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
    } // end if ($nonisam == false)

    return $html;
}

/**
 * return html for Table Structure
 *
 * @param bool   $have_rel        whether have relation
 * @param array  $tbl_is_view     Is a table view?
 * @param array  $columns         columns list
 * @param array  $analyzed_sql    analyzed sql
 * @param array  $pk_array        primary key array
 * @param array  $res_rel         relations array
 * @param string $db              database
 * @param string $table           table
 * @param array  $cfgRelation     config from PMA_getRelationsParam
 * @param array  $cfg             global config
 * @param array  $showtable       showing table information
 * @param int    $cell_align_left cell align left
 *
 * @return string
 */
function PMA_getHtmlForTableStructure(
    $have_rel, $tbl_is_view, $columns, $analyzed_sql,
    $pk_array, $res_rel, $db, $table, $cfgRelation,
    $cfg, $showtable, $cell_align_left
) {
    /**
     * Displays the table structure
     */
    $html  = '<table style="width: 100%;">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('Column') . '</th>';
    $html .= '<th>' . __('Type') . '</th>';
    $html .= '<th>' . __('Null') . '</th>';
    $html .= '<th>' . __('Default') . '</th>';
    if ($have_rel) {
        $html .= '<th>' . __('Links to') . '</th>' . "\n";
    }
    $html .= '    <th>' . __('Comments') . '</th>' . "\n";
    if ($cfgRelation['mimework']) {
        $html .= '    <th>MIME</th>' . "\n";
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= PMA_getHtmlForPrintViewColumns(
        $columns, $analyzed_sql, $pk_array, $have_rel,
        $res_rel, $db, $table, $cfgRelation
    );
    $html .= '</tbody>';
    $html .= '</table>';
    if (! $tbl_is_view && !$GLOBALS['dbi']->isSystemSchema($db)) {
        /**
         * Displays indexes
         */
        $html .= PMA_Index::getView($table, $db, true);

        /**
         * Displays Space usage and row statistics
         *
         */
        if ($cfg['ShowStats']) {
            $html .= PMA_getHtmlForSpaceUsageAndRowStatistics(
                $showtable, $db, $table, $cell_align_left
            );
        } // end if ($cfg['ShowStats'])
    }

    return $html;
}

/**
 * return html for tables' detail
 *
 * @param array  $the_tables      tables list
 * @param string $db              database name
 * @param array  $cfg             global config
 * @param array  $cfgRelation     config from PMA_getRelationsParam
 * @param array  $pk_array        primary key array
 * @param int    $cell_align_left cell align left
 *
 * @return string
 */
function PMA_getHtmlForTablesDetail(
    $the_tables, $db, $cfg, $cfgRelation, $pk_array, $cell_align_left
) {
    $html = '';
    $tables_cnt = count($the_tables);
    $multi_tables = (count($the_tables) > 1);
    $counter = 0;

    foreach ($the_tables as $table) {
        if ($counter + 1 >= $tables_cnt) {
            $breakstyle = '';
        } else {
            $breakstyle = ' style="page-break-after: always;"';
        }
        $counter++;
        $html .= '<div' . $breakstyle . '>' . "\n";
        $html .= '<h1>' . htmlspecialchars($table) . '</h1>' . "\n";

        /**
         * Gets table informations
         */
        $showtable    = PMA_Table::sGetStatusInfo($db, $table);
        $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
        $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');

        $tbl_is_view = PMA_Table::isView($db, $table);

        /**
         * Gets fields properties
         */
        $columns = $GLOBALS['dbi']->getColumns($db, $table);

        // We need this to correctly learn if a TIMESTAMP is NOT NULL, since
        // SHOW FULL FIELDS or INFORMATION_SCHEMA incorrectly says NULL
        // and SHOW CREATE TABLE says NOT NULL (tested
        // in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

        $show_create_table = $GLOBALS['dbi']->fetchValue(
            'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($table),
            0, 1
        );
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

        // Check if we can use Relations
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel  = PMA_getForeigners($db, $table);
        $have_rel = (bool) count($res_rel);

        /**
         * Displays the comments of the table if MySQL >= 3.23
         */
        if (!empty($show_comment)) {
            $html .= __('Table comments:') . ' '
                . htmlspecialchars($show_comment) . '<br /><br />';
        }

        $html .= PMA_getHtmlForTableStructure(
            $have_rel, $tbl_is_view, $columns, $analyzed_sql,
            $pk_array, $res_rel, $db, $table, $cfgRelation,
            $cfg, $showtable, $cell_align_left
        );

        if ($multi_tables) {
            unset($num_rows, $show_comment);
            $html .= '<hr />' . "\n";
        } // end if
        $html .= '</div>' . "\n";

    } // end while

    return $html;
}

?>
