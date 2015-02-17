<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for multi submit forms
 *
 * @usedby  mult_submits.inc.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
    if (/*overload*/mb_strpos(' ' . $action, 'db_') == 1) {
        $_url_params['db']= $db;
    } elseif (/*overload*/mb_strpos(' ' . $action, 'tbl_') == 1
        || $what == 'row_delete'
    ) {
        $_url_params['db']= $db;
        $_url_params['table']= $table;
    }
    foreach ($selected as $sval) {
        if ($what == 'row_delete') {
            $_url_params['selected'][] = 'DELETE FROM '
                . PMA_Util::backquote($db) . '.' . PMA_Util::backquote($table)
                . ' WHERE ' . urldecode($sval) . ' LIMIT 1;';
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
 * Gets query results from
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
function PMA_getQueryStrFromSelected(
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
    $use_sql = false;
    $result = null;

    if ($query_type == 'drop_tbl') {
        $sql_query_views = '';
    }

    $selected_cnt   = count($selected);
    $deletes = false;

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
                       . PMA_Util::backquote($selected[$i]);
            $reload    = 1;
            $run_parts = true;
            $rebuild_database_list = true;
            break;

        case 'drop_tbl':
            PMA_relationsCleanupTable($db, $selected[$i]);
            $current = $selected[$i];
            if (!empty($views) && in_array($current, $views)) {
                $sql_query_views .= (empty($sql_query_views) ? 'DROP VIEW ' : ', ')
                          . PMA_Util::backquote($current);
            } else {
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ')
                           . PMA_Util::backquote($current);
            }
            $reload    = 1;
            break;

        case 'check_tbl':
            $sql_query .= (empty($sql_query) ? 'CHECK TABLE ' : ', ')
                       . PMA_Util::backquote($selected[$i]);
            $use_sql    = true;
            break;

        case 'optimize_tbl':
            $sql_query .= (empty($sql_query) ? 'OPTIMIZE TABLE ' : ', ')
                       . PMA_Util::backquote($selected[$i]);
            $use_sql    = true;
            break;

        case 'analyze_tbl':
            $sql_query .= (empty($sql_query) ? 'ANALYZE TABLE ' : ', ')
                       . PMA_Util::backquote($selected[$i]);
            $use_sql    = true;
            break;

        case 'repair_tbl':
            $sql_query .= (empty($sql_query) ? 'REPAIR TABLE ' : ', ')
                       . PMA_Util::backquote($selected[$i]);
            $use_sql    = true;
            break;

        case 'empty_tbl':
            $deletes = true;
            $a_query = 'TRUNCATE ';
            $a_query .= PMA_Util::backquote($selected[$i]);
            $run_parts = true;
            break;

        case 'drop_fld':
            PMA_relationsCleanupColumn($db, $table, $selected[$i]);
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA_Util::backquote($table)
                : ',')
                       . ' DROP ' . PMA_Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ';' : '');
            break;

        case 'primary_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA_Util::backquote($table) . (empty($primary)
                    ? ''
                    : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY( '
                : ', ')
                       . PMA_Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'index_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD INDEX( '
                : ', ')
                       . PMA_Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'unique_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD UNIQUE( '
                : ', ')
                       . PMA_Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'spatial_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD SPATIAL( '
                : ', ')
                       . PMA_Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'fulltext_fld':
            $sql_query .= (empty($sql_query)
                ? 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD FULLTEXT( '
                : ', ')
                       . PMA_Util::backquote($selected[$i])
                       . (($i == $selected_cnt-1) ? ');' : '');
            break;

        case 'add_prefix_tbl':
            $newtablename = $_POST['add_prefix'] . $selected[$i];
            // ADD PREFIX TO TABLE NAME
            $a_query = 'ALTER TABLE '
                . PMA_Util::backquote($selected[$i])
                . ' RENAME '
                . PMA_Util::backquote($newtablename);
            $run_parts = true;
            break;

        case 'replace_prefix_tbl':
            $current = $selected[$i];
            $subFromPrefix = /*overload*/mb_substr(
                $current,
                0,
                /*overload*/mb_strlen($from_prefix)
            );
            if ($subFromPrefix == $from_prefix) {
                $newtablename = $to_prefix
                    . /*overload*/mb_substr(
                        $current,
                        /*overload*/mb_strlen($from_prefix)
                    );
            } else {
                $newtablename = $current;
            }
            // CHANGE PREFIX PATTERN
            $a_query = 'ALTER TABLE '
                . PMA_Util::backquote($selected[$i])
                . ' RENAME '
                . PMA_Util::backquote($newtablename);
            $run_parts = true;
            break;

        case 'copy_tbl_change_prefix':
            $current = $selected[$i];
            $newtablename = $to_prefix .
                /*overload*/mb_substr($current, /*overload*/mb_strlen($from_prefix));
            // COPY TABLE AND CHANGE PREFIX PATTERN
            $a_query = 'CREATE TABLE '
                . PMA_Util::backquote($newtablename)
                . ' SELECT * FROM '
                . PMA_Util::backquote($selected[$i]);
            $run_parts = true;
            break;

        } // end switch

        // All "DROP TABLE", "DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
        // statements will be run at once below
        if ($run_parts) {
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
        $run_parts, $use_sql, $sql_query, $sql_query_views
    );
}

