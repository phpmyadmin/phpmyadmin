<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles database multi-table querying
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

if (isset($_POST['sql_query'])) {
    MultiTableQuery::displayResults(
        $_POST['sql_query'],
        $_REQUEST['db'],
        $pmaThemeImage
    );
} else {
    $response = Response::getInstance();

    $header = $response->getHeader();
    $scripts = $header->getScripts();
    $scripts->addFile('vendor/jquery/jquery.md5.js');
    $scripts->addFile('db_multi_table_query.js');

    $queryInstance = new MultiTableQuery($GLOBALS['dbi'], $db);

    $response->addHTML($queryInstance->getFormHtml());
}
