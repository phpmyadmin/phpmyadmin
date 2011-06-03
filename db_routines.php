<?php

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';

$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js'; // FIXME: Only include for execute routine dialog.
$GLOBALS['js_include'][] = 'db_routines.js';

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

if ($GLOBALS['is_ajax_request'] != true) {
	/**
	 * Displays the header
	 */
	require_once './libraries/db_common.inc.php';
	/**
	 * Displays the tabs
	 */
	require_once './libraries/db_info.inc.php';
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
