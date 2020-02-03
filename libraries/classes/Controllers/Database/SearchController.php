<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Search;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

class SearchController extends AbstractController
{
    public function index(): void
    {
        global $cfg, $db, $err_url, $url_query, $url_params, $tables, $num_tables, $total_num_tables, $sub_part;
        global $is_show_stats, $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos;

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('database/search.js');
        $scripts->addFile('sql.js');
        $scripts->addFile('makegrid.js');

        Common::database();

        // If config variable $cfg['UseDbSearch'] is on false : exit.
        if (! $cfg['UseDbSearch']) {
            Generator::mysqlDie(
                __('Access denied!'),
                '',
                false,
                $err_url
            );
        }
        $url_params['goto'] = Url::getFromRoute('/database/search');
        $url_query .= Url::getCommon($url_params, '&');

        // Create a database search instance
        $databaseSearch = new Search($this->dbi, $db, $this->template);

        // Display top links if we are not in an Ajax request
        if (! $this->response->isAjax()) {
            [
                $tables,
                $num_tables,
                $total_num_tables,
                $sub_part,
                $is_show_stats,
                $db_is_system_schema,
                $tooltip_truename,
                $tooltip_aliasname,
                $pos,
            ] = Util::getDbInfo($db, $sub_part ?? '');
        }

        // Main search form has been submitted, get results
        if (isset($_POST['submit_search'])) {
            $this->response->addHTML($databaseSearch->getSearchResults());
        }

        // If we are in an Ajax request, we need to exit after displaying all the HTML
        if ($this->response->isAjax() && empty($_REQUEST['ajax_page_request'])) {
            return;
        }

        // Display the search form
        $this->response->addHTML($databaseSearch->getMainHtml());
    }
}
