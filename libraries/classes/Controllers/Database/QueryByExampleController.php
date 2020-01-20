<?php
/**
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\SavedSearches;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Query by Example controller
 *
 * @package PhpMyAdmin\Controllers\Database
 */
class QueryByExampleController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param string            $db       Database name
     * @param Relation          $relation Relation object
     */
    public function __construct($response, $dbi, Template $template, $db, Relation $relation)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->relation = $relation;
    }

    /**
     * @return void
     */
    public function index(): void
    {
        global $db, $pmaThemeImage, $url_query, $savedSearchList, $savedSearch, $currentSearchId;
        global $message_to_display, $sql_query, $goto, $sub_part, $tables, $num_tables, $total_num_tables, $route;
        global $is_show_stats, $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $url_params;

        // Gets the relation settings
        $cfgRelation = $this->relation->getRelationsParam();

        $savedSearchList = [];
        $savedSearch = null;
        $currentSearchId = null;
        if ($cfgRelation['savedsearcheswork']) {
            $header = $this->response->getHeader();
            $scripts = $header->getScripts();
            $scripts->addFile('database/qbe.js');

            //Get saved search list.
            $savedSearch = new SavedSearches($GLOBALS, $this->relation);
            $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                ->setDbname($db);

            if (! empty($_POST['searchId'])) {
                $savedSearch->setId($_POST['searchId']);
            }

            //Action field is sent.
            if (isset($_POST['action'])) {
                $savedSearch->setSearchName($_POST['searchName']);
                if ($_POST['action'] === 'create') {
                    $saveResult = $savedSearch->setId(null)
                        ->setCriterias($_POST)
                        ->save();
                } elseif ($_POST['action'] === 'update') {
                    $saveResult = $savedSearch->setCriterias($_POST)
                        ->save();
                } elseif ($_POST['action'] === 'delete') {
                    $deleteResult = $savedSearch->delete();
                    //After deletion, reset search.
                    $savedSearch = new SavedSearches($GLOBALS, $this->relation);
                    $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                        ->setDbname($db);
                    $_POST = [];
                } elseif ($_POST['action'] === 'load') {
                    if (empty($_POST['searchId'])) {
                        //when not loading a search, reset the object.
                        $savedSearch = new SavedSearches($GLOBALS, $this->relation);
                        $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                            ->setDbname($db);
                        $_POST = [];
                    } else {
                        $loadResult = $savedSearch->load();
                    }
                }
                //Else, it's an "update query"
            }

            $savedSearchList = $savedSearch->getList();
            $currentSearchId = $savedSearch->getId();
        }

        /**
         * A query has been submitted -> (maybe) execute it
         */
        $message_to_display = false;
        if (isset($_POST['submit_sql']) && ! empty($sql_query)) {
            if (stripos($sql_query, 'SELECT') !== 0) {
                $message_to_display = true;
            } else {
                $goto = Url::getFromRoute('/database/sql');
                $sql = new Sql();
                $sql->executeQueryAndSendQueryResponse(
                    null, // analyzed_sql_results
                    false, // is_gotofile
                    $_POST['db'], // db
                    null, // table
                    false, // find_real_end
                    null, // sql_query_for_bookmark
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
            }
        }

        $sub_part  = '_qbe';
        Common::database();

        $url_params['goto'] = Url::getFromRoute('/database/qbe');
        $url_query .= Url::getCommon($url_params, '&');

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
        ) = Util::getDbInfo($db, $sub_part ?? '');

        if ($message_to_display) {
            Message::error(
                __('You have to choose at least one column to display!')
            )
                ->display();
        }
        unset($message_to_display);

        // create new qbe search instance
        $db_qbe = new Qbe($this->relation, $this->template, $this->dbi, $db, $savedSearchList, $savedSearch);

        $secondaryTabs = [
            'multi' => [
                'link' => Url::getFromRoute('/database/multi_table_query'),
                'text' => __('Multi-table query'),
                'active' => $route === '/database/multi_table_query',
            ],
            'qbe' => [
                'link' => Url::getFromRoute('/database/qbe'),
                'text' => __('Query by example'),
                'active' => $route === '/database/qbe',
            ],
        ];
        $this->response->addHTML(
            $this->template->render('secondary_tabs', [
                'url_params' => $url_params,
                'sub_tabs' => $secondaryTabs,
            ])
        );

        $url = Url::getFromRoute(
            '/database/designer',
            array_merge($url_params, ['query' => 1])
        );
        $this->response->addHTML(
            Message::notice(
                sprintf(
                    __('Switch to %svisual builder%s'),
                    '<a href="' . $url . '">',
                    '</a>'
                )
            )
        );

        /**
         * Displays the Query by example form
         */
        $this->response->addHTML($db_qbe->getSelectionForm());
    }
}
