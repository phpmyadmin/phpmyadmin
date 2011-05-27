<?php

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';

$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'display_triggers.js';

require_once './libraries/tbl_common.php';

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

/**
 * Displays the header and tabs
 */
require_once './libraries/tbl_links.inc.php';

/**
 * Displays the list of triggers
 */
require_once './libraries/display_triggers.inc.php';

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';


?>
