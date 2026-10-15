<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Events;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Events\Events;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;

use function __;
use function is_string;
use function sprintf;
use function trim;

#[Route('/events/export', ['GET'])]
final readonly class ExportController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $responseRenderer,
        private Events $events,
        private DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        // selectDatabase is necessary for Events::getEventDefinition()
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            return $this->responseRenderer->respondWithErrorMessage(__('No database selected.'));
        }

        $this->responseRenderer->addScriptFiles(['events.js', 'sql.js']);

        $schedulerStatus = $this->events->getEventSchedulerStatus();
        $eventName = $this->getEventName($request->getQueryParam('item_name'));
        if ($schedulerStatus !== 'DISABLED' && $eventName !== '') {
            $exportData = $this->events->getEventDefinition($databaseName->getName(), $eventName);

            if ($exportData === null || $exportData === '') {
                $exportData = false;
            }

            $eventName = Util::backquote($eventName);
            if ($exportData !== false) {
                $exportData = trim($exportData);
                $title = sprintf(__('Export of event %s'), $eventName);

                if ($request->isAjax()) {
                    $this->responseRenderer->addJSON('message', $exportData);
                    $this->responseRenderer->addJSON('title', $title);

                    return $this->responseRenderer->response();
                }

                $this->responseRenderer->render('events/export', ['title' => $title, 'data' => $exportData]);
            } else {
                $message = Message::error(
                    __('Error in processing request: No event with name %1$s found in database %2$s.'),
                    [
                        $eventName,
                        Util::backquote($databaseName->getName()),
                    ],
                );

                if ($request->isAjax()) {
                    $this->responseRenderer->setRequestStatus(false);
                    $this->responseRenderer->addJSON('message', $message);

                    return $this->responseRenderer->response();
                }

                $this->responseRenderer->addHTML($message);
            }
        }

        return $this->responseRenderer->response();
    }

    private function getEventName(mixed $param): string
    {
        return is_string($param) ? $param : '';
    }
}
