<?php
/* $Id$ */


/**
 * Set of functions used to display the records returned by a sql query
 */



if (!defined('PMA_DISPLAY_TBL_LIB_INCLUDED')){
    define('PMA_DISPLAY_TBL_LIB_INCLUDED', 1);

    /**
     * Defines the display mode to use for the results of a sql query
     *
     * It uses a synthetic string that contains all the required informations.
     * In this string:
     *   - the first two characters stand for the the action to do while
     *     clicking on the "edit" link (eg 'ur' for update a row, 'nn' for no
     *     edit link...);
     *   - the next two characters stand for the the action to do while
     *     clicking on the "delete" link (eg 'kp' for kill a process, 'nn' for
     *     no delete link...);
     *   - the next characters are boolean values (1/0) and respectively stand
     *     for sorting links, navigation bar, "insert a new row" link, the
     *     bookmark feature and the expand/collapse text/blob fields button.
     *     Of course '0'/'1' means the feature won't/will be enabled.
     *
     * @param   string   the synthetic value for display_mode (see §1 a few
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

        // 2. Display mode is not "false for all elements" -> updates the
        // display mode
        if ($the_disp_mode != 'nnnn00000') {
            // 2.1 Statement is a "SELECT COUNT", 
            //     "CHECK/ANALYZE/REPAIR/OPTIMIZE" or an "EXPLAIN"
            if ($GLOBALS['is_count'] || $GLOBALS['is_maint'] || $GLOBALS['is_explain']) {
                $do_display['edit_lnk']  = 'nn'; // no edit link
                $do_display['del_lnk']   = 'nn'; // no delete link
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '1';
                $do_display['text_btn']  = (string) '0';
            }
            // 2.2 Statement is a "SHOW..."
            else if ($GLOBALS['is_show']) {
                // 2.2.1 TODO : defines edit/delete links depending on show statement
                $tmp = eregi('^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS)', $GLOBALS['sql_query'], $which);
                if (strpos(' ' . strtoupper($which[1]), 'PROCESSLIST') > 0) {
                    $do_display['edit_lnk'] = 'nn'; // no edit link
                    $do_display['del_lnk']  = 'kp'; // "kill process" type edit link
                }
                else {
                    // Default case -> no links
                    $do_display['edit_lnk'] = 'nn'; // no edit link
                    $do_display['del_lnk']  = 'nn'; // no delete link
                }
                // 2.2.2 Other settings
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '1';
                $do_display['text_btn']  = (string) '0';
            }
            // 2.3 Other statements (ie "SELECT" ones) -> updates
            //     $do_display['edit_lnk'], $do_display['del_lnk'] and
            //     $do_display['text_btn'] (keeps other default values)
            else {
                $prev_table = $fields_meta[0]->table;
                for ($i = 0; $i < $GLOBALS['fields_cnt']; $i++) {
                    $is_link = ($do_display['edit_lnk'] != 'nn'
                                || $do_display['del_lnk'] != 'nn'
                                || $do_display['sort_lnk'] != '0'
                                || $do_display['ins_row'] != '0');
                    // 2.3.1 Displays text cut/expand button?
                    if ($do_display['text_btn'] == '0' && eregi('BLOB', $fields_meta[$i]->type)) {
                        $do_display['text_btn'] = (string) '1';
                        if (!$is_link) {
                            break;
                        }
                    } // end if (2.3.1)
                    // 2.3.2 Displays edit/delete/sort/insert links?
                    if ($is_link
                        && ($fields_meta[$i]->table == '' || $fields_meta[$i]->table != $prev_table)) {
                        $do_display['edit_lnk'] = 'nn'; // don't display links
                        $do_display['del_lnk']  = 'nn';
                        // TODO: May be problematic with same fields names in
                        //       two joined table.
                        // $do_display['sort_lnk'] = (string) '0';
                        $do_display['ins_row']   = (string) '0';
                        if ($do_display['text_btn'] == '1') {
                            break;
                        }
                    } // end if (2.3.2)
                    $prev_table = $fields_meta[$i]->table;
                } // end for
            } // end if..elseif...else (2.1 -> 2.3)
        } // end if (2)

        // 3. Gets the total number of rows if it is unknown
        if (isset($unlim_num_rows) && $unlim_num_rows != '') {
            $the_total = $unlim_num_rows;
        }
        else if (($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1')
                 && (!empty($db) && !empty($table))) {
            $local_query = 'SELECT COUNT(*) AS total FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
            $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
            $the_total   = mysql_result($result, 0, 'total');
            mysql_free_result($result);
        }

        // 4. If navigation bar or sorting fields names urls should be
        //    displayed but there is only one row, change these settings to
        //    false
        if ($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1') {
            if (isset($unlim_num_rows) && $unlim_num_rows < 2) {
                $do_display['nav_bar']  = (string) '0';
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
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the url to go back in case of errors
     * @global  integer  the total number of rows returned by the sql query
     * @global  integer  the total number of rows returned by the sql query
     *                   without any programmatically appended "LIMIT" clause
     * @global  integer  the current position in results
     * @global  mixed    the maximum number of rows per page ('all' = no limit)
     * @global  string   the display mode (horizontal/vertical)
     * @global  integer  the number of row to display between two table headers
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access  private
     *
     * @see     PMA_displayTable()
     */
    function PMA_displayTableNavigation($pos_next, $pos_prev, $encoded_query)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $num_rows, $unlim_num_rows, $pos, $session_max_rows;
        global $disp_direction, $repeat_cells;
        global $dontlimitchars;
        ?>

<!-- Navigation bar -->
<table border="0">
<tr>
        <?php
        // Move to the beginning or to the previous page
        if ($pos > 0 && $session_max_rows != 'all') {
            // loic1: patch #474210 from Gosha Sakovich - part 1
            if ($GLOBALS['cfgNavigationBarIconic']) {
                $caption1 = '&lt;&lt;';
                $caption2 = '&nbsp;&lt;&nbsp;';
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
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
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
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
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
        echo "\n";
        ?>
    <td>
        &nbsp;&nbsp;&nbsp;
    </td>
    <td align="center">
        <form action="sql.php3" method="post"
            onsubmit="return (checkFormElementInRange(this, 'session_max_rows', 1) && checkFormElementInRange(this, 'pos', 0, <?php echo $unlim_num_rows - 1; ?>))">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strShow']; ?>&nbsp;:" />
            <input type="text" name="session_max_rows" size="3" value="<?php echo (($session_max_rows != 'all') ? $session_max_rows : $GLOBALS['cfgMaxRows']); ?>" onfocus="this.select()" />
            <?php echo $GLOBALS['strRowsFrom'] . "\n"; ?>
            <input type="text" name="pos" size="3" value="<?php echo (($pos_next >= $unlim_num_rows) ? 0 : $pos_next); ?>" onfocus="this.select()" />
            <br />
        <?php
        // Display mode (horizontal/vertical and repeat headers)
        $param1 = '            <select name="disp_direction">' . "\n"
                . '                <option value="horizontal"' . (($disp_direction == 'horizontal') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeHorizontal'] . '</option>' . "\n"
                . '                <option value="vertical"' . (($disp_direction == 'vertical') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeVertical'] . '</option>' . "\n"
                . '            </select>' . "\n"
                . '           ';
        $param2 = '            <input type="text" size="3" name="repeat_cells" value="' . $repeat_cells . '" />' . "\n"
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
            if ($GLOBALS['cfgNavigationBarIconic']) {
                $caption3 = '&nbsp;&gt;&nbsp;';
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
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
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
        <form action="sql.php3" method="post"
            onsubmit="return <?php echo (($pos + $session_max_rows < $unlim_num_rows && $num_rows >= $session_max_rows) ? 'true' : 'false'); ?>">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $unlim_num_rows - $session_max_rows; ?>" />
            <input type="hidden" name="session_max_rows" value="<?php echo $session_max_rows; ?>" />
            <input type="hidden" name="disp_direction" value="<?php echo $disp_direction; ?>" />
            <input type="hidden" name="repeat_cells" value="<?php echo $repeat_cells; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $caption4; ?>"<?php echo $title4; ?> />
        </form>
    </td>
            <?php
        } // end move toward

        // Show all the records if allowed
        if ($GLOBALS['cfgShowAll'] && ($num_rows < $unlim_num_rows)) {
            echo "\n";
            ?>
    <td>
        &nbsp;&nbsp;&nbsp;
    </td>
    <td>
        <form action="sql.php3" method="post">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
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
     *
     * @return  boolean  always true
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the sql query
     * @global  string   the url to go back in case of errors
     * @global  integer  the total number of rows returned by the sql query
     * @global  integer  the current position in results
     * @global  integer  the maximum number of rows per page
     * @global  array    informations used with vertical display mode
     * @global  string   the display mode (horizontal/vertical)
     * @global  integer  the number of row to display between two table headers
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access  private
     *
     * @see     PMA_displayTable()
     */
    function PMA_displayTableHeaders(&$is_display, &$fields_meta, $fields_cnt = 0)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $num_rows, $pos, $session_max_rows;
        global $vertical_display, $disp_direction, $repeat_cells;
        global $dontlimitchars;

        if ($disp_direction == 'horizontal') {
            ?>
<!-- Results table headers -->
<tr>
            <?php
        }
        echo "\n";

        $vertical_display['emptypre']   = 0;
        $vertical_display['emptyafter'] = 0;

        // 1. Displays the full/partial text button (part 1)...
        $colspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' colspan="2"'
                  : '';
        $text_url = 'sql.php3'
                  . '?lang=' . $lang
                  . '&amp;server=' . $server
                  . '&amp;db=' . urlencode($db)
                  . '&amp;table=' . urlencode($table)
                  . '&amp;sql_query=' . urlencode($sql_query)
                  . '&amp;pos=' . $pos
                  . '&amp;session_max_rows=' . $session_max_rows
                  . '&amp;pos=' . $pos
                  . '&amp;disp_direction=' . $disp_direction
                  . '&amp;repeat_cells=' . $repeat_cells
                  . '&amp;goto=' . $goto
                  . '&amp;dontlimitchars=' . (($dontlimitchars) ? 0 : 1);

        //     ... before the result table
        if (($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
            && $is_display['text_btn'] == '1') {
            $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 2 : 1;
            if ($disp_direction == 'horizontal') {
                ?>
    <td colspan="<?php echo $fields_cnt; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
</tr>

<tr>
                <?php
            }
        }
        //     ... at the left column of the result table header if possible
        //     and required
        else if ($GLOBALS['cfgModifyDeleteAtLeft'] && $is_display['text_btn'] == '1') {
            echo "\n";
            $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 2 : 1;
            if ($disp_direction == 'horizontal') {
                ?>
    <td<?php echo $colspan; ?> align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
                <?php
            }
        }
        //     ... else if no button, displays empty(ies) col(s) if required
        else if ($GLOBALS['cfgModifyDeleteAtLeft']
                 && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')) {
            echo "\n";
            $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 2 : 1;
            if ($disp_direction == 'horizontal') {
                ?>
    <td<?php echo $colspan; ?>></td>
                <?php
            }
        }
        echo "\n";

        // 2. Displays the fields' name
        // 2.0 If sorting links should be used, checks if the query is a "JOIN"
        //     statement (see 2.1.3)
        if ($is_display['sort_lnk'] == '1') {
            $is_join = eregi('(.*)[[:space:]]+FROM[[:space:]]+.*[[:space:]]+JOIN', $sql_query, $select_stt);
        } else {
            $is_join = FALSE;
        }
        for ($i = 0; $i < $fields_cnt; $i++) {

            // 2.1 Results can be sorted
            if ($is_display['sort_lnk'] == '1') {
                // Defines the url used to append/modify a sorting order
                // 2.1.1 Checks if an hard coded 'order by' clause exists
                if (eregi('(.*)( ORDER BY (.*))', $sql_query, $regs1)) {
                    if (eregi('((.*)( ASC| DESC)( |$))(.*)', $regs1[2], $regs2)) {
                        $unsorted_sql_query = trim($regs1[1] . ' ' . $regs2[5]);
                        $sql_order          = trim($regs2[1]);
                    }
                    else if (eregi('((.*)) (LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|LOCK IN SHARE MODE)', $regs1[2], $regs3)) {
                        $unsorted_sql_query = trim($regs1[1] . ' ' . $regs3[3]);
                        $sql_order          = trim($regs3[1]) . ' ASC';
                    } else {
                        $unsorted_sql_query = trim($regs1[1]);
                        $sql_order          = trim($regs1[2]) . ' ASC';
                    }
                } else {
                    $unsorted_sql_query     = $sql_query;
                }
                // 2.1.2 Checks if the current column is used to sort the
                //       results
                if (empty($sql_order)) {
                    $is_in_sort = FALSE;
                } else {
                    $is_in_sort = eregi(' (`?)' . str_replace('\\', '\\\\', $fields_meta[$i]->name) . '(`?)[ ,$]', $sql_order);
                }
                // 2.1.3 Checks if the table name is required (it's the case
                //       for a query with a "JOIN" statement and if the column
                //       isn't aliased)
                if ($is_join
                    && !eregi('([^[:space:],]|`[^`]`)[[:space:]]+(as[[:space:]]+)?' . $fields_meta[$i]->name, $select_stt[1], $parts)) {
                    $sort_tbl = PMA_backquote($fields_meta[$i]->table) . '.';
                } else {
                    $sort_tbl = '';
                }
                // 2.1.4 Do define the sorting url
                if (!$is_in_sort) {
                    // loic1: patch #455484 ("Smart" order)
                    $cfgOrder     = strtoupper($GLOBALS['cfgOrder']);
                    if ($cfgOrder == 'SMART') {
                        $cfgOrder = (eregi('time|date', $fields_meta[$i]->type)) ? 'DESC' : 'ASC';
                    }
                    $sort_order = ' ORDER BY ' . $sort_tbl . PMA_backquote($fields_meta[$i]->name) . ' ' . $cfgOrder;
                    $order_img  = '';
                }
                else if (substr($sql_order, -3) == 'ASC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . $sort_tbl . PMA_backquote($fields_meta[$i]->name) . ' DESC';
                    $order_img  = '&nbsp;<img src="./images/asc_order.gif" border="0" width="7" height="7" alt="ASC" />';
                }
                else if (substr($sql_order, -4) == 'DESC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . $sort_tbl . PMA_backquote($fields_meta[$i]->name) . ' ASC';
                    $order_img  = '&nbsp;<img src="./images/desc_order.gif" border="0" width="7" height="7" alt="DESC" />';
                }
                if (eregi('(.*)( LIMIT (.*)| PROCEDURE (.*)| FOR UPDATE| LOCK IN SHARE MODE)', $unsorted_sql_query, $regs3)) {
                    $sorted_sql_query = $regs3[1] . $sort_order . $regs3[2];
                } else {
                    $sorted_sql_query = $unsorted_sql_query . $sort_order;
                }
                $url_query = 'lang=' . $lang
                           . '&amp;server=' . $server
                           . '&amp;db=' . urlencode($db)
                           . '&amp;table=' . urlencode($table)
                           . '&amp;pos=' . $pos
                           . '&amp;session_max_rows=' . $session_max_rows
                           . '&amp;disp_direction=' . $disp_direction
                           . '&amp;repeat_cells=' . $repeat_cells
                           . '&amp;dontlimitchars=' . $dontlimitchars
                           . '&amp;sql_query=' . urlencode($sorted_sql_query);

                // 2.1.5 Displays the sorting url
                if ($disp_direction == 'horizontal') {
                    ?>
    <th>
        <a href="sql.php3?<?php echo $url_query; ?>">
            <?php echo htmlspecialchars($fields_meta[$i]->name); ?></a><?php echo $order_img . "\n"; ?>
    </th>
                    <?php
                }
                $vertical_display['desc'][] = '    <th>' . "\n"
                                            . '        <a href="sql.php3?' . $url_query . '">' . "\n"
                                            . '            ' . htmlspecialchars($fields_meta[$i]->name) . '</a>' . $order_img . "\n"
                                            . '    </th>' . "\n";
            } // end if (2.1)

            // 2.2 Results can't be sorted
            else {
                echo "\n";
                if ($disp_direction == 'horizontal') {
                    ?>
    <th>
        <?php echo htmlspecialchars($fields_meta[$i]->name) . "\n"; ?>
    </th>
                    <?php
                }
                $vertical_display['desc'][] = '    <th>' . "\n"
                                            . '        ' . htmlspecialchars($fields_meta[$i]->name) . "\n"
                                            . '    </th>';
            } // end else (2.2)
            echo "\n";
        } // end for

        // 3. Displays the full/partial text button (part 2) at the right
        //    column of the result table header if possible and required...
        if ($GLOBALS['cfgModifyDeleteAtRight']
            && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')
            && $is_display['text_btn'] == '1') {
            echo "\n";
            $vertical_display['emptyafter'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 2 : 1;
            if ($disp_direction == 'horizontal') {
                ?>
    <td<?php echo $colspan; ?> align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
                <?php
            }
        }
        //     ... else if no button, displays empty cols if required
        else if ($GLOBALS['cfgModifyDeleteAtRight']
                 && ($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')) {
            echo "\n" . '    <td' . $colspan . '></td>';
            $vertical_display['emptyafter'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 2 : 1;
        }
        echo "\n";

        if ($disp_direction == 'horizontal') {
            ?>
</tr>
            <?php
        }
        echo "\n";

        return TRUE;
    } // end of the 'PMA_displayTableHeaders()' function


    /**
     * Displays the body of the results table
     *
     * @param   integer  the link id associated to the query which results have
     *                   to be displayed
     * @param   array    which elements to display
     * @param   array    the list of fields properties
     * @param   integer  the total number of fields returned by the sql query
     *
     * @return  boolean  always true
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the sql query
     * @global  string   the url to go back in case of errors
     * @global  integer  the current position in results
     * @global  integer  the maximum number of rows per page
     * @global  array    the list of fields properties
     * @global  integer  the total number of fields returned by the sql query
     * @global  array    informations used with vertical display mode
     * @global  string   the display mode (horizontal/vertical)
     * @global  integer  the number of row to display between two table headers
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access  private
     *
     * @see     PMA_displayTable()
     */
    function PMA_displayTableBody(&$dt_result, &$is_display)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $pos, $session_max_rows, $fields_meta, $fields_cnt;
        global $vertical_display, $disp_direction, $repeat_cells;
        global $dontlimitchars;

        ?>
<!-- Results table body -->
        <?php
        echo "\n";

        $foo                        = 0;
        $vertical_display['edit']   = array();
        $vertical_display['delete'] = array();
        $vertical_display['data']   = array();

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

        // loic1: use 'mysql_fetch_array' rather than 'mysql_fetch_row' to get
        //        the NULL values

        while ($row = mysql_fetch_array($dt_result)) {

            // lem9: "vertical display" mode stuff
            if (($foo != 0) && ($repeat_cells != 0) && !($foo % $repeat_cells) && $disp_direction == 'horizontal') {
                echo '<tr>' . "\n";

                for ($foo_i = 0; $foo_i < $vertical_display['emptypre']; $foo_i++) {
                    echo '    <td>&nbsp;</td>' . "\n";
                }

                reset($vertical_display['desc']);
                while (list($key, $val) = each($vertical_display['desc'])) {
                    echo '    ' . $val;
                }

                for ($foo_i = 0; $foo_i < $vertical_display['emptyafter']; $foo_i++) {
                    echo '    <td>&nbsp;</td>' . "\n";
                }

                echo '</tr>' . "\n";
            } // end if

            $bgcolor = ($foo % 2) ? $GLOBALS['cfgBgcolorOne'] : $GLOBALS['cfgBgcolorTwo'];

            if ($disp_direction == 'horizontal') {
                // loic1: pointer code part
                $on_mouse = ($GLOBALS['cfgBrowsePointerColor'] == '')
                          ? ''
                          : ' onmouseover="setPointer(this, \'' . $GLOBALS['cfgBrowsePointerColor'] . '\')" onmouseout="setPointer(this, \'' . $bgcolor . '\')"';

                ?>
<tr<?php echo $on_mouse; ?>>
                <?php
            }
            echo (($disp_direction == 'horizontal') ? "\n" : '');

            // 1. Prepares the row (gets primary keys to use)
            if ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn') {
                $primary_key              = '';
                $unique_key               = '';
                $uva_nonprimary_condition = '';

                // 1.1 Results from a "SELECT" statement -> builds the
                //     the "primary" key to use in links
                if ($is_display['edit_lnk'] == 'ur' /* || $is_display['edit_lnk'] == 'dr' */) {
                    for ($i = 0; $i < $fields_cnt; ++$i) {
                        $primary   = $fields_meta[$i];
                        $condition = ' ' . PMA_backquote($primary->name) . ' ';
                        if (!isset($row[$primary->name])
                            || (function_exists('is_null') && is_null($row[$primary->name]))) {
                            $condition .= 'IS NULL AND';
                        } else {
                            $condition .= '= \'' . PMA_sqlAddslashes($row[$primary->name]) . '\' AND';
                        }
                        if ($primary->primary_key > 0) {
                            $primary_key .= $condition;
                        } else if ($primary->unique_key > 0) {
                            $unique_key  .= $condition;
                        }
                        $uva_nonprimary_condition .= $condition;
                    } // end for

                    // Correction uva 19991216: prefer primary or unique keys
                    // for condition, but use conjunction of all values if no
                    // primary key
                    if ($primary_key) {
                        $uva_condition = $primary_key;
                    } else if ($unique_key) {
                        $uva_condition = $unique_key;
                    } else {
                        $uva_condition = $uva_nonprimary_condition;
                    }
                    $uva_condition     = urlencode(ereg_replace(' ?AND$', '', $uva_condition));
                } // end if (1.1)

                // 1.2 Defines the urls for the modify/delete link(s)
                $url_query  = 'lang=' . $lang
                            . '&amp;server=' . $server
                            . '&amp;db=' . urlencode($db)
                            . '&amp;table=' . urlencode($table)
                            . '&amp;pos=' . $pos
                            . '&amp;session_max_rows=' . $session_max_rows
                            . '&amp;disp_direction=' . $disp_direction
                            . '&amp;repeat_cells=' . $repeat_cells
                            . '&amp;dontlimitchars=' . $dontlimitchars;

                // 1.2.1 Modify link(s)
                if ($is_display['edit_lnk'] == 'ur') { // update row case
                    if (!empty($goto)
                        && empty($GLOBALS['QUERY_STRING'])
                        && (empty($GLOBALS['HTTP_SERVER_VARS']) || empty($GLOBALS['HTTP_SERVER_VARS']['QUERY_STRING']))) {
                        $lnk_goto = $goto;
                    } else {
                        $lnk_goto = 'sql.php3';
                    }
                    $edit_url = 'tbl_change.php3'
                              . '?' . $url_query
                              . '&amp;primary_key=' . $uva_condition
                              . '&amp;sql_query=' . urlencode($sql_query)
                              . '&amp;goto=' . urlencode($lnk_goto);
                    $edit_str = $GLOBALS['strEdit'];
                } // end if (1.2.1)

                // 1.2.2 Delete/Kill link(s)
                if ($is_display['del_lnk'] == 'dr') { // delete row case
                    $lnk_goto = 'sql.php3'
                              . '?' . str_replace('&amp;', '&', $url_query)
                              . '&sql_query=' . urlencode($sql_query)
                              . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                              . '&goto=tbl_properties.php3';
                    $del_url  = 'sql.php3'
                              . '?' . $url_query
                              . '&amp;sql_query=' . urlencode('DELETE FROM ' . PMA_backquote($table) . ' WHERE') . $uva_condition . urlencode(' LIMIT 1')
                              . '&amp;zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                              . '&amp;goto=' . urlencode($lnk_goto);
                    $js_conf  = 'DELETE FROM ' . PMA_jsFormat($table)
                              . ' WHERE ' . trim(PMA_jsFormat(urldecode($uva_condition), FALSE)) . ' LIMIT 1';
                    $del_str  = $GLOBALS['strDelete'];
                } else if ($is_display['del_lnk'] == 'kp') { // kill process case
                    $lnk_goto = 'sql.php3'
                              . '?' . str_replace('&amp;', '&', $url_query)
                              . '&sql_query=' . urlencode($sql_query)
                              . '&goto=main.php3';
                    $del_url  = 'sql.php3'
                              . '?lang=' . $lang
                              . '&amp;server=' . $server
                              . '&amp;db=mysql'
                              . '&amp;sql_query=' . urlencode('KILL ' . $row['Id'])
                              . '&amp;goto=' . urlencode($lnk_goto);
                    $js_conf  = 'KILL ' . $row['Id'];
                    $del_str  = $GLOBALS['strKill'];
                } // end if (1.2.2)

                // 1.3 Displays the links at left if required
                if ($GLOBALS['cfgModifyDeleteAtLeft']
                    && ($disp_direction == 'horizontal')) {
                    if (!empty($edit_url)) {
                        ?>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="<?php echo $edit_url; ?>">
            <?php echo $edit_str; ?></a>
    </td>
                        <?php
                    }
                    if (!empty($del_url)) {
                        echo "\n";
                        ?>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="<?php echo $del_url; ?>"
            <?php if (isset($js_conf)) echo 'onclick="return confirmLink(this, \'' . $js_conf . '\')"'; ?>>
            <?php echo $del_str; ?></a>
    </td>
                        <?php
                    }
                } // end if (1.3)
                echo (($disp_direction == 'horizontal') ? "\n" : '');
            } // end if (1)

            // 2. Displays the rows' values
            for ($i = 0; $i < $fields_cnt; ++$i) {
                $primary = $fields_meta[$i];
                if ($primary->numeric == 1) {
                    if (!isset($row[$primary->name])
                        || (function_exists('is_null') && is_null($row[$primary->name]))) {
                        $vertical_display['data'][$foo][$i]     = '    <td align="right" valign="top" bgcolor="' . $bgcolor . '"><i>NULL</i></td>' . "\n";
                    } else if ($row[$i] != '') {
                        $vertical_display['data'][$foo][$i]     = '    <td align="right" valign="top" bgcolor="' . $bgcolor . '">' . $row[$primary->name] . '</td>' . "\n";
                    } else {
                        $vertical_display['data'][$foo][$i]     = '    <td align="right" valign="top" bgcolor="' . $bgcolor . '">&nbsp;</td>' . "\n";
                    }
                } else if ($GLOBALS['cfgShowBlob'] == FALSE && eregi('BLOB', $primary->type)) {
                    // loic1 : mysql_fetch_fields returns BLOB in place of TEXT
                    // fields type, however TEXT fields must be displayed even
                    // if $cfgShowBlob is false -> get the true type of the
                    // fields.
                    $field_flags = mysql_field_flags($dt_result, $i);
                    if (eregi('BINARY', $field_flags)) {
                        $vertical_display['data'][$foo][$i]     = '    <td align="center" valign="top" bgcolor="' . $bgcolor . '">[BLOB]</td>' . "\n";
                    } else {
                        if (!isset($row[$primary->name])
                            || (function_exists('is_null') && is_null($row[$primary->name]))) {
                            $vertical_display['data'][$foo][$i] = '    <td valign="top" bgcolor="' . $bgcolor . '"><i>NULL</i></td>' . "\n";
                        } else if ($row[$primary->name] != '') {
                            if (strlen($row[$primary->name]) > $GLOBALS['cfgLimitChars'] && ($dontlimitchars != 1)) {
                                $row[$primary->name] = substr($row[$primary->name], 0, $GLOBALS['cfgLimitChars']) . '...';
                            }
                            // loic1: displays all space characters, 4 space
                            // characters for tabulations and <cr>/<lf>
                            $row[$primary->name] = htmlspecialchars($row[$primary->name]);
                            $row[$primary->name] = str_replace("\011", '&nbsp;&nbsp;&nbsp;&nbsp;', str_replace(' ', '&nbsp;', $row[$primary->name]));
                            $row[$primary->name] = ereg_replace("((\015\012)|(\015)|(\012))", '<br />', $row[$primary->name]);
                            $vertical_display['data'][$foo][$i] = '    <td valign="top" bgcolor="' . $bgcolor . '">' . $row[$primary->name] . '</td>' . "\n";
                        } else {
                            $vertical_display['data'][$foo][$i] = '    <td valign="top" bgcolor="' . $bgcolor . '">&nbsp;</td>' . "\n";
                        }
                    }
                } else {
                    if (!isset($row[$primary->name])
                        || (function_exists('is_null') && is_null($row[$primary->name]))) {
                        $vertical_display['data'][$foo][$i]     = '    <td valign="top" bgcolor="' . $bgcolor . '"><i>NULL</i></td>' . "\n";
                    } else if ($row[$primary->name] != '') {
                        // loic1: Cut text/blob fields even if $cfgShowBlob is true
                        if (eregi('BLOB', $primary->type)) {
                            if (strlen($row[$primary->name]) > $GLOBALS['cfgLimitChars'] && ($dontlimitchars != 1)) {
                                $row[$primary->name] = substr($row[$primary->name], 0, $GLOBALS['cfgLimitChars']) . '...';
                            }
                        }
                        // loic1: displays special characters from binaries
                        $field_flags = mysql_field_flags($dt_result, $i);
                        if (eregi('BINARY', $field_flags)) {
                            $row[$primary->name] = str_replace("\x00", '\0', $row[$primary->name]);
                            $row[$primary->name] = str_replace("\x08", '\b', $row[$primary->name]);
                            $row[$primary->name] = str_replace("\x0a", '\n', $row[$primary->name]);
                            $row[$primary->name] = str_replace("\x0d", '\r', $row[$primary->name]);
                            $row[$primary->name] = str_replace("\x1a", '\Z', $row[$primary->name]);
                        }
                        // loic1: displays all space characters, 4 space
                        // characters for tabulations and <cr>/<lf>
                        else {
                            $row[$primary->name] = htmlspecialchars($row[$primary->name]);
                            $row[$primary->name] = str_replace("\011", '&nbsp;&nbsp;&nbsp;&nbsp;', str_replace(' ', '&nbsp;', $row[$primary->name]));
                            $row[$primary->name] = ereg_replace("((\015\012)|(\015)|(\012))", '<br />', $row[$primary->name]);
                        }
                        $vertical_display['data'][$foo][$i]     = '    <td valign="top" bgcolor="' . $bgcolor . '">' . $row[$primary->name] . '</td>' . "\n";
                    } else {
                        $vertical_display['data'][$foo][$i]     = '    <td valign="top" bgcolor="' . $bgcolor . '">&nbsp;</td>' . "\n";
                    }
                }

                // lem9: output stored cell
                if ($disp_direction == 'horizontal') {
                    echo $vertical_display['data'][$foo][$i];
                }

                if (isset($vertical_display['rowdata'][$i][$foo])) {
                    $vertical_display['rowdata'][$i][$foo] .= $vertical_display['data'][$foo][$i];
                } else {
                    $vertical_display['rowdata'][$i][$foo] = $vertical_display['data'][$foo][$i];
                }
            } // end for (2)

            // 3. Displays the modify/delete links on the right if required
            if ($GLOBALS['cfgModifyDeleteAtRight']
                && ($disp_direction == 'horizontal')) {
                if (!empty($edit_url)) {
                    ?>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="<?php echo $edit_url; ?>">
            <?php echo $edit_str; ?></a>
    </td>
                    <?php
                }
                if (!empty($del_url)) {
                    echo "\n";
                    ?>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="<?php echo $del_url; ?>"
            <?php if (isset($js_conf)) echo 'onclick="return confirmLink(this, \'' . $js_conf . '\')"'; ?>>
            <?php echo $del_str; ?></a>
    </td>
                    <?php
                }
            } // end if (3)

            if ($disp_direction == 'horizontal') {
                echo "\n";
                ?>
</tr>
                <?php
            } // end if

            // 4. Gather links of del_urls and edit_urls in an array for later
            //    output
            if (!isset($vertical_display['edit'][$foo])) {
                $vertical_display['edit'][$foo]   = '';
                $vertical_display['delete'][$foo] = '';
            }
            if (isset($edit_url)) {
                $vertical_display['edit'][$foo]       .= '    <td bgcolor="' . $bgcolor . '">' . "\n"
                                                  .  '        <a href="' . $edit_url . '">' . "\n"
                                                  .  '            ' . $edit_str . '</a>' . "\n"
                                                  .  '    </td>' . "\n";
            }

            if (isset($del_url)) {
                $vertical_display['delete'][$foo]     .= '    <td bgcolor="' . $bgcolor . '">' . "\n"
                                                  .  '        <a href="' . $del_url . '"';
            }
            if (isset($js_conf)) {
                $vertical_display['delete'][$foo] .= 'onclick="return confirmLink(this, \'' . $js_conf . '\')"';
            }
            if (isset($del_str)) {
                $vertical_display['delete'][$foo]     .= '>' . "\n"
                                                  .  '            ' . $del_str . '</a>' . "\n"
                                                  .  '    </td>' . "\n";
            }

            echo (($disp_direction == 'horizontal') ? "\n" : '');
            $foo++;
        } // end while

        return TRUE;
    } // end of the 'PMA_displayTableBody()' function


    /**
     * Do display the result table with the vertical direction mode.
     * Credits for this feature goes to Garvin Hicking <hicking@faktor-e.de>.
     *
     * @return  boolean  always true
     *
     * @global  array    the information to display
     * @global  integer  the number of row to display between two table headers
     *
     * @access  private
     *
     * @see     PMA_displayTable()
     */
    function PMA_displayVerticalTable()
    {
        global $vertical_display, $repeat_cells;

        reset($vertical_display);

        // Displays "edit" link at top if required
        if ($GLOBALS['cfgModifyDeleteAtLeft'] && is_array($vertical_display['edit'])) {
            echo '<tr>' . "\n";
            echo '    <td>&nbsp;</td>' . "\n";
            reset($vertical_display['edit']);
            $foo_counter = 0;
            while (list($key, $val) = each($vertical_display['edit'])) {
                if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                    echo '    <td>&nbsp;</td>' . "\n";
                }

                echo $val;
                $foo_counter++;
            } // end while
            echo '</tr>' . "\n";
        } // end if

        // Displays "delete" link at top if required
        if ($GLOBALS['cfgModifyDeleteAtLeft'] && is_array($vertical_display['delete'])) {
            echo '<tr>' . "\n";
            echo '<td>&nbsp;</td>' . "\n";
            reset($vertical_display['delete']);
            $foo_counter = 0;
            while (list($key, $val) = each($vertical_display['delete'])) {
                if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                    echo '<td>&nbsp;</td>' . "\n";
                }

                echo $val;
                $foo_counter++;
            } // end while
            echo '</tr>' . "\n";
        } // end if

        // Displays data
        reset($vertical_display['desc']);
        while (list($key, $val) = each($vertical_display['desc'])) {
            echo '<tr>' . "\n";
            echo $val;

            $foo_counter = 0;
            while (list($subkey, $subval) = each($vertical_display['rowdata'][$key])) {
                if (($foo_counter != 0) && ($repeat_cells != 0) and !($foo_counter % $repeat_cells)) {
                    echo $val;
                }

                echo $subval;
                $foo_counter++;
            } // end while

            echo '</tr>' . "\n";
        } // end while

        // Displays "edit" link at bottom if required
        if ($GLOBALS['cfgModifyDeleteAtRight'] && is_array($vertical_display['edit'])) {
            echo '<tr>' . "\n";
            echo '    <td>&nbsp;</td>' . "\n";
            reset($vertical_display['edit']);
            $foo_counter = 0;
            while (list($key, $val) = each($vertical_display['edit'])) {
                if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                    echo '<td>&nbsp;</td>' . "\n";
                }

                echo $val;
                $foo_counter++;
            } // end while
            echo '</tr>' . "\n";
        } // end if

        // Displays "delete" link at bottom if required
        if ($GLOBALS['cfgModifyDeleteAtRight'] && is_array($vertical_display['delete'])) {
            echo '<tr>' . "\n";
            echo '<td>&nbsp;</td>' . "\n";
            reset($vertical_display['delete']);
            $foo_counter = 0;
            while (list($key, $val) = each($vertical_display['delete'])) {
                if (($foo_counter != 0) && ($repeat_cells != 0) && !($foo_counter % $repeat_cells)) {
                    echo '<td>&nbsp;</td>' . "\n";
                }

                echo $val;
                $foo_counter++;
            } // end while
            echo '</tr>' . "\n";
        }

        return TRUE;
    } // end of the 'PMA_displayVerticalTable' function


    /**
     * Displays a table of results returned by a sql query.
     * This function is called by the "sql.php3" script.
     *
     * @param   integer the link id associated to the query which results have
     *                  to be displayed
     * @param   array   the display mode
     *
     * @global  string   the current language
     * @global  integer  the server to use (refers to the number in the
     *                   configuration file)
     * @global  string   the database name
     * @global  string   the table name
     * @global  string   the url to go back in case of errors
     * @global  string   the current sql query
     * @global  integer  the total number of rows returned by the sql query
     * @global  integer  the total number of rows returned by the sql query
     *                   without any programmatically appended "LIMIT" clause
     * @global  integer  the current postion of the first record to be
     *                   displayed
     * @global  array    the list of fields properties
     * @global  integer  the total number of fields returned by the sql query
     * @global  array    informations used with vertical display mode
     * @global  string   the display mode (horizontal/vertical)
     * @global  integer  the number of row to display between two table headers
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access  private
     *
     * @see     PMA_showMessage(), PMA_setDisplayMode(),
     *          PMA_displayTableNavigation(), PMA_displayTableHeaders(),
     *          PMA_displayTableBody()
     */
    function PMA_displayTable(&$dt_result, &$the_disp_mode)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $num_rows, $unlim_num_rows, $pos, $fields_meta, $fields_cnt;
        global $vertical_display, $disp_direction, $repeat_cells;
        global $dontlimitchars;

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
                $pos_next     = $pos + $GLOBALS['cfgMaxRows'];
                $pos_prev     = $pos - $GLOBALS['cfgMaxRows'];
                if ($pos_prev < 0) {
                    $pos_prev = 0;
                }
            }
        } // end if

        // 1.3 Urlencodes the query to use in input form fields ($sql_query
        //     will be stripslashed in 'sql.php3' if the 'magic_quotes_gpc'
        //     directive is set to 'on')
        if (get_magic_quotes_gpc()) {
            $encoded_sql_query = urlencode(addslashes($sql_query));
        } else {
            $encoded_sql_query = urlencode($sql_query);
        }

        // 2. ----- Displays the top of the page -----

        // 2.1 Displays a messages with position informations
        if ($is_display['nav_bar'] == '1' && isset($pos_next)) {
            if (isset($unlim_num_rows) && $unlim_num_rows != $total) {
                $selectstring = ', ' . $unlim_num_rows . ' ' . $GLOBALS['strSelectNumRows'];
            } else {
                $selectstring = '';
            }
            $last_shown_rec = ($GLOBALS['session_max_rows'] == 'all' || $pos_next > $total)
                            ? $total
                            : $pos_next;
            PMA_showMessage($GLOBALS['strShowingRecords'] . " $pos - $last_shown_rec ($total " . $GLOBALS['strTotal'] . $selectstring . ')');
        } else {
            PMA_showMessage($GLOBALS['strSQLQuery']);
        }

        // 2.3 Displays the navigation bars
        if (!isset($table) || strlen(trim($table)) == 0) {
            $table = $fields_meta[0]->table;
        }
        if ($is_display['nav_bar'] == '1') {
            PMA_displayTableNavigation($pos_next, $pos_prev, $encoded_sql_query);
            echo "\n";
        } else {
            echo "\n" . '<br /><br />' . "\n";
        }

        // 3. ----- Displays the results table -----
        ?>
<!-- Results table -->
<table border="<?php echo $GLOBALS['cfgBorder']; ?>" cellpadding="5">
        <?php
        echo "\n";
        PMA_displayTableHeaders($is_display, $fields_meta, $fields_cnt);
        PMA_displayTableBody($dt_result, $is_display);
        // lem9: vertical output case
        if ($disp_direction == 'vertical') {
            PMA_displayVerticalTable();
        } // end if
        unset($vertical_display);
        ?>
</table>
<br />
        <?php

        echo "\n";

        // 4. ----- Displays the navigation bar at the bottom if required -----

        if ($is_display['nav_bar'] == '1') {
            PMA_displayTableNavigation($pos_next, $pos_prev, $encoded_sql_query);
        } else {
            echo "\n" . '<br />' . "\n";
        }
    } // end of the 'PMA_displayTable()' function

} // $__PMA_DISPLAY_TBL_LIB__
?>
