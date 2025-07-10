<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\StorageEngine;

/**
 * Handles viewing storage engine details
 */
#[Route('/server/engines', ['GET'])]
final class EnginesController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->response->render('server/engines/index', ['engines' => StorageEngine::getStorageEngines()]);

        return $this->response->response();
    }
}
