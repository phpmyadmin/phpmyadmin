<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for multi submit forms
 *
 * @usedby  mult_submits.inc.php
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Table;

/**
 * Gets url params
 *
 * @param string $what               mult submit type
 * @param bool   $reload             is reload
 * @param string $action             action type
 * @param string $db                 database name
 * @param string $table              table name
 * @param array  $selected           selected rows(table,db)
 * @param array  $views              table views
 * @param string $original_sql_query original sql query
 * @param string $original_url_query original url query
 *
 * @return array
 */
function PMA_getUrlParams(
    $what, $reload, $action, $db, $table, $selected, $views,
    $original_sql_query, $original_url_query
) {
    $_url_params = array(
        'query_type' => $what,
        'reload' => (! empty($reload) ? 1 : 0),
    );
    if (mb_strpos(' ' . $action, 'db_') == 1) {
        $_url_params['db']= $db;
    } elseif (mb_strpos(' ' . $action, 'tbl_') == 1
        || $what == 'row_delete'
    ) {
        $_url_params['db']= $db;
        $_url_params['table']= $table;
    }
    foreach ($selected as $sval) {
        if ($what == 'row_delete') {
            $_url_params['selected'][] = 'DELETE FROM '
                . PMA\libraries\Util::backquote($table)
                . ' WHERE ' . $sval . ' LIMIT 1;';
        } else {
            $_url_params['selected'][] = $sval;
        }
    }
    if ($what == 'drop_tbl' && !empty($views)) {
        foreach ($views as $current) {
            $_url_params['views'][] = $current;
        }
    }
    if ($what == 'row_delete') {
        $_url_params['original_sql_query'] = $original_sql_query;
        if (! empty($original_url_query)) {
            $_url_params['original_url_query'] = $original_url_query;
        }
    }

    return  $_url_params;
}

/**
 * Builds or execute queries for multiple elements, depending on $query_type
 *
 * @param string $query_type  query type
 * @param array  $selected    selected tables
 * @param string $db          db name
 * @param string $table       table name
 * @param array  $views       table views
 * @param string $primary     table primary
 * @param string $from_prefix from prefix original
 * @param string $to_prefix   to prefix original
 *
 * @return array
 */
