<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * provides the sql form
 * 
 * @usedby  server_sql.php  server SQL-tab
 * @usedby  db_details.php  database SQL-tab
 * @usedby  server_sql.php  server SQL-tab
 */

// Check parameters
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');
require_once './libraries/sql_query_form.lib.php';

// we dont need to check booth variables each time
if ( false == $cfg['QueryFrame'] ) {
    $cfg['QueryFrameJS'] = false;
}

if ( empty( $querydisplay_tab ) ) {
    $querydisplay_tab = '';
}

// in case of javascript disabled in queryframe ...
if ( $GLOBALS['cfg']['QueryFrame'] && ! $GLOBALS['cfg']['QueryFrameJS'] 
  && $is_inside_querywindow ) {
    // ... we redirect to appropriate query sql page
    // works only full if $db and $table is also stored/grabbed from $_COOKIE
    if ( ! empty( $table ) ) {
        require './tbl_properties.php';
    }
    elseif ( ! empty( $db ) ) {
        require './db_details.php';
    }
    else {
        require './server_sql.php';
    }
    exit;
}

/**
 * Defines the query to be displayed in the query textarea
 */
if ( ! empty( $show_query ) ) {
    $query_to_display = $sql_query;
} else {
    $query_to_display = '';
}
unset( $sql_query );

PMA_sqlQueryForm( $query_to_display, $querydisplay_tab );
?>
