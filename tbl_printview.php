<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Print view for table
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$response->getHeader()->enablePrintView();

require 'libraries/tbl_common.inc.php';

// Check parameters

if (! isset($the_tables) || ! is_array($the_tables)) {
    $the_tables = array();
}

/**
 * Gets the relations settings
 */
require_once 'libraries/transformations.lib.php';
require_once 'libraries/Index.class.php';

$cfgRelation = PMA_getRelationsParam();

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (strlen($table)) {
    $err_url = 'tbl_sql.php?' . PMA_generate_common_url($db, $table);
} else {
    $err_url = 'db_sql.php?' . PMA_generate_common_url($db);
}


/**
 * Selects the database
 */
PMA_DBI_select_db($db);


/**
 * Multi-tables printview
 */
if (isset($selected_tbl) && is_array($selected_tbl)) {
    $the_tables   = $selected_tbl;
} elseif (strlen($table)) {
    $the_tables[] = $table;
}
$multi_tables     = (count($the_tables) > 1);

if ($multi_tables) {
    $tbl_list     = '';
    foreach ($the_tables as $key => $table) {
        $tbl_list .= (empty($tbl_list) ? '' : ', ')
                  . PMA_Util::backquote($table);
    }
    echo '<strong>'.  __('Showing tables') . ': '
        . htmlspecialchars($tbl_list) . '</strong>' . "\n";
    echo '<hr />' . "\n";
} // end if

$tables_cnt = count($the_tables);
$counter    = 0;