function PMA_buildOrExecuteQueryForMulti(
    $query_type, $selected, $db, $table, $views, $primary,
    $from_prefix, $to_prefix
) {
    $rebuild_database_list = false;
    $reload = null;
    $a_query = null;
    $sql_query = '';
    $sql_query_views = null;
    // whether to run query after each pass
    $run_parts = false;
    // whether to execute the query at the end (to display results)
    $execute_query_later = false;
    $result = null;

    if ($query_type == 'drop_tbl') {
        $sql_query_views = '';
    }

    $selected_cnt   = count($selected);
    $deletes = false;
    $copy_tbl =false;

    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($query_type) {
        case 'row_delete':
            $deletes = true;
            $a_query = $selected[$i];
            $run_parts = true;
            break;

        case 'drop_db':
            PMA_relationsCleanupDatabase($selected[$i]);
            $a_query   = 'DROP DATABASE '
                       . PMA\libraries\Util::backquote($selected[$i]);
            $reload    = 1;
            $run_parts = true;
            $rebuild_database_list = true;
            break;

        case 'drop_tbl':
            PMA_relationsCleanupTable($db, $selected[$i]);
            $current = $selected[$i];
            if (!empty($views) && in_array($current, $views)) {
                $sql_query_views .= (empty($sql_query_views) ? 'DROP VIEW ' : ', ')
                          . PMA\libraries\Util::backquote($current);
            } else {
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ')
                           . PMA\libraries\Util::backquote($current);
            }
            $reload    = 1;
            break;

        case 'check_tbl':
            $sql_query .= (empty($sql_query) ? 'CHECK TABLE ' : ', ')
                       . PMA\libraries\Util::backquote($selected[$i]);
            $execute_query_later = true;
            break;

        case 'optimize_tbl':
            $sql_query .= (empty($sql_query) ? 'OPTIMIZE TABLE ' : ', ')
                       . PMA\libraries\Util::backquote($selected[$i]);
            $execute_query_later = true;
            break;

        case 'analyze_tbl':
            $sql_query .= (empty($sql_query) ? 'ANALYZE TABLE ' : ', ')
                       . PMA\libraries\Util::backquote($selected[$i]);
            $execute_query_later = true;
            break;

        case 'checksum_tbl':
            $sql_query .= (empty($sql_query) ? 'CHECKSUM TABLE ' : ', ')
                       . PMA\libraries\Util::backquote($selected[$i]);
            $execute_query_later = true;
            break;

        case 'repair_tbl':
            $sql_query .= (empty($sql_query) ? 'REPAIR TABLE ' : ', ')
                       . PMA\libraries\Util::backquote($selected[$i]);
            $execute_query_later = true;
            break;

        case 'empty_tbl':
            $deletes = true;
            $a_query = 'TRUNCATE ';
            $a_query .= PMA\libraries\Util::backquote($selected[$i]);
            $run_parts = true;
            break;

        case 'drop_fld':
            PMA_relationsCleanupColumn($db, $table, $selected[$i]);
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA\libraries\Util::backquote($table)
                : ',')
                       . ' DROP ' . PMA\libraries\Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ';' : '');
            break;

        case 'primary_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA\libraries\Util::backquote($table)
                    . (empty($primary)
                    ? ''
                    : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY( '
                : ', ')
                       . PMA\libraries\Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'index_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA\libraries\Util::backquote($table)
                    . ' ADD INDEX( '
                : ', ')
                       . PMA\libraries\Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'unique_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA\libraries\Util::backquote($table)
                    . ' ADD UNIQUE( '
                : ', ')
                       . PMA\libraries\Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'spatial_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA\libraries\Util::backquote($table)
                    . ' ADD SPATIAL( '
                : ', ')
                       . PMA\libraries\Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'fulltext_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA\libraries\Util::backquote($table)
                    . ' ADD FULLTEXT( '
                : ', ')
                       . PMA\libraries\Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'add_prefix_tbl':
            $newtablename = $_POST['add_prefix'] . $selected[$i];
            // ADD PREFIX TO TABLE NAME
            $a_query = 'ALTER TABLE '
                . PMA\libraries\Util::backquote($selected[$i])
                . ' RENAME '
                . PMA\libraries\Util::backquote($newtablename);
            $run_parts = true;
            break;

        case 'replace_prefix_tbl':
            $current = $selected[$i];
            $subFromPrefix = mb_substr(
                $current,
                0,
                mb_strlen($from_prefix)
            );
            if ($subFromPrefix == $from_prefix) {
                $newtablename = $to_prefix
                    . mb_substr(
                        $current,
                        mb_strlen($from_prefix)
                    );
            } else {
                $newtablename = $current;
            }
            // CHANGE PREFIX PATTERN
            $a_query = 'ALTER TABLE '
                . PMA\libraries\Util::backquote($selected[$i])
                . ' RENAME '
                . PMA\libraries\Util::backquote($newtablename);
            $run_parts = true;
            break;

        case 'copy_tbl_change_prefix':
            $run_parts = true;
            $copy_tbl = true;

            $current = $selected[$i];
            $newtablename = $to_prefix .
                mb_substr($current, mb_strlen($from_prefix));

            // COPY TABLE AND CHANGE PREFIX PATTERN
            Table::moveCopy(
                $db, $current, $db, $newtablename,
                'data', false, 'one_table'
            );
            break;

        case 'copy_tbl':
            $run_parts = true;
            $copy_tbl = true;
            Table::moveCopy($db, $selected[$i], $_POST['target_db'], $selected[$i], $_POST['what'], false, 'one_table');
            if (isset($_POST['adjust_privileges']) && !empty($_POST['adjust_privileges'])) {
                include_once 'operations.lib.php';
                PMA_AdjustPrivileges_copyTable($db, $selected[$i], $_POST['target_db'], $selected[$i]);
            }
            break;
        } // end switch

        // All "DROP TABLE", "DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
        // statements will be run at once below
        if ($run_parts && !$copy_tbl) {
            $sql_query .= $a_query . ';' . "\n";
            if ($query_type != 'drop_db') {
                $GLOBALS['dbi']->selectDb($db);
            }
            $result = $GLOBALS['dbi']->query($a_query);

            if ($query_type == 'drop_db') {
                PMA_clearTransformations($selected[$i]);
            } elseif ($query_type == 'drop_tbl') {
                PMA_clearTransformations($db, $selected[$i]);
            } else if ($query_type == 'drop_fld') {
                PMA_clearTransformations($db, $table, $selected[$i]);
            }
        } // end if
    } // end for

    if ($deletes && ! empty($_REQUEST['pos'])) {
        $_REQUEST['pos'] = PMA_calculatePosForLastPage(
            $db, $table, isset($_REQUEST['pos']) ? $_REQUEST['pos'] : null
        );
    }

    return array(
        $result, $rebuild_database_list, $reload,
        $run_parts, $execute_query_later, $sql_query, $sql_query_views
    );
}

