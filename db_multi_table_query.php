<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Response;
use PhpMyAdmin\DbMultiTableQuery;
use PhpMyAdmin\Sql;

require_once 'libraries/common.inc.php';

if (isset($_REQUEST['sql_query'])) {
    $sql_query = $_REQUEST['sql_query'];
    $db = $_REQUEST['db'];
    include_once 'libraries/parse_analyze.lib.php';
    list(
        $analyzed_sql_results,
        $db,
        $table_from_sql
    ) = PMA_parseAnalyze($sql_query, $db);

    extract($analyzed_sql_results);
    $goto = 'db_multi_table_query.php';
    $html_output = Sql::executeQueryAndSendQueryResponse(
        null, // analyzed_sql_results
        false, // is_gotofile
        $db, // db
        null, // table
        null, // find_real_end
        null, // sql_query_for_bookmark - see below
        null, // extra_data
        null, // message_to_show
        null, // message
        null, // sql_data
        $goto, // goto
        $pmaThemeImage, // pmaThemeImage
        null, // disp_query
        null, // disp_message
        null, // query_type
        $sql_query, // sql_query
        null, // selectedTables
        null // complete_query
    );
    exit;
}

$response = Response::getInstance();

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('vendor/jquery.md5.js');
$scripts->addFile('db_multi_table_query.js');

$QueryInstance = new DbMultiTableQuery($db);

$response->addHTML($QueryInstance->getFormHTML());
