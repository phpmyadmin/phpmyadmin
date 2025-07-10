<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/columns', ['POST'])]
final class ColumnController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $db = $request->getParsedBodyParamAsStringOrNull('db');
        $table = $request->getParsedBodyParamAsStringOrNull('table');

        if (! isset($db, $table)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return $this->response->response();
        }

        $this->response->addJSON(['columns' => $this->dbi->getColumnNames($db, $table)]);

        return $this->response->response();
    }
}
