<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerEnginesController
 *
 * @package PMA\libraries\controllers\server
 */

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;
use PMA\libraries\StorageEngine;
use PMA\libraries\Template;
use PMA\libraries\Util;

/**
 * Handles viewing storage engine details
 *
 * @package PMA\libraries\controllers\server
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
        $this->response->addHTML(PMA_getHtmlForSubPageHeader('engines'));

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
    private function _getHtmlForServerEngine($engine)
    {
        $pageOutput = ! empty($_REQUEST['page'])
            ? $engine->getPage($_REQUEST['page']) : '';

        /**
         * Displays details about a given Storage Engine
         */
        return Template::get('server/engines/engine')->render(
            array(
                'title' => $engine->getTitle(),
                'helpPage' => $engine->getMysqlHelpPage(),
                'comment' => $engine->getComment(),
                'infoPages' => $engine->getInfoPages(),
                'support' => $engine->getSupportInformationMessage(),
                'variables' => $engine->getHtmlVariables(),
                'pageOutput' => $pageOutput,
            )
        );
    }
}