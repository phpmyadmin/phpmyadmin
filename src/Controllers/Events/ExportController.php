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
use function htmlspecialchars;
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
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $message = __('No database selected.');
            if (! $request->isAjax()) {
                return $this->responseRenderer->redirectToRoute('/', ['reload' => true, 'message' => $message]);
            }

            $this->responseRenderer->addJSON('message', $message);
            $this->responseRenderer->setRequestStatus(false);

            return $this->responseRenderer->response();
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

                $exportData = htmlspecialchars($exportData);

                $output = '<div class="container">';
                $output .= '<h2>' . $title . '</h2>';
                $output .= '<div class="card"><div class="card-body">';
                $output .= '<textarea rows="15" class="form-control">' . $exportData . '</textarea>';
                $output .= '</div></div></div>';

                $this->responseRenderer->addHTML($output);
            } else {
                $message = sprintf(
                    __('Error in processing request: No event with name %1$s found in database %2$s.'),
                    htmlspecialchars($eventName),
                    htmlspecialchars(Util::backquote($databaseName->getName())),
                );
                $message = Message::error($message);

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
