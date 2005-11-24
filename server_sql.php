<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Does the common work
 */
$js_to_run = 'functions.js';
require_once './libraries/server_common.inc.php';
require_once './libraries/sql_query_form.lib.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm();

/**
 * Displays the footer
 */
require_once './footer.inc.php';
?>
