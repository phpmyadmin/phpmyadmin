<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Common Option Constants For DBI Functions
 */
define('PMA_DBI_QUERY_STORE',       1);  // Force STORE_RESULT method, ignored by classic MySQL.
define('PMA_DBI_QUERY_UNBUFFERED',  2);  // Do not read whole query

/**
 * Including The DBI Plugin
 */
require_once('./libraries/dbi/' . $cfg['Server']['extension'] . '.dbi.lib.php');

/**
 * Common Functions
 */
function PMA_DBI_query($query, $link = NULL, $options = 0) {
    $res = PMA_DBI_try_query($query, $link, $options)
        or PMA_mysqlDie(PMA_DBI_getError(), $query);

    return $res;
}

function PMA_DBI_get_dblist($link = NULL) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    $res = PMA_DBI_try_query('SHOW DATABASES;', $link);
    $dbs_array = array();
    while ($row = PMA_DBI_fetch_row($res)) {

       // Before MySQL 4.0.2, SHOW DATABASES could send the
       // whole list, so check if we really have access:
       if (PMA_MYSQL_CLIENT_API < 40002) {
           $dblink = @PMA_DBI_select_db($row[0], $link);
           if (!$dblink) {
               continue;
           }
       }
       $dbs_array[] = $row[0];
    }
    PMA_DBI_free_result($res);
    unset($res);

    return $dbs_array;
}

function PMA_DBI_get_tables($database, $link = NULL) {
    $result       = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($database) . ';', NULL, PMA_DBI_QUERY_STORE);
    $tables       = array();
    while (list($current) = PMA_DBI_fetch_row($result)) {
        $tables[] = $current;
    }
    PMA_DBI_free_result($result);

    return $tables;
}

function PMA_DBI_get_fields($database, $table, $link = NULL) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    $result = PMA_DBI_query('SHOW FULL FIELDS FROM ' . PMA_backquote($database) . '.' . PMA_backquote($table), $link);

    $fields = array();
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $fields[] = $row;
    }

    return $fields;
}

?>
