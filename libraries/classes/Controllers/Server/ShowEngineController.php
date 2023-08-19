<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function is_array;
use function is_string;

/**
 * Displays details about a given Storage Engine.
 */
final class ShowEngineController extends AbstractController
{
    private string $engine = '';
    private string $page = '';

    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->setEngineAndPageProperties($request->getAttribute('routeVars'));

        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

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

        $this->render('server/engines/show', ['engine' => $engine, 'page' => $this->page]);
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