/**
 * Gets HTML for copy tables form
 *
 * @param string $action      action type
 * @param array  $_url_params URL params
 *
 * @return string
 */
function PMA_getHtmlForCopyMultipleTables($action, $_url_params)
{
    $html = '<form id="ajax_form" action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $databases_list = $GLOBALS['dblist']->databases;
    foreach ($databases_list as $key => $db_name)
        if ($db_name == $GLOBALS['db']){
            $databases_list->offsetUnset($key);
            break;
        }
    $html .= '<strong><label for="db_name_dropdown">' . __('Database') . ':</label></strong>';
    $html .= '<select id="db_name_dropdown" class="halfWidth" name="target_db" >'
        . $databases_list->getHtmlOptions(true, false)
        . '</select>';
    $html .= '<br><br>';
    $html .= '<strong><label>' . __('Options') . ':</label></strong><br>';
    $html .= '<input type="radio" id ="what_structure" value="structure" name="what"></input>';
    $html .= '<label for="what_structure">' . __('Structure only') . '</label><br>';
    $html .= '<input type="radio" id ="what_data" value="data" name="what" checked="checked"></input>';
    $html .= '<label for="what_data">' . __('Structure and data') . '</label><br>';
    $html .= '<input type="radio" id ="what_dataonly" value="dataonly" name="what"></input>';
    $html .= '<label for="what_dataonly">' . __('Data only') . '</label><br><br>';
    $html .= '<input type="checkbox" id="checkbox_drop" value="1" name="drop_if_exists"></input>';
    $html .= '<label for="checkbox_drop">' . __('Add DROP TABLE') . '</label><br>';
    $html .= '<input type="checkbox" id="checkbox_auto_increment_cp" value="1" name="sql_auto_increment"></input>';
    $html .= '<label for="checkbox_auto_increment_cp">' . __('Add AUTO INCREMENT value') . '</label><br>';
    $html .= '<input type="checkbox" id="checkbox_constraints" value="1" name="sql_auto_increment" checked="checked"></input>';
    $html .= '<label for="checkbox_constraints">' . __('Add constraints') . '</label><br><br>';
    $html .= '<input name="adjust_privileges" value="1" id="checkbox_adjust_privileges" checked="checked" type="checkbox"></input>';
    $html .= '<label for="checkbox_adjust_privileges">' . __('Adjust privileges') . '<a href="./doc/html/faq.html#faq6-39" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a></label>';
    $html .= '</fieldset>';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '</form>';
   return $html;
}

/**
 * Gets HTML for replace_prefix_tbl or copy_tbl_change_prefix
 *
 * @param string $action      action type
 * @param array  $_url_params URL params
 *
 * @return string
 */
function PMA_getHtmlForReplacePrefixTable($action, $_url_params)
{
    $html  = '<form id="ajax_form" action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<td>' . __('From') . '</td>';
    $html .= '<td>';
    $html .= '<input type="text" name="from_prefix" id="initialPrefix" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td>' . __('To') . '</td>';
    $html .= '<td>';
    $html .= '<input type="text" name="to_prefix" id="newPrefix" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</fieldset>';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '</form>';

    return $html;
}

/**
 * Gets HTML for add_prefix_tbl
 *
 * @param string $action      action type
 * @param array  $_url_params URL params
 *
 * @return string
 */
function PMA_getHtmlForAddPrefixTable($action, $_url_params)
{
    $html  = '<form id="ajax_form" action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<td>' . __('Add prefix') . '</td>';
    $html .= '<td>';
    $html .= '<input type="text" name="add_prefix" id="txtPrefix" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '</table>';
    $html .= '</fieldset>';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '</form>';

    return $html;
}

/**
 * Gets HTML for other mult_submits actions
 *
 * @param string $what        mult_submit type
 * @param string $action      action type
 * @param array  $_url_params URL params
 * @param string $full_query  full sql query string
 *
 * @return string
 */