/**
 * Gets table primary key
 *
 * @param string $db    name of db
 * @param string $table name of table
 *
 * @return string
 */
function PMA_getKeyForTablePrimary($db, $table)
{
    $GLOBALS['dbi']->selectDb($db);
    $result = $GLOBALS['dbi']->query(
        'SHOW KEYS FROM ' . PMA_Util::backquote($table) . ';'
    );
    $primary = '';
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        // Backups the list of primary keys
        if ($row['Key_name'] == 'PRIMARY') {
            $primary .= $row['Column_name'] . ', ';
        }
    } // end while
    $GLOBALS['dbi']->freeResult($result);

    return $primary;
}

/**
 * Gets HTML for replace_prefix_tbl or copy_tbl_change_prefix
 *
 * @param string $what        mult_submit type
 * @param string $action      action type
 * @param array  $_url_params URL params
 *
 * @return string
 */
function PMA_getHtmlForReplacePrefixTable($what, $action, $_url_params)
{
    $html  = '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $html .= '<legend>';
    if ($what == 'replace_prefix_tbl') {
        $html .= __('Replace table prefix:');
    } else {
        $html .= __('Copy table with prefix:');
    }
    $html .= '</legend>';
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
    $html .= '<fieldset class="tblFooters">';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '<input type="submit" value="' . __('Submit') . '" id="buttonYes" />';
    $html .= '</fieldset>';
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
    $html  = '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $html .= '<legend>' . __('Add table prefix:') . '</legend>';
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
    $html .= '<fieldset class="tblFooters">';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '<input type="submit" value="' . __('Submit') . '" id="buttonYes" />';
    $html .= '</fieldset>';
    $html .= '</form>';

    return $html;
}

/**
 * Gets HTML for other mult_submits actions
 *
 * @param string $what        mult_submit type
 * @param string $action      action type
 * @param array  $_url_params URL params
 * @param array  $full_query  full sql query string
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
        $html .= '<label for="fkc_checkbox">';
        $html .= __('Foreign key check:');
        $html .= '</label>';
        $html .= '<span class="checkbox">';
        $html .= '<input type="checkbox" name="fk_check" value="1" '
            . 'id="fkc_checkbox"';
        $default_fk_check_value = $GLOBALS['dbi']->fetchValue(
            'SHOW VARIABLES LIKE \'foreign_key_checks\';', 0, 1
        ) == 'ON';
        if ($default_fk_check_value) {
            $html .= ' checked="checked"';
        }
        $html .= '/></span>';
        $html .= '<label id="fkc_status" for="fkc_checkbox">';
        $html .= ($default_fk_check_value) ? __('(Enabled)') : __('(Disabled)');
        $html .= '</label>';
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
 * Get List of information for Submit Mult
 *
 * @param string $submit_mult mult_submit type
 * @param string $db          database name
 * @param string $table       table name
 * @param array  $selected    the selected columns
 * @param string $action      action type
 *
 * @return array
 */
