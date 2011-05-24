<?php

/**
 *
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'db_routines.js';

require_once './libraries/common.lib.php';
require_once './libraries/db_common.inc.php';
require_once './libraries/db_info.inc.php';

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

if ($GLOBALS['is_ajax_request'] != true) {
	/**
	 * Displays the header
	 */
	require_once './libraries/header.inc.php';

	/**
	 * Displays the tabs
	 */
	require_once './libraries/db_links.inc.php';
}

/**
 * Displays the list of routines
 */
require_once './libraries/db_routines.inc.php';

if ($GLOBALS['is_ajax_request'] != true) {
	/**
	 * Displays the footer
	 */
	require './libraries/footer.inc.php';
}

?>
