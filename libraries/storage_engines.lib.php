<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Library for extracting information about the available storage engines
 *
 * Requires at least MySQL 4.1.2
 * TODO: Emulation for earlier versions.
 */

$GLOBALS['mysql_storage_engines'] = array();
$res = PMA_DBI_query('SHOW STORAGE ENGINES');
while ($row = PMA_DBI_fetch_assoc($res)) {
    $GLOBALS['mysql_storage_engines'][strtolower($row['Engine'])] = $row;
}
PMA_DBI_free_result($res);
unset($res, $row);

?>
