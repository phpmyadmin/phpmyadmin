<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once './libraries/Table.class.php';

/**
 * Set of functions used to display the records returned by a sql query
 */

/**
 * Avoids undefined variables
 */
if (!isset($pos)) {
    $pos = 0;
}

/**
 * Defines the display mode to use for the results of a sql query
 *
 * It uses a synthetic string that contains all the required informations.
 * In this string:
 *   - the first two characters stand for the action to do while
 *     clicking on the "edit" link (eg 'ur' for update a row, 'nn' for no
 *     edit link...);
 *   - the next two characters stand for the action to do while
 *     clicking on the "delete" link (eg 'kp' for kill a process, 'nn' for
 *     no delete link...);
 *   - the next characters are boolean values (1/0) and respectively stand
 *     for sorting links, navigation bar, "insert a new row" link, the
 *     bookmark feature, the expand/collapse text/blob fields button and
 *     the "display printable view" option.
 *     Of course '0'/'1' means the feature won't/will be enabled.
 *
 * @param   string   the synthetic value for display_mode (see �1 a few
 *                   lines above for explanations)
 * @param   integer  the total number of rows returned by the sql query
 *                   without any programmatically appended "LIMIT" clause
 *                   (just a copy of $unlim_num_rows if it exists, else
 *                   computed inside this function)
 *
 * @return  array    an array with explicit indexes for all the display
 *                   elements
 *
 * @global  string   the database name
 * @global  string   the table name
 * @global  integer  the total number of rows returned by the sql query
 *                   without any programmatically appended "LIMIT" clause
 * @global  array    the properties of the fields returned by the query
 * @global  string   the url to return to in case of error in a sql
 *                   statement
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_setDisplayMode(&$the_disp_mode, &$the_total)
{
    global $db, $table;
    global $unlim_num_rows, $fields_meta;
    global $err_url;

    // 1. Initializes the $do_display array
    $do_display              = array();
    $do_display['edit_lnk']  = $the_disp_mode[0] . $the_disp_mode[1];
    $do_display['del_lnk']   = $the_disp_mode[2] . $the_disp_mode[3];
    $do_display['sort_lnk']  = (string) $the_disp_mode[4];
    $do_display['nav_bar']   = (string) $the_disp_mode[5];
    $do_display['ins_row']   = (string) $the_disp_mode[6];
    $do_display['bkm_form']  = (string) $the_disp_mode[7];
    $do_display['text_btn']  = (string) $the_disp_mode[8];
    $do_display['pview_lnk'] = (string) $the_disp_mode[9];

    // 2. Display mode is not "false for all elements" -> updates the
    // display mode
    if ($the_disp_mode != 'nnnn000000') {
        // 2.0 Print view -> set all elements to false!
        if (isset($GLOBALS['printview']) && $GLOBALS['printview'] == '1') {
            $do_display['edit_lnk']  = 'nn'; // no edit link
            $do_display['del_lnk']   = 'nn'; // no delete link
            $do_display['sort_lnk']  = (string) '0';
            $do_display['nav_bar']   = (string) '0';
            $do_display['ins_row']   = (string) '0';
            $do_display['bkm_form']  = (string) '0';
            $do_display['text_btn']  = (string) '0';
            $do_display['pview_lnk'] = (string) '0';
        }
        // 2.1 Statement is a "SELECT COUNT", a
        //     "CHECK/ANALYZE/REPAIR/OPTIMIZE", an "EXPLAIN" one or
        //     contains a "PROC ANALYSE" part
        elseif ($GLOBALS['is_count'] || $GLOBALS['is_analyse'] || $GLOBALS['is_maint'] || $GLOBALS['is_explain']) {
            $do_display['edit_lnk']  = 'nn'; // no edit link
            $do_display['del_lnk']   = 'nn'; // no delete link
            $do_display['sort_lnk']  = (string) '0';
            $do_display['nav_bar']   = (string) '0';
            $do_display['ins_row']   = (string) '0';
            $do_display['bkm_form']  = (string) '1';
            if ($GLOBALS['is_analyse']) {
                $do_display['text_btn']  = (string) '1';
            } else {
                $do_display['text_btn']  = (string) '0';
            }
            $do_display['pview_lnk'] = (string) '1';
        }
        // 2.2 Statement is a "SHOW..."
        elseif ($GLOBALS['is_show']) {
            // 2.2.1 TODO : defines edit/delete links depending on show statement
            $tmp = preg_match('@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS)@i', $GLOBALS['sql_query'], $which);
            if (isset($which[1]) && strpos(' ' . strtoupper($which[1]), 'PROCESSLIST') > 0) {
                $do_display['edit_lnk'] = 'nn'; // no edit link
                $do_display['del_lnk']  = 'kp'; // "kill process" type edit link
            } else {
                // Default case -> no links
                $do_display['edit_lnk'] = 'nn'; // no edit link
                $do_display['del_lnk']  = 'nn'; // no delete link
            }
            // 2.2.2 Other settings
            $do_display['sort_lnk']  = (string) '0';
            $do_display['nav_bar']   = (string) '0';
            $do_display['ins_row']   = (string) '0';
            $do_display['bkm_form']  = (string) '1';
            $do_display['text_btn']  = (string) '1';
            $do_display['pview_lnk'] = (string) '1';
        }
        // 2.3 Other statements (ie "SELECT" ones) -> updates
        //     $do_display['edit_lnk'], $do_display['del_lnk'] and
        //     $do_display['text_btn'] (keeps other default values)
        else {
            $prev_table = $fields_meta[0]->table;
            $do_display['text_btn']  = (string) '1';
            for ($i = 0; $i < $GLOBALS['fields_cnt']; $i++) {
                $is_link = ($do_display['edit_lnk'] != 'nn'
                            || $do_display['del_lnk'] != 'nn'
                            || $do_display['sort_lnk'] != '0'
                            || $do_display['ins_row'] != '0');
                // 2.3.2 Displays edit/delete/sort/insert links?
                if ($is_link
                    && ($fields_meta[$i]->table == '' || $fields_meta[$i]->table != $prev_table)) {
                    $do_display['edit_lnk'] = 'nn'; // don't display links
                    $do_display['del_lnk']  = 'nn';
                    // TODO: May be problematic with same fields names in
                    //       two joined table.
                    // $do_display['sort_lnk'] = (string) '0';
                    $do_display['ins_row']  = (string) '0';
                    if ($do_display['text_btn'] == '1') {
                        break;
                    }
                } // end if (2.3.2)
                // 2.3.3 Always display print view link
                $do_display['pview_lnk']    = (string) '1';
                $prev_table = $fields_meta[$i]->table;
            } // end for
        } // end if..elseif...else (2.1 -> 2.3)
    } // end if (2)

    // 3. Gets the total number of rows if it is unknown
    if (isset($unlim_num_rows) && $unlim_num_rows != '') {
        $the_total = $unlim_num_rows;
    } elseif (($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1')
             && (isset($db) && strlen($db) && !empty($table))) {
        $the_total   = PMA_Table::countRecords($db, $table, true);
    }

    // 4. If navigation bar or sorting fields names urls should be
    //    displayed but there is only one row, change these settings to
    //    false
    if ($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1') {

        if (isset($unlim_num_rows) && $unlim_num_rows < 2) {
            // garvin: force display of navbar for vertical/horizontal display-choice.
            // $do_display['nav_bar']  = (string) '0';
            $do_display['sort_lnk'] = (string) '0';
        }

    } // end if (3)

    // 5. Updates the synthetic var
    $the_disp_mode = join('', $do_display);

    return $do_display;
} // end of the 'PMA_setDisplayMode()' function


/**
 * Displays a navigation bar to browse among the results of a sql query
 *
 * @param   integer  the offset for the "next" page
 * @param   integer  the offset for the "previous" page
 * @param   string   the url-encoded query
 *
 * @global  string   $db             the database name
 * @global  string   $table          the table name
 * @global  string   $goto           the url to go back in case of errors
 * @global  boolean  $dontlimitchars whether to limit the number of displayed
 *                                   characters  of text type fields or not
 * @global  integer  $num_rows       the total number of rows returned by the
 *                                   sql query
 * @global  integer  $unlim_num_rows the total number of rows returned by the
 *                                   sql any programmatically appended "LIMIT" clause
 * @global  integer  $pos            the current position in results
 * @global  mixed    $session_max_rows the maximum number of rows per page
 *                                   ('all'  = no limit)
 * @global  string   $disp_direction the display mode
 *                                   (horizontal / vertical / horizontalflipped)
 * @global  integer  $repeat_cells   the number of row to display between two
 *                                   table headers
 * @global  boolean  $is_innodb      whether its InnoDB or not
 * @global  array    $showtable      table definitions
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayTableNavigation($pos_next, $pos_prev, $encoded_query)
{
    global $db, $table, $goto, $dontlimitchars;
    global $num_rows, $unlim_num_rows, $pos, $session_max_rows;
    global $disp_direction, $repeat_cells;
    global $is_innodb;
    global $showtable;

    // FIXME: move this to a central place
    // FIXME: for other future table types
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');

    ?>

<!-- Navigation bar -->
<table border="0" cellpadding="2" cellspacing="0">
<tr>
    <?php
    // Move to the beginning or to the previous page
    if ($pos > 0 && $session_max_rows != 'all') {
        // loic1: patch #474210 from Gosha Sakovich - part 1
        if ($GLOBALS['cfg']['NavigationBarIconic']) {
            $caption1 = '&lt;&lt;';
            $caption2 = ' &lt; ';
            $title1   = ' title="' . $GLOBALS['strPos1'] . '"';
            $title2   = ' title="' . $GLOBALS['strPrevious'] . '"';
        } else {
            $caption1 = $GLOBALS['strPos1'] . ' &lt;&lt;';
            $caption2 = $GLOBALS['strPrevious'] . ' &lt;';
            $title1   = '';
            $title2   = '';
        } // end if... else...
        ?>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
        <input type="hidden" name="pos" value="0" />
        <input type="hidden" name="session_max_rows" value="<?php echo $session_max_rows; ?>" />
        <input type="hidden" name="disp_direction" value="<?php echo $disp_direction; ?>" />
        <input type="hidden" name="repeat_cells" value="<?php echo $repeat_cells; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption1; ?>"<?php echo $title1; ?> />
    </form>
</td>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
        <input type="hidden" name="pos" value="<?php echo $pos_prev; ?>" />
        <input type="hidden" name="session_max_rows" value="<?php echo $session_max_rows; ?>" />
        <input type="hidden" name="disp_direction" value="<?php echo $disp_direction; ?>" />
        <input type="hidden" name="repeat_cells" value="<?php echo $repeat_cells; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption2; ?>"<?php echo $title2; ?> />
    </form>
</td>
        <?php
    } // end move back
    ?>
<td>
    &nbsp;&nbsp;&nbsp;
</td>
<td align="center">
    <form action="sql.php" method="post"
        onsubmit="return (checkFormElementInRange(this, 'session_max_rows', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidRowNumber']); ?>', 1) &amp;&amp; checkFormElementInRange(this, 'pos', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidRowNumber']); ?>', 0, <?php echo $unlim_num_rows - 1; ?>))">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
        <input type="submit" name="navig" value="<?php echo $GLOBALS['strShow']; ?> :" />
        <input type="text" name="session_max_rows" size="3" value="<?php echo (($session_max_rows != 'all') ? $session_max_rows : $GLOBALS['cfg']['MaxRows']); ?>" class="textfield" onfocus="this.select()" />
        <?php echo $GLOBALS['strRowsFrom'] . "\n"; ?>
        <input type="text" name="pos" size="6" value="<?php echo (($pos_next >= $unlim_num_rows) ? 0 : $pos_next); ?>" class="textfield" onfocus="this.select()" />
        <br />
    <?php
    // Display mode (horizontal/vertical and repeat headers)
    $param1 = '            <select name="disp_direction">' . "\n"
            . '                <option value="horizontal"' . (($disp_direction == 'horizontal') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeHorizontal'] . '</option>' . "\n"
            . '                <option value="horizontalflipped"' . (($disp_direction == 'horizontalflipped') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeFlippedHorizontal'] . '</option>' . "\n"
            . '                <option value="vertical"' . (($disp_direction == 'vertical') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeVertical'] . '</option>' . "\n"
            . '            </select>' . "\n"
            . '           ';
    $param2 = '            <input type="text" size="3" name="repeat_cells" value="' . $repeat_cells . '" class="textfield" />' . "\n"
            . '           ';
    echo '    ' . sprintf($GLOBALS['strRowsModeOptions'], "\n" . $param1, "\n" . $param2) . "\n";
    ?>
    </form>
</td>
<td>
    &nbsp;&nbsp;&nbsp;
</td>
    <?php
    // Move to the next page or to the last one
    if (($pos + $session_max_rows < $unlim_num_rows) && $num_rows >= $session_max_rows
        && $session_max_rows != 'all') {
        // loic1: patch #474210 from Gosha Sakovich - part 2
        if ($GLOBALS['cfg']['NavigationBarIconic']) {
            $caption3 = ' &gt; ';
            $caption4 = '&gt;&gt;';
            $title3   = ' title="' . $GLOBALS['strNext'] . '"';
            $title4   = ' title="' . $GLOBALS['strEnd'] . '"';
        } else {
            $caption3 = '&gt; ' . $GLOBALS['strNext'];
            $caption4 = '&gt;&gt; ' . $GLOBALS['strEnd'];
            $title3   = '';
            $title4   = '';
        } // end if... else...
        echo "\n";
        ?>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
        <input type="hidden" name="pos" value="<?php echo $pos_next; ?>" />
        <input type="hidden" name="session_max_rows" value="<?php echo $session_max_rows; ?>" />
        <input type="hidden" name="disp_direction" value="<?php echo $disp_direction; ?>" />
        <input type="hidden" name="repeat_cells" value="<?php echo $repeat_cells; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption3; ?>"<?php echo $title3; ?> />
    </form>
</td>
<td>
    <form action="sql.php" method="post"
        onsubmit="return <?php echo (($pos + $session_max_rows < $unlim_num_rows && $num_rows >= $session_max_rows) ? 'true' : 'false'); ?>">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
        <input type="hidden" name="pos" value="<?php echo @((ceil($unlim_num_rows / $session_max_rows)- 1) * $session_max_rows); ?>" />
        <?php
        if ($is_innodb && $unlim_num_rows > $GLOBALS['cfg']['MaxExactCount']) {
            echo '<input type="hidden" name="find_real_end" value="1" />' . "\n";
            // no backquote around this message
            $onclick = ' onclick="return confirmAction(\'' . PMA_jsFormat($GLOBALS['strLongOperation'], false) . '\')"';
        }
        ?>
        <input type="hidden" name="session_max_rows" value="<?php echo $session_max_rows; ?>" />
        <input type="hidden" name="disp_direction" value="<?php echo $disp_direction; ?>" />
        <input type="hidden" name="repeat_cells" value="<?php echo $repeat_cells; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption4; ?>"<?php echo $title4; ?> <?php echo (empty($onclick) ? '' : $onclick); ?>/>
    </form>
</td>
        <?php
    } // end move toward


    //page redirection
    $pageNow = @floor($pos / $session_max_rows) + 1;
    $nbTotalPage = @ceil($unlim_num_rows / $session_max_rows);

    if ($nbTotalPage > 1){ //if1
       ?>
   <td>
       &nbsp;&nbsp;&nbsp;
   </td>
   <td>
        <?php //<form> for keep the form alignment of button < and << ?>
        <form action="none">
        <?php echo PMA_pageselector(
                     'sql.php?sql_query='        . $encoded_query .
                        '&amp;session_max_rows=' . $session_max_rows .
                        '&amp;disp_direction='   . $disp_direction .
                        '&amp;repeat_cells='     . $repeat_cells .
                        '&amp;goto='             . $goto .
                        '&amp;dontlimitchars='   . $dontlimitchars .
                        '&amp;'                  . PMA_generate_common_url($db, $table) .
                        '&amp;',
                     $session_max_rows,
                     $pageNow,
                     $nbTotalPage
              );
        ?>
        </form>
    </td>
        <?php
    } //_if1


    // Show all the records if allowed
    if ($GLOBALS['cfg']['ShowAll'] && ($num_rows < $unlim_num_rows)) {
        echo "\n";
        ?>
<td>
    &nbsp;&nbsp;&nbsp;
</td>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
        <input type="hidden" name="pos" value="0" />
        <input type="hidden" name="session_max_rows" value="all" />
        <input type="hidden" name="disp_direction" value="<?php echo $disp_direction; ?>" />
        <input type="hidden" name="repeat_cells" value="<?php echo $repeat_cells; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
        <input type="submit" name="navig" value="<?php echo $GLOBALS['strShowAll']; ?>" />
    </form>
</td>
        <?php
    } // end show all
    echo "\n";
    ?>
</tr>
</table>

    <?php
} // end of the 'PMA_displayTableNavigation()' function


/**
 * Displays the headers of the results table
 *
 * @param   array    which elements to display
 * @param   array    the list of fields properties
 * @param   integer  the total number of fields returned by the sql query
 * @param   array    the analyzed query
 *
 * @return  boolean  always true
 *
 * @global  string   $db               the database name
 * @global  string   $table            the table name
 * @global  string   $goto             the url to go back in case of errors
 * @global  boolean  $dontlimitchars   whether to limit the number of displayed
 *                                     characters of text type fields or not
 * @global  string   $sql_query        the sql query
 * @global  integer  $num_rows         the total number of rows returned by the
 *                                     sql query
 * @global  integer  $pos              the current position in results
 * @global  integer  $session_max_rows the maximum number of rows per page
 * @global  array    $vertical_display informations used with vertical display
 *                                     mode
 * @global  string   $disp_direction   the display mode
 *                                     (horizontal/vertical/horizontalflipped)
 * @global  integer  $repeat_cellsthe  number of row to display between two
 *                                     table headers
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayTableHeaders(&$is_display, &$fields_meta, $fields_cnt = 0, $analyzed_sql = '')
{
    global $db, $table, $goto, $dontlimitchars;
    global $sql_query, $num_rows, $pos, $session_max_rows;
    global $vertical_display, $disp_direction, $repeat_cells, $highlight_columns;

    if ($analyzed_sql == '') {
        $analyzed_sql = array();
    }

    // can the result be sorted?
    if ($is_display['sort_lnk'] == '1') {

        // Just as fallback
        $unsorted_sql_query     = $sql_query;
        if (isset($analyzed_sql[0]['unsorted_query'])) {
            $unsorted_sql_query = $analyzed_sql[0]['unsorted_query'];
        }

        // we need $sort_expression and $sort_expression_nodir
        // even if there are many table references

        $sort_expression = trim(str_replace('  ', ' ', $analyzed_sql[0]['order_by_clause']));

        // Get rid of ASC|DESC (TODO: analyzer)
        preg_match('@(.*)([[:space:]]*(ASC|DESC))@si', $sort_expression, $matches);
        $sort_expression_nodir = isset($matches[1]) ? trim($matches[1]) : $sort_expression;

        // sorting by indexes, only if it makes sense (only one table ref)
        if (isset($analyzed_sql) && isset($analyzed_sql[0]) &&
            isset($analyzed_sql[0]['querytype']) && $analyzed_sql[0]['querytype'] == 'SELECT' &&
            isset($analyzed_sql[0]['table_ref']) && count($analyzed_sql[0]['table_ref']) == 1) {

            // grab indexes data:
            PMA_DBI_select_db($db);
            if (!defined('PMA_IDX_INCLUDED')) {
                $ret_keys = PMA_get_indexes($table);
            }

            $prev_index = '';
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
            //    I don't know what does the following column mean....
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

            // do we have any index?
            if (isset($indexes_data)) {

                if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
                    $span = $fields_cnt;
                    if ($is_display['edit_lnk'] != 'nn') {
                        $span++;
                    }
                    if ($is_display['del_lnk'] != 'nn') {
                        $span++;
                    }
                    if ($is_display['del_lnk'] != 'kp' && $is_display['del_lnk'] != 'nn') {
                        $span++;
                    }
                } else {
                    $span = $num_rows + floor($num_rows/$repeat_cells) + 1;
                }

                echo '<form action="sql.php" method="post">' . "\n";
                echo PMA_generate_common_hidden_inputs($db, $table, 5);
                echo '<input type="hidden" name="pos" value="' . $pos .  '" />' . "\n";
                echo '<input type="hidden" name="session_max_rows" value="' . $session_max_rows . '" />' . "\n";
                echo '<input type="hidden" name="disp_direction" value="' . $disp_direction . '" />' . "\n";
                echo '<input type="hidden" name="repeat_cells" value="' . $repeat_cells . '" />' . "\n";
                echo '<input type="hidden" name="dontlimitchars" value="' . $dontlimitchars . '" />' . "\n";
                echo $GLOBALS['strSortByKey'] . ': <select name="sql_query">' . "\n";
                $used_index = false;
                $local_order = (isset($sort_expression) ? $sort_expression : '');
                foreach ($indexes_data AS $key => $val) {
                    $asc_sort = '';
                    $desc_sort = '';
                    foreach ($val AS $key2 => $val2) {
                        $asc_sort .= PMA_backquote($val2['Column_name']) . ' ASC , ';
                        $desc_sort .= PMA_backquote($val2['Column_name']) . ' DESC , ';
                    }
                    $asc_sort = substr($asc_sort, 0, -3);
                    $desc_sort = substr($desc_sort, 0, -3);
                    $used_index = $used_index || $local_order == $asc_sort || $local_order == $desc_sort;
                    echo '<option value="' . htmlspecialchars($unsorted_sql_query . ' ORDER BY ' . $asc_sort) . '"' . ($local_order == $asc_sort ? ' selected="selected"' : '') . '>' . htmlspecialchars($key) . ' (' . $GLOBALS['strAscending'] . ')</option>';
                    echo "\n";
                    echo '<option value="' . htmlspecialchars($unsorted_sql_query . ' ORDER BY ' . $desc_sort) . '"' . ($local_order == $desc_sort ? ' selected="selected"' : '') . '>' . htmlspecialchars($key) . ' (' . $GLOBALS['strDescending'] . ')</option>';
                    echo "\n";
                }
                echo '<option value="' . htmlspecialchars($unsorted_sql_query) . '"' . ($used_index ? '' : ' selected="selected"') . '>' . $GLOBALS['strNone'] . '</option>';
                echo "\n";
                echo '</select>' . "\n";
                echo '<input type="submit" value="' . $GLOBALS['strGo'] . '" />';
                echo "\n";
                echo '</form>' . "\n";
            }
        }
    }


    $vertical_display['emptypre']   = 0;
    $vertical_display['emptyafter'] = 0;
    $vertical_display['textbtn']    = '';


    // Start of form for multi-rows delete

    if ($is_display['del_lnk'] == 'dr' || $is_display['del_lnk'] == 'kp') {
        echo '<form method="post" action="tbl_row_action.php" name="rowsDeleteForm" id="rowsDeleteForm">' . "\n";
        echo PMA_generate_common_hidden_inputs($db, $table, 1);
        echo '<input type="hidden" name="disp_direction"   value="' . $disp_direction . '" />' . "\n";
        echo '<input type="hidden" name="repeat_cells"     value="' . $repeat_cells   . '" />' . "\n";
        echo '<input type="hidden" name="dontlimitchars"   value="' . $dontlimitchars . '" />' . "\n";
        echo '<input type="hidden" name="pos"              value="' . $pos . '" />' . "\n";
        echo '<input type="hidden" name="session_max_rows" value="' . $session_max_rows . '" />' . "\n";
        echo '<input type="hidden" name="goto"             value="sql.php" />' . "\n";
    }

    echo '<table id="table_results" class="data">' . "\n";
    if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
        echo '<thead><tr>' . "\n";
    }

    // 1. Displays the full/partial text button (part 1)...
    if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
        $colspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' colspan="3"'
                  : '';
    } else {
        $rowspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' rowspan="3"'
                  : '';
    }
    $text_url = 'sql.php?'
              . PMA_generate_common_url($db, $table)
              . '&amp;sql_query=' . urlencode($sql_query)
              . '&amp;session_max_rows=' . $session_max_rows
              . '&amp;pos=' . $pos
              . '&amp;disp_direction=' . $disp_direction
              . '&amp;repeat_cells=' . $repeat_cells
              . '&amp;goto=' . $goto
              . '&amp;dontlimitchars=' . (($dontlimitchars) ? 0 : 1);
    $text_message = '<img class="fulltext" src="' . $GLOBALS['pmaThemeImage'] . 's_'.($dontlimitchars ? 'partialtext' : 'fulltext') . '.png" width="50" height="20" alt="' . ($dontlimitchars ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']) . '" title="' . ($dontlimitchars ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']) . '" />';
    $text_link = PMA_linkOrButton($text_url, $text_message, array(), false);

    //     ... before the result table
    if (($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
        && $is_display['text_btn'] == '1') {
        $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 0;
        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            ?>
    <th colspan="<?php echo $fields_cnt; ?>"><?php echo $text_link; ?></th>
</tr>
<tr>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            ?>
<tr>
    <th colspan="<?php echo $num_rows + floor($num_rows/$repeat_cells) + 1; ?>">
        <?php echo $text_link; ?></th>
</tr>
            <?php
        } // end vertical mode
    }

    //     ... at the left column of the result table header if possible
    //     and required
    elseif ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && $is_display['text_btn'] == '1') {
        $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 0;
        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            ?>
    <th <?php echo $colspan; ?>><?php echo $text_link; ?></th>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            $vertical_display['textbtn'] = '    <th ' . $rowspan . ' valign="middle">' . "\n"
                                         . '        ' . $text_link . "\n"
                                         . '    </th>' . "\n";
        } // end vertical mode
    }

    //     ... elseif no button, displays empty(ies) col(s) if required
    elseif ($GLOBALS['cfg']['ModifyDeleteAtLeft']
             && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')) {
        $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 0;
        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            ?>
    <td<?php echo $colspan; ?>></td>
            <?php
        } // end horizontal/horizontalfipped mode
        else {
            $vertical_display['textbtn'] = '    <td' . $rowspan . '></td>' . "\n";
        } // end vertical mode
    }

    // 2. Displays the fields' name
    // 2.0 If sorting links should be used, checks if the query is a "JOIN"
    //     statement (see 2.1.3)

    // 2.0.1 Prepare Display column comments if enabled ($GLOBALS['cfg']['ShowBrowseComments']).
    //       Do not show comments, if using horizontalflipped mode, because of space usage
    if ($GLOBALS['cfg']['ShowBrowseComments'] && ($GLOBALS['cfgRelation']['commwork'] || PMA_MYSQL_INT_VERSION >= 40100) && $disp_direction != 'horizontalflipped') {
        $comments_map = array();
        if (isset($analyzed_sql[0]) && is_array($analyzed_sql[0])) {
            foreach ($analyzed_sql[0]['table_ref'] as $tbl) {
                $tb = $tbl['table_true_name'];
                $comments_map[$tb] = PMA_getComments($db, $tb);
                unset($tb);
            }
        }
    }

    if ($GLOBALS['cfgRelation']['commwork'] && $GLOBALS['cfgRelation']['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
        require_once './libraries/transformations.lib.php';
        $GLOBALS['mime_map'] = PMA_getMIME($db, $table);
    }

    if ($is_display['sort_lnk'] == '1') {
        //$is_join = preg_match('@(.*)[[:space:]]+FROM[[:space:]]+.*[[:space:]]+JOIN@im', $sql_query, $select_stt);
        $is_join = (isset($analyzed_sql[0]['queryflags']['join']) ? true : false);
        $select_expr = $analyzed_sql[0]['select_expr_clause'];
    } else {
        $is_join = false;
    }

    // garvin: See if we have to highlight any header fields of a WHERE query.
    //  Uses SQL-Parser results.
    $highlight_columns = array();
    if (isset($analyzed_sql) && isset($analyzed_sql[0]) &&
        isset($analyzed_sql[0]['where_clause_identifiers'])) {

        $wi = 0;
        if (isset($analyzed_sql[0]['where_clause_identifiers']) && is_array($analyzed_sql[0]['where_clause_identifiers'])) {
            foreach ($analyzed_sql[0]['where_clause_identifiers'] AS $wci_nr => $wci) {
                $highlight_columns[$wci] = 'true';
            }
        }
    }

    for ($i = 0; $i < $fields_cnt; $i++) {
        // garvin: See if this column should get highlight because it's used in the
        //  where-query.
        if (isset($highlight_columns[$fields_meta[$i]->name]) || isset($highlight_columns[PMA_backquote($fields_meta[$i]->name)])) {
            $column_style = 'style="border: 1px solid ' . $GLOBALS['cfg']['BrowseMarkerColor'] . '"';
        } else {
            $column_style = '';
        }

        // 2.0 Prepare comment-HTML-wrappers for each row, if defined/enabled.
        if (isset($comments_map) &&
                isset($comments_map[$fields_meta[$i]->table]) &&
                isset($comments_map[$fields_meta[$i]->table][$fields_meta[$i]->name])) {
            $comments = '<span class="tblcomment">' . htmlspecialchars($comments_map[$fields_meta[$i]->table][$fields_meta[$i]->name]) . '</span>';
        } else {
            $comments = '';
        }

        // 2.1 Results can be sorted
        if ($is_display['sort_lnk'] == '1') {

            // 2.1.1 Checks if the table name is required; it's the case
            //       for a query with a "JOIN" statement and if the column
            //       isn't aliased, or in queries like
            //       SELECT `1`.`master_field` , `2`.`master_field`
            //       FROM `PMA_relation` AS `1` , `PMA_relation` AS `2`

            if (($is_join
                && !preg_match('~([^[:space:],]|`[^`]`)[[:space:]]+(as[[:space:]]+)?' . strtr($fields_meta[$i]->name, array('[' => '\\[', '~' => '\\~', '\\' => '\\\\')) . '~i', $select_expr, $parts))
               || (isset($analyzed_sql[0]['select_expr'][$i]['expr'])
                   && isset($analyzed_sql[0]['select_expr'][$i]['column'])
                   && $analyzed_sql[0]['select_expr'][$i]['expr'] !=
                   $analyzed_sql[0]['select_expr'][$i]['column']
                  && isset($fields_meta[$i]->table) && strlen($fields_meta[$i]->table))) {
                $sort_tbl = PMA_backquote($fields_meta[$i]->table) . ' . ';
            } else {
                $sort_tbl = '';
            }
            // 2.1.2 Checks if the current column is used to sort the
            //       results
            if (empty($sort_expression)) {
                $is_in_sort = false;
            } else {
                // field name may be preceded by a space, or any number
                // of characters followed by a dot (tablename.fieldname)
                // so do a direct comparison
                // for the sort expression (avoids problems with queries
                // like "SELECT id, count(id)..." and clicking to sort
                // on id or on count(id))
                $is_in_sort = ($sort_tbl . PMA_backquote($fields_meta[$i]->name) == $sort_expression_nodir ? true : false);
            }
            // 2.1.3 Check the field name for backquotes.
            //       If it contains some, it's probably a function column
            //       like 'COUNT(`field`)'
            if (strpos(' ' . $fields_meta[$i]->name, '`') > 0) {
                $sort_order = ' ORDER BY \'' . $fields_meta[$i]->name . '\' ';
            } else {
                $sort_order = ' ORDER BY ' . $sort_tbl . PMA_backquote($fields_meta[$i]->name) . ' ';
            }

            // 2.1.4 Do define the sorting url
            if (!$is_in_sort) {
                // loic1: patch #455484 ("Smart" order)
                $GLOBALS['cfg']['Order']  = strtoupper($GLOBALS['cfg']['Order']);
                if ($GLOBALS['cfg']['Order'] == 'SMART') {
                    $GLOBALS['cfg']['Order'] = (preg_match('@time|date@i', $fields_meta[$i]->type)) ? 'DESC' : 'ASC';
                }
                $sort_order .= $GLOBALS['cfg']['Order'];
                $order_img   = '';
            } elseif (preg_match('@[[:space:]]ASC$@i', $sort_expression)) {
                $sort_order .= ' DESC';
                $order_img   = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_asc.png" width="11" height="9" alt="'. $GLOBALS['strAscending'] . '" title="'. $GLOBALS['strAscending'] . '" id="soimg' . $i . '" />';
            } elseif (preg_match('@[[:space:]]DESC$@i', $sort_expression)) {
                $sort_order .= ' ASC';
                $order_img   = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_desc.png" width="11" height="9" alt="'. $GLOBALS['strDescending'] . '" title="'. $GLOBALS['strDescending'] . '" id="soimg' . $i . '" />';
            } else {
                $sort_order .= ' DESC';
                $order_img   = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_asc.png" width="11" height="9" alt="'. $GLOBALS['strAscending'] . '" title="'. $GLOBALS['strAscending'] . '" id="soimg' . $i . '" />';
            }

            if (preg_match('@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|LOCK IN SHARE MODE))@i', $unsorted_sql_query, $regs3)) {
                $sorted_sql_query = $regs3[1] . $sort_order . $regs3[2];
            } else {
                $sorted_sql_query = $unsorted_sql_query . $sort_order;
            }
            $url_query = PMA_generate_common_url($db, $table)
                       . '&amp;pos=' . $pos
                       . '&amp;session_max_rows=' . $session_max_rows
                       . '&amp;disp_direction=' . $disp_direction
                       . '&amp;repeat_cells=' . $repeat_cells
                       . '&amp;dontlimitchars=' . $dontlimitchars
                       . '&amp;sql_query=' . urlencode($sorted_sql_query);
            $order_url  = 'sql.php?' . $url_query;

            // 2.1.5 Displays the sorting url
            // added 20004-06-09: Michael Keck <mail@michaelkeck.de>
            //                    enable sord order swapping for image
            $order_link_params = array();
            if (isset($order_img) && $order_img!='') {
                if (strstr($order_img, 'asc')) {
                    $order_link_params['onmouseover'] = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
                    $order_link_params['onmouseout']  = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
                } elseif (strstr($order_img, 'desc')) {
                    $order_link_params['onmouseover'] = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
                    $order_link_params['onmouseout']  = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
                }
            }
            if ($disp_direction == 'horizontalflipped'
              && $GLOBALS['cfg']['HeaderFlipType'] == 'css') {
                $order_link_params['style'] = 'direction: ltr; writing-mode: tb-rl;';
            }
            $order_link_params['title'] = $GLOBALS['strSort'];
            $order_link_content = ($disp_direction == 'horizontalflipped' && $GLOBALS['cfg']['HeaderFlipType'] == 'fake' ? PMA_flipstring(htmlspecialchars($fields_meta[$i]->name), "<br />\n") : htmlspecialchars($fields_meta[$i]->name));
            $order_link = PMA_linkOrButton($order_url, $order_link_content . $order_img, $order_link_params, false, true);

            if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
                ?>
<th <?php echo $column_style; ?> <?php if ($disp_direction == 'horizontalflipped') { echo 'valign="bottom"'; } ?>>
    <?php echo $order_link; ?>
    <?php echo $comments; ?>
</th>
                <?php
            }
            $vertical_display['desc'][] = '    <th ' . $column_style . '>' . "\n"
                                        . $order_link
                                        . $comments
                                        . '    </th>' . "\n";
        } // end if (2.1)

        // 2.2 Results can't be sorted
        else {
            if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
                ?>
<th <?php echo $column_style; ?> <?php if ($disp_direction == 'horizontalflipped') { echo 'valign="bottom"'; } ?>  <?php echo ($disp_direction == 'horizontalflipped' && $GLOBALS['cfg']['HeaderFlipType'] == 'css' ? 'style="direction: ltr; writing-mode: tb-rl;"' : ''); ?>>
    <?php echo ($disp_direction == 'horizontalflipped' && $GLOBALS['cfg']['HeaderFlipType'] == 'fake'? PMA_flipstring(htmlspecialchars($fields_meta[$i]->name), "<br />\n") : htmlspecialchars($fields_meta[$i]->name)) . "\n"; ?>
    <?php echo $comments; ?>
</th>
                <?php
            }
            $vertical_display['desc'][] = '    <th ' . $column_style . '>' . "\n"
                                        . '        ' . htmlspecialchars($fields_meta[$i]->name) . "\n"
                                        . $comments
                                        . '    </th>';
        } // end else (2.2)
    } // end for

    // 3. Displays the full/partial text button (part 2) at the right
    //    column of the result table header if possible and required...
    if ($GLOBALS['cfg']['ModifyDeleteAtRight']
        && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')
        && $is_display['text_btn'] == '1') {
        $vertical_display['emptyafter'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 1;
        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            echo "\n";
            ?>
<th <?php echo $colspan; ?>>
    <?php echo $text_link; ?>
</th>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            $vertical_display['textbtn'] = '    <th ' . $rowspan . ' valign="middle">' . "\n"
                                         . '        ' . $text_link . "\n"
                                         . '    </th>' . "\n";
        } // end vertical mode
    }

    //     ... elseif no button, displays empty cols if required
    // (unless coming from Browse mode print view)
    elseif ($GLOBALS['cfg']['ModifyDeleteAtRight']
             && ($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
             && (!$GLOBALS['is_header_sent'])) {
        $vertical_display['emptyafter'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 1;
        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            echo "\n";
            ?>
<td<?php echo $colspan; ?>></td>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            $vertical_display['textbtn'] = '    <td' . $rowspan . '></td>' . "\n";
        } // end vertical mode
    }

    if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
        ?>
</tr>
</thead>
        <?php
    }

    return true;
} // end of the 'PMA_displayTableHeaders()' function



/**
 * Displays the body of the results table
 *
 * @param   integer  the link id associated to the query which results have
 *                   to be displayed
 * @param   array    which elements to display
 * @param   array    the list of relations
 * @param   array    the analyzed query
 *
 * @return  boolean  always true
 *
 * @global  string   $db                the database name
 * @global  string   $table             the table name
 * @global  string   $goto              the url to go back in case of errors
 * @global  boolean  $dontlimitchars    whether to limit the number of displayed
 *                                      characters of text type fields or not
 * @global  string   $sql_query         the sql query
 * @global  integer  $pos               the current position in results
 * @global  integer  $session_max_rows  the maximum number of rows per page
 * @global  array    $fields_meta       the list of fields properties
 * @global  integer  $fields_cnt        the total number of fields returned by
 *                                      the sql query
 * @global  array    $vertical_display  informations used with vertical display
 *                                      mode
 * @global  string   $disp_direction    the display mode
 *                                      (horizontal/vertical/horizontalflipped)
 * @global  integer  $repeat_cells      the number of row to display between two
 *                                      table headers
 * @global  array    $highlight_columns collumn names to highlight
 * @gloabl  array    $row               current row data
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayTableBody(&$dt_result, &$is_display, $map, $analyzed_sql) {
    global $db, $table, $goto, $dontlimitchars;
    global $sql_query, $pos, $session_max_rows, $fields_meta, $fields_cnt;
    global $vertical_display, $disp_direction, $repeat_cells, $highlight_columns;
    global $row; // mostly because of browser transformations, to make the row-data accessible in a plugin

    $url_sql_query          = $sql_query;

    // query without conditions to shorten urls when needed, 200 is just
    // guess, it should depend on remaining url length

    if (isset($analyzed_sql) && isset($analyzed_sql[0]) &&
        isset($analyzed_sql[0]['querytype']) && $analyzed_sql[0]['querytype'] == 'SELECT' &&
        strlen($sql_query) > 200) {

        $url_sql_query = 'SELECT ';
        if (isset($analyzed_sql[0]['queryflags']['distinct'])) {
            $url_sql_query .= ' DISTINCT ';
        }
        $url_sql_query .= $analyzed_sql[0]['select_expr_clause'];
        if (!empty($analyzed_sql[0]['from_clause'])) {
            $url_sql_query .= ' FROM ' . $analyzed_sql[0]['from_clause'];
        }
    }

    if (!is_array($map)) {
        $map = array();
    }
    $row_no                         = 0;
    $vertical_display['edit']       = array();
    $vertical_display['delete']     = array();
    $vertical_display['data']       = array();
    $vertical_display['row_delete'] = array();

    // Correction uva 19991216 in the while below
    // Previous code assumed that all tables have keys, specifically that
    // the phpMyAdmin GUI should support row delete/edit only for such
    // tables.
    // Although always using keys is arguably the prescribed way of
    // defining a relational table, it is not required. This will in
    // particular be violated by the novice.
    // We want to encourage phpMyAdmin usage by such novices. So the code
    // below has been changed to conditionally work as before when the
    // table being displayed has one or more keys; but to display
    // delete/edit options correctly for tables without keys.

    // loic1: use 'PMA_mysql_fetch_array' rather than 'PMA_mysql_fetch_row'
    //        to get the NULL values

    // rabus: This function needs a little rework.
    //        Using MYSQL_BOTH just pollutes the memory!

    // ne0x:  Use function PMA_DBI_fetch_array() due to mysqli
    //        compatibility. Now this function is wrapped.

    $odd_row = true;
    while ($row = PMA_DBI_fetch_row($dt_result)) {
        // lem9: "vertical display" mode stuff
        if ($row_no != 0 && $repeat_cells != 0 && !($row_no % $repeat_cells)
          && ($disp_direction == 'horizontal'
            || $disp_direction == 'horizontalflipped'))
        {
            echo '<tr>' . "\n";
            if ($vertical_display['emptypre'] > 0) {
                echo '    <th colspan="' . $vertical_display['emptypre'] . '">' . "\n"
                    .'        &nbsp;</th>' . "\n";
            }

            foreach ($vertical_display['desc'] as $val) {
                echo $val;
            }

            if ($vertical_display['emptyafter'] > 0) {
                echo '    <th colspan="' . $vertical_display['emptyafter'] . '">' . "\n"
                    .'        &nbsp;</th>' . "\n";
            }
            echo '</tr>' . "\n";
        } // end if

        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            // loic1: pointer code part
            echo '    <tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n";
            $odd_row = ! $odd_row;
            $bgcolor = '';
        } elseif (isset($GLOBALS['printview']) && ($GLOBALS['printview'] == '1')) {
            $bgcolor = ' bgcolor="#ffffff" ';
        } else {
            $bgcolor = ' bgcolor="' . ($row_no % 2 ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']) . '" ';
        }


        // 1. Prepares the row (gets primary keys to use)
        // 1.1 Results from a "SELECT" statement -> builds the
        //     "primary" key to use in links
        $uva_condition     = urlencode(PMA_getUvaCondition($dt_result, $fields_cnt, $fields_meta, $row));

        // 1.2 Defines the urls for the modify/delete link(s)
        $url_query  = PMA_generate_common_url($db, $table)
                    . '&amp;pos=' . $pos
                    . '&amp;session_max_rows=' . $session_max_rows
                    . '&amp;disp_direction=' . $disp_direction
                    . '&amp;repeat_cells=' . $repeat_cells
                    . '&amp;dontlimitchars=' . $dontlimitchars;

        if ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn') {
            // We need to copy the value or else the == 'both' check will always return true

            if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
                $iconic_spacer = '<div class="nowrap">';
            } else {
                $iconic_spacer = '';
            }

            // 1.2.1 Modify link(s)
            if ($is_display['edit_lnk'] == 'ur') { // update row case
                $lnk_goto = 'sql.php';

                $edit_url = 'tbl_change.php'
                          . '?' . $url_query
                          . '&amp;primary_key=' . $uva_condition
                          . '&amp;sql_query=' . urlencode($url_sql_query)
                          . '&amp;goto=' . urlencode($lnk_goto);
                if ($GLOBALS['cfg']['PropertiesIconic'] === false) {
                    $edit_str = $GLOBALS['strEdit'];
                } else {
                    $edit_str = $iconic_spacer . '<img class="icon" width="16" height="16" src="' . $GLOBALS['pmaThemeImage'] . 'b_edit.png" alt="' . $GLOBALS['strEdit'] . '" title="' . $GLOBALS['strEdit'] . '" />';
                    if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
                        $edit_str .= ' ' . $GLOBALS['strEdit'] . '</div>';
                    }
                }
            } // end if (1.2.1)

            if ($table == $GLOBALS['cfg']['Bookmark']['table'] && $db == $GLOBALS['cfg']['Bookmark']['db'] && isset($row[1]) && isset($row[0])) {
                $bookmark_go = '<a href="import.php?'
                                . PMA_generate_common_url($row[1], '')
                                . '&amp;id_bookmark=' . $row[0]
                                . '&amp;action_bookmark=0'
                                . '&amp;action_bookmark_all=1'
                                . '&amp;SQL=' . $GLOBALS['strExecuteBookmarked']
                                .' " title="' . $GLOBALS['strExecuteBookmarked'] . '">';

                if ($GLOBALS['cfg']['PropertiesIconic'] === false) {
                    $bookmark_go .= $GLOBALS['strExecuteBookmarked'];
                } else {
                    $bookmark_go .= $iconic_spacer . '<img class="icon" width="16" height="16" src="' . $GLOBALS['pmaThemeImage'] . 'b_bookmark.png" alt="' . $GLOBALS['strExecuteBookmarked'] . '" title="' . $GLOBALS['strExecuteBookmarked'] . '" />';
                    if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
                        $bookmark_go .= ' ' . $GLOBALS['strExecuteBookmarked'] . '</div>';
                    }
                }

                $bookmark_go .= '</a>';
            } else {
                $bookmark_go = '';
            }

            // 1.2.2 Delete/Kill link(s)
            if ($is_display['del_lnk'] == 'dr') { // delete row case
                $lnk_goto = 'sql.php'
                          . '?' . str_replace('&amp;', '&', $url_query)
                          . '&sql_query=' . urlencode($url_sql_query)
                          . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                          . '&goto=' . (empty($goto) ? 'tbl_properties.php' : $goto);
                $del_query = urlencode('DELETE FROM ' . PMA_backquote($table) . ' WHERE') . $uva_condition . '+LIMIT+1';
                $del_url  = 'sql.php'
                          . '?' . $url_query
                          . '&amp;sql_query=' . $del_query
                          . '&amp;zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                          . '&amp;goto=' . urlencode($lnk_goto);
                $js_conf  = 'DELETE FROM ' . PMA_jsFormat($table)
                          . ' WHERE ' . trim(PMA_jsFormat(urldecode($uva_condition), false))
                          . ' LIMIT 1';
                if ($GLOBALS['cfg']['PropertiesIconic'] === false) {
                    $del_str = $GLOBALS['strDelete'];
                } else {
                    $del_str = $iconic_spacer . '<img class="icon" width="16" height="16" src="' . $GLOBALS['pmaThemeImage'] . 'b_drop.png" alt="' . $GLOBALS['strDelete'] . '" title="' . $GLOBALS['strDelete'] . '" />';
                    if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
                        $del_str .= ' ' . $GLOBALS['strDelete'] . '</div>';
                    }
                }
            } elseif ($is_display['del_lnk'] == 'kp') { // kill process case
                $lnk_goto = 'sql.php'
                          . '?' . str_replace('&amp;', '&', $url_query)
                          . '&sql_query=' . urlencode($url_sql_query)
                          . '&goto=main.php';
                $del_url  = 'sql.php?'
                          . PMA_generate_common_url('mysql')
                          . '&amp;sql_query=' . urlencode('KILL ' . $row[0])
                          . '&amp;goto=' . urlencode($lnk_goto);
                $del_query = urlencode('KILL ' . $row[0]);
                $js_conf  = 'KILL ' . $row[0];
                if ($GLOBALS['cfg']['PropertiesIconic'] === false) {
                    $del_str = $GLOBALS['strKill'];
                } else {
                    $del_str = $iconic_spacer . '<img class="icon" width="16" height="16" src="' . $GLOBALS['pmaThemeImage'] . 'b_drop.png" alt="' . $GLOBALS['strKill'] . '" title="' . $GLOBALS['strKill'] . '" />';
                    if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
                        $del_str .= ' ' . $GLOBALS['strKill'] . '</div>';
                    }
                }
            } // end if (1.2.2)

            // 1.3 Displays the links at left if required
            if ($GLOBALS['cfg']['ModifyDeleteAtLeft']
                && ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped')) {
                $doWriteModifyAt = 'left';
                require './libraries/display_tbl_links.lib.php';
            } // end if (1.3)
        } // end if (1)

        // 2. Displays the rows' values
        for ($i = 0; $i < $fields_cnt; ++$i) {
            $meta    = $fields_meta[$i];
            // loic1: To fix bug #474943 under php4, the row pointer will
            //        depend on whether the "is_null" php4 function is
            //        available or not
            $pointer = (function_exists('is_null') ? $i : $meta->name);
            // garvin: See if this column should get highlight because it's used in the
            //  where-query.
            if (isset($highlight_columns) && (isset($highlight_columns[$meta->name]) || isset($highlight_columns[PMA_backquote($meta->name)]))) {
                $column_style = ' style="border: 1px solid ' . $GLOBALS['cfg']['BrowseMarkerColor'] . '" ';
            } else {
                $column_style = '';
            }

            if ($disp_direction == 'vertical' && (!isset($GLOBALS['printview']) || ($GLOBALS['printview'] != '1'))) {
                if ($GLOBALS['cfg']['BrowsePointerColor'] == true) {
                    $column_style .= ' onmouseover="setVerticalPointer(this, ' . $row_no . ', \'over\', \'' . $GLOBALS['cfg']['BgcolorOne'] . '\', \'' . $GLOBALS['cfg']['BgcolorTwo'] . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"'
                              . ' onmouseout="setVerticalPointer(this, ' . $row_no . ', \'out\', \'' . $GLOBALS['cfg']['BgcolorOne'] . '\', \'' . $GLOBALS['cfg']['BgcolorTwo'] . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');" ';
                }
                if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
                    $column_style .= ' onmousedown="setVerticalPointer(this, ' . $row_no . ', \'click\', \'' . $GLOBALS['cfg']['BgcolorOne'] . '\', \'' . $GLOBALS['cfg']['BgcolorTwo'] . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\'); setCheckboxColumn(\'id_rows_to_delete' . $row_no . '\');" ';
                } else {
                    $column_style .= ' onmousedown="setCheckboxColumn(\'id_rows_to_delete' . $row_no . '\');" ';
                }
            }// end if

            // garvin: Wrap MIME-transformations. [MIME]
            $default_function = 'default_function'; // default_function
            $transform_function = $default_function;
            $transform_options = array();

            if ($GLOBALS['cfgRelation']['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {

                if (isset($GLOBALS['mime_map'][$meta->name]['mimetype']) && isset($GLOBALS['mime_map'][$meta->name]['transformation']) && !empty($GLOBALS['mime_map'][$meta->name]['transformation'])) {
                    $include_file = PMA_sanitizeTransformationFile($GLOBALS['mime_map'][$meta->name]['transformation']);

                    if (file_exists('./libraries/transformations/' . $include_file)) {
                        $transformfunction_name = preg_replace('@(\.inc\.php3?)$@i', '', $GLOBALS['mime_map'][$meta->name]['transformation']);

                        require_once './libraries/transformations/' . $include_file;

                        if (function_exists('PMA_transformation_' . $transformfunction_name)) {
                            $transform_function = 'PMA_transformation_' . $transformfunction_name;
                            $transform_options  = PMA_transformation_getOptions((isset($GLOBALS['mime_map'][$meta->name]['transformation_options']) ? $GLOBALS['mime_map'][$meta->name]['transformation_options'] : ''));
                            $meta->mimetype     = str_replace('_', '/', $GLOBALS['mime_map'][$meta->name]['mimetype']);
                        }
                    } // end if file_exists
                } // end if transformation is set
            } // end if mime/transformation works.

            $transform_options['wrapper_link'] = '?'
                                                . (isset($url_query) ? $url_query : '')
                                                . '&amp;primary_key=' . (isset($uva_condition) ? $uva_condition : '')
                                                . '&amp;sql_query=' . (isset($sql_query) ? urlencode($url_sql_query) : '')
                                                . '&amp;goto=' . (isset($sql_goto) ? urlencode($lnk_goto) : '')
                                                . '&amp;transform_key=' . urlencode($meta->name);


            // n u m e r i c
            if ($meta->numeric == 1) {


            // lem9: if two fields have the same name (this is possible
            //       with self-join queries, for example), using $meta->name
            //       will show both fields NULL even if only one is NULL,
            //       so use the $pointer
            //      (works only if function_exists('is_null')
            // PS:   why not always work with the number ($i), since
            //       the default second parameter of
            //       mysql_fetch_array() is MYSQL_BOTH, so we always get
            //       associative and numeric indices?

                //if (!isset($row[$meta->name])
                if (!isset($row[$i]) || is_null($row[$i])) {
                    $vertical_display['data'][$row_no][$i]     = '    <td align="right"' . $column_style . $bgcolor . '><i>NULL</i></td>' . "\n";
                } elseif ($row[$i] != '') {
                    $vertical_display['data'][$row_no][$i]     = '    <td align="right"' . $column_style . $bgcolor . ' class="nowrap">';

                    if (isset($analyzed_sql[0]['select_expr']) && is_array($analyzed_sql[0]['select_expr'])) {
                        foreach ($analyzed_sql[0]['select_expr'] AS $select_expr_position => $select_expr) {
                            $alias = $analyzed_sql[0]['select_expr'][$select_expr_position]['alias'];
                            if (isset($alias) && strlen($alias)) {
                                $true_column = $analyzed_sql[0]['select_expr'][$select_expr_position]['column'];
                                if ($alias == $meta->name) {
                                    $meta->name = $true_column;
                                } // end if
                            } // end if
                        } // end while
                    }

                    if (isset($map[$meta->name])) {
                        // Field to display from the foreign table?
                        if (isset($map[$meta->name][2]) && strlen($map[$meta->name][2])) {
                            $dispsql     = 'SELECT ' . PMA_backquote($map[$meta->name][2])
                                         . ' FROM ' . PMA_backquote($map[$meta->name][3]) . '.' . PMA_backquote($map[$meta->name][0])
                                         . ' WHERE ' . PMA_backquote($map[$meta->name][1])
                                         . ' = ' . $row[$i];
                            $dispresult  = PMA_DBI_try_query($dispsql, null, PMA_DBI_QUERY_STORE);
                            if ($dispresult && PMA_DBI_num_rows($dispresult) > 0) {
                                list($dispval) = PMA_DBI_fetch_row($dispresult, 0);
                            } else {
                                $dispval = $GLOBALS['strLinkNotFound'];
                            }
                            @PMA_DBI_free_result($dispresult);
                        } else {
                            $dispval     = '';
                        } // end if... else...

                        if (isset($GLOBALS['printview']) && $GLOBALS['printview'] == '1') {
                            $vertical_display['data'][$row_no][$i] .= ($transform_function != $default_function ? $transform_function($row[$i], $transform_options, $meta) : $transform_function($row[$i], array(), $meta)) . ' <code>[-&gt;' . $dispval . ']</code>';
                        } else {
                            $title = (!empty($dispval))? ' title="' . htmlspecialchars($dispval) . '"' : '';

                            $vertical_display['data'][$row_no][$i] .= '<a href="sql.php?'
                                                                   .  PMA_generate_common_url($map[$meta->name][3], $map[$meta->name][0])
                                                                   .  '&amp;pos=0&amp;session_max_rows=' . $session_max_rows . '&amp;dontlimitchars=' . $dontlimitchars
                                                                   .  '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($map[$meta->name][0]) . ' WHERE ' . PMA_backquote($map[$meta->name][1]) . ' = ' . $row[$i]) . '"' . $title . '>'
                                                                   .  ($transform_function != $default_function ? $transform_function($row[$i], $transform_options, $meta) : $transform_function($row[$i], array(), $meta)) . '</a>';
                        }
                    } else {
                        $vertical_display['data'][$row_no][$i] .= ($transform_function != $default_function ? $transform_function($row[$i], $transform_options, $meta) : $transform_function($row[$i], array(), $meta));
                    }
                    $vertical_display['data'][$row_no][$i]     .= '</td>' . "\n";
                } else {
                    $vertical_display['data'][$row_no][$i]     = '    <td align="right"' . $column_style . $bgcolor . ' class="nowrap">&nbsp;</td>' . "\n";
                }

            //  b l o b

            } elseif ($GLOBALS['cfg']['ShowBlob'] == false && stristr($meta->type, 'BLOB')) {
                // loic1 : PMA_mysql_fetch_fields returns BLOB in place of
                // TEXT fields type, however TEXT fields must be displayed
                // even if $GLOBALS['cfg']['ShowBlob'] is false -> get the true type
                // of the fields.
                $field_flags = PMA_DBI_field_flags($dt_result, $i);
                if (stristr($field_flags, 'BINARY')) {
                    $blobtext = '[BLOB';
                    if (!isset($row[$i]) || is_null($row[$i])) {
                        $blobtext .= ' - NULL';
                    } elseif (isset($row[$i])) {
                        $blob_size = strlen($row[$i]);
                        $display_blob_size = PMA_formatByteDown($blob_size, 3, 1);
                        $blobtext .= ' - '. $display_blob_size[0] . ' ' . $display_blob_size[1];
                        unset($display_blob_size);
                    }

                    $blobtext .= ']';
                    if ($blob_size > 0) {
                        $blobtext = ($default_function != $transform_function ? $transform_function($blobtext, $transform_options, $meta) : $default_function($blobtext, array(), $meta));
                    }
                    unset($blob_size);

                    $vertical_display['data'][$row_no][$i]      = '    <td align="center"' . $column_style . $bgcolor . '>' . $blobtext . '</td>';
                } else {
                    if (!isset($row[$i]) || is_null($row[$i])) {
                        $vertical_display['data'][$row_no][$i] = '    <td' . $column_style . $bgcolor . '><i>NULL</i></td>' . "\n";
                    } elseif ($row[$i] != '') {
                        // garvin: if a transform function for blob is set, none of these replacements will be made
                        if (PMA_strlen($row[$i]) > $GLOBALS['cfg']['LimitChars'] && ($dontlimitchars != 1)) {
                            $row[$i] = PMA_substr($row[$i], 0, $GLOBALS['cfg']['LimitChars']) . '...';
                        }
                        // loic1: displays all space characters, 4 space
                        // characters for tabulations and <cr>/<lf>
                        $row[$i]     = ($default_function != $transform_function ? $transform_function($row[$i], $transform_options, $meta) : $default_function($row[$i], array(), $meta));

                        $vertical_display['data'][$row_no][$i] = '    <td' . $column_style . $bgcolor . '>' . $row[$i] . '</td>' . "\n";
                    } else {
                        $vertical_display['data'][$row_no][$i] = '    <td' . $column_style . $bgcolor . '>&nbsp;</td>' . "\n";
                    }
                }
            } else {
                if (!isset($row[$i]) || is_null($row[$i])) {
                    $vertical_display['data'][$row_no][$i]     = '    <td' . $column_style . $bgcolor . '><i>NULL</i></td>' . "\n";
                } elseif ($row[$i] != '') {
                    // loic1: support blanks in the key
                    $relation_id = $row[$i];

                    // nijel: Cut all fields to $GLOBALS['cfg']['LimitChars']
                    if (PMA_strlen($row[$i]) > $GLOBALS['cfg']['LimitChars'] && ($dontlimitchars != 1)) {
                        $row[$i] = PMA_substr($row[$i], 0, $GLOBALS['cfg']['LimitChars']) . '...';
                    }

                    // loic1: displays special characters from binaries
                    $field_flags = PMA_DBI_field_flags($dt_result, $i);
                    if (stristr($field_flags, 'BINARY')) {
                        $row[$i]     = str_replace("\x00", '\0', $row[$i]);
                        $row[$i]     = str_replace("\x08", '\b', $row[$i]);
                        $row[$i]     = str_replace("\x0a", '\n', $row[$i]);
                        $row[$i]     = str_replace("\x0d", '\r', $row[$i]);
                        $row[$i]     = str_replace("\x1a", '\Z', $row[$i]);
                        $row[$i]     = ($default_function != $transform_function ? $transform_function($row[$i], $transform_options, $meta) : $default_function($row[$i], array(), $meta));
                    }
                    // loic1: displays all space characters, 4 space
                    // characters for tabulations and <cr>/<lf>
                    else {
                        $row[$i]     = ($default_function != $transform_function ? $transform_function($row[$i], $transform_options, $meta) : $default_function($row[$i], array(), $meta));
                    }

                    // garvin: transform functions may enable nowrapping:
                    $function_nowrap = $transform_function . '_nowrap';
                    $bool_nowrap = (($default_function != $transform_function && function_exists($function_nowrap)) ? $function_nowrap($transform_options) : false);

                    // loic1: do not wrap if date field type
                    $nowrap = ((preg_match('@DATE|TIME@i', $meta->type) || $bool_nowrap) ? ' nowrap="nowrap"' : '');
                    $vertical_display['data'][$row_no][$i]     = '    <td' . $column_style . $bgcolor . $nowrap . '>';

                    if (isset($analyzed_sql[0]['select_expr']) && is_array($analyzed_sql[0]['select_expr'])) {
                        foreach ($analyzed_sql[0]['select_expr'] AS $select_expr_position => $select_expr) {
                            $alias = $analyzed_sql[0]['select_expr'][$select_expr_position]['alias'];
                            if (isset($alias) && strlen($alias)) {
                                $true_column = $analyzed_sql[0]['select_expr'][$select_expr_position]['column'];
                                if ($alias == $meta->name) {
                                    $meta->name = $true_column;
                                } // end if
                            } // end if
                        } // end while
                    }

                    if (isset($map[$meta->name])) {
                        // Field to display from the foreign table?
                        if (isset($map[$meta->name][2]) && strlen($map[$meta->name][2])) {
                            $dispsql     = 'SELECT ' . PMA_backquote($map[$meta->name][2])
                                         . ' FROM ' . PMA_backquote($map[$meta->name][3]) . '.' . PMA_backquote($map[$meta->name][0])
                                         . ' WHERE ' . PMA_backquote($map[$meta->name][1])
                                         . ' = \'' . PMA_sqlAddslashes($row[$i]) . '\'';
                            $dispresult  = PMA_DBI_try_query($dispsql, null, PMA_DBI_QUERY_STORE);
                            if ($dispresult && PMA_DBI_num_rows($dispresult) > 0) {
                                list($dispval) = PMA_DBI_fetch_row($dispresult);
                                @PMA_DBI_free_result($dispresult);
                            } else {
                                $dispval = $GLOBALS['strLinkNotFound'];
                            }
                        } else {
                            $dispval = '';
                        }
                        $title = (!empty($dispval))? ' title="' . htmlspecialchars($dispval) . '"' : '';

                        $vertical_display['data'][$row_no][$i] .= '<a href="sql.php?'
                                                               .  PMA_generate_common_url($map[$meta->name][3], $map[$meta->name][0])
                                                               .  '&amp;pos=0&amp;session_max_rows=' . $session_max_rows . '&amp;dontlimitchars=' . $dontlimitchars
                                                               .  '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($map[$meta->name][0]) . ' WHERE ' . PMA_backquote($map[$meta->name][1]) . ' = \'' . PMA_sqlAddslashes($relation_id) . '\'') . '"' . $title . '>'
                                                               .  $row[$i] . '</a>';
                    } else {
                        $vertical_display['data'][$row_no][$i] .= $row[$i];
                    }
                    $vertical_display['data'][$row_no][$i]     .= '</td>' . "\n";
                } else {
                    $vertical_display['data'][$row_no][$i]     = '    <td' . $column_style . $bgcolor . '>&nbsp;</td>' . "\n";
                }
            }

            // lem9: output stored cell
            if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
                echo $vertical_display['data'][$row_no][$i];
            }

            if (isset($vertical_display['rowdata'][$i][$row_no])) {
                $vertical_display['rowdata'][$i][$row_no] .= $vertical_display['data'][$row_no][$i];
            } else {
                $vertical_display['rowdata'][$i][$row_no] = $vertical_display['data'][$row_no][$i];
            }
        } // end for (2)

        // 3. Displays the modify/delete links on the right if required
        if ($GLOBALS['cfg']['ModifyDeleteAtRight']
            && ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped')) {
                $doWriteModifyAt = 'right';
                require './libraries/display_tbl_links.lib.php';
        } // end if (3)

        if ($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') {
            ?>
</tr>
            <?php
        } // end if

        // 4. Gather links of del_urls and edit_urls in an array for later
        //    output
        if (!isset($vertical_display['edit'][$row_no])) {
            $vertical_display['edit'][$row_no]       = '';
            $vertical_display['delete'][$row_no]     = '';
            $vertical_display['row_delete'][$row_no] = '';
        }

        $column_style_vertical = '';
        if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
            $column_style_vertical .= ' onmouseover="setVerticalPointer(this, ' . $row_no . ', \'over\', \'' . $GLOBALS['cfg']['BgcolorOne'] . '\', \'' . $GLOBALS['cfg']['BgcolorTwo'] . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"'
                         . ' onmouseout="setVerticalPointer(this, ' . $row_no . ', \'out\', \'' . $GLOBALS['cfg']['BgcolorOne'] . '\', \'' . $GLOBALS['cfg']['BgcolorTwo'] . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');"';
        }
        $column_marker_vertical = '';
        if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
            $column_marker_vertical .= 'setVerticalPointer(this, ' . $row_no . ', \'click\', \'' . $GLOBALS['cfg']['BgcolorOne'] . '\', \'' . $GLOBALS['cfg']['BgcolorTwo'] . '\', \'' . $GLOBALS['cfg']['BrowsePointerColor'] . '\', \'' . $GLOBALS['cfg']['BrowseMarkerColor'] . '\');';
        }

        if (!empty($del_url) && $is_display['del_lnk'] != 'kp') {
            $vertical_display['row_delete'][$row_no] .= '    <td align="center" ' . $bgcolor . $column_style_vertical . '>' . "\n"
                                                     .  '        <input type="checkbox" id="id_rows_to_delete' . $row_no . '[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[' . $uva_condition . ']"'
                                                     .  ' onclick="' . $column_marker_vertical . 'copyCheckboxesRange(\'rowsDeleteForm\', \'id_rows_to_delete' . $row_no . '\',\'[%_PMA_CHECKBOX_DIR_%]\');"'
                                                     .  ' value="' . $del_query . '" ' . (isset($GLOBALS['checkall']) ? 'checked="checked"' : '') . ' />' . "\n"
                                                     .  '    </td>' . "\n";
        } else {
            unset($vertical_display['row_delete'][$row_no]);
        }

        if (isset($edit_url)) {
            $vertical_display['edit'][$row_no]   .= '    <td align="center"' . $bgcolor . $column_style_vertical . '>' . "\n"
                                                 . PMA_linkOrButton($edit_url, $edit_str, array(), false)
                                                 . $bookmark_go
                                                 .  '    </td>' . "\n";
        } else {
            unset($vertical_display['edit'][$row_no]);
        }

        if (isset($del_url)) {
            $vertical_display['delete'][$row_no] .= '    <td align="center"' . $bgcolor . $column_style_vertical . '>' . "\n"
                                                 . PMA_linkOrButton($del_url, $del_str, (isset($js_conf) ? $js_conf : ''), false)
                                                 .  '    </td>' . "\n";
        } else {
            unset($vertical_display['delete'][$row_no]);
        }

        echo (($disp_direction == 'horizontal' || $disp_direction == 'horizontalflipped') ? "\n" : '');
        $row_no++;
    } // end while

    if (isset($url_query)) {
        $GLOBALS['url_query'] = $url_query;
    }

    return true;
} // end of the 'PMA_displayTableBody()' function


/**
 * Do display the result table with the vertical direction mode.
 * Credits for this feature goes to Garvin Hicking <hicking@faktor-e.de>.
 *
 * @return  boolean  always true
 *
 * @global  array    $vertical_display the information to display
 * @global  integer  $repeat_cells     the number of row to display between two
 *                                     table headers
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayVerticalTable()
{
    global $vertical_display, $repeat_cells;

    // Displays "multi row delete" link at top if required
    if ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && is_array($vertical_display['row_delete']) && (count($vertical_display['row_delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        echo $vertical_display['textbtn'];
        $foo_counter = 0;
        foreach ($vertical_display['row_delete'] as $val) {
            if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                echo '<th>&nbsp;</th>' . "\n";
            }

            echo str_replace('[%_PMA_CHECKBOX_DIR_%]', '', $val);
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "edit" link at top if required
    if ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && is_array($vertical_display['edit']) && (count($vertical_display['edit']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['edit'] as $val) {
            if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                echo '    <th>&nbsp;</th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "delete" link at top if required
    if ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && is_array($vertical_display['delete']) && (count($vertical_display['delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['edit']) && !is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['delete'] as $val) {
            if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                echo '<th>&nbsp;</th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays data
    foreach ($vertical_display['desc'] AS $key => $val) {

        echo '<tr>' . "\n";
        echo $val;

        $foo_counter = 0;
        foreach ($vertical_display['rowdata'][$key] as $subval) {
            if (($foo_counter != 0) && ($repeat_cells != 0) and !($foo_counter % $repeat_cells)) {
                echo $val;
            }

            echo $subval;
            $foo_counter++;
        } // end while

        echo '</tr>' . "\n";
    } // end while

    // Displays "multi row delete" link at bottom if required
    if ($GLOBALS['cfg']['ModifyDeleteAtRight'] && is_array($vertical_display['row_delete']) && (count($vertical_display['row_delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        echo $vertical_display['textbtn'];
        $foo_counter = 0;
        foreach ($vertical_display['row_delete'] as $val) {
            if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                echo '<th>&nbsp;</th>' . "\n";
            }

            echo str_replace('[%_PMA_CHECKBOX_DIR_%]', 'r', $val);
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "edit" link at bottom if required
    if ($GLOBALS['cfg']['ModifyDeleteAtRight'] && is_array($vertical_display['edit']) && (count($vertical_display['edit']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['edit'] as $val) {
            if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                echo '<th>&nbsp;</th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "delete" link at bottom if required
    if ($GLOBALS['cfg']['ModifyDeleteAtRight'] && is_array($vertical_display['delete']) && (count($vertical_display['delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['edit']) && !is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['delete'] as $val) {
            if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                echo '<th>&nbsp;</th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    }

    return true;
} // end of the 'PMA_displayVerticalTable' function


/**
 * Displays a table of results returned by a sql query.
 * This function is called by the "sql.php" script.
 *
 * @param   integer the link id associated to the query which results have
 *                  to be displayed
 * @param   array   the display mode
 * @param   array   the analyzed query
 *
 * @global  string   $db                the database name
 * @global  string   $table             the table name
 * @global  string   $goto              the url to go back in case of errors
 * @global  boolean  $dontlimitchars    whether to limit the number of displayed
 *                                      characters of text type fields or not
 * @global  string   $sql_query         the current sql query
 * @global  integer  $num_rows          the total number of rows returned by the
 *                                      sql query
 * @global  integer  $unlim_num_rows    the total number of rows returned by the
 *                                      sql query without any programmatically
 *                                      appended "LIMIT" clause
 * @global  integer  $pos               the current postion of the first record
 *                                      to be displayed
 * @global  array    $fields_meta       the list of fields properties
 * @global  integer  $fields_cnt        the total number of fields returned by
 *                                      the sql query
 * @global  array    $vertical_display  informations used with vertical display
 *                                      mode
 * @global  string   $disp_direction    the display mode
 *                                      (horizontal/vertical/horizontalflipped)
 * @global  integer  $repeat_cells      the number of row to display between two
 *                                      table headers
 * @global  array    $highlight_columns collumn names to highlight
 * @global  array    $cfgRelation       the relation settings
 *
 * @access  private
 *
 * @see     PMA_showMessage(), PMA_setDisplayMode(),
 *          PMA_displayTableNavigation(), PMA_displayTableHeaders(),
 *          PMA_displayTableBody(), PMA_displayResultsOperations()
 */
