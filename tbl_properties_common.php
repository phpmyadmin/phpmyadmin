<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');

// Check parameters
PMA_checkParameters(array('db','table'));

if ( PMA_MYSQL_INT_VERSION >= 50002 && $db === 'information_schema' ) {
    $db_is_information_schema = true;
} else {
    $db_is_information_schema = false;
}

$url_query = PMA_generate_common_url($db, $table);

/**
 * Ensures the database and the table exist (else move to the "parent" script)
 */
require_once('./libraries/db_table_exists.lib.php');

?>
