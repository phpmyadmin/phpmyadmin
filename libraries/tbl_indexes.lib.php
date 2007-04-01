<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function library for handling table indexes
 *
 * @version $Id$
 */

/**
 * Return a list of all index types
 *
 * @access  public
 * @return  array       Index types
 * @author  Garvin Hicking (pma@supergarv.de)
 */
function PMA_get_indextypes()
{
    return array(
        'PRIMARY',
        'INDEX',
        'UNIQUE',
        'FULLTEXT',
    );
}

/**
 * Function to get all index information from a certain table
 *
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_backquote()
 * @param   string  $tbl_name   Table name to ftech indexes from
 * @param   string  $err_url_0  Error URL
 *
 * @access  public
 * @return  array       Index keys
 */
function PMA_get_indexes($tbl_name, $err_url_0 = '')
{
    return PMA_DBI_fetch_result('SHOW KEYS FROM ' . PMA_backquote($tbl_name));
}

/**
 * Function to check over array of indexes and look for common problems
 *
 * @uses    $GLOBALS['strIndexesSeemEqual']
 * @uses    PMA_get_indexes()
 * @uses    is_string()
 * @uses    is_array()
 * @uses    count()
 * @uses    array_pop()
 * @uses    reset()
 * @uses    current()
 * @access  public
 * @param   mixed       array of indexes from PMA_get_indexes()
 *                      or name of table
 * @return  string      Output HTML
 */
function PMA_check_indexes($idx_collection)
{
    if (is_string($idx_collection)) {
        $idx_collection = PMA_get_indexes($idx_collection);
    }

    // count($idx_collection) < 2:
    //   there is no need to check if there less than two indexes
    if (! is_array($idx_collection) || count($idx_collection) < 2) {
        return false;
    }

    $indexes = array();
    foreach ($idx_collection as $index_field) {
        $indexes[$index_field['Key_name']][$index_field['Column_name']]
            = $index_field;
    }

    $output  = '';

    // remove last index from stack and ...
    while ($while_index = array_pop($indexes)) {
        // ... compare with every remaining index in stack
        foreach ($indexes as $each_index_name => $each_index) {
            if (count($while_index) !== count($each_index)) {
                // number of fields are not equal
                continue;
            }

            // compare some key elements of every column in this two indexes
            foreach ($each_index as $col_name => $each_index_column) {
                if (! isset($while_index[$col_name])
                 // the position
                 || $while_index[$col_name]['Seq_in_index'] !== $each_index_column['Seq_in_index']
                 // the order, ASC or DESC
                 || $while_index[$col_name]['Collation']    !== $each_index_column['Collation']
                 // the length
                 || $while_index[$col_name]['Sub_part']     !== $each_index_column['Sub_part']
                 // BTREE or HASH
                 || $while_index[$col_name]['Index_type']   !== $each_index_column['Index_type']) {
                    continue 2;
                }
            }

            // did not find any difference
            // so it makes no sense to have this two equal indexes

            // use first column from index to fetch index name
            reset($while_index);
            $first_column = current($while_index);

            $output .= '<div class="warning">';
            $output .= $GLOBALS['strIndexesSeemEqual'] . ' ';
            $output .= $each_index_name . ', ' . $first_column['Key_name'];
            $output .= '</div>';

            // there is no need to check any further indexes if we have already
            // found that this one has a duplicate
            continue 2;
        }
    }

    if ($output) {
        $output = '<tr><td colspan=7">' . $output . '</td></tr>';
    }

    return $output;
}

/**
 * Loop array of returned index keys and extract key information to
 * seperate arrays. Those arrays are passed by reference.
 *
 * @param   array       Referenced Array of indexes
 * @param   array       Referenced return array
 * @param   array       Referenced return array
 * @param   array       Referenced return array
 *
 * @access  public
 * @return  boolean     void
 * @author  Garvin Hicking (pma@supergarv.de)
 */
function PMA_extract_indexes(&$ret_keys, &$indexes, &$indexes_info, &$indexes_data)
{
    if (! is_array($ret_keys)) {
        return false;
    }

    $prev_index   = '';
    foreach ($ret_keys as $row) {
        if ($row['Key_name'] != $prev_index){
            $indexes[]  = $row['Key_name'];
            $prev_index = $row['Key_name'];
        }

        $indexes_info[$row['Key_name']]['Sequences'][]     = $row['Seq_in_index'];
        $indexes_info[$row['Key_name']]['Non_unique']      = $row['Non_unique'];

        if (isset($row['Cardinality'])) {
            $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
        }

        //    I don't know what does following column mean....
        //    $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];
        $indexes_info[$row['Key_name']]['Comment']         = (isset($row['Comment']))
                                                           ? $row['Comment']
                                                           : '';
        $indexes_info[$row['Key_name']]['Index_type']      = (isset($row['Index_type']))
                                                           ? $row['Index_type']
                                                           : '';

        $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name']  = $row['Column_name'];
        if (isset($row['Sub_part'])) {
            $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part'] = $row['Sub_part'];
        }
    } // end while

    return true;
}

