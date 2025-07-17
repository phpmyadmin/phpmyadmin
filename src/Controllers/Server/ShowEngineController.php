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

use function is_array;
use function is_string;

/**
 * Displays details about a given Storage Engine.
 */
#[Route('/server/engines/{engine}[/{page}]', ['GET'])]
final class ShowEngineController implements InvocableController
{
    private string $engine = '';
    private string $page = '';

    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->setEngineAndPageProperties($request->getAttribute('routeVars'));

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $engine = [];
        if (StorageEngine::isValid($this->engine)) {
            $storageEngine = StorageEngine::getEngine($this->engine);
            $engine = [
                'engine' => $this->engine,
                'title' => $storageEngine->getTitle(),
                'help_page' => $storageEngine->getMysqlHelpPage(),
                'comment' => $storageEngine->getComment(),
                'info_pages' => $storageEngine->getInfoPages(),
                'support' => $storageEngine->getSupportInformationMessage(),
                'variables' => $storageEngine->getHtmlVariables(),
                'page' => $this->page !== '' ? $storageEngine->getPage($this->page) : '',
            ];
        }

        $this->response->render('server/engines/show', ['engine' => $engine, 'page' => $this->page]);

        return $this->response->response();
    }

    private function setEngineAndPageProperties(mixed $routeVars): void
    {
        if (! is_array($routeVars)) {
            return;
        }

        $this->engine = isset($routeVars['engine']) && is_string($routeVars['engine']) ? $routeVars['engine'] : '';
        $this->page = isset($routeVars['page']) && is_string($routeVars['page']) ? $routeVars['page'] : '';
    }
}
