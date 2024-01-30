<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Privileges;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Template;
use Throwable;

use function __;

final class AccountUnlockController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private AccountLocking $accountLocking)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isAjax()) {
            return;
        }

        /** @var string $userName */
        $userName = $request->getParsedBodyParam('username');
        /** @var string $hostName */
        $hostName = $request->getParsedBodyParam('hostname');

        try {
            $this->accountLocking->unlock($userName, $hostName);
        } catch (Throwable $exception) {
            $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error($exception->getMessage())]);

            return;
        }

        $message = Message::success(__('The account %s@%s has been successfully unlocked.'));
        $message->addParam($userName);
        $message->addParam($hostName);
        $this->response->addJSON(['message' => $message]);
    }
}
