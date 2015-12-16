<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerPluginsController
*
* @package PMA\libraries\controllers\server
*/

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;
use PMA\libraries\Template;

/**
 * Handles viewing server plugin details
 *
 * @package PMA\libraries\controllers\server
 */
class ServerPluginsController extends Controller
{
    /**
     * @var array plugin details
     */
    protected $plugins;

    /**
     * Constructs ServerPluginsController
     */
    public function __construct()
    {
        parent::__construct();
        $this->_setServerPlugins();
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        include 'libraries/server_common.inc.php';

        $header  = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('jquery/jquery.tablesorter.js');
        $scripts->addFile('server_plugins.js');

        /**
         * Displays the page
        */
        $this->response->addHTML(PMA_getHtmlForSubPageHeader('plugins'));
        $this->response->addHTML($this->_getPluginsHtml());
    }

    /**
     * Sets details about server plugins
     *
     * @return void
     */
    private function _setServerPlugins()
    {
        $sql = "SELECT plugin_name,
                       plugin_type,
                       (plugin_status = 'ACTIVE') AS is_active,
                       plugin_type_version,
                       plugin_author,
                       plugin_description,
                       plugin_license
                FROM information_schema.plugins
                ORDER BY plugin_type, plugin_name";

        $res = $this->dbi->query($sql);
        $this->plugins = array();
        while ($row = $this->dbi->fetchAssoc($res)) {
            $this->plugins[$row['plugin_type']][] = $row;
        }
        $this->dbi->freeResult($res);
        ksort($this->plugins);
    }

    /**
     * Returns the html for plugin Tab.
     *
     * @return string
     */
    private function _getPluginsHtml()
    {
        $html  = '<div id="plugins_plugins">';
        $html .= Template::get('server/plugins/section_links')
            ->render(array('plugins' => $this->plugins));

        foreach ($this->plugins as $plugin_type => $plugin_list) {
            $html .= Template::get('server/plugins/section')
                ->render(
                    array(
                        'plugin_type' => $plugin_type,
                        'plugin_list' => $plugin_list,
                    )
                );
        }
        $html .= '</div>';
        return $html;
    }
}