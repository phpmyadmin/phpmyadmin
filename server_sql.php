<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Does the common work
 */
$GLOBALS['js_include'][] = 'functions.js';
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
require_once './libraries/footer.inc.php';
?>
