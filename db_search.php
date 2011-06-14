<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * searchs the entire database
 *
 * @todo    make use of UNION when searching multiple tables
 * @todo    display executed query, optional?
 * @uses    $cfg['UseDbSearch']
 * @uses    $GLOBALS['db']
 * @uses    PMA_DBI_get_tables()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_getSearchSqls()
 * @uses    PMA_DBI_fetch_value()
 * @uses    PMA_linkOrButton()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_generate_common_hidden_inputs()
 * @uses    PMA_showMySQLDocu()
 * @uses    $_REQUEST['search_str']
 * @uses    $_REQUEST['submit_search']
 * @uses    $_REQUEST['search_option']
 * @uses    $_REQUEST['table_select']
 * @uses    $_REQUEST['unselectall']
 * @uses    $_REQUEST['selectall']
 * @uses    $_REQUEST['field_str']
 * @uses    is_string()
 * @uses    htmlspecialchars()
 * @uses    array_key_exists()
 * @uses    is_array()
 * @uses    array_intersect()
 * @uses    sprintf()
 * @uses    in_array()
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'db_search.js';

/**
 * Gets some core libraries and send headers
 */
require './libraries/db_common.inc.php';

/**
 * init
 */
// If config variable $GLOBALS['cfg']['Usedbsearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    PMA_mysqlDie(__('Access denied'), '', false, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

/**
 * @global array list of tables from the current database
 * but do not clash with $tables coming from db_info.inc.php
 */
$tables_names_only = PMA_DBI_get_tables($GLOBALS['db']);

$search_options = array(
    '1' => __('at least one of the words'),
    '2' => __('all words'),
    '3' => __('the exact phrase'),
    '4' => __('as regular expression'),
);

if (empty($_REQUEST['search_option']) || ! is_string($_REQUEST['search_option'])
 || ! array_key_exists($_REQUEST['search_option'], $search_options)) {
    $search_option = 1;
    unset($_REQUEST['submit_search']);
} else {
    $search_option = (int) $_REQUEST['search_option'];
    $option_str = $search_options[$_REQUEST['search_option']];
}

if (empty($_REQUEST['search_str']) || ! is_string($_REQUEST['search_str'])) {
    unset($_REQUEST['submit_search']);
    $searched = '';
} else {
    $searched = htmlspecialchars($_REQUEST['search_str']);
    // For "as regular expression" (search option 4), we should not treat
    // this as an expression that contains a LIKE (second parameter of
    // PMA_sqlAddslashes()).
    //
    // Usage example: If user is seaching for a literal $ in a regexp search,
    // he should enter \$ as the value.
    $search_str = PMA_sqlAddslashes($_REQUEST['search_str'], ($search_option == 4 ? false : true));
}

$tables_selected = array();
if (empty($_REQUEST['table_select']) || ! is_array($_REQUEST['table_select'])) {
    unset($_REQUEST['submit_search']);
} elseif (! isset($_REQUEST['selectall']) && ! isset($_REQUEST['unselectall'])) {
    $tables_selected = array_intersect($_REQUEST['table_select'], $tables_names_only);
}

if (isset($_REQUEST['selectall'])) {
    $tables_selected = $tables_names_only;
} elseif (isset($_REQUEST['unselectall'])) {
    $tables_selected = array();
}

if (empty($_REQUEST['field_str']) || ! is_string($_REQUEST['field_str'])) {
    unset($field_str);
} else {
    $field_str = PMA_sqlAddslashes($_REQUEST['field_str'], true);
}

/**
 * Displays top links if we are not in an Ajax request
 */
$sub_part = '';

if( $GLOBALS['is_ajax_request'] != true) {
    require './libraries/db_info.inc.php';
    echo '<div id="searchresults">';
}

/**
 * 1. Main search form has been submitted
 */