function PMA_getHtmlForOtherActions($what, $action, $_url_params, $full_query)
{
    $html = '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class="confirmation">';
    $html .= '<legend>';
    if ($what == 'drop_db') {
        $html .=  __('You are about to DESTROY a complete database!') . ' ';
    }
    $html .= __('Do you really want to execute the following query?');
    $html .= '<input type="submit" name="mult_btn" value="'
        . __('Yes') . '" />';
    $html .= '<input type="submit" name="mult_btn" value="'
        . __('No') . '" />';
    $html .= '</legend>';
    $html .= '<code>' . $full_query . '</code>';
    $html .= '</fieldset>';
    $html .= '<fieldset class="tblFooters">';
    // Display option to disable foreign key checks while dropping tables
    if ($what === 'drop_tbl' || $what === 'empty_tbl' || $what === 'row_delete') {
        $html .= '<div id="foreignkeychk">';
        $html .= PMA\libraries\Util::getFKCheckbox();
        $html .= '</div>';
    }
    $html .= '<input id="buttonYes" type="submit" name="mult_btn" value="'
        . __('Yes') . '" />';
    $html .= '<input id="buttonNo" type="submit" name="mult_btn" value="'
        . __('No') . '" />';
    $html .= '</fieldset>';
    $html .= '</form>';

    return $html;
}

/**
 * Get query string from Selected
 *
 * @param string $what     mult_submit type
 * @param string $table    table name
 * @param array  $selected the selected columns
 * @param array  $views    table views
 *
 * @return array
 */
function PMA_getQueryFromSelected($what, $table, $selected, $views)
{
    $reload = false;
    $full_query_views = null;
    $full_query     = '';

    if ($what == 'drop_tbl') {
        $full_query_views = '';
    }

    $selected_cnt   = count($selected);
    $i = 0;
    foreach ($selected as $sval) {
        switch ($what) {
        case 'row_delete':
            $full_query .= 'DELETE FROM '
                . PMA\libraries\Util::backquote(htmlspecialchars($table))
                // Do not append a "LIMIT 1" clause here
                // (it's not binlog friendly).
                // We don't need the clause because the calling panel permits
                // this feature only when there is a unique index.
                . ' WHERE ' . htmlspecialchars($sval)
                . ';<br />';
            break;
        case 'drop_db':
            $full_query .= 'DROP DATABASE '
                . PMA\libraries\Util::backquote(htmlspecialchars($sval))
                . ';<br />';
            $reload = true;
            break;

        case 'drop_tbl':
            $current = $sval;
            if (!empty($views) && in_array($current, $views)) {
                $full_query_views .= (empty($full_query_views) ? 'DROP VIEW ' : ', ')
                    . PMA\libraries\Util::backquote(htmlspecialchars($current));
            } else {
                $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                    . PMA\libraries\Util::backquote(htmlspecialchars($current));
            }
            break;

        case 'empty_tbl':
            $full_query .= 'TRUNCATE ';
            $full_query .= PMA\libraries\Util::backquote(htmlspecialchars($sval))
                        . ';<br />';
            break;

        case 'primary_fld':
            if ($full_query == '') {
                $full_query .= 'ALTER TABLE '
                    . PMA\libraries\Util::backquote(htmlspecialchars($table))
                    . '<br />&nbsp;&nbsp;DROP PRIMARY KEY,'
                    . '<br />&nbsp;&nbsp; ADD PRIMARY KEY('
                    . '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                    . PMA\libraries\Util::backquote(htmlspecialchars($sval))
                    . ',';
            } else {
                $full_query .= '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                    . PMA\libraries\Util::backquote(htmlspecialchars($sval))
                    . ',';
            }
            if ($i == $selected_cnt-1) {
                $full_query = preg_replace('@,$@', ');<br />', $full_query);
            }
            break;

        case 'drop_fld':
            if ($full_query == '') {
                $full_query .= 'ALTER TABLE '
                    . PMA\libraries\Util::backquote(htmlspecialchars($table));
            }
            $full_query .= '<br />&nbsp;&nbsp;DROP '
                . PMA\libraries\Util::backquote(htmlspecialchars($sval))
                . ',';
            if ($i == $selected_cnt - 1) {
                $full_query = preg_replace('@,$@', ';<br />', $full_query);
            }
            break;
        } // end switch
        $i++;
    }

    if ($what == 'drop_tbl') {
        if (!empty($full_query)) {
            $full_query .= ';<br />' . "\n";
        }
        if (!empty($full_query_views)) {
            $full_query .= $full_query_views . ';<br />' . "\n";
        }
        unset($full_query_views);
    }

    $full_query_views = isset($full_query_views)? $full_query_views : null;

    return array($full_query, $reload, $full_query_views);
}

