<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

use function __;
use function is_array;
use function is_numeric;

final class KillController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isAjax()) {
            return;
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
    }

    private function getProcessId(mixed $routeVars): int
    {
        if (is_array($routeVars) && isset($routeVars['id']) && is_numeric($routeVars['id'])) {
            return (int) $routeVars['id'];
        }

        return 0;
    }
}
