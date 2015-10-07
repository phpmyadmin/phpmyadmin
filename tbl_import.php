<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table import
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\config\PageSettings;

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

PageSettings::showGroup('Import');

$response = PMA\libraries\Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

/**
 * Gets tables information and displays top links
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_import.php&amp;back=tbl_import.php';

require_once 'libraries/tbl_info.inc.php';

require 'libraries/display_import.lib.php';
$response = PMA\libraries\Response::getInstance();
$response->addHTML(
    PMA_getImportDisplay(
        'table', $db, $table, $max_upload_size
    )
);
