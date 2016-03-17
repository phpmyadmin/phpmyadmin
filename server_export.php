<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server export page
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\config\PageSettings;
use PMA\libraries\Response;

/**
 * Does the common work
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/display_export.lib.php';

PageSettings::showGroup('Export');

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('export.js');

$export_page_title = __('View dump (schema) of databases') . "\n";

$select_item = isset($tmp_select)? $tmp_select : '';
$multi_values  = PMA_getHtmlForExportSelectOptions($select_item);

require_once 'libraries/display_export.lib.php';

if (! isset($sql_query)) {
    $sql_query = '';
}
if (! isset($num_tables)) {
    $num_tables = 0;
}
if (! isset($unlim_num_rows)) {
    $unlim_num_rows = 0;
}
$response = Response::getInstance();
$response->addHTML(
    PMA_getExportDisplay(
        'server', $db, $table, $sql_query, $num_tables,
        $unlim_num_rows, $multi_values
    )
);