function PMA_displayTable(&$dt_result, &$the_disp_mode, $analyzed_sql)
{
    global $db, $table, $goto, $dontlimitchars;
    global $sql_query, $num_rows, $unlim_num_rows, $pos, $fields_meta, $fields_cnt;
    global $vertical_display, $disp_direction, $repeat_cells, $highlight_columns;
    global $cfgRelation;

    // 1. ----- Prepares the work -----

    // 1.1 Gets the informations about which functionnalities should be
    //     displayed
    $total      = '';
    $is_display = PMA_setDisplayMode($the_disp_mode, $total);
    if ($total == '') {
        unset($total);
    }

    // 1.2 Defines offsets for the next and previous pages
    if ($is_display['nav_bar'] == '1') {
        if (!isset($pos)) {
            $pos          = 0;
        }
        if ($GLOBALS['session_max_rows'] == 'all') {
            $pos_next     = 0;
            $pos_prev     = 0;
        } else {
            $pos_next     = $pos + $GLOBALS['cfg']['MaxRows'];
            $pos_prev     = $pos - $GLOBALS['cfg']['MaxRows'];
            if ($pos_prev < 0) {
                $pos_prev = 0;
            }
        }
    } // end if

    // 1.3 Urlencodes the query to use in input form fields
    $encoded_sql_query = urlencode($sql_query);

    // 2. ----- Displays the top of the page -----

    // 2.1 Displays a messages with position informations
    if ($is_display['nav_bar'] == '1' && isset($pos_next)) {
        if (isset($unlim_num_rows) && $unlim_num_rows != $total) {
            $selectstring = ', ' . $unlim_num_rows . ' ' . $GLOBALS['strSelectNumRows'];
        } else {
            $selectstring = '';
        }
        $last_shown_rec = ($GLOBALS['session_max_rows'] == 'all' || $pos_next > $total)
                        ? $total - 1
                        : $pos_next - 1;
        PMA_showMessage($GLOBALS['strShowingRecords'] . " $pos - $last_shown_rec (" . PMA_formatNumber($total, 0) . ' ' . $GLOBALS['strTotal'] . $selectstring . ', ' . sprintf($GLOBALS['strQueryTime'], $GLOBALS['querytime']) . ')');
        if (PMA_Table::isView($db, $table) && $total ==  $GLOBALS['cfg']['MaxExactCount']) {
            echo '<div class="notice">' . "\n";
            echo PMA_sanitize(sprintf($GLOBALS['strViewMaxExactCount'], PMA_formatNumber($GLOBALS['cfg']['MaxExactCount'], 0), '[a@./Documentation.html#cfg_MaxExactCount@_blank]', '[/a]')) . "\n";
            echo '</div>' . "\n";
        }

    } elseif (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        PMA_showMessage($GLOBALS['strSQLQuery']);
    }

    // 2.3 Displays the navigation bars
    if (!isset($table) || strlen(trim($table)) == 0) {
        if (isset($analyzed_sql[0]['query_type'])
           && $analyzed_sql[0]['query_type'] == 'SELECT') {
            // table does not always contain a real table name,
            // for example in MySQL 5.0.x, the query SHOW STATUS
            // returns STATUS as a table name
            $table = $fields_meta[0]->table;
        } else {
            $table = '';
        }
    }
    if (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        PMA_displayResultsOperations($the_disp_mode, $analyzed_sql);
    }
    if ($is_display['nav_bar'] == '1') {
        PMA_displayTableNavigation($pos_next, $pos_prev, $encoded_sql_query);
        echo "\n";
    } elseif (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        echo "\n" . '<br /><br />' . "\n";
    }

    // 2b ----- Get field references from Database -----
    // (see the 'relation' config variable)
    // loic1, 2002-03-02: extended to php3

    // init map
    $map = array();

    // find tables
    $target=array();
    if (isset($analyzed_sql[0]['table_ref']) && is_array($analyzed_sql[0]['table_ref'])) {
        foreach ($analyzed_sql[0]['table_ref'] AS $table_ref_position => $table_ref) {
           $target[] = $analyzed_sql[0]['table_ref'][$table_ref_position]['table_true_name'];
        }
    }
    $tabs    = '(\'' . join('\',\'', $target) . '\')';

    if ($cfgRelation['displaywork']) {
        if (! isset($table) || ! strlen($table)) {
            $exist_rel = false;
        } else {
            $exist_rel = PMA_getForeigners($db, $table, '', 'both');
            if ($exist_rel) {
                foreach ($exist_rel AS $master_field => $rel) {
                    $display_field = PMA_getDisplayField($rel['foreign_db'], $rel['foreign_table']);
                    $map[$master_field] = array($rel['foreign_table'],
                                          $rel['foreign_field'],
                                          $display_field,
                                          $rel['foreign_db']);
                } // end while
            } // end if
        } // end if
    } // end if
    // end 2b

    // 3. ----- Displays the results table -----
    PMA_displayTableHeaders($is_display, $fields_meta, $fields_cnt, $analyzed_sql);
    $url_query='';
    echo '<tbody>' . "\n";
    PMA_displayTableBody($dt_result, $is_display, $map, $analyzed_sql);
    echo '</tbody>' . "\n";
    // vertical output case
    if ($disp_direction == 'vertical') {
        PMA_displayVerticalTable();
    } // end if
    unset($vertical_display);
    ?>
</table>

    <?php
    // 4. ----- Displays the link for multi-fields delete

    if ($is_display['del_lnk'] == 'dr' && $is_display['del_lnk'] != 'kp') {

        $delete_text = $is_display['del_lnk'] == 'dr' ? $GLOBALS['strDelete'] : $GLOBALS['strKill'];

        $uncheckall_url = 'sql.php?'
                  . PMA_generate_common_url($db, $table)
                  . '&amp;sql_query=' . urlencode($sql_query)
                  . '&amp;pos=' . $pos
                  . '&amp;session_max_rows=' . $GLOBALS['session_max_rows']
                  . '&amp;pos=' . $pos
                  . '&amp;disp_direction=' . $disp_direction
                  . '&amp;repeat_cells=' . $repeat_cells
                  . '&amp;goto=' . $goto
                  . '&amp;dontlimitchars=' . $dontlimitchars;
        $checkall_url = $uncheckall_url . '&amp;checkall=1';

        if ($disp_direction == 'vertical') {
            $checkall_params['onclick'] = 'if (setCheckboxes(\'rowsDeleteForm\', true)) return false;';
            $uncheckall_params['onclick'] = 'if (setCheckboxes(\'rowsDeleteForm\', false)) return false;';
        } else {
            $checkall_params['onclick'] = 'if (markAllRows(\'rowsDeleteForm\')) return false;';
            $uncheckall_params['onclick'] = 'if (unMarkAllRows(\'rowsDeleteForm\')) return false;';
        }
        $checkall_link = PMA_linkOrButton($checkall_url, $GLOBALS['strCheckAll'], $checkall_params, false);
        $uncheckall_link = PMA_linkOrButton($uncheckall_url, $GLOBALS['strUncheckAll'], $uncheckall_params, false);
        if ($disp_direction != 'vertical') {
            echo '<img class="selectallarrow" width="38" height="22"'
                .' src="' . $GLOBALS['pmaThemeImage'] . 'arrow_' . $GLOBALS['text_dir'] . '.png' . '"'
                .' alt="' . $GLOBALS['strWithChecked'] . '" />';
        }
        echo $checkall_link . "\n"
            .' / ' . "\n"
            .$uncheckall_link . "\n"
            .'<i>' . $GLOBALS['strWithChecked'] . '</i>' . "\n";

        if ($GLOBALS['cfg']['PropertiesIconic']) {
            PMA_buttonOrImage('submit_mult', 'mult_submit',
                'submit_mult_change', $GLOBALS['strChange'], 'b_edit.png');
            PMA_buttonOrImage('submit_mult', 'mult_submit',
                'submit_mult_delete', $delete_text, 'b_drop.png');
            if ($analyzed_sql[0]['querytype'] == 'SELECT') {
                PMA_buttonOrImage('submit_mult', 'mult_submit',
                    'submit_mult_export', $GLOBALS['strExport'],
                    'b_tblexport.png');
            }
            echo "\n";
        } else {
            echo ' <input type="submit" name="submit_mult"'
                .' value="' . htmlspecialchars($GLOBALS['strEdit']) . '"'
                .' title="' . $GLOBALS['strEdit'] . '" />' . "\n";
            echo ' <input type="submit" name="submit_mult"'
                .' value="' . htmlspecialchars($delete_text) . '"'
                .' title="' . $delete_text . '" />' . "\n";
            if ($analyzed_sql[0]['querytype'] == 'SELECT') {
                echo ' <input type="submit" name="submit_mult"'
                    .' value="' . htmlspecialchars($GLOBALS['strExport']) . '"'
                    .' title="' . $GLOBALS['strExport'] . '" />' . "\n";
            }
        }
        echo '<input type="hidden" name="sql_query"'
            .' value="' . htmlspecialchars($sql_query) . '" />' . "\n";
        echo '<input type="hidden" name="pos" value="' . $pos . '" />' . "\n";
        echo '<input type="hidden" name="url_query"'
            .' value="' . $GLOBALS['url_query'] . '" />' . "\n";
        echo '</form>' . "\n";
    }

    // 5. ----- Displays the navigation bar at the bottom if required -----

    if ($is_display['nav_bar'] == '1') {
        echo '<br />' . "\n";
        PMA_displayTableNavigation($pos_next, $pos_prev, $encoded_sql_query);
    } elseif (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        echo "\n" . '<br /><br />' . "\n";
    }
} // end of the 'PMA_displayTable()' function