/**
 * Show index data and prepare returned collection array for index
 * key checks.
 *
 * @param   string      $table          The tablename
 * @param   array       $indexes        Referenced Array of indexes
 * @param   array       $indexes_info   Referenced info array
 * @param   array       $indexes_data   Referenced data array
 * @param   boolean     $display_html   Output HTML code, or just return collection array?
 * @param   boolean     $print_mode
 * @access  public
 * @return  array       Index collection array
 * @author  Garvin Hicking (pma@supergarv.de)
 */
function PMA_show_indexes($table, &$indexes, &$indexes_info, &$indexes_data,
    $display_html = true, $print_mode = false)
{
    $idx_collection = array();
    $odd_row = true;
    foreach ($indexes as $index_name) {
        if ($display_html) {
            $row_span = ' rowspan="' . count($indexes_info[$index_name]['Sequences']) . '" ';

            echo '        <tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n";
            echo '            <th ' . $row_span . '>' . "\n"
               . '                ' . htmlspecialchars($index_name) . "\n"
               . '            </th>' . "\n";
        }

        if ((PMA_MYSQL_INT_VERSION < 40002 && $indexes_info[$index_name]['Comment'] == 'FULLTEXT')
            || (PMA_MYSQL_INT_VERSION >= 40002 && $indexes_info[$index_name]['Index_type'] == 'FULLTEXT')) {
            $index_type = 'FULLTEXT';
        } elseif ($index_name == 'PRIMARY') {
            $index_type = 'PRIMARY';
        } elseif ($indexes_info[$index_name]['Non_unique'] == '0') {
            $index_type = 'UNIQUE';
        } else {
            $index_type = 'INDEX';
        }

        if ($display_html) {
            echo '            <td ' . $row_span . '>' . "\n"
               . '                ' . $index_type . '</td>' . "\n";

            echo '            <td ' . $row_span . ' align="right">' . "\n"
               . '                ' . (isset($indexes_info[$index_name]['Cardinality']) ? $indexes_info[$index_name]['Cardinality'] : $GLOBALS['strNone']) . '&nbsp;' . "\n"
               . '            </td>' . "\n";

            if (!$print_mode) {
                echo '            <td ' . $row_span . '>' . "\n"
                   . '                <a href="tbl_indexes.php?'
                   . $GLOBALS['url_query'] . '&amp;index=' . urlencode($index_name)
                   . '">' . $GLOBALS['edit_link_text'] . '</a>' . "\n"
                   . '            </td>' . "\n";

                if ($index_name == 'PRIMARY') {
                    $local_query = urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP PRIMARY KEY');
                    $js_msg      = 'ALTER TABLE ' . PMA_jsFormat($table) . ' DROP PRIMARY KEY';
                    $zero_rows   = urlencode($GLOBALS['strPrimaryKeyHasBeenDropped']);
                } else {
                    $local_query = urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP INDEX ' . PMA_backquote($index_name));
                    $js_msg      = 'ALTER TABLE ' . PMA_jsFormat($table) . ' DROP INDEX ' . PMA_jsFormat($index_name);
                    $zero_rows   = urlencode(sprintf($GLOBALS['strIndexHasBeenDropped'], htmlspecialchars($index_name)));
                }

                echo '            <td ' . $row_span . '>' . "\n"
                   . '                <a href="sql.php?' . $GLOBALS['url_query']
                   . '&amp;sql_query=' . $local_query . '&amp;zero_rows='
                   . $zero_rows . '" onclick="return confirmLink(this, \''
                   . $js_msg . '\')">' . $GLOBALS['drop_link_text']  . '</a>' . "\n"
                   . '            </td>' . "\n";
            }
        }

        foreach ($indexes_info[$index_name]['Sequences'] AS $row_no => $seq_index) {
            $col_name = $indexes_data[$index_name][$seq_index]['Column_name'];
            if ($row_no == 0) {
                if (isset($idx_collection[$index_type][$col_name])) {
                    $idx_collection[$index_type][$col_name]++;
                } else {
                    $idx_collection[$index_type][$col_name] = 1;
                }

                if (isset($idx_collection['ALL'][$col_name])) {
                    $idx_collection['ALL'][$col_name]++;
                } else {
                    $idx_collection['ALL'][$col_name] = 1;
                }
            }

            if ($display_html) {
                if ($row_no > 0) {
                    echo '        <tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n";
                }

                if (isset($indexes_data[$index_name][$seq_index]['Sub_part'])
                 && strlen($indexes_data[$index_name][$seq_index]['Sub_part'])) {
                    echo '            <td>' . $col_name . '</td>' . "\n";
                    echo '            <td align="right">' . "\n"
                       . '                ' . $indexes_data[$index_name][$seq_index]['Sub_part'] . "\n"
                       . '            </td>' . "\n";
                    echo '        </tr>' . "\n";
                } else {
                    echo '            <td colspan="2">' . "\n"
                       . '                ' . htmlspecialchars($col_name) . "\n"
                       . '            </td>' . "\n";
                    echo '        </tr>' . "\n";
                }
            }
        } // end foreach $indexes_info[$index_name]['Sequences']

        $odd_row = ! $odd_row;
    } // end while

    return $idx_collection;
}

?>
