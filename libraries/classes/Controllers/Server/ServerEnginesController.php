<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerEnginesController
 *
 * @package PhpMyAdmin\Controllers
 */

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
            Template::get('server/sub_page_header')->render([
                'type' => 'engines',
            ])
        );

        /**
         * Did the user request information about a certain storage engine?
         */
        if (empty($_REQUEST['engine'])
            || ! StorageEngine::isValid($_REQUEST['engine'])
        ) {
            $this->response->addHTML($this->_getHtmlForAllServerEngines());
        } else {
            $engine = StorageEngine::getEngine($_REQUEST['engine']);
            $this->response->addHTML($this->_getHtmlForServerEngine($engine));
        }
    }

    /**
     * Return HTML with all Storage Engine information
     *
     * @return string
     */
    private function _getHtmlForAllServerEngines()
    {
        return Template::get('server/engines/engines')->render(
            array('engines' => StorageEngine::getStorageEngines())
        );
    }

    /**
     * Return HTML for a given Storage Engine
     *
     * @param StorageEngine $engine storage engine
     *
     * @return string
     */
    private function _getHtmlForServerEngine(StorageEngine $engine)
    {
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
        $pageOutput = ! empty($page) ? $engine->getPage($page) : '';

        /**
         * Displays details about a given Storage Engine
         */
        return Template::get('server/engines/engine')->render(
            array(
                'title' => $engine->getTitle(),
                'help_page' => $engine->getMysqlHelpPage(),
                'comment' => $engine->getComment(),
                'info_pages' => $engine->getInfoPages(),
                'support' => $engine->getSupportInformationMessage(),
                'variables' => $engine->getHtmlVariables(),
                'page_output' => $pageOutput,
                'page' => $page,
                'engine' => $_REQUEST['engine'],
            )
        );
    }
}
