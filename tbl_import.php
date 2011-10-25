<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'import.js';

/**
 * Gets tables informations and displays top links
 */
require_once './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_import.php&amp;back=tbl_import.php';

require_once './libraries/tbl_info.inc.php';
/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';

$import_type = 'table';
require_once './libraries/display_import.lib.php';

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>

