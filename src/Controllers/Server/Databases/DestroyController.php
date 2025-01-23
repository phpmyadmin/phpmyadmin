<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function array_filter;
use function count;
use function is_array;
use function is_string;

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
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $config = Config::getInstance();
        if (
            ! $request->isAjax()
            || (! $this->dbi->isSuperUser() && ! $config->settings['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return $this->response->response();
        }

        $selectedDbs = $request->getParsedBodyParam('selected_dbs');
        $selectedDbs = is_array($selectedDbs) ? $selectedDbs : [];
        $selectedDbs = array_filter($selectedDbs, static function ($database) use ($config): bool {
            return is_string($database)
                && ! Utilities::isSystemSchema($database, true)
                && $database !== ($config->selectedServer['pmadb'] ?? '');
        });

        if ($selectedDbs === []) {
            $message = Message::error(__('No databases selected.'));
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return $this->response->response();
        }

        $numberOfDatabases = count($selectedDbs);

        foreach ($selectedDbs as $database) {
            $this->relationCleanup->database($database);
            $aQuery = 'DROP DATABASE ' . Util::backquote($database);
            ResponseRenderer::$reload = true;

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
