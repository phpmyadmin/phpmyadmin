<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\EnginesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\StorageEngine;

/**
 * Handles viewing storage engine details
 *
 * @package PhpMyAdmin\Controllers
 */
class EnginesController extends AbstractController
{
    /**
     * Index action
     *
     * @return string
     */
    public function index(): string
    {
        require ROOT_PATH . 'libraries/server_common.inc.php';

        return $this->template->render('server/engines/index', [
            'engines' => StorageEngine::getStorageEngines(),
        ]);
    }

    /**
     * Displays details about a given Storage Engine
     *
     * @param array $params Request params
     *
     * @return string
     */
    public function show(array $params): string
    {
        require ROOT_PATH . 'libraries/server_common.inc.php';

        $page = $params['page'] ?? '';

        $engine = [];
        if (StorageEngine::isValid($params['engine'])) {
            $storageEngine = StorageEngine::getEngine($params['engine']);
            $engine = [
                'engine' => $params['engine'],
                'title' => $storageEngine->getTitle(),
                'help_page' => $storageEngine->getMysqlHelpPage(),
                'comment' => $storageEngine->getComment(),
                'info_pages' => $storageEngine->getInfoPages(),
                'support' => $storageEngine->getSupportInformationMessage(),
                'variables' => $storageEngine->getHtmlVariables(),
                'page' => ! empty($page) ? $storageEngine->getPage($page) : '',
            ];
        }

        return $this->template->render('server/engines/show', [
            'engine' => $engine,
            'page' => $page,
        ]);
    }
}
