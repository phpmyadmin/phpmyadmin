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
            $this->response->addHTML($this->_getHtmlForSpecifiedServerEngines());
        }
    }

    /**
     * Return HTML for server all Engines information
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
     * @return string
     */
    private function _getHtmlForSpecifiedServerEngines()
    {
        $engine_plugin = StorageEngine::getEngine($_REQUEST['engine']);
        $pageOutput = ! empty($_REQUEST['page'])
            ? $engine_plugin->getPage($_REQUEST['page']) : '';

        /**
         * Displays details about a given Storage Engine
         */
        return Template::get('server/engines/engine')->render(
            array(
                'title' => $engine_plugin->getTitle(),
                'helpPage' => $engine_plugin->getMysqlHelpPage(),
                'comment' => $engine_plugin->getComment(),
                'infoPages' => $engine_plugin->getInfoPages(),
                'support' => $engine_plugin->getSupportInformationMessage(),
                'variables' => $engine_plugin->getHtmlVariables(),
                'pageOutput' => $pageOutput,
            )
        );
    }
}