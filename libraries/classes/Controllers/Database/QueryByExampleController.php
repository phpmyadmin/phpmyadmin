<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\SavedSearches;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function stripos;

class QueryByExampleController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, Relation $relation, $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $db, $savedSearchList, $savedSearch, $currentSearchId, $PMA_Theme;
        global $sql_query, $goto, $sub_part, $tables, $num_tables, $total_num_tables;
        global $tooltip_truename, $tooltip_aliasname, $pos, $url_params, $cfg, $err_url;

        // Gets the relation settings
        $cfgRelation = $this->relation->getRelationsParam();

        $savedSearchList = [];
        $savedSearch = null;
        $currentSearchId = null;
        $this->addScriptFiles(['database/qbe.js']);
        if ($cfgRelation['savedsearcheswork']) {
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
        $hasMessageToDisplay = false;
        if (isset($_POST['submit_sql']) && ! empty($sql_query)) {
            if (stripos($sql_query, 'SELECT') !== 0) {
                $hasMessageToDisplay = true;
            } else {
                $goto = Url::getFromRoute('/database/sql');

                $sql = new Sql(
                    $this->dbi,
                    $this->relation,
                    new RelationCleanup($this->dbi, $this->relation),
                    new Operations($this->dbi, $this->relation),
                    new Transformations(),
                    $this->template
                );

                $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
                    null, // analyzed_sql_results
                    false, // is_gotofile
                    $_POST['db'], // db
                    null, // table
                    false, // find_real_end
                    null, // sql_query_for_bookmark
                    null, // extra_data
                    null, // message_to_show
                    null, // sql_data
                    $goto, // goto
                    $PMA_Theme->getImgPath(),
                    null, // disp_query
                    null, // disp_message
                    $sql_query, // sql_query
                    null // complete_query
                ));
            }
        }

        $sub_part  = '_qbe';

        Util::checkParameters(['db']);

        $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $err_url .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $url_params['goto'] = Url::getFromRoute('/database/qbe');

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,,,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part ?? '');

        $databaseQbe = new Qbe($this->relation, $this->template, $this->dbi, $db, $savedSearchList, $savedSearch);

        $this->render('database/qbe/index', [
            'url_params' => $url_params,
            'has_message_to_display' => $hasMessageToDisplay,
            'selection_form_html' => $databaseQbe->getSelectionForm(),
        ]);
    }
}
