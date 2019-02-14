<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\PluginsController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Server\Common;
use PhpMyAdmin\Template;

/**
 * Handles viewing server plugin details
 *
 * @package PhpMyAdmin\Controllers
 */
class PluginsController extends Controller
{
    /**
     * @var array plugin details
     */
    protected $plugins;

    /**
     * Constructs PluginsController
     *
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     */
    public function __construct($response, $dbi)
    {
        parent::__construct($response, $dbi);
        $this->_setServerPlugins();
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        include ROOT_PATH . 'libraries/server_common.inc.php';

        $header  = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('vendor/jquery/jquery.tablesorter.js');
        $scripts->addFile('server_plugins.js');

        /**
         * Displays the page
        */
        $this->response->addHTML(
            $this->template->render('server/sub_page_header', [
                'type' => 'plugins',
            ])
        );
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
        $this->plugins = [];
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
        $plugins_type_clean = [];
        $keys = array_keys($this->plugins);
        foreach ($keys as $plugin_type) {
            $plugins_type_clean[$plugin_type] = preg_replace(
                '/[^a-z]/',
                '',
                mb_strtolower($plugin_type)
            );
        }
        return $this->template->render('server/plugins/main', [
            'plugins' => $this->plugins,
            'plugins_type_clean' => $plugins_type_clean,
        ]);
    }
}