if (isset($_REQUEST['submit_search'])) {

    /**
     * Builds the SQL search query
     *
     * @todo    can we make use of fulltextsearch IN BOOLEAN MODE for this?
     * @uses    PMA_DBI_query
     * PMA_backquote
     * PMA_DBI_free_result
     * PMA_DBI_fetch_assoc
     * $GLOBALS['db']
     * explode
     * count
     * strlen
     * @param   string   the table name
     * @param   string   restrict the search to this field
     * @param   string   the string to search
     * @param   integer  type of search (1 -> 1 word at least, 2 -> all words,
     *                                   3 -> exact string, 4 -> regexp)
     *
     * @return  array    3 SQL querys (for count, display and delete results)
     *
     * @global  string   the url to return to in case of errors
     * @global  string   charset connection
     */
    function PMA_getSearchSqls($table, $field, $search_str, $search_option)
    {
        global $err_url;

        // Statement types
        $sqlstr_select = 'SELECT';
        $sqlstr_delete = 'DELETE';

        // Fields to select
        $tblfields = PMA_DBI_fetch_result('SHOW FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($GLOBALS['db']),
            null, 'Field');

        // Table to use
        $sqlstr_from = ' FROM ' . PMA_backquote($GLOBALS['db']) . '.' . PMA_backquote($table);

        $search_words    = (($search_option > 2) ? array($search_str) : explode(' ', $search_str));
        $search_wds_cnt  = count($search_words);

        $like_or_regex   = (($search_option == 4) ? 'REGEXP' : 'LIKE');
        $automatic_wildcard   = (($search_option < 3) ? '%' : '');

        $fieldslikevalues = array();
        foreach ($search_words as $search_word) {
            // Eliminates empty values
            if (strlen($search_word) === 0) {
                continue;
            }

            $thefieldlikevalue = array();
            foreach ($tblfields as $tblfield) {
                if (! isset($field) || strlen($field) == 0 || $tblfield == $field) {
                    $thefieldlikevalue[] = 'CONVERT(' . PMA_backquote($tblfield) . ' USING utf8)'
                                         . ' ' . $like_or_regex . ' '
                                         . "'" . $automatic_wildcard
                                         . $search_word
                                         . $automatic_wildcard . "'";
                }
            } // end for

            if (count($thefieldlikevalue) > 0) {
                $fieldslikevalues[]      = implode(' OR ', $thefieldlikevalue);
            }
        } // end for

        $implode_str  = ($search_option == 1 ? ' OR ' : ' AND ');
        if ( empty($fieldslikevalues)) {
            // this could happen when the "inside field" does not exist
            // in any selected tables
            $sqlstr_where = ' WHERE FALSE';
        } else {
            $sqlstr_where = ' WHERE (' . implode(') ' . $implode_str . ' (', $fieldslikevalues) . ')';
        }
        unset($fieldslikevalues);

        // Builds complete queries
        $sql['select_fields'] = $sqlstr_select . ' * ' . $sqlstr_from . $sqlstr_where;
        // here, I think we need to still use the COUNT clause, even for
        // VIEWs, anyway we have a WHERE clause that should limit results
        $sql['select_count']  = $sqlstr_select . ' COUNT(*) AS `count`' . $sqlstr_from . $sqlstr_where;
        $sql['delete']        = $sqlstr_delete . $sqlstr_from . $sqlstr_where;

        return $sql;
    } // end of the "PMA_getSearchSqls()" function


    /**
     * Displays the results
     */
    $this_url_params = array(
        'db'    => $GLOBALS['db'],
        'goto'  => 'db_sql.php',
        'pos'   => 0,
        'is_js_confirmed' => 0,
    );

    // Displays search string
    echo '<br />' . "\n"
        .'<table class="data">' . "\n"
        .'<caption class="tblHeaders">' . "\n"
        .sprintf(__('Search results for "<i>%s</i>" %s:'),
            $searched, $option_str) . "\n"
        .'</caption>' . "\n";

    $num_search_result_total = 0;
    $odd_row = true;

    foreach ($tables_selected as $each_table) {
        // Gets the SQL statements
        $newsearchsqls = PMA_getSearchSqls($each_table, (! empty($field_str) ? $field_str : ''), $search_str, $search_option);

        // Executes the "COUNT" statement
        $res_cnt = PMA_DBI_fetch_value($newsearchsqls['select_count']);
        $num_search_result_total += $res_cnt;

        $sql_query .= $newsearchsqls['select_count'];

        echo '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">'
            .'<td>' . sprintf(_ngettext('%s match inside table <i>%s</i>', '%s matches inside table <i>%s</i>', $res_cnt), $res_cnt,
                htmlspecialchars($each_table)) . "</td>\n";

        if ($res_cnt > 0) {
            $this_url_params['sql_query'] = $newsearchsqls['select_fields'];
             $browse_result_path = 'sql.php' . PMA_generate_common_url($this_url_params);
             ?>
            <td> <a name="browse_search" href="<?php echo $browse_result_path; ?>" onclick="loadResult('<?php echo $browse_result_path ?> ',' <?php echo  $each_table?> ' , '<?php echo PMA_generate_common_url($GLOBALS['db'], $each_table)?>','<?php echo ($GLOBALS['cfg']['AjaxEnable']); ?>');return false;" ><?php echo __('Browse') ?></a>   </td>
            <?php
            $this_url_params['sql_query'] = $newsearchsqls['delete'];
            $delete_result_path = 'sql.php' . PMA_generate_common_url($this_url_params);
            ?>
            <td> <a name="delete_search" href="<?php echo $delete_result_path; ?>" onclick="deleteResult('<?php echo $delete_result_path ?>' , ' <?php printf(__('Delete the matches for the %s table?'), htmlspecialchars($each_table)); ?>','<?php echo ($GLOBALS['cfg']['AjaxEnable']); ?>');return false;" ><?php echo __('Delete') ?></a>   </td>
            <?php
         } else {
            echo '<td>&nbsp;</td>' . "\n"
                .'<td>&nbsp;</td>' . "\n";
        }// end if else
        $odd_row = ! $odd_row;
        echo '</tr>' . "\n";
    } // end for

    echo '</table>' . "\n";

    if (count($tables_selected) > 1) {
        echo '<p>' . sprintf(_ngettext('<b>Total:</b> <i>%s</i> match', '<b>Total:</b> <i>%s</i> matches', $num_search_result_total),
            $num_search_result_total) . '</p>' . "\n";
    }
} // end 1.

