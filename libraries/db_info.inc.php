<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 *
 * fills tooltip arrays and provides $tables, $num_tables, $is_show_stats
 * and $db_is_information_schema
 *
 * staybyte: speedup view on locked tables - 11 June 2001
 *
 * @uses    $cfg['ShowStats']
 * @uses    $cfg['ShowTooltip']
 * @uses    $cfg['ShowTooltipAliasTB']
 * @uses    $cfg['SkipLockedTables']
 * @uses    $GLOBALS['db']
 * @uses    PMA_fillTooltip()
 * @uses    PMA_checkParameters()
 * @uses    PMA_escape_mysql_wildcards()
 * @uses    PMA_DBI_query()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_free_result()
 * @uses    PMA_DBI_get_tables_full()
 * @uses    PMA_isValid()
 * @uses    preg_match()
 * @uses    preg_quote()
 * @uses    uksort()
 * @uses    strnatcasecmp()
 * @uses    count()
 * @uses    addslashes()
 * @version $Id$
 */

/**
 * requirements
 */
require_once './libraries/common.inc.php';

/**
 * limits for table list
 */
if (! isset($_SESSION['userconf']['table_limit_offset'])) {
    $_SESSION['userconf']['table_limit_offset'] = 0;
}
if (isset($_REQUEST['pos'])) {
    $_SESSION['userconf']['table_limit_offset'] = (int) $_REQUEST['pos'];
}
$pos = $_SESSION['userconf']['table_limit_offset'];

/**
 * fills given tooltip arrays
 *
 * @uses    $cfg['ShowTooltipAliasTB']
 * @uses    $GLOBALS['strStatCreateTime']
 * @uses    PMA_localisedDate()
 * @uses    strtotime()
 * @param   array   $tooltip_truename   tooltip data
 * @param   array   $tooltip_aliasname  tooltip data
 * @param   array   $table              tabledata
 */
function PMA_fillTooltip(&$tooltip_truename, &$tooltip_aliasname, $table)
{
    if (empty($table['Comment'])) {
        $table['Comment'] = $table['Name'];
    } else {
        // why?
        $table['Comment'] .= ' ';
    }

    if ($GLOBALS['cfg']['ShowTooltipAliasTB']
     && $GLOBALS['cfg']['ShowTooltipAliasTB'] != 'nested') {
        $tooltip_truename[$table['Name']] = $table['Comment'];
        $tooltip_aliasname[$table['Name']] = $table['Name'];
    } else {
        $tooltip_truename[$table['Name']] = $table['Name'];
        $tooltip_aliasname[$table['Name']] = $table['Comment'];
    }

    if (isset($table['Create_time']) && !empty($table['Create_time'])) {
        $tooltip_aliasname[$table['Name']] .= ', ' . $GLOBALS['strStatCreateTime']
             . ': ' . PMA_localisedDate(strtotime($table['Create_time']));
    }

    if (! empty($table['Update_time'])) {
        $tooltip_aliasname[$table['Name']] .= ', ' . $GLOBALS['strStatUpdateTime']
             . ': ' . PMA_localisedDate(strtotime($table['Update_time']));
    }

    if (! empty($table['Check_time'])) {
        $tooltip_aliasname[$table['Name']] .= ', ' . $GLOBALS['strStatCheckTime']
             . ': ' . PMA_localisedDate(strtotime($table['Check_time']));
    }
}

PMA_checkParameters(array('db'));

/**
 * @global bool whether to display extended stats
 */
$is_show_stats = $cfg['ShowStats'];

/**
 * @global bool whether selected db is information_schema
 */
$db_is_information_schema = false;

if ($db == 'information_schema') {
    $is_show_stats = false;
    $db_is_information_schema = true;
}

/**
 * @global array information about tables in db
 */
$tables = array();

// When used in Nested table group mode, only show tables matching the given groupname
if (PMA_isValid($tbl_group) && !$cfg['ShowTooltipAliasTB']) {
    $tbl_group_sql = ' LIKE "' . PMA_escape_mysql_wildcards($tbl_group) . '%"';
} else {
    $tbl_group_sql = '';
}

if ($cfg['ShowTooltip']) {
    $tooltip_truename = array();
    $tooltip_aliasname = array();
}

// Special speedup for newer MySQL Versions (in 4.0 format changed)
if (true === $cfg['SkipLockedTables']) {
    $db_info_result = PMA_DBI_query('SHOW OPEN TABLES FROM ' . PMA_backquote($db) . ';');

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
                'SHOW TABLES FROM ' . PMA_backquote($db) . $tbl_group_sql . ';',
                null, PMA_DBI_QUERY_STORE);
            if ($db_info_result && PMA_DBI_num_rows($db_info_result) > 0) {
                while ($tmp = PMA_DBI_fetch_row($db_info_result)) {
                    if (!isset($sot_cache[$tmp[0]])) {
                        $sts_result  = PMA_DBI_query(
                            'SHOW TABLE STATUS FROM ' . PMA_backquote($db)
                             . ' LIKE \'' . addslashes($tmp[0]) . '\';');
                        $sts_tmp     = PMA_DBI_fetch_assoc($sts_result);
                        PMA_DBI_free_result($sts_result);
                        unset($sts_result);

                        if (!isset($sts_tmp['Type']) && isset($sts_tmp['Engine'])) {
                            $sts_tmp['Type'] =& $sts_tmp['Engine'];
                        }

                        if (!empty($tbl_group) && $cfg['ShowTooltipAliasTB']
                         && !preg_match('@' . preg_quote($tbl_group, '@') . '@i', $sts_tmp['Comment'])) {
                            continue;
                        }

                        if ($cfg['ShowTooltip']) {
                            PMA_fillTooltip($tooltip_truename, $tooltip_aliasname, $sts_tmp);
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
    if (! empty($tbl_group) && ! $cfg['ShowTooltipAliasTB']) {
        // only tables for selected group
        $tables = PMA_DBI_get_tables_full($db, $tbl_group, true);
    } elseif (! empty($tbl_group) && $cfg['ShowTooltipAliasTB']) {
        // only tables for selected group,
        // but grouping is done on comment ...
        $tables = PMA_DBI_get_tables_full($db, $tbl_group, 'comment');
    } else {
        // all tables in db
        // - get the total number of tables
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
            $tables = PMA_DBI_get_tables_full($db, false, false, null, 0, false);
        } else {
            // fetch the details for a possible limited subset
            $tables = PMA_DBI_get_tables_full($db, false, false, null, $pos, true);
        }
    }

    if ($cfg['ShowTooltip']) {
        foreach ($tables as $each_table) {
            PMA_fillTooltip($tooltip_truename, $tooltip_aliasname, $each_table);
        }
    }
}

/**
 * @global int count of tables in db
 */
$num_tables = count($tables);
if (! isset($total_num_tables)) {
    $total_num_tables = $num_tables;
}

/**
 * cleanup
 */
unset($each_table, $tbl_group_sql, $db_info_result);

/**
 * Displays top menu links
 */
require './libraries/db_links.inc.php';
?>
