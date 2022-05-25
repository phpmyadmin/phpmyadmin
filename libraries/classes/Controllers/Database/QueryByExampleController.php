<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
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

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['savedSearchList'] = $GLOBALS['savedSearchList'] ?? null;
        $GLOBALS['savedSearch'] = $GLOBALS['savedSearch'] ?? null;
        $GLOBALS['currentSearchId'] = $GLOBALS['currentSearchId'] ?? null;
        $GLOBALS['goto'] = $GLOBALS['goto'] ?? null;
        $GLOBALS['sub_part'] = $GLOBALS['sub_part'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $savedQbeSearchesFeature = $this->relation->getRelationParameters()->savedQueryByExampleSearchesFeature;

        $GLOBALS['savedSearchList'] = [];
        $GLOBALS['savedSearch'] = null;
        $GLOBALS['currentSearchId'] = null;
        $this->addScriptFiles(['database/qbe.js']);
        if ($savedQbeSearchesFeature !== null) {
            //Get saved search list.
            $GLOBALS['savedSearch'] = new SavedSearches();
            $GLOBALS['savedSearch']->setUsername($GLOBALS['cfg']['Server']['user'])
                ->setDbname($GLOBALS['db']);

            if (! empty($_POST['searchId'])) {
                $GLOBALS['savedSearch']->setId($_POST['searchId']);
            }

            //Action field is sent.
            if (isset($_POST['action'])) {
                $GLOBALS['savedSearch']->setSearchName($_POST['searchName']);
                if ($_POST['action'] === 'create') {
                    $GLOBALS['savedSearch']->setId(null)
                        ->setCriterias($_POST)
                        ->save($savedQbeSearchesFeature);
                } elseif ($_POST['action'] === 'update') {
                    $GLOBALS['savedSearch']->setCriterias($_POST)
                        ->save($savedQbeSearchesFeature);
                } elseif ($_POST['action'] === 'delete') {
                    $GLOBALS['savedSearch']->delete($savedQbeSearchesFeature);
                    //After deletion, reset search.
                    $GLOBALS['savedSearch'] = new SavedSearches();
                    $GLOBALS['savedSearch']->setUsername($GLOBALS['cfg']['Server']['user'])
                        ->setDbname($GLOBALS['db']);
                    $_POST = [];
                } elseif ($_POST['action'] === 'load') {
                    if (empty($_POST['searchId'])) {
                        //when not loading a search, reset the object.
                        $GLOBALS['savedSearch'] = new SavedSearches();
                        $GLOBALS['savedSearch']->setUsername($GLOBALS['cfg']['Server']['user'])
                            ->setDbname($GLOBALS['db']);
                        $_POST = [];
                    } else {
                        $GLOBALS['savedSearch']->load($savedQbeSearchesFeature);
                    }
                }
                //Else, it's an "update query"
            }

            $GLOBALS['savedSearchList'] = $GLOBALS['savedSearch']->getList($savedQbeSearchesFeature);
            $GLOBALS['currentSearchId'] = $GLOBALS['savedSearch']->getId();
        }

        /**
         * A query has been submitted -> (maybe) execute it
         */
        $hasMessageToDisplay = false;
        if (isset($_POST['submit_sql']) && ! empty($GLOBALS['sql_query'])) {
            if (stripos($GLOBALS['sql_query'], 'SELECT') !== 0) {
                $hasMessageToDisplay = true;
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/database/sql');

                $sql = new Sql(
                    $this->dbi,
                    $this->relation,
                    new RelationCleanup($this->dbi, $this->relation),
                    new Operations($this->dbi, $this->relation),
                    new Transformations(),
                    $this->template
                );

                $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
                    null,
                    false, // is_gotofile
                    $_POST['db'], // db
                    null, // table
                    false, // find_real_end
                    null, // sql_query_for_bookmark
                    null, // extra_data
                    null, // message_to_show
                    null, // sql_data
                    $GLOBALS['goto'], // goto
                    null, // disp_query
                    null, // disp_message
                    $GLOBALS['sql_query'], // sql_query
                    null // complete_query
                ));
            }
        }

        $GLOBALS['sub_part'] = '_qbe';

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/qbe');

        [
            $GLOBALS['tables'],
            $GLOBALS['num_tables'],
            $GLOBALS['total_num_tables'],
            $GLOBALS['sub_part'],,,
            $GLOBALS['tooltip_truename'],
            $GLOBALS['tooltip_aliasname'],
            $GLOBALS['pos'],
        ] = Util::getDbInfo($GLOBALS['db'], $GLOBALS['sub_part']);

        $databaseQbe = new Qbe(
            $this->relation,
            $this->template,
            $this->dbi,
            $GLOBALS['db'],
            $GLOBALS['savedSearchList'],
            $GLOBALS['savedSearch']
        );

        $this->render('database/qbe/index', [
            'url_params' => $GLOBALS['urlParams'],
            'has_message_to_display' => $hasMessageToDisplay,
            'selection_form_html' => $databaseQbe->getSelectionForm(),
        ]);
    }
}