function default_function($buffer) {
    $buffer = htmlspecialchars($buffer);
    $buffer = str_replace("\011", ' &nbsp;&nbsp;&nbsp;',
        str_replace('  ', ' &nbsp;', $buffer));
    $buffer = preg_replace("@((\015\012)|(\015)|(\012))@", '<br />', $buffer);

    return $buffer;
}

/**
 * Displays operations that are available on results.
 *
 * @param   array   the display mode
 * @param   array   the analyzed query
 *
 * @global  string   $db                the database name
 * @global  string   $table             the table name
 * @global  boolean  $dontlimitchars    whether to limit the number of displayed
 *                                      characters of text type fields or not
 * @global  integer  $pos               the current postion of the first record
 *                                      to be displayed
 * @global  string   $sql_query         the current sql query
 * @global  integer  $unlim_num_rows    the total number of rows returned by the
 *                                      sql query without any programmatically
 *                                      appended "LIMIT" clause
 * @global  string   $disp_direction    the display mode
 *                                      (horizontal/vertical/horizontalflipped)
 * @global  integer  $repeat_cells      the number of row to display between two
 *                                      table headers
 *
 * @access  private
 *
 * @see     PMA_showMessage(), PMA_setDisplayMode(),
 *          PMA_displayTableNavigation(), PMA_displayTableHeaders(),
 *          PMA_displayTableBody(), PMA_displayResultsOperations()
 */
