<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/* $Id$ */

require_once('./libraries/common.lib.php');

/**
 * Runs common work
 */
require('./libraries/db_common.inc.php');
require_once './libraries/sql_query_form.lib.php';

/**
 * Gets informations about the database and, if it is empty, move to the
 * "db_structure.php" script where table can be created
 */
require('./libraries/db_info.inc.php');
if ( $num_tables == 0 && empty( $db_query_force ) ) {
    $sub_part   = '';
    $is_info    = TRUE;
    require './db_structure.php';
    exit();
}

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm(true, false, isset($_REQUEST['delimiter']) ? $_REQUEST['delimiter'] : ';');

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
