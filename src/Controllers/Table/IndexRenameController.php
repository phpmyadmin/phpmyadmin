<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function is_array;

final class IndexRenameController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private Indexes $indexes,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        if (! isset($_POST['create_edit_table'])) {
            if (! $this->checkParameters(['db', 'table'])) {
                return;
            }

            $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                Config::getInstance()->settings['DefaultTabTable'],
                'table',
            );
            $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->hasDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return;
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No table selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

                return;
            }
        }

        if (isset($_POST['index'])) {
            if (is_array($_POST['index'])) {
                // coming already from form
                $index = new Index($_POST['index']);
            } else {
                $index = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table'])->getIndex($_POST['index']);
            }
        } else {
            $index = new Index();
        }

        if (isset($_POST['do_save_data'])) {
            $oldIndexName = $request->getParsedBodyParam('old_index', '');
            $this->indexes->doSaveData(
                $request,
                $index,
                true,
                $GLOBALS['db'],
                $GLOBALS['table'],
                $request->hasBodyParam('preview_sql'),
                $oldIndexName,
            );

            return;
        }

        $this->displayRenameForm($index);
    }

    /**
     * Display the rename form to rename an index
     *
     * @param Index $index An Index instance.
     */
    private function displayRenameForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);

        $formParams = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];

        if (isset($_POST['old_index'])) {
            $formParams['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $formParams['old_index'] = $_POST['index'];
        }

        $this->render('table/index_rename_form', ['index' => $index, 'form_params' => $formParams]);
    }
}
