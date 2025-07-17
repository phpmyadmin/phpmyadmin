<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

use function __;
use function is_array;
use function is_numeric;

#[Route('/server/status/processes/kill/{id:\d+}', ['POST'])]
final class KillController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $processId = $this->getProcessId($request->getAttribute('routeVars'));
        $query = $this->dbi->getKillQuery($processId);

        if ($this->dbi->tryQuery($query)) {
            $message = Message::success(
                __('Thread %s was successfully killed.'),
            );
            $this->response->setRequestStatus(true);
        } else {
            $message = Message::error(
                __(
                    'phpMyAdmin was unable to kill thread %s. It probably has already been closed.',
                ),
            );
            $this->response->setRequestStatus(false);
        }

        $message->addParam($processId);

        $this->response->addJSON(['message' => $message]);

        return $this->response->response();
    }

    private function getProcessId(mixed $routeVars): int
    {
        if (is_array($routeVars) && isset($routeVars['id']) && is_numeric($routeVars['id'])) {
            return (int) $routeVars['id'];
        }

        return 0;
    }
}
