<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\PluginsController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Handles viewing server plugin details
 *
 * @package PhpMyAdmin\Controllers
 */
class PluginsController extends AbstractController
{
    /**
     * @var array plugin details
     */
    private $plugins;

    /**
     * Constructs PluginsController
     *
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     */
    public function __construct($response, $dbi, Template $template)
    {
        parent::__construct($response, $dbi, $template);
        $this->setPlugins();
    }

    /**
     * Index action
     *
     * @return string
     */
    public function index(): string
    {
        include ROOT_PATH . 'libraries/server_common.inc.php';

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('vendor/jquery/jquery.tablesorter.js');
        $scripts->addFile('server/plugins.js');

        $pluginsTypeClean = [];
        foreach (array_keys($this->plugins) as $pluginType) {
            $pluginsTypeClean[$pluginType] = preg_replace(
                '/[^a-z]/',
                '',
                mb_strtolower($pluginType)
            );
        }
        return $this->template->render('server/plugins/index', [
            'plugins' => $this->plugins,
            'plugins_type_clean' => $pluginsTypeClean,
        ]);
    }

    /**
     * Sets details about server plugins
     *
     * @return void
     */
    private function setPlugins(): void
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
}
