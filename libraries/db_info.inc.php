<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Gets the list of the table in the current db and information about these
 * tables if possible
 *
 * fills tooltip arrays and provides $tables, $num_tables, $is_show_stats
 * and $db_is_system_schema
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
if (! isset($_SESSION['tmpval']['table_limit_offset'])
    || $_SESSION['tmpval']['table_limit_offset_db'] != $db
) {
    $_SESSION['tmpval']['table_limit_offset'] = 0;
    $_SESSION['tmpval']['table_limit_offset_db'] = $db;
}
if (isset($_REQUEST['pos'])) {
    $_SESSION['tmpval']['table_limit_offset'] = (int) $_REQUEST['pos'];
}
$pos = $_SESSION['tmpval']['table_limit_offset'];

PMA_Util::checkParameters(array('db'));

/**
 * @global bool whether to display extended stats
 */
$is_show_stats = $cfg['ShowStats'];

/**
 * @global bool whether selected db is information_schema
 */
$db_is_system_schema = false;

if ($GLOBALS['dbi']->isSystemSchema($db)) {
    $is_show_stats = false;
    $db_is_system_schema = true;
}

/**
 * @global array information about tables in db
 */
$tables = array();

$tooltip_truename = array();
$tooltip_aliasname = array();

// Special speedup for newer MySQL Versions (in 4.0 format changed)
if (true === $cfg['SkipLockedTables'] && ! PMA_DRIZZLE) {
    $db_info_result = $GLOBALS['dbi']->query(
        'SHOW OPEN TABLES FROM ' . PMA_Util::backquote($db) . ';'
    );

    // Blending out tables in use
    if ($db_info_result && $GLOBALS['dbi']->numRows($db_info_result) > 0) {
        while ($tmp = $GLOBALS['dbi']->fetchAssoc($db_info_result)) {
            // if in use, memorize table name
            if ($tmp['In_use'] > 0) {
                $sot_cache[$tmp['Table']] = true;
            }
        }
        $GLOBALS['dbi']->freeResult($db_info_result);

        if (isset($sot_cache)) {
            $db_info_result = false;

            $tblGroupSql = "";
            $whereAdded = false;
            if (PMA_isValid($_REQUEST['tbl_group'])) {
                $group = PMA_Util::escapeMysqlWildcards($_REQUEST['tbl_group']);
                $groupWithSeparator = PMA_Util::escapeMysqlWildcards(
                    $_REQUEST['tbl_group']
                    . $GLOBALS['cfg']['NavigationTreeTableSeparator']
                );
                $tblGroupSql .= " WHERE ("
                    . PMA_Util::backquote('Tables_in_' . $db)
                    . " LIKE '" . $groupWithSeparator . "%'"
                    . " OR "
                    . PMA_Util::backquote('Tables_in_' . $db)
                    . " LIKE '" . $group . "')";
                $whereAdded = true;
            }
            if (PMA_isValid($_REQUEST['tbl_type'], array('table', 'view'))) {
                $tblGroupSql .= $whereAdded ? " AND" : " WHERE";
                if ($_REQUEST['tbl_type'] == 'view') {
                    $tblGroupSql .= " `Table_type` != 'BASE TABLE'";
                } else {
                    $tblGroupSql .= " `Table_type` = 'BASE TABLE'";
                }
            }
            $db_info_result = $GLOBALS['dbi']->query(
                'SHOW FULL TABLES FROM ' . PMA_Util::backquote($db) . $tblGroupSql,
                null, PMA_DatabaseInterface::QUERY_STORE
            );
            unset($tblGroupSql, $whereAdded);

            if ($db_info_result && $GLOBALS['dbi']->numRows($db_info_result) > 0) {
                while ($tmp = $GLOBALS['dbi']->fetchRow($db_info_result)) {
                    if (! isset($sot_cache[$tmp[0]])) {
                        $sts_result  = $GLOBALS['dbi']->query(
                            "SHOW TABLE STATUS FROM " . PMA_Util::backquote($db)
                            . " LIKE '" . PMA_Util::sqlAddSlashes($tmp[0], true)
                            . "';"
                        );
                        $sts_tmp     = $GLOBALS['dbi']->fetchAssoc($sts_result);
                        $GLOBALS['dbi']->freeResult($sts_result);
                        unset($sts_result);

                        $tableArray = $GLOBALS['dbi']->copyTableProperties(
                            array($sts_tmp), $db
                        );
                        $tables[$sts_tmp['Name']] = $tableArray[0];
                    } else { // table in use
                        $tables[$tmp[0]] = array(
                            'TABLE_NAME' => $tmp[0],
                            'ENGINE' => '',
                            'TABLE_TYPE' => '',
                            'TABLE_ROWS' => 0,
                        );
                    }
                }
                if ($GLOBALS['cfg']['NaturalOrder']) {
                    uksort($tables, 'strnatcasecmp');
                }

                $sot_ready = true;
            } elseif ($db_info_result) {
                $GLOBALS['dbi']->freeResult($db_info_result);
            }
            unset($sot_cache);
        }
        unset($tmp);
    } elseif ($db_info_result) {
        $GLOBALS['dbi']->freeResult($db_info_result);
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

    $tbl_group = false;
    $groupWithSeparator = false;
    $tbl_type = null;
    $limit_offset = 0;
    $limit_count = false;
    $groupTable = array();

    if (! empty($_REQUEST['tbl_group']) || ! empty($_REQUEST['tbl_type'])) {
        if (! empty($_REQUEST['tbl_type'])) {
            // only tables for selected type
            $tbl_type = $_REQUEST['tbl_type'];
        }
        if (! empty($_REQUEST['tbl_group'])) {
            // only tables for selected group
            $tbl_group = $_REQUEST['tbl_group'];
            // include the table with the exact name of the group if such exists
            $groupTable = $GLOBALS['dbi']->getTablesFull(
                $db, $tbl_group, false, null, $limit_offset,
                $limit_count, $sort, $sort_order, $tbl_type
            );
            $groupWithSeparator = $tbl_group
                . $GLOBALS['cfg']['NavigationTreeTableSeparator'];
        }
    } else {
        // all tables in db
        // - get the total number of tables
        //  (needed for proper working of the MaxTableList feature)
        $tables = $GLOBALS['dbi']->getTables($db);
        $total_num_tables = count($tables);
        if (isset($sub_part) && $sub_part == '_export') {
            // (don't fetch only a subset if we are coming from db_export.php,
            // because I think it's too risky to display only a subset of the
            // table names when exporting a db)
            /**
             *
             * @todo Page selector for table names?
             */
        } else {
            // fetch the details for a possible limited subset
            $limit_offset = $pos;
            $limit_count = true;
        }
    }
    $tables = array_merge(
        $groupTable,
        $GLOBALS['dbi']->getTablesFull(
            $db, $groupWithSeparator, ($groupWithSeparator != false), null,
            $limit_offset, $limit_count, $sort, $sort_order, $tbl_type
        )
    );
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
unset($each_table, $db_info_result);

/**
 * If coming from a Show MySQL link on the home page,
 * put something in $sub_part
 */
if (empty($sub_part)) {
    $sub_part = '_structure';
}
?>