function PMA_getDataForSubmitMult($submit_mult, $db, $table, $selected, $action)
{
    $what = null;
    $query_type = null;
    $is_unset_submit_mult = false;
    $mult_btn = null;
    $centralColsError = null;
    switch ($submit_mult) {
    case 'drop':
        $what     = 'drop_fld';
        break;
    case 'primary':
        // Gets table primary key
        $primary = PMA_getKeyForTablePrimary($db, $table);
        if (empty($primary)) {
            // no primary key, so we can safely create new
            $is_unset_submit_mult = true;
            $query_type = 'primary_fld';
            $mult_btn   = __('Yes');
        } else {
            // primary key exists, so lets as user
            $what = 'primary_fld';
        }
        break;
    case 'index':
        $is_unset_submit_mult = true;
        $query_type = 'index_fld';
        $mult_btn   = __('Yes');
        break;
    case 'unique':
        $is_unset_submit_mult = true;
        $query_type = 'unique_fld';
        $mult_btn   = __('Yes');
        break;
    case 'spatial':
        $is_unset_submit_mult = true;
        $query_type = 'spatial_fld';
        $mult_btn   = __('Yes');
        break;
    case 'ftext':
        $is_unset_submit_mult = true;
        $query_type = 'fulltext_fld';
        $mult_btn   = __('Yes');
        break;
    case 'add_to_central_columns':
        include_once 'libraries/central_columns.lib.php';
        $centralColsError = PMA_syncUniqueColumns($selected, false);
        break;
    case 'remove_from_central_columns':
        include_once 'libraries/central_columns.lib.php';
        $centralColsError = PMA_deleteColumnsFromList($selected, false);
        break;
    case 'change':
        PMA_displayHtmlForColumnChange($db, $table, $selected, $action);
        // execution stops here but PMA_Response correctly finishes
        // the rendering
        exit;
    case 'browse':
        // this should already be handled by tbl_structure.php
    }

    return array(
        $what, $query_type, $is_unset_submit_mult, $mult_btn,
        $centralColsError
            );
}

/**
 * Get query string from Selected
 *
 * @param string $what     mult_submit type
 * @param string $db       database name
 * @param string $table    table name
 * @param array  $selected the selected columns
 * @param array  $views    table views
 *
 * @return array
 */
function PMA_getQueryFromSelected($what, $db, $table, $selected, $views)
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
                . PMA_Util::backquote(htmlspecialchars($db))
                . '.' . PMA_Util::backquote(htmlspecialchars($table))
                // Do not append a "LIMIT 1" clause here
                // (it's not binlog friendly).
                // We don't need the clause because the calling panel permits
                // this feature only when there is a unique index.
                . ' WHERE ' . urldecode(htmlspecialchars($sval))
                . ';<br />';
            break;
        case 'drop_db':
            $full_query .= 'DROP DATABASE '
                . PMA_Util::backquote(htmlspecialchars($sval))
                . ';<br />';
            $reload = true;
            break;

        case 'drop_tbl':
            $current = $sval;
            if (!empty($views) && in_array($current, $views)) {
                $full_query_views .= (empty($full_query_views) ? 'DROP VIEW ' : ', ')
                    . PMA_Util::backquote(htmlspecialchars($current));
            } else {
                $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                    . PMA_Util::backquote(htmlspecialchars($current));
            }
            break;

        case 'empty_tbl':
            $full_query .= 'TRUNCATE ';
            $full_query .= PMA_Util::backquote(htmlspecialchars($sval))
                        . ';<br />';
            break;

        case 'primary_fld':
            if ($full_query == '') {
                $full_query .= 'ALTER TABLE '
                    . PMA_Util::backquote(htmlspecialchars($table))
                    . '<br />&nbsp;&nbsp;DROP PRIMARY KEY,'
                    . '<br />&nbsp;&nbsp; ADD PRIMARY KEY('
                    . '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                    . PMA_Util::backquote(htmlspecialchars($sval))
                    . ',';
            } else {
                $full_query .= '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                    . PMA_Util::backquote(htmlspecialchars($sval))
                    . ',';
            }
            if ($i == $selected_cnt-1) {
                $full_query = preg_replace('@,$@', ');<br />', $full_query);
            }
            break;

        case 'drop_fld':
            if ($full_query == '') {
                $full_query .= 'ALTER TABLE '
                    . PMA_Util::backquote(htmlspecialchars($table));
            }
            $full_query .= '<br />&nbsp;&nbsp;DROP '
                . PMA_Util::backquote(htmlspecialchars($sval))
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

?>
