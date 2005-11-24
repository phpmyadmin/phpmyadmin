<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Gets tables informations and displays top links
 */
require_once('./tbl_properties_common.php');
require_once('./libraries/tbl_properties_table_info.inc.php');
/**
 * Displays top menu links
 */
require_once('./libraries/tbl_properties_links.inc.php');

$import_type = 'table';
require_once('./libraries/display_import.lib.php');

/**
 * Displays the footer
 */
require_once('./libraries/footer.inc.php');
?>

