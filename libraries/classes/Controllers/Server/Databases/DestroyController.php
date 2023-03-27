<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function count;
use function is_array;

final class DestroyController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private Transformations $transformations,
        private RelationCleanup $relationCleanup,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['selected'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['reload'] ??= null;

        $selectedDbs = $request->getParsedBodyParam('selected_dbs');

        if (
            ! $this->response->isAjax()
            || (! $this->dbi->isSuperUser() && ! $GLOBALS['cfg']['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        if (
            ! is_array($selectedDbs)
            || $selectedDbs === []
        ) {
            $message = Message::error(__('No databases selected.'));
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        $GLOBALS['errorUrl'] = Url::getFromRoute('/server/databases');
        $GLOBALS['selected'] = $selectedDbs;
        $numberOfDatabases = count($selectedDbs);

        foreach ($selectedDbs as $database) {
            $this->relationCleanup->database($database);
            $aQuery = 'DROP DATABASE ' . Util::backquote($database);
            $GLOBALS['reload'] = true;

            $this->dbi->query($aQuery);
            $this->transformations->clear($database);
        }

        $this->dbi->getDatabaseList()->build();

        $message = Message::success(
            _ngettext(
                '%1$d database has been dropped successfully.',
                '%1$d databases have been dropped successfully.',
                $numberOfDatabases,
            ),
        );
        $message->addParam($numberOfDatabases);
        $json = ['message' => $message];
        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON($json);
    }
}
