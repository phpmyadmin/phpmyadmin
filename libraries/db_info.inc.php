<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 *
 * fills tooltip arrays and provides $tables, $num_tables, $is_show_stats
 * and $db_is_information_schema
 *
 * speedup view on locked tables
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * limits for table list
 */
if (! isset($_SESSION['tmp_user_values']['table_limit_offset'])
    || $_SESSION['tmp_user_values']['table_limit_offset_db'] != $db
) {
    $_SESSION['tmp_user_values']['table_limit_offset'] = 0;
    $_SESSION['tmp_user_values']['table_limit_offset_db'] = $db;
}
if (isset($_REQUEST['pos'])) {
    $_SESSION['tmp_user_values']['table_limit_offset'] = (int) $_REQUEST['pos'];
}
$pos = $_SESSION['tmp_user_values']['table_limit_offset'];

PMA_Util::checkParameters(array('db'));

/**
 * @global bool whether to display extended stats
 */
$is_show_stats = $cfg['ShowStats'];

/**
 * @global bool whether selected db is information_schema
 */
$db_is_information_schema = false;

if (PMA_is_system_schema($db)) {
    $is_show_stats = false;
    $db_is_information_schema = true;
}

/**
 * @global array information about tables in db
 */
$tables = array();

// When used in Nested table group mode,
// only show tables matching the given groupname
if (PMA_isValid($_REQUEST['tbl_group'])) {
    $tbl_group_sql = ' LIKE "'
        . PMA_Util::escapeMysqlWildcards($_REQUEST['tbl_group'])
        . '%"';
} else {
    $tbl_group_sql = '';
}

$tooltip_truename = array();
$tooltip_aliasname = array();

// Special speedup for newer MySQL Versions (in 4.0 format changed)
if (true === $cfg['SkipLockedTables']) {
    $db_info_result = PMA_DBI_query(
        'SHOW OPEN TABLES FROM ' . PMA_Util::backquote($db) . ';'
    );

    // Blending out tables in use
    if ($db_info_result && PMA_DBI_num_rows($db_info_result) > 0) {
        while ($tmp = PMA_DBI_fetch_row($db_info_result)) {
            // if in use memorize tablename
            if (preg_match('@in_use=[1-9]+@i', $tmp[1])) {
                $sot_cache[$tmp[0]] = true;
            }
        }
        PMA_DBI_free_result($db_info_result);

        if (isset($sot_cache)) {
            $db_info_result = PMA_DBI_query(
                'SHOW TABLES FROM ' . PMA_Util::backquote($db) . $tbl_group_sql . ';',
                null, PMA_DBI_QUERY_STORE
            );
            if ($db_info_result && PMA_DBI_num_rows($db_info_result) > 0) {
                while ($tmp = PMA_DBI_fetch_row($db_info_result)) {
                    if (! isset($sot_cache[$tmp[0]])) {
                        $sts_result  = PMA_DBI_query(
                            'SHOW TABLE STATUS FROM ' . PMA_Util::backquote($db)
                            . ' LIKE \'' . PMA_Util::sqlAddSlashes($tmp[0], true) . '\';'
                        );
                        $sts_tmp     = PMA_DBI_fetch_assoc($sts_result);
                        PMA_DBI_free_result($sts_result);
                        unset($sts_result);

                        if (! isset($sts_tmp['Type']) && isset($sts_tmp['Engine'])) {
                            $sts_tmp['Type'] =& $sts_tmp['Engine'];
                        }

                        if (! empty($_REQUEST['tbl_group'])
                            && ! preg_match('@' . preg_quote($_REQUEST['tbl_group'], '@') . '@i', $sts_tmp['Comment'])
                        ) {
                            continue;
                        }

                        $tables[$sts_tmp['Name']]    = $sts_tmp;
                    } else { // table in use
                        $tables[$tmp[0]]    = array('Name' => $tmp[0]);
                    }
                }
                if ($GLOBALS['cfg']['NaturalOrder']) {
                    uksort($tables, 'strnatcasecmp');
                }

                $sot_ready = true;
            } elseif ($db_info_result) {
                PMA_DBI_free_result($db_info_result);
            }
            unset($sot_cache);
        }
        unset($tmp);
    } elseif ($db_info_result) {
        PMA_DBI_free_result($db_info_result);
    }
}

if (! isset($sot_ready)) {

    // Set some sorting defaults
    $sort = 'Name';
    $sort_order = 'ASC';

    if (isset($_REQUEST['sort'])) {
        $sortable_name_mappings = array(
            'table'       => 'Name',
            'records'     => 'Rows',
            'type'        => 'Engine',
            'collation'   => 'Collation',
            'size'        => 'Data_length',
            'overhead'    => 'Data_free',
            'creation'    => 'Create_time',
            'last_update' => 'Update_time',
            'last_check'  => 'Check_time'
        );

        // Make sure the sort type is implemented
        if (isset($sortable_name_mappings[$_REQUEST['sort']])) {
            $sort = $sortable_name_mappings[$_REQUEST['sort']];
            if ($_REQUEST['sort_order'] == 'DESC') {
                $sort_order = 'DESC';
            }
        }
    }

    if (! empty($_REQUEST['tbl_group'])) {
        // only tables for selected group
        $tables = PMA_DBI_get_tables_full(
            $db, $_REQUEST['tbl_group'], true, null, 0, false, $sort, $sort_order
        );
    } else {
        // all tables in db
        // - get the total number of tables
        //  (needed for proper working of the MaxTableList feature)
        $tables = PMA_DBI_get_tables($db);
        $total_num_tables = count($tables);
        if (isset($sub_part) && $sub_part == '_export') {
            // (don't fetch only a subset if we are coming from db_export.php,
            // because I think it's too risky to display only a subset of the
            // table names when exporting a db)
            /**
             *
             * @todo Page selector for table names?
             */
            $tables = PMA_DBI_get_tables_full(
                $db, false, false, null, 0, false, $sort, $sort_order
            );
        } else {
            // fetch the details for a possible limited subset
            $tables = PMA_DBI_get_tables_full(
                $db, false, false, null, $pos, true, $sort, $sort_order
            );
        }
    }
}

/**
 * @global int count of tables in db
 */
$num_tables = count($tables);
//  (needed for proper working of the MaxTableList feature)
if (! isset($total_num_tables)) {
    $total_num_tables = $num_tables;
}

/**
 * cleanup
 */
unset($each_table, $tbl_group_sql, $db_info_result);

/**
 * If coming from a Show MySQL link on the home page,
 * put something in $sub_part
 */
if (empty($sub_part)) {
    $sub_part = '_structure';
}
?>
