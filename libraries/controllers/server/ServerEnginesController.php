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
        /**
         * Displays details about a given Storage Engine
         */
        $html = '';
        $engine_plugin = StorageEngine::getEngine($_REQUEST['engine']);
        $html .= '<h2>' . "\n"
            . Util::getImage('b_engine.png')
            . '    ' . htmlspecialchars($engine_plugin->getTitle()) . "\n"
            . '    ' . Util::showMySQLDocu(
                $engine_plugin->getMysqlHelpPage()
            )
            . "\n" . '</h2>' . "\n\n";
        $html .= '<p>' . "\n"
            . '    <em>' . "\n"
            . '        ' . htmlspecialchars($engine_plugin->getComment()) . "\n"
            . '    </em>' . "\n"
            . '</p>' . "\n\n";
        $infoPages = $engine_plugin->getInfoPages();
        if (! empty($infoPages) && is_array($infoPages)) {
            $html .= '<p>' . "\n"
                . '    <strong>[</strong>' . "\n";
            if (empty($_REQUEST['page'])) {
                $html .= '    <strong>' . __('Variables') . '</strong>' . "\n";
            } else {
                $html .= '    <a href="server_engines.php'
                    . PMA_URL_getCommon(array('engine' => $_REQUEST['engine']))
                    . '">' . __('Variables') . '</a>' . "\n";
            }
            foreach ($infoPages as $current => $label) {
                $html .= '    <strong>|</strong>' . "\n";
                if (isset($_REQUEST['page']) && $_REQUEST['page'] == $current) {
                    $html .= '    <strong>' . $label . '</strong>' . "\n";
                } else {
                    $html .= '    <a href="server_engines.php'
                        . PMA_URL_getCommon(
                            array('engine' => $_REQUEST['engine'], 'page' => $current)
                        )
                        . '">' . htmlspecialchars($label) . '</a>' . "\n";
                }
            }
            unset($current, $label);
            $html .= '    <strong>]</strong>' . "\n"
                . '</p>' . "\n\n";
        }
        unset($infoPages, $page_output);
        if (! empty($_REQUEST['page'])) {
            $page_output = $engine_plugin->getPage($_REQUEST['page']);
        }
        if (! empty($page_output)) {
            $html .= $page_output;
        } else {
            $html .= '<p> ' . $engine_plugin->getSupportInformationMessage() . "\n"
               . '</p>' . "\n"
               . $engine_plugin->getHtmlVariables();
        }

        return $html;
    }
}