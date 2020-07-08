<?php
/**
 * Holds the PhpMyAdmin\Controllers\Server\EnginesController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\StorageEngine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handles viewing storage engine details
 */
class EnginesController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        Common::server();

        $this->render('server/engines/index', [
            'engines' => StorageEngine::getStorageEngines(),
        ]);

        return $response;
    }

    /**
     * Displays details about a given Storage Engine
     *
     * @param array $args Request params
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        Common::server();

        $page = $args['page'] ?? '';

        $engine = [];
        if (StorageEngine::isValid($args['engine'])) {
            $storageEngine = StorageEngine::getEngine($args['engine']);
            $engine = [
                'engine' => $args['engine'],
                'title' => $storageEngine->getTitle(),
                'help_page' => $storageEngine->getMysqlHelpPage(),
                'comment' => $storageEngine->getComment(),
                'info_pages' => $storageEngine->getInfoPages(),
                'support' => $storageEngine->getSupportInformationMessage(),
                'variables' => $storageEngine->getHtmlVariables(),
                'page' => ! empty($page) ? $storageEngine->getPage($page) : '',
            ];
        }

        $this->render('server/engines/show', [
            'engine' => $engine,
            'page' => $page,
        ]);

        return $response;
    }
}
