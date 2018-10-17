<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerEnginesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Server\Common;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Handles viewing storage engine details
 *
 * @package PhpMyAdmin\Controllers
 */
class ServerEnginesController extends Controller
{
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        /**
         * Does the common work
         */
        require 'libraries/server_common.inc.php';

        /**
         * Displays the sub-page heading
         */
        $this->response->addHTML(
            $this->template->render('server/sub_page_header', [
                'type' => 'engines',
            ])
        );

        /**
         * Did the user request information about a certain storage engine?
         */
        if (empty($_REQUEST['engine'])
            || ! StorageEngine::isValid($_REQUEST['engine'])
        ) {
            $html = $this->template->render('server/engines/list_engines', [
                'engines' => StorageEngine::getStorageEngines(),
            ]);
        } else {
            $engine = StorageEngine::getEngine($_REQUEST['engine']);
            $html = $this->_getHtmlForShowEngine($engine);
        }
        $this->response->addHTML($html);
    }

    /**
     * Returns HTML code for engine inspect
     *
     * @param  StorageEngine $engine engine beeing inspected
     *
     * @return string
     */
    private function _getHtmlForShowEngine(StorageEngine $engine): string
    {
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
        $pageOutput = ! empty($page) ? $engine->getPage($page) : '';

        /**
         * Displays details about a given Storage Engine
         */
        return $this->template->render('server/engines/show_engine', [
            'title' => $engine->getTitle(),
            'help_page' => $engine->getMysqlHelpPage(),
            'comment' => $engine->getComment(),
            'info_pages' => $engine->getInfoPages(),
            'support' => $engine->getSupportInformationMessage(),
            'variables' => $engine->getHtmlVariables(),
            'page_output' => $pageOutput,
            'page' => $page,
            'engine' => $_REQUEST['engine'],
        ]);
    }
}
