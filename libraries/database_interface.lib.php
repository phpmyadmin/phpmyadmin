<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/dbi/' . $cfg['Server']['extension'] . '.dbi.lib.php');

function PMA_DBI_query($query, $dbh = '') {
    if (empty($dbh)) {
        $dbh = $GLOBALS['userlink'];
    }

    $res = PMA_DBI_try_query($query, $dbh)
        or PMA_mysqlDie(PMA_DBI_getError($dbh), $query);

    return $res;
}

?>