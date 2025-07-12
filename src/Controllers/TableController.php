<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/tables', ['POST'])]
final class TableController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->hasBodyParam('db')) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return $this->response->response();
        }

        $this->response->addJSON(['tables' => $this->dbi->getTables($request->getParsedBodyParamAsString('db'))]);

        return $this->response->response();
    }
}
