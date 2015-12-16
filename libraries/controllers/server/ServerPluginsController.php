<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerPluginsController
*
* @package PMA\libraries\controllers\server
*/

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;

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
        $this->response->addHTML($this->_getPluginTab());
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
        $plugins = array();
        while ($row = $this->dbi->fetchAssoc($res)) {
            $plugins[$row['plugin_type']][] = $row;
        }
        $this->dbi->freeResult($res);
        ksort($plugins);

        $this->plugins = $plugins;
    }

    /**
     * Returns the html for plugin Tab.
     *
     * @return string
     */
    private function _getPluginTab()
    {
        $html  = '<div id="plugins_plugins">';
        $html .= '<div id="sectionlinks">';

        foreach ($this->plugins as $plugin_type => $plugin_list) {
            $key = 'plugins-'
                . preg_replace('/[^a-z]/', '', /*overload*/mb_strtolower($plugin_type));
            $html .= '<a href="#' . $key . '">'
                . htmlspecialchars($plugin_type) . '</a>' . "\n";
        }

        $html .= '</div>';
        $html .= '<br />';

        foreach ($this->plugins as $plugin_type => $plugin_list) {
            $key = 'plugins-'
                . preg_replace('/[^a-z]/', '', /*overload*/mb_strtolower($plugin_type));
            sort($plugin_list);

            $html .= '<table class="data_full_width" id="' . $key . '">';
            $html .= '<caption class="tblHeaders">';
            $html .=  htmlspecialchars($plugin_type);
            $html .= '</caption>';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>' . __('Plugin') . '</th>';
            $html .= '<th>' . __('Description') . '</th>';
            $html .= '<th>' . __('Version') . '</th>';
            $html .= '<th>' .  __('Author') . '</th>';
            $html .= '<th>' . __('License') . '</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            $html .= $this->_getPluginList($plugin_list);

            $html .= '</tbody>';
            $html .= '</table>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Returns the html for plugin List.
     *
     * @param array $plugin_list list
     *
     * @return string
     */
    private function _getPluginList($plugin_list)
    {
        $html = "";
        $odd_row = false;
        foreach ($plugin_list as $plugin) {
            $odd_row = !$odd_row;
            $html .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
            $html .= '<th>';
            $html .= htmlspecialchars($plugin['plugin_name']);
            if (! $plugin['is_active']) {
                $html .= '&nbsp;<small class="attention">' . __('disabled') . '</small>';
            }
            $html .= '</th>';
            $html .= '<td>' . htmlspecialchars($plugin['plugin_description']) . '</td>';
            $html .= '<td>' . htmlspecialchars($plugin['plugin_type_version']) . '</td>';
            $html .= '<td>' . htmlspecialchars($plugin['plugin_author']) . '</td>';
            $html .= '<td>' . htmlspecialchars($plugin['plugin_license']) . '</td>';
            $html .= '</tr>';
        }
        return $html;
    }
}