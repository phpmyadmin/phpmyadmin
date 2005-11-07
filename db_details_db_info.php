<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


// Check parameters

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db'));

if ( PMA_MYSQL_INT_VERSION >= 50002 && $db == 'information_schema' ) {
    $cfg['ShowStats'] = false;
    $db_is_information_schema = true;
} else {
    $db_is_information_schema = false;
}

function fillTooltip( &$tooltip_truename, &$tooltip_aliasname, &$tmp ) {
    $tooltip_truename[$tmp['Name']] = ($GLOBALS['cfg']['ShowTooltipAliasTB'] && $GLOBALS['cfg']['ShowTooltipAliasTB'] != 'nested' ? (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : $tmp['Name']) : $tmp['Name']);
    $tooltip_aliasname[$tmp['Name']] = ($GLOBALS['cfg']['ShowTooltipAliasTB'] && $GLOBALS['cfg']['ShowTooltipAliasTB'] != 'nested'  ? $tmp['Name'] : (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : $tmp['Name']));
    if (isset($tmp['Create_time']) && !empty($tmp['Create_time'])) {
        $tooltip_aliasname[$tmp['Name']] .= ', ' . $GLOBALS['strStatCreateTime'] . ': ' . PMA_localisedDate(strtotime($tmp['Create_time']));
    }

    if (isset($tmp['Update_time']) && !empty($tmp['Update_time'])) {
        $tooltip_aliasname[$tmp['Name']] .= ', ' . $GLOBALS['strStatUpdateTime'] . ': ' . PMA_localisedDate(strtotime($tmp['Update_time']));
    }

    if (isset($tmp['Check_time']) && !empty($tmp['Check_time'])) {
        $tooltip_aliasname[$tmp['Name']] .= ', ' . $GLOBALS['strStatCheckTime'] . ': ' . PMA_localisedDate(strtotime($tmp['Check_time']));
    }

    return true;
}

/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 */
// staybyte: speedup view on locked tables - 11 June 2001
$tables = array();

// When used in Nested table group mode, only show tables matching the given groupname
if (!empty($tbl_group) && !$cfg['ShowTooltipAliasTB']) {
    $tbl_group_sql = ' LIKE "' . PMA_escape_mysql_wildcards( $tbl_group ) . '%"';
} else {
    $tbl_group_sql = '';
}

if ( $cfg['ShowTooltip'] ) {
    $tooltip_truename = array();
    $tooltip_aliasname = array();
}

// Special speedup for newer MySQL Versions (in 4.0 format changed)
if ( true === $cfg['SkipLockedTables'] ) {
    $db_info_result = PMA_DBI_query('SHOW OPEN TABLES FROM ' . PMA_backquote($db) . ';');
    
    // Blending out tables in use
    if ($db_info_result != FALSE && PMA_DBI_num_rows($db_info_result) > 0) {
        while ($tmp = PMA_DBI_fetch_row($db_info_result)) {
            // if in use memorize tablename
            if (preg_match('@in_use=[1-9]+@i', $tmp[1])) {
                $sot_cache[$tmp[0]] = TRUE;
            }
        }
        PMA_DBI_free_result($db_info_result);

        if (isset($sot_cache)) {
            $db_info_result = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db) . $tbl_group_sql . ';', NULL, PMA_DBI_QUERY_STORE);
            if ($db_info_result != FALSE && PMA_DBI_num_rows($db_info_result) > 0) {
                while ($tmp = PMA_DBI_fetch_row($db_info_result)) {
                    if (!isset($sot_cache[$tmp[0]])) {
                        $sts_result  = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . addslashes($tmp[0]) . '\';');
                        $sts_tmp     = PMA_DBI_fetch_assoc($sts_result);
                        PMA_DBI_free_result($sts_result);
                        unset($sts_result);

                        if (!isset($sts_tmp['Type']) && isset($sts_tmp['Engine'])) {
                            $sts_tmp['Type'] =& $sts_tmp['Engine'];
                        }

                        if (!empty($tbl_group) && $cfg['ShowTooltipAliasTB'] && !preg_match('@' . preg_quote($tbl_group, '@') . '@i', $sts_tmp['Comment'])) {
                            continue;
                        }

                        if ($cfg['ShowTooltip']) {
                            fillTooltip($tooltip_truename, $tooltip_aliasname, $sts_tmp);
                        }

                        $tables[$sts_tmp['Name']]    = $sts_tmp;
                    } else { // table in use
                        $tables[$tmp[0]]    = array('Name' => $tmp[0]);
                    }
                }
                PMA_DBI_free_result($db_info_result);
                
                if ( $GLOBALS['cfg']['NaturalOrder'] ) {
                    uksort( $tables, 'strnatcasecmp' );
                }

                $sot_ready = TRUE;
            }
        }
    } else {
        PMA_DBI_free_result($db_info_result);
        unset($db_info_result);
    }
}

if ( ! isset( $sot_ready ) ) {
    if ( ! empty( $tbl_group ) && ! $cfg['ShowTooltipAliasTB'] ) {
        // only tables for selected group
        $tables = PMA_DBI_get_tables_full( $db, $tbl_group, true );
    } elseif ( ! empty( $tbl_group ) && $cfg['ShowTooltipAliasTB'] ) {
        // only tables for selected group,
        // but grouping is done on comment ...
        $tables = PMA_DBI_get_tables_full( $db, $tbl_group, 'comment' );
    } else {
        // all tables in db
        $tables = PMA_DBI_get_tables_full( $db );
    }
    
    if ( $cfg['ShowTooltip'] ) {
        foreach( $tables as $each_table ) {
            fillTooltip( $tooltip_truename, $tooltip_aliasname, $each_table );
        }
    }
}

$num_tables = count( $tables );

/**
 * Displays top menu links
 */
require('./db_details_links.php');
?>
