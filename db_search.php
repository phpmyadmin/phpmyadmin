<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * Credits for this script goes to Thomas Chaumeny <chaume92 at aol.com>
 */


/**
 * Gets some core libraries and send headers
 */
require('./db_details_common.php');
// If config variable $cfg['Usedbsearch'] is on FALSE : exit.
if (!$cfg['UseDbSearch']) {
    PMA_mysqlDie($strAccessDenied, '', FALSE, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php';


/**
 * Get the list of tables from the current database
 */
$tables     = PMA_DBI_get_tables($db);
$num_tables = count($tables);


/**
 * Displays top links
 */
$sub_part = '';
require('./db_details_links.php');


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
        global $err_url, $charset_connection;

        // Statement types
        $sqlstr_select = 'SELECT';
        $sqlstr_delete = 'DELETE';

        // Fields to select
        $res                  = PMA_DBI_query('SHOW ' . (PMA_MYSQL_INT_VERSION >= 40100 ? 'FULL ' : '') . 'FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($GLOBALS['db']) . ';');
        while ($current = PMA_DBI_fetch_assoc($res)) {
            if (PMA_MYSQL_INT_VERSION >= 40100) {
                list($current['Charset']) = explode('_', $current['Collation']);
            }
            $current['Field'] = PMA_backquote($current['Field']);
            $tblfields[]      = $current;
        } // while
        PMA_DBI_free_result($res);
        unset($current, $res);
        $tblfields_cnt         = count($tblfields);

        // Table to use
        $sqlstr_from = ' FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($table);

        // Beginning of WHERE clause
        $sqlstr_where    = ' WHERE';

        $search_words    = (($search_option > 2) ? array($search_str) : explode(' ', $search_str));
        $search_wds_cnt  = count($search_words);

        $like_or_regex   = (($search_option == 4) ? 'REGEXP' : 'LIKE');
        $automatic_wildcard   = (($search_option <3) ? '%' : '');

        for ($i = 0; $i < $search_wds_cnt; $i++) {
            // Eliminates empty values
            if (!empty($search_words[$i])) {
                for ($j = 0; $j < $tblfields_cnt; $j++) {
                    $prefix = PMA_MYSQL_INT_VERSION >= 40100 && $tblfields[$j]['Charset'] != $charset_connection && $tblfields[$j]['Charset'] != 'NULL'
                            ? 'CONVERT(_utf8 '
                            : '';
                    $suffix = PMA_MYSQL_INT_VERSION >= 40100 && $tblfields[$j]['Charset'] != $charset_connection && $tblfields[$j]['Charset'] != 'NULL'
                            ? ' USING ' . $tblfields[$j]['Charset'] . ') COLLATE ' . $tblfields[$j]['Collation']
                            : '';
                    $thefieldlikevalue[] = $tblfields[$j]['Field']
                                         . ' ' . $like_or_regex . ' '
                                         . $prefix
                                         . '\''
                                         . $automatic_wildcard
                                         . $search_words[$i]
                                         . $automatic_wildcard . '\''
                                         . $suffix;
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
        $sql['select_fields'] = $sqlstr_select . ' * ' . $sqlstr_from . $sqlstr_where;
        $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS count' . $sqlstr_from . $sqlstr_where;
        $sql['delete']        = $sqlstr_delete . $sqlstr_from . $sqlstr_where;

        return $sql;
    } // end of the "PMA_getSearchSqls()" function


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

        <?php
        $url_sql_query = PMA_generate_common_url($db)
                   . '&amp;goto=db_details.php'
                   . '&amp;pos=0'
                   . '&amp;is_js_confirmed=0';

        // Only one table defined in an variable $onetable
        if (isset($onetable)) {
            // Displays search string
            echo '    ' . sprintf($strSearchResultsFor, htmlspecialchars($original_search_str), $option_str) . "\n";
            echo '    <br />' . "\n";

            // Gets the SQL statements
            $newsearchsqls = PMA_getSearchSqls($onetable, $search_str, $search_option);

            // Executes the "COUNT" statement
            $res                     = PMA_DBI_query($newsearchsqls['select_count']);
            $res_cnt                 = PMA_DBI_fetch_assoc($res);
            $res_cnt                 = $res_cnt['count'];
            PMA_DBI_free_result($res);
            $num_search_result_total = $res_cnt;

            echo '    <!-- Search results in table ' . $onetable . ' (' . $res_cnt . ') -->' . "\n"
                 . '    <br />' . "\n"
                 . '    <table><tr><td>' . sprintf($strNumSearchResultsInTable, $res_cnt, htmlspecialchars($onetable)) . "</td>\n";

            if ($res_cnt > 0) {
                   echo '<td>' . PMA_linkOrButton('sql.php?' . $url_sql_query
                    . '&amp;sql_query=' .urlencode($newsearchsqls['select_fields']),
                    $strBrowse, '') .  "</td>\n";

                   echo '<td>' . PMA_linkOrButton('sql.php?' . $url_sql_query
                    . '&amp;sql_query=' .urlencode($newsearchsqls['delete']),
                    $strDelete, $newsearchsqls['delete']) .  "</td>\n";

            } // end if
            echo '</tr></table>' . "\n";
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
                $res           = PMA_DBI_query($newsearchsqls['select_count']);
                $res_cnt       = PMA_DBI_fetch_assoc($res);
                $res_cnt       = $res_cnt['count'];
                PMA_DBI_free_result($res);
                unset($res);
                $num_search_result_total += $res_cnt;

                echo '        <!-- Search results in table ' . $table_select[$i] . ' (' . $res_cnt . ') -->' . "\n"
                     . '        <li>' . "\n"
                     . '            <table><tr><td>' . sprintf($strNumSearchResultsInTable, $res_cnt, htmlspecialchars($table_select[$i])) . "</td>\n";

                if ($res_cnt > 0) {
                   echo '<td>' . PMA_linkOrButton('sql.php?' . $url_sql_query
                    . '&amp;sql_query=' .urlencode($newsearchsqls['select_fields']),
                    $strBrowse, '') .  "</td>\n";

                   echo '<td>' . PMA_linkOrButton('sql.php?' . $url_sql_query
                    . '&amp;sql_query=' .urlencode($newsearchsqls['delete']),
                    $strDelete, $newsearchsqls['delete']) .  "</td>\n";

                } // end if

                echo '        </tr></table></li>' . "\n";
            } // end for

            echo '    </ul>' . "\n";
            echo '    <p>' . sprintf($strNumSearchResultsTotal, $num_search_result_total) . '</p>' . "\n";
        } // end several tables

        echo "\n";
        ?>
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
<a name="db_search"></a>
<form method="post" action="db_search.php" name="db_search">
    <?php echo PMA_generate_common_hidden_inputs($db); ?>

    <table border="0" cellpadding="3" cellspacing="0">
    <tr>
        <th class="tblHeaders" align="center" colspan="2"><?php echo $strSearchFormTitle; ?></th>
    </tr>
    <tr><td colspan="2"></td></tr>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo $strSearchNeedle; ?>&nbsp;<br />
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <input type="text" name="search_str" size="60" value="<?php echo $searched; ?>" />
        </td>
    </tr>
    <tr><td colspan="2"></td></tr><tr>
        <td align="right" valign="top" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo $strSearchType; ?>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <input type="radio" id="search_option_1" name="search_option" value="1"<?php if ($search_option == 1) echo ' checked="checked"'; ?> /><label for="search_option_1"><?php echo $strSearchOption1; ?></label>&nbsp;*<br />
            <input type="radio" id="search_option_2" name="search_option" value="2"<?php if ($search_option == 2) echo ' checked="checked"'; ?> /><label for="search_option_2"><?php echo $strSearchOption2; ?></label>&nbsp;*<br />
            <input type="radio" id="search_option_3" name="search_option" value="3"<?php if ($search_option == 3) echo ' checked="checked"'; ?> /><label for="search_option_3"><?php echo $strSearchOption3; ?></label><br />
            <input type="radio" id="search_option_4" name="search_option" value="4"<?php if ($search_option == 4) echo ' checked="checked"'; ?> /><label for="search_option_4"><?php echo $strSearchOption4; ?></label><?php echo PMA_showMySQLDocu('Regexp', 'Regexp'); ?><br />
            <br />
            *&nbsp;<?php echo $strSplitWordsWithSpace . "\n"; ?>
        </td>
    </tr>
    <tr><td colspan="2"></td></tr>
    <tr>
        <td align="right" valign="top" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo $strSearchInTables; ?>&nbsp;
        </td>
        <td rowspan="2" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
<?php
$strDoSelectAll='&nbsp;';
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
    $strDoSelectAll = '<a href="db_search.php?' . $url_query . '&amp;selectall=1#db_search"'
                    . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', true); return false;">' . $strSelectAll . '</a>'
                    . '&nbsp;/&nbsp;'
                    . '<a href="db_search.php?' . $url_query . '&amp;unselectall=1#db_search"'
                    . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', false); return false;">' . $strUnselectAll . '</a>';
}
else {
    echo "\n";
    echo '            ' . htmlspecialchars($tables[0]) . "\n";
    echo '            <input type="hidden" name="table" value="' . htmlspecialchars($tables[0]) . '" />' . "\n";
} // end if... else...

echo"\n";
?>
        </td>
    </tr><tr><td align="right" valign="bottom" bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><?php echo $strDoSelectAll; ?></td></tr>
    <tr><td colspan="2"></td>
    </tr><tr>
        <td colspan="2" align="right" class="tblHeaders"><input type="submit" name="submit_search" value="<?php echo $strGo; ?>" id="buttonGo" /></td>
    </tr>
    </table>
</form>


<?php
/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
