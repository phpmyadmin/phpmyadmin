<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function count;
use function is_array;

final class DestroyController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly Transformations $transformations,
        private readonly RelationCleanup $relationCleanup,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['selected'] ??= null;

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $selectedDbs = $request->getParsedBodyParam('selected_dbs');

        if (
            ! $request->isAjax()
            || (! $this->dbi->isSuperUser() && ! Config::getInstance()->settings['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return $this->response->response();
        }

        if (
            ! is_array($selectedDbs)
            || $selectedDbs === []
        ) {
            $message = Message::error(__('No databases selected.'));
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return $this->response->response();
        }

        $GLOBALS['selected'] = $selectedDbs;
        $numberOfDatabases = count($selectedDbs);

        foreach ($selectedDbs as $database) {
            $this->relationCleanup->database($database);
            $aQuery = 'DROP DATABASE ' . Util::backquote($database);
            $GLOBALS['reload'] = true;

            $this->dbi->query($aQuery);
            $this->transformations->clear($database);
        }

        $this->dbi->getDatabaseList()->build($userPrivileges);

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

        return $this->response->response();
    }
}