foreach ($the_tables as $key => $table) {
    if ($counter + 1 >= $tables_cnt) {
        $breakstyle = '';
    } else {
        $breakstyle = ' style="page-break-after: always;"';
    }
    $counter++;
    echo '<div' . $breakstyle . '>' . "\n";
    echo '<h1>' . htmlspecialchars($table) . '</h1>' . "\n";

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
    $columns = PMA_DBI_get_columns($db, $table);


    // We need this to correctly learn if a TIMESTAMP is NOT NULL, since
    // SHOW FULL FIELDS or INFORMATION_SCHEMA incorrectly says NULL
    // and SHOW CREATE TABLE says NOT NULL (tested
    // in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

    $show_create_table = PMA_DBI_fetch_value(
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
        echo __('Table comments') . ': '
            . htmlspecialchars($show_comment) . '<br /><br />';
    }

    /**
     * Displays the table structure
     */
    echo '<table style="width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('Column') . '</th>';
    echo '<th>' . __('Type') . '</th>';
    echo '<th>' . __('Null') . '</th>';
    echo '<th>' . __('Default') . '</th>';
    if ($have_rel) {
        echo '<th>' . __('Links to') . '</th>' . "\n";
    }
    echo '    <th>' . __('Comments') . '</th>' . "\n";
    if ($cfgRelation['mimework']) {
        echo '    <th>MIME</th>' . "\n";
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($columns as $row) {
        $extracted_columnspec = PMA_Util::extractColumnSpec($row['Type']);
        $type = $extracted_columnspec['print_type'];
        $attribute = $extracted_columnspec['attribute'];

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
         * or move it in a function similar to PMA_DBI_get_columns_full()
         * but based on SHOW CREATE TABLE because information_schema
         * cannot be trusted in this case (MySQL bug)
         */
        if (! empty($analyzed_sql[0]['create_table_fields'][$field_name]['type'])
            && $analyzed_sql[0]['create_table_fields'][$field_name]['type'] == 'TIMESTAMP'
            && $analyzed_sql[0]['create_table_fields'][$field_name]['timestamp_not_null']
        ) {
            $row['Null'] = '';
        }

        echo "\n";
        echo '<tr><td>';

        if (isset($pk_array[$row['Field']])) {
            echo '    <u>' . $field_name . '</u>' . "\n";
        } else {
            echo '    ' . $field_name . "\n";
        }
        echo '</td>';
        echo '<td>' . $type. '<bdo dir="ltr"></bdo></td>';
        echo '<td>';
        echo (($row['Null'] == '' || $row['Null'] == 'NO')
            ? __('No')
            : __('Yes'));
        echo '&nbsp;</td>';
        echo '<td>';
        if (isset($row['Default'])) {
            echo $row['Default'];
        }
        echo '&nbsp;</td>';
        if ($have_rel) {
            echo '    <td>';
            if (isset($res_rel[$field_name])) {
                echo htmlspecialchars(
                    $res_rel[$field_name]['foreign_table']
                    . ' -> ' . $res_rel[$field_name]['foreign_field']
                );
            }
            echo '&nbsp;</td>' . "\n";
        }
        echo '    <td>';
        $comments = PMA_getComments($db, $table);
        if (isset($comments[$field_name])) {
            echo htmlspecialchars($comments[$field_name]);
        }
        echo '&nbsp;</td>' . "\n";
        if ($cfgRelation['mimework']) {
            $mime_map = PMA_getMIME($db, $table, true);

            echo '    <td>';
            if (isset($mime_map[$field_name])) {
                echo htmlspecialchars(
                    str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                );
            }
            echo '&nbsp;</td>' . "\n";
        }
        echo '</tr>';
    } // end foreach
    echo '</tbody>';
    echo '</table>';
    if (! $tbl_is_view && !PMA_is_system_schema($db)) {
        /**
         * Displays indexes
         */
        echo PMA_Index::getView($table, $db, true);

        /**
         * Displays Space usage and row statistics
         *
         */
        if ($cfg['ShowStats']) {
            $nonisam     = false;
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
                echo '<br /><br />';

                echo '<table cellspacing="0" cellpadding="0">';
                echo "\n";
                echo '<tr>';

                // Space usage
                echo '<td class="vtop">';
                echo '<big>' . __('Space usage') . ':</big>';
                echo '<table width="100%">';
                echo '<tr>';
                echo '<td style="padding-right: 10px">' . __('Data') . '</td>';
                echo '<td class="right">' . $data_size . '</td>';
                echo '<td>' . $data_unit . '</td>';
                echo '</tr>';
                if (isset($index_size)) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td style="padding-right: 10px">' . __('Index') . '</td>';
                    echo '<td class="right">' . $index_size . '</td>';
                    echo '<td>' . $index_unit. '</td>';
                    echo '</tr>';
                }
                if (isset($free_size)) {
                    echo "\n";
                    echo '<tr style="color: #bb0000">';
                    echo '<td style="padding-right: 10px">';
                    echo __('Overhead');
                    echo '</td>';
                    echo '<td class="right">' . $free_size . '</td>';
                    echo '<td>' . $free_unit . '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td style="padding-right: 10px">';
                    echo __('Effective');
                    echo '</td>';
                    echo '<td class="right">' . $effect_size . '</td>';
                    echo '<td>' . $effect_unit . '</td>';
                    echo '</tr>';
                }
                if (isset($tot_size) && $mergetable == false) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td style="padding-right: 10px">' . __('Total') . '</td>';
                    echo '<td class="right">' . $tot_size . '</td>';
                    echo '<td>' . $tot_unit . '</td>';
                    echo '</tr>';
                }
                echo "\n";
                echo '</table>';
                echo '</td>';

                echo '<td width="20">&nbsp;</td>';

                // Rows Statistic
                echo "\n";
                echo '<td class="vtop">';
                echo '<big>' . __('Row Statistics') . ':</big>';
                echo '<table width="100%">';
                if (isset($showtable['Row_format'])) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Format') . '</td>';
                    echo '<td class="' . $cell_align_left . '">';
                    if ($showtable['Row_format'] == 'Fixed') {
                        echo __('static');
                    } elseif ($showtable['Row_format'] == 'Dynamic') {
                        echo __('dynamic');
                    } else {
                        echo $showtable['Row_format'];
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Rows'])) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Rows') . '</td>';
                    echo '<td class="right">';
                    echo PMA_Util::formatNumber($showtable['Rows'], 0);
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Avg_row_length'])
                    && $showtable['Avg_row_length'] > 0
                ) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Row length') . '&nbsp;&oslash;</td>';
                    echo '<td>';
                    echo PMA_Util::formatNumber(
                        $showtable['Avg_row_length'], 0
                    );
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Data_length'])
                    && $showtable['Rows'] > 0
                    && $mergetable == false
                ) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Row size') . '&nbsp;&oslash;</td>';
                    echo '<td class="right">';
                    echo $avg_size . ' ' . $avg_unit;
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Auto_increment'])) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Next autoindex'). ' </td>';
                    echo '<td class="right">';
                    echo PMA_Util::formatNumber(
                        $showtable['Auto_increment'], 0
                    );
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Create_time'])) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Creation') . '</td>';
                    echo '<td class="right">';
                    echo PMA_Util::localisedDate(
                        strtotime($showtable['Create_time'])
                    );
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Update_time'])) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Last update') . '</td>';
                    echo '<td class="right">';
                    echo PMA_Util::localisedDate(
                        strtotime($showtable['Update_time'])
                    );
                    echo '</td>';
                    echo '</tr>';
                }
                if (isset($showtable['Check_time'])) {
                    echo "\n";
                    echo '<tr>';
                    echo '<td>' . __('Last check') . '</td>';
                    echo '<td class="right">';
                    echo PMA_Util::localisedDate(
                        strtotime($showtable['Check_time'])
                    );
                    echo '</td>';
                    echo '</tr>';
                }

                echo "\n";
                echo '</table>';
                echo '</td>';
                echo '</tr>';
                echo '</table>';
            } // end if ($nonisam == false)
        } // end if ($cfg['ShowStats'])
    }
    if ($multi_tables) {
        unset($num_rows, $show_comment);
        echo '<hr />' . "\n";
    } // end if
    echo '</div>' . "\n";

} // end while

/**
 * Displays the footer
 */
echo PMA_Util::getButton();

echo "<div id='PMA_disable_floating_menubar'></div>\n";
?>
