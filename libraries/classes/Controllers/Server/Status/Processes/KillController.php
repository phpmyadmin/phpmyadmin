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

    /** @param mixed[] $params Request parameters */
    public function __invoke(ServerRequest $request, array $params): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        $kill = (int) $params['id'];
        $query = $this->dbi->getKillQuery($kill);

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

        $message->addParam($kill);

        $this->response->addJSON(['message' => $message]);
    }
}
