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
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;

/**
 * Handles viewing server plugin details
 *
 * @package PhpMyAdmin\Controllers
 */
class PluginsController extends AbstractController
{
    /**
     * @var Plugins
     */
    private $plugins;

    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param Plugins           $plugins  Plugins object
     */
    public function __construct($response, $dbi, Template $template, Plugins $plugins)
    {
        parent::__construct($response, $dbi, $template);
        $this->plugins = $plugins;
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

        $plugins = [];
        $serverPlugins = $this->plugins->getAll();
        foreach ($serverPlugins as $plugin) {
            $plugins[$plugin->getType()][] = $plugin->toArray();
        }
        ksort($plugins);

        $cleanTypes = [];
        foreach (array_keys($plugins) as $type) {
            $cleanTypes[$type] = preg_replace(
                '/[^a-z]/',
                '',
                mb_strtolower($type)
            );
        }
        return $this->template->render('server/plugins/index', [
            'plugins' => $plugins,
            'clean_types' => $cleanTypes,
        ]);
    }
}
