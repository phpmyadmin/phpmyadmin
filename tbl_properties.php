<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Runs common work
 */
require('./tbl_properties_common.php');
require_once('./libraries/sql_query_form.lib.php');

$err_url   = 'tbl_properties.php' . $err_url;
$goto = 'tbl_properties.php';
$back = 'tbl_properties.php';

/**
 * Get table information
 */
require_once('./libraries/tbl_properties_table_info.inc.php');

/**
 * Displays top menu links
 */
require_once('./libraries/tbl_properties_links.inc.php');

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm();

/**
 * Displays the footer
 */
require_once('./libraries/footer.inc.php');
?>