function PMA_displayResultsOperations($the_disp_mode, $analyzed_sql) {
    global $db, $table, $dontlimitchars, $pos, $sql_query, $unlim_num_rows, $disp_direction, $repeat_cells;

    $header_shown = FALSE;
    $header = '<fieldset><legend>' . $GLOBALS['strQueryResultsOperations'] . '</legend>';

    if ($the_disp_mode[6] == '1' || $the_disp_mode[9] == '1') {
        // Displays "printable view" link if required
        if ($the_disp_mode[9] == '1') {

            if (!$header_shown) {
                echo $header;
                $header_shown = TRUE;
            }

            $url_query = '?'
                       . PMA_generate_common_url($db, $table)
                       . '&amp;pos=' . $pos
                       . '&amp;session_max_rows=' . $GLOBALS['session_max_rows']
                       . '&amp;disp_direction=' . $disp_direction
                       . '&amp;repeat_cells=' . $repeat_cells
                       . '&amp;printview=1'
                       . '&amp;sql_query=' . urlencode($sql_query);
            echo '    <!-- Print view -->' . "\n";
            echo PMA_linkOrButton(
                'sql.php' . $url_query . ((isset($dontlimitchars) && $dontlimitchars == '1') ? '&amp;dontlimitchars=1' : ''),
                ($GLOBALS['cfg']['PropertiesIconic'] ? '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_print.png" height="16" width="16" alt="' . $GLOBALS['strPrintView'] . '"/>' : '') . $GLOBALS['strPrintView'],
                '', true, true, 'print_view') . "\n";

            if (!$dontlimitchars) {
                echo   '    &nbsp;&nbsp;' . "\n";
                echo PMA_linkOrButton(
                    'sql.php' . $url_query . '&amp;dontlimitchars=1',
                    ($GLOBALS['cfg']['PropertiesIconic'] ? '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_print.png" height="16" width="16" alt="' . $GLOBALS['strPrintViewFull'] . '"/>' : '') . $GLOBALS['strPrintViewFull'],
                    '', true, true, 'print_view') . "\n";
            }
        } // end displays "printable view"

        echo "\n";
    }

    // Export link
    // (the url_query has extra parameters that won't be used to export)
    // (the single_table parameter is used in display_export.lib.php
    //  to hide the SQL and the structure export dialogs)
    if (isset($analyzed_sql[0]) && $analyzed_sql[0]['querytype'] == 'SELECT' && !isset($printview)) {
        if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name']) && !isset($analyzed_sql[0]['table_ref'][1]['table_true_name'])) {
            $single_table   = '&amp;single_table=true';
        } else {
            $single_table   = '';
        }
        if (!$header_shown) {
            echo $header;
            $header_shown = TRUE;
        }
        echo '    <!-- Export -->' . "\n";
        echo   '    &nbsp;&nbsp;' . "\n";
        echo PMA_linkOrButton(
            'tbl_properties_export.php' . $url_query . '&amp;unlim_num_rows=' . $unlim_num_rows . $single_table,
            ($GLOBALS['cfg']['PropertiesIconic'] ? '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_tblexport.png" height="16" width="16" alt="' . $GLOBALS['strExport'] . '" />' : '') . $GLOBALS['strExport'],
            '', true, true, '') . "\n";
    }
    if ($header_shown) {
        echo '</fieldset><br />';
    }
}
?>
