<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Runs common work
 */
require('./db_details_common.php');
require_once './libraries/sql_query_form.lib.php';

/**
 * Gets informations about the database and, if it is empty, move to the
 * "db_details_structure.php" script where table can be created
 */
require('./db_details_db_info.php');
if ( $num_tables == 0 && empty( $db_query_force ) ) {
    $sub_part   = '';
    $is_info    = TRUE;
    require './db_details_structure.php';
    exit();
}

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm();

/**
 * Displays the footer
 */
require_once './footer.inc.php';
?>
