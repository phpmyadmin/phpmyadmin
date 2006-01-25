<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * Credits for this script goes to Thomas Chaumeny <chaume92 at aol.com>
 */

require_once('./libraries/common.lib.php');

/**
 * Gets some core libraries and send headers
 */
require('./libraries/db_details_common.inc.php');
// If config variable $cfg['Usedbsearch'] is on FALSE : exit.
if (!$cfg['UseDbSearch']) {
    PMA_mysqlDie($strAccessDenied, '', FALSE, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

/**
 * Get the list of tables from the current database
 */
$tables     = PMA_DBI_get_tables($GLOBALS['db']);
$num_tables = count( $tables );

/**
 * Displays top links
 */
$sub_part = '';
require('./libraries/db_details_links.inc.php');


/**
 * 1. Main search form has been submitted
 */
if (isset($_REQUEST['submit_search'])) {

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
     * @global  string   the url to return to in case of errors
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
            // In MySQL 4.1, if a field has no collation we get NULL in Charset
            // but in MySQL 5.0.x we get ''
            if (!empty($search_words[$i])) {
                for ($j = 0; $j < $tblfields_cnt; $j++) {
                    if (PMA_MYSQL_INT_VERSION >= 40100 && $tblfields[$j]['Charset'] != $charset_connection && $tblfields[$j]['Charset'] != 'NULL' && $tblfields[$j]['Charset'] != '') {
                        $prefix = 'CONVERT(_utf8 ';
                        $suffix = ' USING ' . $tblfields[$j]['Charset'] . ') COLLATE ' . $tblfields[$j]['Collation'];
                    } else {
                        $prefix = $suffix = '';
                    }
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
        // here, I think we need to still use the COUNT clause, even for
        // VIEWs, anyway we have a WHERE clause that should limit results
        $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS count' . $sqlstr_from . $sqlstr_where;
        $sql['delete']        = $sqlstr_delete . $sqlstr_from . $sqlstr_where;

        return $sql;
    } // end of the "PMA_getSearchSqls()" function


    /**
     * Displays the results
     */
    if (!empty($_REQUEST['search_str']) && !empty($_REQUEST['search_option'])) {

        $original_search_str = $_REQUEST['search_str'];
        $search_str          = PMA_sqlAddslashes($_REQUEST['search_str'], TRUE);

        // Get the true string to display as option's comment
        switch ($_REQUEST['search_option']) {
            case 1:
                $option_str = ' (' . $strSearchOption1 . ')';
                $search_option = 1;
                break;
            case 2:
                $option_str = ' (' . $strSearchOption2 . ')';
                $search_option = 2;
                break;
            case 3:
                $option_str = ' (' . $strSearchOption3 . ')';
                $search_option = 3;
                break;
            case 4:
                $option_str = ' (' . $strSearchOption4 . ')';
                $search_option = 4;
                break;
        } // end switch

        $this_url_params = array(
            'db'    => $GLOBALS['db'],
            'goto'  => 'db_details.php',
            'pos'   => 0,
            'is_js_confirmed' => 0,
        );

        // Displays search string
        echo '<br />' . "\n"
            .'<table class="data">' . "\n"
            .'<caption class="tblHeaders">' . "\n"
            .sprintf($strSearchResultsFor,
                htmlspecialchars($original_search_str), $option_str) . "\n"
            .'</caption>' . "\n";

        $num_search_result_total = 0;
        $odd_row = true;

        foreach ( $_REQUEST['table_select'] as $each_table ) {
            // Gets the SQL statements
            $newsearchsqls = PMA_getSearchSqls($each_table,
                $search_str, $search_option);

            // Executes the "COUNT" statement
            $res_cnt = PMA_DBI_fetch_value($newsearchsqls['select_count']);
            $num_search_result_total += $res_cnt;

            echo '<tr class="' . ( $odd_row ? 'odd' : 'even' ) . '">'
                .'<td>' . sprintf($strNumSearchResultsInTable, $res_cnt,
                    htmlspecialchars($each_table)) . "</td>\n";

            if ($res_cnt > 0) {
                $this_url_params['sql_query'] = $newsearchsqls['select_fields'];
                echo '<td>' . PMA_linkOrButton(
                        'sql.php' . PMA_generate_common_url($this_url_params),
                        $strBrowse, '') .  "</td>\n";

                $this_url_params['sql_query'] = $newsearchsqls['delete'];
                echo '<td>' . PMA_linkOrButton(
                        'sql.php' . PMA_generate_common_url($this_url_params),
                        $strDelete, $newsearchsqls['delete']) .  "</td>\n";

            } else {
                echo '<td>&nbsp;</td>' . "\n"
                    .'<td>&nbsp;</td>' . "\n";
            }// end if else
            $odd_row = ! $odd_row;
            echo '</tr>' . "\n";
        } // end for

        echo '</table>' . "\n";

        if ( count($_REQUEST['table_select']) > 1 ) {
            echo '<p>' . sprintf($strNumSearchResultsTotal,
                $num_search_result_total) . '</p>' . "\n";
        }
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
<a name="db_search"></a>
<form method="post" action="db_search.php" name="db_search">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db']); ?>
<fieldset>
    <legend><?php echo $strSearchFormTitle; ?></legend>

    <table class="formlayout">
    <tr><td><?php echo $strSearchNeedle; ?></td>
        <td><input type="text" name="search_str" size="60"
                value="<?php echo $searched; ?>" /></td>
    </tr>
    <tr><td align="right" valign="top">
            <?php echo $strSearchType; ?></td>
        <td><input type="radio" id="search_option_1" name="search_option"
                value="1"<?php if ($search_option == 1) echo ' checked="checked"'; ?> />
            <label for="search_option_1">
                <?php echo $strSearchOption1; ?></label><sup>1</sup><br />
            <input type="radio" id="search_option_2" name="search_option"
                value="2"<?php if ($search_option == 2) echo ' checked="checked"'; ?> />
            <label for="search_option_2">
                <?php echo $strSearchOption2; ?></label><sup>1</sup><br />
            <input type="radio" id="search_option_3" name="search_option"
                value="3"<?php if ($search_option == 3) echo ' checked="checked"'; ?> />
            <label for="search_option_3">
                <?php echo $strSearchOption3; ?></label><br />
            <input type="radio" id="search_option_4" name="search_option"
                value="4"<?php if ($search_option == 4) echo ' checked="checked"'; ?> />
            <label for="search_option_4">
                <?php echo $strSearchOption4; ?></label>
            <?php echo PMA_showMySQLDocu('Regexp', 'Regexp'); ?><br />
            <br />
            <sup>1</sup><?php echo $strSplitWordsWithSpace; ?></td>
    </tr>
    <tr><td align="right" valign="top">
            <?php echo $strSearchInTables; ?></td>
        <td rowspan="2">
<?php
echo '            <select name="table_select[]" size="6"  multiple="multiple">' . "\n";
foreach ( $tables as $each_table ) {
    if ( isset($_REQUEST['unselectall'])) {
        $is_selected = '';
    } elseif ( ! isset($_REQUEST['table_select'])
          || in_array($each_table, $_REQUEST['table_select'])
          || isset($_REQUEST['selectall']) ) {
        $is_selected = ' selected="selected"';
    } else {
        $is_selected = '';
    }

    echo '                <option value="' . htmlspecialchars($each_table) . '"'
        . $is_selected . '>'
        . htmlspecialchars($each_table) . '</option>' . "\n";
} // end while
echo '            </select>' . "\n";
$strDoSelectAll = '<a href="db_search.php?' . $url_query . '&amp;selectall=1#db_search"'
                . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', true); return false;">' . $strSelectAll . '</a>'
                . '&nbsp;/&nbsp;'
                . '<a href="db_search.php?' . $url_query . '&amp;unselectall=1#db_search"'
                . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', false); return false;">' . $strUnselectAll . '</a>';
?>
        </td>
    </tr>
    <tr><td align="right" valign="bottom">
            <?php echo $strDoSelectAll; ?></td></tr>
    </tr>
    </table>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_search" value="<?php echo $strGo; ?>"
        id="buttonGo" />
</fieldset>
</form>


<?php
/**
 * Displays the footer
 */
echo "\n";
require_once('./libraries/footer.inc.php');
?>