/**
 * If we are in an Ajax request, we need to exit after displaying all the HTML
 */
if($GLOBALS['is_ajax_request'] == true) {
    exit;
}
else {
    echo '</div>';//end searchresults div
}

/**
 * 2. Displays the main search form
 */
?>
<a name="db_search"></a>
<form id="db_search_form"<?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?> method="post" action="db_search.php" name="db_search">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db']); ?>
<fieldset>
    <legend><?php echo __('Search in database'); ?></legend>

    <table class="formlayout">
    <tr><td><?php echo __('Word(s) or value(s) to search for (wildcard: "%"):'); ?></td>
        <td><input type="text" name="search_str" size="60"
                value="<?php echo $searched; ?>" /></td>
    </tr>
    <tr><td align="right" valign="top">
            <?php echo __('Find:'); ?></td>
            <td><?php

$choices = array(
    '1' => __('at least one of the words') . PMA_showHint(__('Words are separated by a space character (" ").')),
    '2' => __('all words') . PMA_showHint(__('Words are separated by a space character (" ").')),
    '3' => __('the exact phrase'),
    '4' => __('as regular expression') . ' ' . PMA_showMySQLDocu('Regexp', 'Regexp')
);
// 4th parameter set to true to add line breaks
// 5th parameter set to false to avoid htmlspecialchars() escaping in the label
//  since we have some HTML in some labels
PMA_display_html_radio('search_option', $choices, $search_option, true, false);
unset($choices);
            ?>
            </td>
    </tr>
    <tr><td align="right" valign="top">
            <?php echo __('Inside table(s):'); ?></td>
        <td rowspan="2">
<?php
echo '            <select name="table_select[]" size="6" multiple="multiple">' . "\n";
foreach ($tables_names_only as $each_table) {
    if (in_array($each_table, $tables_selected)) {
        $is_selected = ' selected="selected"';
    } else {
        $is_selected = '';
    }

    echo '                <option value="' . htmlspecialchars($each_table) . '"'
        . $is_selected . '>'
        . str_replace(' ', '&nbsp;', htmlspecialchars($each_table)) . '</option>' . "\n";
} // end while

echo '            </select>' . "\n";
$alter_select =
    '<a href="db_search.php' . PMA_generate_common_url(array_merge($url_params, array('selectall' => 1))) . '#db_search"'
    . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', true); return false;">' . __('Select All') . '</a>'
    . '&nbsp;/&nbsp;'
    . '<a href="db_search.php' . PMA_generate_common_url(array_merge($url_params, array('unselectall' => 1))) . '#db_search"'
    . ' onclick="setSelectOptions(\'db_search\', \'table_select[]\', false); return false;">' . __('Unselect All') . '</a>';
?>
        </td>
    </tr>
    <tr><td align="right" valign="bottom">
            <?php echo $alter_select; ?></td>
    </tr>
    <tr><td align="right">
            <?php echo __('Inside column:'); ?></td>
        <td><input type="text" name="field_str" size="60"
                value="<?php echo ! empty($field_str) ? htmlspecialchars($field_str) : ''; ?>" /></td>
    </tr>
    </table>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_search" value="<?php echo __('Go'); ?>"
        id="buttonGo" />
</fieldset>
</form>

<!-- These two table-image and table-link elements display the table name in browse search results  -->
<div id='table-info'>
<a class="item" id="table-link" ></a>
</div>
<div id="browse-results">
<!-- this browse-results div is used to load the browse and delete results in the db search -->
</div>
<br class="clearfloat" />
<div id="sqlqueryform">
<!-- this sqlqueryform div is used to load the delete form in the db search -->
</div>
<!--  toggle query box link-->
<a id="togglequerybox"></a>

<?php
/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
