<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles find and replace tab
 *
 * Displays find and replace form, allows previewing and do the replacing
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/TableSearch.class.php';

$response = PMA_Response::getInstance();
$table_search = new PMA_TableSearch($db, $table, "replace");

$connectionCharSet = $GLOBALS['dbi']->fetchValue(
    "SHOW VARIABLES LIKE 'character_set_connection'", 0, 1
);
if (isset($_POST['find'])) {
    $preview = $table_search->getReplacePreview(
        $_POST['columnIndex'],
        $_POST['find'],
        $_POST['replaceWith'],
        $connectionCharSet
    );
    $response->addJSON('preview', $preview);
    exit;
}

$header  = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('tbl_find_replace.js');

// Show secondary level of tabs
$htmlOutput  = $table_search->getSecondaryTabs();

if (isset($_POST['replace'])) {
    $htmlOutput .= $table_search->replace(
        $_POST['columnIndex'],
        $_POST['findString'],
        $_POST['replaceWith'],
        $connectionCharSet
    );
    $htmlOutput .= PMA_Util::getMessage(
        __('Your SQL query has been executed successfully.'),
        null, 'success'
    );
}

if (! isset($goto)) {
    $goto = $GLOBALS['cfg']['DefaultTabTable'];
}
// Defines the url to return to in case of error in the next sql statement
$err_url   = $goto . '?' . PMA_URL_getCommon($db, $table);
// Displays the find and replace form
$htmlOutput .= $table_search->getSelectionForm($goto);
$response->addHTML($htmlOutput);

?>
