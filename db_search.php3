<?php
/* $Id$ */

/**
 * Credits for this script goes to Thomas Chaumeny <chaume92 at aol.com>
 */


/**
 * Gets some core libraries and send headers
 */
require('./db_details_common.php3');
// If config variable $cfg['Usedbsearch'] is on FALSE : exit.
if (!$cfg['UseDbSearch']) {
    PMA_mysqlDie($strAccessDenied, '', FALSE, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php3';


/**
 * Get the list of tables from the current database
 */
$list_tables  = PMA_mysql_list_tables($db);
$num_tables   = ($list_tables ? mysql_num_rows($list_tables) : 0);
for ($i = 0; $i < $num_tables; $i++) {
    $tables[] = PMA_mysql_tablename($list_tables, $i);
}
if ($num_tables) {
    mysql_free_result($list_tables);
}


/**
 * Displays top links
 */
$sub_part = '';
require('./db_details_links.php3');


/**
 * 1. Main search form has been submitted
 */
if (isset($submit_search)) {

    /**
     * Builds the SQL search query
     *
     * @param   string   the table name
     * @param   string   the string to search
     * @param   integer  type of search (1 -> 1 word at least, 2 -> all words,
     *                                   3 -> exact string, 4 -> regexp)
     *
     * @return  array    3 SQL querys (for count, display and delete results)
     *
     * @global  string   the url to retun to in case of errors
     */
    function PMA_getSearchSqls($table, $search_str, $search_option)
    {
        global $err_url;

        // Statement types
        $sqlstr_select = 'SELECT';
        $sqlstr_delete = 'DELETE';

        // Fields to select
        $local_query           = 'SHOW FIELDS FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($table);
        $res                   = @PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, FALSE, $err_url);
        $res_cnt               = ($res ? mysql_num_rows($res) : 0);
        for ($i = 0; $i < $res_cnt; $i++) {
            $tblfields[]       = PMA_backquote(PMA_mysql_result($res, $i, 'field'));
        } // end if
        $sqlstr_fieldstoselect = ' ' . implode(', ', $tblfields);
        $tblfields_cnt         = count($tblfields);
        if ($res) {
            mysql_free_result($res);
        }

        // Table to use
        $sqlstr_from = ' FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($table);

        // Beginning of WHERE clause
        $sqlstr_where    = ' WHERE';

        $search_words    = (($search_option > 2) ? array($search_str) : explode(' ', $search_str));
        $search_wds_cnt  = count($search_words);

        $like_or_regex   = (($search_option == 4) ? 'REGEXP' : 'LIKE');

        for ($i = 0; $i < $search_wds_cnt; $i++) {
            // Elimines empty values
            if (!empty($search_words[$i])) {
                for ($j = 0; $j < $tblfields_cnt; $j++) {
                    $thefieldlikevalue[] = $tblfields[$j]
                                         . ' ' . $like_or_regex
                                         . ' \'' . $search_words[$i] . '\'';
                } // end for

                $fieldslikevalues[]      = ($search_wds_cnt > 1)
                                         ? '(' . implode(' OR ', $thefieldlikevalue) . ')'
                                         : implode(' OR ', $thefieldlikevalue);
                unset($thefieldlikevalue);
            } // end if
        } // end for

        $implode_str  = ($search_option == 1 ? ' OR ' : ' AND ');
        $sqlstr_where .= ' ' . implode($implode_str, $fieldslikevalues);
        unset($fieldslikevalues);

        // Builds complete queries
        $sql['select_fields'] = $sqlstr_select . $sqlstr_fieldstoselect . $sqlstr_from . $sqlstr_where;
        $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS count' . $sqlstr_from . $sqlstr_where;
        $sql['delete']        = $sqlstr_delete . $sqlstr_from . $sqlstr_where;

        return $sql;
    } // end of the "PMA_getSearchSqls()" function


    /**
     * Strip slashes if necessary
     */
    if (get_magic_quotes_gpc()) {
        $search_str               = stripslashes($search_str);
        if (isset($table)) {
            $table                = stripslashes($table);
        }
        else if (isset($table_select)) {
            $table_select_cnt     = count($table_select);
            reset($table_select);
            for ($i = 0; $i < $table_select_cnt; $i++) {
                $table_select[$i] = stripslashes($table_select[$i]);
            } // end for
        } // end if... else if...
    } // end if


    /**
     * Displays the results
     */
    if (!empty($search_str) && !empty($search_option)) {

        $original_search_str = $search_str;
        $search_str          = PMA_sqlAddslashes($search_str, TRUE);

        // Get the true string to display as option's comment
        switch ($search_option) {
            case 1:
                $option_str = ' (' . $strSearchOption1 . ')';
                break;
            case 2:
                $option_str = ' (' . $strSearchOption2 . ')';
                break;
            case 3:
                $option_str = ' (' . $strSearchOption3 . ')';
                break;
            case 4:
                $option_str = ' (' . $strSearchOption4 . ')';
                break;
        } // end switch

        // If $table is defined or if there is only one table in $table_select
        // set $onetable to the table's name (display is different if there is
        // only one table).
        //
        // Recall:
        //  $tables is an array with all tables in database $db
        //  $num_tables is the size of $tables
        if (isset($table)) {
            $onetable           = $table;
        }
        else if (isset($table_select)) {
            $num_selectedtables = count($table_select);
            if ($num_selectedtables == 1) {
                $onetable       = $table_select[0];
            }
        }
        else if ($num_tables == 1) {
            $onetable           = $tables[0];
        }
        else {
            for ($i = 0; $i < $num_tables; $i++) {
                $table_select[] = $tables[$i];
            }
            $num_selectedtables = $num_tables;
        } // end if... else if... else
        ?>
<br />

<!-- Results form -->
<form method="post" action="sql.php3" name="db_search_results_form">
    <input type="hidden" name="is_js_confirmed" value="0" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="goto" value="db_details.php3" />
    <input type="hidden" name="pos" value="0" />
        <?php
        echo "\n";
        // Only one table defined in an variable $onetable
        if (isset($onetable)) {
            // Displays search string
            echo '    ' . sprintf($strSearchResultsFor, htmlspecialchars($original_search_str), $option_str) . "\n";
            echo '    <br />' . "\n";

            // Gets the SQL statements
            $newsearchsqls = PMA_getSearchSqls($onetable, $search_str, $search_option);

            // Executes the "COUNT" statement
            $local_query   = $newsearchsqls['select_count'];
            $res           = @PMA_mysql_query($local_query)  or PMA_mysqlDie('', $local_query, FALSE, $err_url);
            if ($res) {
                $res_cnt   = PMA_mysql_result($res, 0, 'count');
                mysql_free_result($res);
            } else {
                $res_cnt   = 0;
            } // end if... else ...
            $num_search_result_total = $res_cnt;

            echo '    <!-- Search results in table ' . $onetable . ' (' . $res_cnt . ') -->' . "\n"
                 . '    <br />' . "\n"
                 . '    ' . sprintf($strNumSearchResultsInTable, $res_cnt, htmlspecialchars($onetable)) . "\n";

            if ($res_cnt > 0) {
                echo '    <input id="checkbox_select_' . $i . '" type="radio" name="sql_query" value="' . htmlspecialchars($newsearchsqls['select_fields']) . '" onclick="this.form.submit()" />' . "\n";
                echo '    <label for="checkbox_select_' . $i . '">' . $strBrowse . '</label>' . "\n";
                echo '    <input id="checkbox_delete_' . $i . '" type="radio" name="sql_query" value="' . htmlspecialchars($newsearchsqls['delete']).'" onclick="this.form.submit()" />' . "\n";
                echo '    <label for="checkbox_delete_' . $i . '">' . $strDelete . '</label>' . "\n";
            } // end if
        } // end only one table

        // Several tables defined in the array $table_select
        else if (isset($table_select)) {
            // Displays search string
            echo '    ' . sprintf($strSearchResultsFor, htmlspecialchars($original_search_str), $option_str) . "\n";
            echo '    <ul>' . "\n";

            $num_search_result_total = 0;
            for ($i = 0; $i < $num_selectedtables; $i++) {
                // Gets the SQL statements
                $newsearchsqls = PMA_getSearchSqls($table_select[$i], $search_str, $search_option);

                // Executes the "COUNT" statement
                $local_query   = $newsearchsqls['select_count'];
                $res           = @PMA_mysql_query($local_query)  or PMA_mysqlDie('', $local_query, FALSE, $err_url);
                if ($res) {
                    $res_cnt   = PMA_mysql_result($res, 0, 'count');
                    mysql_free_result($res);
                } else {
                    $res_cnt   = 0;
                } // end if... else ...
                $num_search_result_total += $res_cnt;

                echo '        <!-- Search results in table ' . $table_select[$i] . ' (' . $res_cnt . ') -->' . "\n"
                     . '        <li>' . "\n"
                     . '            ' . sprintf($strNumSearchResultsInTable, $res_cnt, htmlspecialchars($table_select[$i])) . "\n";

                if ($res_cnt > 0) {
                    echo '            <input id="checkbox_select_' . $i .'" type="radio" name="sql_query" value="' . htmlspecialchars($newsearchsqls['select_fields']) . '" onclick="this.form.submit()" />' . "\n";
                    echo '            <label for="checkbox_select_' . $i . '">' . $strBrowse . '</label>' . "\n";
                    echo '            <input id="checkbox_delete_' . $i . '" type="radio" name="sql_query" value="' . htmlspecialchars($newsearchsqls['delete']) . '" onclick="this.form.submit()" />' . "\n";
                    echo '            <label for="checkbox_delete_' . $i . '">' . $strDelete . '</label>' . "\n";
                } // end if

                echo '        </li>' . "\n";
            } // end for

            echo '    </ul>' . "\n";
            echo '    <p>' . sprintf($strNumSearchResultsTotal, $num_search_result_total) . '</p>' . "\n";
        } // end several tables

        // Submit button if string found
        if ($num_search_result_total) {
            ?>
    <script type="text/javascript" language="javascript">
    <!--
        // Fake js to allow the use of the <noscript> tag
    //-->
    </script>
    <noscript>
        <input type="submit" value="<?php echo $strGo; ?>" />
    </noscript>
            <?php
        } // end if

        echo "\n";
        ?>
</form>
<hr width="100%">
        <?php
    } // end if (!empty($search_str) && !empty($search_option))

} // end 1.


/**
 * 2. Displays the main search form
 */
echo "\n";
$searched          = (isset($original_search_str))
                   ? htmlspecialchars($original_search_str)
                   : '';
if (empty($search_option)) {
    $search_option = 1;
}
?>
<!-- Display search form -->
<p align="center">
    <b><?php echo $strSearchFormTitle; ?></b>
</p>

<a name="db_search"></a>
<form method="post" action="db_search.php3" name="db_search">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />

    <table>
    <tr>
        <td>
            <?php echo $strSearchNeedle; ?>&nbsp;
        </td>
        <td>
            <input type="text" name="search_str" size="30" value="<?php echo $searched; ?>" />
        </td>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td valign="top">
            <?php echo $strSearchType; ?>&nbsp;
        </td>
        <td>
            <input type="radio" id="search_option_1" name="search_option" value="1"<?php if ($search_option == 1) echo ' checked="checked"'; ?> />
            <label for="search_option_1"><?php echo $strSearchOption1; ?></label>&nbsp;*<br />
            <input type="radio" id="search_option_2" name="search_option" value="2"<?php if ($search_option == 2) echo ' checked="checked"'; ?> />
            <label for="search_option_2"><?php echo $strSearchOption2; ?></label>&nbsp;*<br />
            <input type="radio" id="search_option_3" name="search_option" value="3"<?php if ($search_option == 3) echo ' checked="checked"'; ?> />
            <label for="search_option_3"><?php echo $strSearchOption3; ?></label><br />
            <input type="radio" id="search_option_4" name="search_option" value="4"<?php if ($search_option == 4) echo ' checked="checked"'; ?> />
            <label for="search_option_4"><?php echo $strSearchOption4 . '</label> ' . PMA_showDocuShort('R/e/Regexp.html'); ?><br />
            <br />
            *&nbsp;<?php echo $strSplitWordsWithSpace . "\n"; ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td valign="top">
            <?php echo $strSearchInTables; ?>&nbsp;
        </td>
        <td>
<?php
if ($num_tables > 1) {
    $i = 0;

    echo '            <select name="table_select[]" size="6"  multiple="multiple">' . "\n";
    while ($i < $num_tables) {
        if (!empty($unselectall)) {
            $is_selected = '';
        }
        else if ((isset($table_select) && PMA_isInto($tables[$i], $table_select) != -1)
                || (!empty($selectall))
                || (isset($onetable) && $onetable == $tables[$i])) {
            $is_selected = ' selected="selected"';
        }
        else {
            $is_selected = '';
        }

        echo '                <option value="' . htmlspecialchars($tables[$i]) . '"' . $is_selected . '>' . htmlspecialchars($tables[$i]) . '</option>' . "\n";
        $i++;
    } // end while
    echo '            </select>' . "\n";
    ?>
            <br />
            <a href="db_search.php3?<?php echo $url_query; ?>&amp;selectall=1#db_search" onclick="setSelectOptions('db_search', 'table_select[]', true); return false;"><?php echo $strSelectAll; ?></a>
            &nbsp;/&nbsp;
            <a href="db_search.php3?<?php echo $url_query; ?>&amp;unselectall=1#db_search" onclick="setSelectOptions('db_search', 'table_select[]', false); return false;"><?php echo $strUnselectAll; ?></a>
    <?php
}
else {
    echo "\n";
    echo '            ' . htmlspecialchars($tables[0]) . "\n";
    echo '            <input type="hidden" name="table" value="' . htmlspecialchars($tables[0]) . '" />' . "\n";
} // end if... else...

echo"\n";
?>
        </td>
    </tr>

    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2"><input type="submit" name="submit_search" value="<?php echo $strGo; ?>" /></td>
    </tr>
    </table>
</form>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>