<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

    /**
     * Return a list of all index types
     *
     * @access  public
     * @return  array       Index types
     * @author  Garvin Hicking (pma@supergarv.de)
     */

    function PMA_get_indextypes() {
        return array(
            'PRIMARY',
            'INDEX',
            'UNIQUE',
            'FULLTEXT'
        );
    }

    /**
     * Function to get all index information from a certain table
     *
     * @param   string      Table name
     * @param   string      Error URL
     *
     * @access  public
     * @return  array       Index keys
     */
    function PMA_get_indexes($tbl_name, $err_url_0 = '') {
        $tbl_local_query = 'SHOW KEYS FROM ' . PMA_backquote($tbl_name);
        $tbl_result      = PMA_DBI_query($tbl_local_query) or PMA_mysqlDie('', $tbl_local_query, '', $err_url_0);
        $tbl_ret_keys    = array();
        while ($tbl_row = PMA_DBI_fetch_assoc($tbl_result)) {
            $tbl_ret_keys[]  = $tbl_row;
        }
        PMA_DBI_free_result($tbl_result);

        return $tbl_ret_keys;
    }

    /**
     * Function to check over array of indexes and look for common problems
     *
     * @param   array       Array of indexes
     * @param   boolean     Whether to output HTML in table layout
     *
     * @access  public
     * @return  string      Output HTML
     * @author  Garvin Hicking (pma@supergarv.de)
     */
    function PMA_check_indexes($idx_collection, $table = true) {
        $index_types = PMA_get_indextypes();
        $output  = '';
        if (is_array($idx_collection) && isset($idx_collection['ALL'])) {
            foreach($idx_collection['ALL'] AS $w_keyname => $w_count) {
                if (isset($idx_collection['PRIMARY'][$w_keyname]) && (isset($idx_collection['INDEX'][$w_keyname]) || isset($idx_collection['UNIQUE'][$w_keyname]))) {
                    $output .= PMA_index_warning(sprintf($GLOBALS['strIndexWarningPrimary'], htmlspecialchars($w_keyname)), $table);
                } elseif (isset($idx_collection['UNIQUE'][$w_keyname]) && isset($idx_collection['INDEX'][$w_keyname])) {
                    $output .= PMA_index_warning(sprintf($GLOBALS['strIndexWarningUnique'], htmlspecialchars($w_keyname)), $table);
                }

                foreach($index_types AS $index_type) {
                    if (isset($idx_collection[$index_type][$w_keyname]) && $idx_collection[$index_type][$w_keyname] > 1) {
                        $output .= PMA_index_warning(sprintf($GLOBALS['strIndexWarningMultiple'], $index_type, htmlspecialchars($w_keyname)), $table);
                    }
                }
            }
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
    function PMA_extract_indexes(&$ret_keys, &$indexes, &$indexes_info, &$indexes_data) {
        if (!is_array($ret_keys)) {
            return false;
        }

        $prev_index   = '';
        foreach ($ret_keys as $row) {
            if ($row['Key_name'] != $prev_index ){
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
     * @param   string      The tablename
     * @param   array       Referenced Array of indexes
     * @param   array       Referenced info array
     * @param   array       Referenced data array
     * @param   boolean     Output HTML code, or just return collection array?
     *
     * @access  public
     * @return  array       Index collection array
     * @author  Garvin Hicking (pma@supergarv.de)
     */
    function PMA_show_indexes($table, &$indexes, &$indexes_info, &$indexes_data, $display_html = true) {
        $idx_collection = array();
        foreach ($indexes AS $index_no => $index_name) {
            if ($display_html) {
                $cell_bgd = (($index_no % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']);
                $index_td = '            <td bgcolor="' . $cell_bgd . '" rowspan="' . count($indexes_info[$index_name]['Sequences']) . '">' . "\n";
                echo '        <tr>' . "\n";
                echo $index_td
                     . '                ' . htmlspecialchars($index_name) . "\n"
                     . '            </td>' . "\n";
            }

            if ((PMA_MYSQL_INT_VERSION < 40002 && $indexes_info[$index_name]['Comment'] == 'FULLTEXT')
                || (PMA_MYSQL_INT_VERSION >= 40002 && $indexes_info[$index_name]['Index_type'] == 'FULLTEXT')) {
                $index_type = 'FULLTEXT';
            } else if ($index_name == 'PRIMARY') {
                $index_type = 'PRIMARY';
            } else if ($indexes_info[$index_name]['Non_unique'] == '0') {
                $index_type = 'UNIQUE';
            } else {
                $index_type = 'INDEX';
            }

            if ($display_html) {
                echo $index_td
                     . '                ' . $index_type . "\n"
                     . '            </td>' . "\n";

                echo str_replace('">' . "\n", '" align="right">' . "\n", $index_td)
                     . '                ' . (isset($indexes_info[$index_name]['Cardinality']) ? $indexes_info[$index_name]['Cardinality'] : $GLOBALS['strNone']) . '&nbsp;' . "\n"
                     . '            </td>' . "\n";

                echo $index_td
                     . '                <a href="tbl_indexes.php?' . $GLOBALS['url_query'] . '&amp;index=' . urlencode($index_name) . '">' . $GLOBALS['edit_link_text'] . '</a>' . "\n"
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

                echo $index_td
                     . '                <a href="sql.php?' . $GLOBALS['url_query'] . '&amp;sql_query=' . $local_query . '&amp;zero_rows=' . $zero_rows . '" onclick="return confirmLink(this, \'' . $js_msg . '\')">' . $GLOBALS['drop_link_text']  . '</a>' . "\n"
                     . '            </td>' . "\n";
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
                        echo '        <tr>' . "\n";
                    }

                    if (!empty($indexes_data[$index_name][$seq_index]['Sub_part'])) {
                        echo '            <td bgcolor="' . $cell_bgd . '">' . "\n"
                             . '                ' . $col_name . "\n"
                             . '            </td>' . "\n";
                        echo '            <td align="right" bgcolor="' . $cell_bgd . '">' . "\n"
                             . '                ' . $indexes_data[$index_name][$seq_index]['Sub_part'] . "\n"
                             . '            </td>' . "\n";
                        echo '        </tr>' . "\n";
                    } else {
                        echo '            <td bgcolor="' . $cell_bgd . '" colspan="2">' . "\n"
                             . '                ' . htmlspecialchars($col_name) . "\n"
                             . '            </td>' . "\n";
                        echo '        </tr>' . "\n";
                    }
                }
            } // end while
        } // end while

        return $idx_collection;
    }

    /**
     * Function to emit a index warning
     *
     * @param   string      Message string
     * @param   boolean     Whether to output HTML in table layout
     *
     * @access  public
     * @output  string      Output HTML
     * @author  Garvin Hicking (pma@supergarv.de)
     */
    function PMA_index_warning($string, $table = true) {
        $output = '';
        if ($table) {
            $output .= "\n" . '        <tr><td colspan=7">' . "\n";
        }

        if ($GLOBALS['cfg']['ErrorIconic']) {
            $output .=  '<img src="' . $GLOBALS['pmaThemeImage'] . 's_warn.png" width="16" height="16" border="0" alt="Warning" hspace="2" align="middle" />';
        }

        $output .=  '        <b>' . $string . '</b>';

        if ($table) {
            $output .=  '</td></tr>';
        } else {
            $output .=  '<br />';
        }

        $output .=  "\n\n";
        return $output;
    }
?>