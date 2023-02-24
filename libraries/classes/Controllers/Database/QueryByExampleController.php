<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Exceptions\SavedSearchesException;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
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
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['goto'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $savedQbeSearchesFeature = $this->relation->getRelationParameters()->savedQueryByExampleSearchesFeature;

        $savedSearchList = [];
        $savedSearch = null;
        $this->addScriptFiles(['database/qbe.js']);
        if ($savedQbeSearchesFeature !== null) {
            //Get saved search list.
            $savedSearch = new SavedSearches();
            $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                ->setDbname($GLOBALS['db']);

            $searchId = $request->getParsedBodyParam('searchId');
            if (! empty($searchId)) {
                $savedSearch->setId($searchId);
            }

            //Action field is sent.
            if ($request->hasBodyParam('action')) {
                $savedSearch->setSearchName($request->getParsedBodyParam('searchName'));
                $action = $request->getParsedBodyParam('action');
                if ($action === 'create') {
                    try {
                        $savedSearch->setId(null)
                            ->setCriterias($request->getParsedBody())
                            ->save($savedQbeSearchesFeature);
                    } catch (SavedSearchesException $exception) {
                        $this->response->setRequestStatus(false);
                        $this->response->addJSON('fieldWithError', 'searchName');
                        $this->response->addJSON('message', Message::error($exception->getMessage())->getDisplay());

                        return;
                    }
                } elseif ($action === 'update') {
                    try {
                        $savedSearch->setCriterias($request->getParsedBody())
                            ->save($savedQbeSearchesFeature);
                    } catch (SavedSearchesException $exception) {
                        $this->response->setRequestStatus(false);
                        $this->response->addJSON('fieldWithError', 'searchName');
                        $this->response->addJSON('message', Message::error($exception->getMessage())->getDisplay());

                        return;
                    }
                } elseif ($action === 'delete') {
                    try {
                        $savedSearch->delete($savedQbeSearchesFeature);
                    } catch (SavedSearchesException $exception) {
                        $this->response->setRequestStatus(false);
                        $this->response->addJSON('fieldWithError', 'searchId');
                        $this->response->addJSON('message', Message::error($exception->getMessage())->getDisplay());

                        return;
                    }

                    //After deletion, reset search.
                    $savedSearch = new SavedSearches();
                    $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                        ->setDbname($GLOBALS['db']);
                    $_POST = [];
                } elseif ($action === 'load') {
                    if (empty($searchId)) {
                        //when not loading a search, reset the object.
                        $savedSearch = new SavedSearches();
                        $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                            ->setDbname($GLOBALS['db']);
                        $_POST = [];
                    } else {
                        try {
                            $savedSearch->load($savedQbeSearchesFeature);
                        } catch (SavedSearchesException $exception) {
                            $this->response->setRequestStatus(false);
                            $this->response->addJSON('fieldWithError', 'searchId');
                            $this->response->addJSON('message', Message::error($exception->getMessage())->getDisplay());

                            return;
                        }
                    }
                }
                //Else, it's an "update query"
            }

            $savedSearchList = $savedSearch->getList($savedQbeSearchesFeature);
        }

        /**
         * A query has been submitted -> (maybe) execute it
         */
        $hasMessageToDisplay = false;
        if ($request->hasBodyParam('submit_sql') && ! empty($GLOBALS['sql_query'])) {
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
                    $this->template,
                );

                $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
                    null,
                    false, // is_gotofile
                    $request->getParsedBodyParam('db'), // db
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
                    null, // complete_query
                ));
            }
        }

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/qbe');

        $databaseQbe = new Qbe(
            $this->relation,
            $this->template,
            $this->dbi,
            $GLOBALS['db'],
            $savedSearchList,
            $savedSearch,
        );

        $this->render('database/qbe/index', [
            'url_params' => $GLOBALS['urlParams'],
            'has_message_to_display' => $hasMessageToDisplay,
            'selection_form_html' => $databaseQbe->getSelectionForm(),
        ]);
    }
}
