<?php
/* $Id$ */


/**
 * Set of functions used to display the records returned by a sql query
 */



if (!defined('__LIB_DISPLAY_TBL__')){
    define('__LIB_DISPLAY_TBL__', 1);

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
     * @access	private
     *
     * @see display_table()
     */
    function set_display_mode(&$the_disp_mode, &$the_total)
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
                $tmp = eregi('^SHOW[[:space:]]+(VARIABLES|PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS)', $GLOBALS['sql_query'], $which);
                if (strtoupper($which[1]) == 'PROCESSLIST') {
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
            $local_query = 'SELECT COUNT(*) AS total FROM ' . backquote($db) . '.' . backquote($table);
            $result      = mysql_query($local_query) or mysql_die('', $local_query, '', $err_url);
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
    } // end of the 'set_display_mode()' function


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
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see display_table()
     */
    function display_table_navigation($pos_next, $pos_prev, $encoded_query)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $num_rows, $unlim_num_rows, $pos, $sessionMaxRows;
        global $dontlimitchars;
        ?>

<!-- Navigation bar -->
<table border="0">
<tr>
        <?php
        // Move to the beginning or to the previous page
        if ($pos > 0 && $sessionMaxRows != 'all') {
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
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
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
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
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
    <td>
        <form action="sql.php3" method="post"
            onsubmit="return (checkFormElementInRange(this, 'sessionMaxRows', 1) && checkFormElementInRange(this, 'pos', 0, <?php echo $unlim_num_rows - 1; ?>))">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $GLOBALS['strShow']; ?>&nbsp;:" />
            <input type="text" name="sessionMaxRows" size="3" value="<?php echo (($sessionMaxRows != 'all') ? $sessionMaxRows : $GLOBALS['cfgMaxRows']); ?>" />
            <?php echo $GLOBALS['strRowsFrom'] . "\n"; ?>
            <input type="text" name="pos" size="3" value="<?php echo (($pos_next >= $unlim_num_rows) ? 0 : $pos_next); ?>" />
        </form>
    </td>
    <td>
        &nbsp;&nbsp;&nbsp;
    </td>
        <?php
        // Move to the next page or to the last one
        if (($pos + $sessionMaxRows < $unlim_num_rows) && $num_rows >= $sessionMaxRows
            && $sessionMaxRows != 'all') {
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
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="dontlimitchars" value="<?php echo $dontlimitchars; ?>" />
            <input type="submit" name="navig" value="<?php echo $caption3; ?>"<?php echo $title3; ?> />
        </form>
    </td>
    <td>
        <form action="sql.php3" method="post"
            onsubmit="return <?php echo (($pos + $sessionMaxRows < $unlim_num_rows && $num_rows >= $sessionMaxRows) ? 'true' : 'false'); ?>">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="sql_query" value="<?php echo $encoded_query; ?>" />
            <input type="hidden" name="pos" value="<?php echo $unlim_num_rows - $sessionMaxRows; ?>" />
            <input type="hidden" name="sessionMaxRows" value="<?php echo $sessionMaxRows; ?>" />
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
            <input type="hidden" name="sessionMaxRows" value="all" />
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
    } // end of the 'display_table_navigation()' function


    /**
     * Displays the headers of the results table
     *
     * @param   array    which elements to display
     * @param   array    the list of fields properties
     * @param   integer  the total number of fields returned by the sql query
     * @param   string   the url-encoded sql query
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
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see display_table()
     */
    function display_table_headers(&$is_display, &$fields_meta, $fields_cnt = 0, $encoded_query = '')
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $num_rows, $pos, $sessionMaxRows;
        global $dontlimitchars;

        ?>
<!-- Results table headers -->
<tr>
        <?php
        echo "\n";
        
        // 1. Displays the full/partial text button (part 1)...
        $colspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' colspan="2"'
                  : '';
        $text_url = 'sql.php3'
                  . '?lang=' . $lang
                  . '&amp;server=' . $server
                  . '&amp;db=' . urlencode($db)
                  . '&amp;table=' . urlencode($table)
                  . '&amp;sql_query=' . $encoded_query
                  . '&amp;pos=' . $pos
                  . '&amp;sessionMaxRows=' . $sessionMaxRows
                  . '&amp;pos=' . $pos
                  . '&amp;goto=' . $goto
                  . '&amp;dontlimitchars=' . (($dontlimitchars) ? 0 : 1);

        //     ... before the result table
        if (($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
            && $is_display['text_btn'] == '1') {
            ?>
    <td colspan="<?php echo $fields_cnt; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
</tr>

<tr>
            <?php
        }
        //     ... at the left column of the result table header if possible
        //     and required
        else if ($GLOBALS['cfgModifyDeleteAtLeft'] && $is_display['text_btn'] == '1') {
            echo "\n";
            ?>
    <td<?php echo $colspan; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
            <?php
        }
        //     ... else if no button, displays empty(ies) col(s) if required
        else if ($GLOBALS['cfgModifyDeleteAtLeft']
                 && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')) {
            echo "\n";
            ?>
    <td<?php echo $colspan; ?>></td>
            <?php
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
                    $sort_tbl = backquote($fields_meta[$i]->table) . '.';
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
                    $sort_order = ' ORDER BY ' . $sort_tbl . backquote($fields_meta[$i]->name) . ' ' . $cfgOrder;
                    $order_img  = '';
                }
                else if (substr($sql_order, -3) == 'ASC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . $sort_tbl . backquote($fields_meta[$i]->name) . ' DESC';
                    $order_img  = '&nbsp;<img src="./images/asc_order.gif" border="0" width="7" height="7" alt="ASC" />';
                }
                else if (substr($sql_order, -4) == 'DESC' && $is_in_sort) {
                    $sort_order = ' ORDER BY ' . $sort_tbl . backquote($fields_meta[$i]->name) . ' ASC';
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
                           . '&amp;sessionMaxRows=' . $sessionMaxRows
                           . '&amp;dontlimitchars' . $dontlimitchars
                           . '&amp;sql_query=' . urlencode($sorted_sql_query);
                // 2.1.5 Displays the sorting url
                ?>
    <th>
        <a href="sql.php3?<?php echo $url_query; ?>">
            <?php echo htmlspecialchars($fields_meta[$i]->name); ?></a><?php echo $order_img . "\n"; ?>
    </th>
                <?php
            } // end if (2.1)

            // 2.2 Results can't be sorted
            else {
                echo "\n";
                ?>
    <th>
        <?php echo htmlspecialchars($fields_meta[$i]->name) . "\n"; ?>
    </th>
                <?php
            } // end else (2.2)
            echo "\n";
        } // end for

        // 3. Displays the full/partial text button (part 2) at the right
        //    column of the result table header if possible and required...
        if ($GLOBALS['cfgModifyDeleteAtRight']
            && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')
            && $is_display['text_btn'] == '1') {
            echo "\n";
           ?>
    <td<?php echo $colspan; ?>" align="center">
        <a href="<?php echo $text_url; ?>">
            <img src="./images/<?php echo (($dontlimitchars) ? 'partialtext' : 'fulltext'); ?>.png" border="0" width="50" height="20" alt="<?php echo (($dontlimitchars) ? $GLOBALS['strPartialText'] : $GLOBALS['strFullText']); ?>" /></a>
    </td>
            <?php
        }
        //     ... else if no button, displays empty cols if required
        else if ($GLOBALS['cfgModifyDeleteAtRight']
                 && ($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')) {
            echo "\n" . '    <td' . $colspan . '></td>';
        }
        echo "\n";
        ?>
</tr>
        <?php
        echo "\n";

        return true;
    } // end of the 'display_table_headers()' function


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
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see display_table()
     */
    function display_table_body(&$dt_result, &$is_display)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $pos, $sessionMaxRow, $fields_meta, $fields_cnt;
        global $dontlimitchars;

        ?>
<!-- Results table body -->
        <?php
        echo "\n";

        $foo = 0;

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
            $bgcolor = ($foo % 2) ? $GLOBALS['cfgBgcolorOne'] : $GLOBALS['cfgBgcolorTwo'];

            ?>
<tr bgcolor="<?php echo $bgcolor; ?>" onmouseover="if (typeof(this.style) != 'undefined') this.style.backgroundColor = '<?php echo $GLOBALS['cfgPointerColor']; ?>'" onmouseout="if (typeof(this.style) != 'undefined') this.style.backgroundColor = ''">
            <?php
            echo "\n";

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
                        $condition = ' ' . backquote($primary->name) . ' ';
                        if (!isset($row[$primary->name])) {
                            $condition .= 'IS NULL AND';
                        } else {
                            $condition .= '= \'' . sql_addslashes($row[$primary->name]) . '\' AND';
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
                            . '&amp;sessionMaxRow=' . $sessionMaxRow
                            . '&amp;dontlimitchars=' . $dontlimitchars;

                // 1.2.1 Modify link(s)
                if ($is_display['edit_lnk'] == 'ur') { // update row case
                    if (!empty($goto)
                        && empty($GLOBALS['QUERY_STRING'])
                        && (empty($GLOBALS['HTTP_SERVER_VARS']) || empty($GLOBALS['HTTP_SERVER_VARS']['QUERY_STRING']))) {
                        // void
                    } else {
                        $goto = 'sql.php3';
                    }
                    $edit_url = 'tbl_change.php3'
                              . '?' . $url_query
                              . '&amp;primary_key=' . $uva_condition
                              . '&amp;sql_query=' . urlencode($sql_query)
                              . '&amp;goto=' . urlencode($goto);
                    $edit_str = $GLOBALS['strEdit'];
                } // end if (1.2.1)

                // 1.2.2 Delete/Kill link(s)
                if ($is_display['del_lnk'] == 'dr') { // delete row case
                    $goto     = 'sql.php3'
                              . '?' . str_replace('&amp;', '&', $url_query)
                              . '&sql_query=' . urlencode($sql_query)
                              . '&zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                              . '&goto=tbl_properties.php3';
                    $del_url  = 'sql.php3'
                              . '?' . $url_query
                              . '&amp;sql_query=' . urlencode('DELETE FROM ' . backquote($table) . ' WHERE') . $uva_condition . urlencode(' LIMIT 1')
                              . '&amp;zero_rows=' . urlencode(htmlspecialchars($GLOBALS['strDeleted']))
                              . '&amp;goto=' . urlencode($goto);
                    $js_conf  = 'DELETE FROM ' . js_format($table)
                              . ' WHERE ' . trim(js_format(urldecode($uva_condition), FALSE)) . ' LIMIT 1';
                    $del_str  = $GLOBALS['strDelete'];
                } else if ($is_display['del_lnk'] == 'kp') { // kill process case
                    $del_url  = 'sql.php3'
                              . '?lang=' . $lang
                              . '&amp;server=' . $server
                              . '&amp;db=mysql'
                              . '&amp;sql_query=' . urlencode('KILL ' . $row['Id'])
                              . '&amp;goto=main.php3';
                    $js_conf  = 'KILL ' . $row['Id'];
                    $del_str  = $GLOBALS['strKill'];
                } // end if (1.2.2)

                // 1.3 Displays the links at left if required
                if ($GLOBALS['cfgModifyDeleteAtLeft']) {
                    if (!empty($edit_url)) {
                        ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $edit_str; ?></a>
    </td>
                        <?php
                    }
                    if (!empty($del_url)) {
                        echo "\n";
                        ?>
    <td>
        <a href="<?php echo $del_url; ?>"
            <?php if (isset($js_conf)) echo 'onclick="return confirmLink(this, \'' . $js_conf . '\')"'; ?>>
            <?php echo $del_str; ?></a>
    </td>
                        <?php
                    }
                } // end if (1.3)
                echo "\n";
            } // end if (1)

            // 2. Displays the rows' values
            for ($i = 0; $i < $fields_cnt; ++$i) {
                $primary = $fields_meta[$i];
                if ($primary->numeric == 1) {
                    if (!isset($row[$primary->name])) {
                        echo '    <td align="right" valign="top"><i>NULL</i></td>' . "\n";
                    } else if ($row[$i] != '') {
                        echo '    <td align="right" valign="top">' . $row[$primary->name] . '</td>' . "\n";
                    } else {
                        echo '    <td align="right" valign="top">&nbsp;</td>' . "\n";
                    }
                } else if ($GLOBALS['cfgShowBlob'] == FALSE && eregi('BLOB', $primary->type)) {
                    // loic1 : mysql_fetch_fields returns BLOB in place of TEXT
                    // fields type, however TEXT fields must be displayed even
                    // if $cfgShowBlob is false -> get the true type of the
                    // fields.
                    $field_flags = mysql_field_flags($dt_result, $i);
                    if (eregi('BINARY', $field_flags)) {
                        echo '    <td align="center" valign="top">[BLOB]</td>' . "\n";
                    } else {
                        if (!isset($row[$primary->name])) {
                            echo '    <td><i>NULL</i></td>' . "\n";
                        } else if ($row[$primary->name] != '') {
                            if (strlen($row[$primary->name]) > $GLOBALS['cfgLimitChars'] && ($dontlimitchars != 1)) {
                                $row[$primary->name] = substr($row[$primary->name], 0, $GLOBALS['cfgLimitChars']) . '...';
                            }
                            // loic1: displays <cr>/<lf>
                            $row[$primary->name] = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', htmlspecialchars($row[$primary->name]));
                            echo '    <td valign="top">' . $row[$primary->name] . '</td>' . "\n";
                        } else {
                            echo '    <td valign="top">&nbsp;</td>' . "\n";
                        }
                    }
                } else {
                    if (!isset($row[$primary->name])) {
                        echo '    <td valign="top"><i>NULL</i></td>' . "\n";
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
                        // loic1: displays <cr>/<lf>
                        else {
                            $row[$primary->name] = ereg_replace("((\015\012)|(\015)|(\012))+", '<br />', htmlspecialchars($row[$primary->name]));
                        }
                        echo '    <td valign="top">' . $row[$primary->name] . '</td>' . "\n";
                    } else {
                        echo '    <td valign="top">&nbsp;</td>' . "\n";
                    }
                }
            } // end for (2)

            // 3. Displays the modify/delete links on the right if required
            if ($GLOBALS['cfgModifyDeleteAtRight']) {
                if (!empty($edit_url)) {
                    ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $edit_str; ?></a>
    </td>
                    <?php
                }
                if (!empty($del_url)) {
                    echo "\n";
                    ?>
    <td>
        <a href="<?php echo $del_url; ?>"
            <?php if (isset($js_conf)) echo 'onclick="return confirmLink(this, \'' . $js_conf . '\')"'; ?>>
            <?php echo $del_str; ?></a>
    </td>
                    <?php
                }
            } // end if (3)
            ?>
</tr>
            <?php
            echo "\n";
            $foo++;
        } // end while

        return true;
    } // end of the 'display_table_body()' function


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
     * @global  boolean  whether to limit the number of displayed characters of
     *                   text type fields or not
     *
     * @access	private
     *
     * @see     show_message(), set_display_mode(), display_table_navigation(),
     *          display_table_headers(), display_table_body()
     */
    function display_table(&$dt_result, &$the_disp_mode)
    {
        global $lang, $server, $db, $table;
        global $goto;
        global $sql_query, $num_rows, $unlim_num_rows, $pos, $fields_meta, $fields_cnt;
        global $dontlimitchars;

        // 1. ----- Prepares the work -----

        // 1.1 Gets the informations about which functionnalities should be
        //     displayed
        $total      = '';
        $is_display = set_display_mode($the_disp_mode, $total);
        if ($total == '') {
            unset($total);
        }

        // 1.2 Defines offsets for the next and previous pages
        if ($is_display['nav_bar'] == '1') {
            if (!isset($pos)) {
                $pos          = 0;
            }
            if ($GLOBALS['sessionMaxRows'] == 'all') {
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
            $last_shown_rec = ($GLOBALS['sessionMaxRows'] == 'all' || $pos_next > $total)
                            ? $total
                            : $pos_next;
            show_message($GLOBALS['strShowingRecords'] . " $pos - $last_shown_rec ($total " . $GLOBALS['strTotal'] . $selectstring . ')');
        } else {
            show_message($GLOBALS['strSQLQuery']);
        }

        // 2.3 Displays the navigation bars
        if (!isset($table) || strlen(trim($table)) == 0) {
            $table = $fields_meta[0]->table;
        }
        if ($is_display['nav_bar'] == '1') {
            display_table_navigation($pos_next, $pos_prev, $encoded_sql_query);
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
        display_table_headers($is_display, $fields_meta, $fields_cnt, $encoded_sql_query);
        display_table_body($dt_result, $is_display);
        ?>
</table>
<br />
        <?php
        echo "\n";

        // 4. ----- Displays the navigation bar at the bottom if required -----

        if ($is_display['nav_bar'] == '1') {
            display_table_navigation($pos_next, $pos_prev, $encoded_sql_query);
        } else {
            echo "\n" . '<br />' . "\n";
        }
    } // end of the 'display_table()' function

} // $__LIB_DISPLAY_TBL__
?>
