<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Privileges;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use Throwable;

use function __;

#[Route('/server/privileges/account-lock', ['POST'])]
final class AccountLockController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly AccountLocking $accountLocking,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $userName = $request->getParsedBodyParamAsString('username');
        $hostName = $request->getParsedBodyParamAsString('hostname');

        try {
            $this->accountLocking->lock($userName, $hostName);
        } catch (Throwable $exception) {
            $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error($exception->getMessage())]);

            return $this->response->response();
        }

        $message = Message::success(__('The account %s@%s has been successfully locked.'));
        $message->addParam($userName);
        $message->addParam($hostName);
        $this->response->addJSON(['message' => $message]);

        return $this->response->response();
    }
}
