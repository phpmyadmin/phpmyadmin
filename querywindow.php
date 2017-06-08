<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * script for query_window
 *
 * @package phpMyAdmin
 */
use PMA\libraries\Response;

require_once 'libraries/common.inc.php';
require_once 'libraries/sql_query_form.lib.php';

$response = Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('query_window.js');

$tabs = array();
$tabs['sql']['icon'] = 'b_sql.png';
$tabs['sql']['text'] = __('SQL');
$tabs['sql']['fragment'] = '#';
$tabs['sql']['attr'] = 'onclick="PMA_querywindowCommit(\'sql\');return false;"';
$tabs['sql']['active'] = true;
$response->addHTML(PMA\libraries\Util::getHtmlTabs($tabs, array(), 'topmenu', true));
$response->addHTML(PMA_getHtmlForSqlQueryForm());
?>
