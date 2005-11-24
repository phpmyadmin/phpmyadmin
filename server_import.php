<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Does the common work
 */
require('./libraries/server_common.inc.php');


/**
 * Displays the links
 */
require('./libraries/server_links.inc.php');

$import_type = 'server';
require('./libraries/display_import.lib.php');
/**
 * Displays the footer
 */
require('./footer.inc.php');
?>

