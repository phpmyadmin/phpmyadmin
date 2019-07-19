<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * searches the entire database
 *
 * @todo    make use of UNION when searching multiple tables
 * @todo    display executed query, optional?
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Database\Search;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db, $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var Template $template */
$template = $containerBuilder->get('template');

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('database/search.js');
$scripts->addFile('sql.js');
$scripts->addFile('makegrid.js');

require ROOT_PATH . 'libraries/db_common.inc.php';

// If config variable $GLOBALS['cfg']['UseDbSearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    Util::mysqlDie(
        __('Access denied!'),
        '',
        false,
        $err_url
    );
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

// Create a database search instance
$db_search = new Search($dbi, $db, $template);

// Display top links if we are not in an Ajax request
if (! $response->isAjax()) {
    list(
        $tables,
        $num_tables,
        $total_num_tables,
        $sub_part,
        $is_show_stats,
        $db_is_system_schema,
        $tooltip_truename,
        $tooltip_aliasname,
        $pos
    ) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
}

// Main search form has been submitted, get results
if (isset($_POST['submit_search'])) {
    $response->addHTML($db_search->getSearchResults());
}

// If we are in an Ajax request, we need to exit after displaying all the HTML
if ($response->isAjax() && empty($_REQUEST['ajax_page_request'])) {
    exit;
}

// Display the search form
$response->addHTML($db_search->getMainHtml());
