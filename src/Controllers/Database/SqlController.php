<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;

/**
 * Database SQL executor
 */
class SqlController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly SqlQueryForm $sqlQueryForm,
        private readonly PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $GLOBALS['goto'] ??= null;
        $GLOBALS['back'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->response->addScriptFiles(['makegrid.js', 'vendor/jquery/jquery.uitablefilter.js', 'sql.js']);

        $this->pageSettings->init('Sql');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        if (! $this->response->checkParameters(['db'])) {
            return null;
        }

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabDatabase'],
            'database',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return null;
            }

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return null;
        }

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $GLOBALS['goto'] = Url::getFromRoute('/database/sql');
        $GLOBALS['back'] = $GLOBALS['goto'];
        $delimiter = $request->getParsedBodyParam('delimiter', ';');

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            Current::$database,
            '',
            true,
            false,
            htmlspecialchars($delimiter),
        ));

        return null;
    }
}
