<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;
use function array_keys;
use function ksort;
use function mb_strtolower;
use function preg_replace;

/**
 * Handles viewing server plugin details
 */
class PluginsController extends AbstractController
{
    /** @var Plugins */
    private $plugins;

    /**
     * @param Response $response
     */
    public function __construct($response, Template $template, Plugins $plugins)
    {
        parent::__construct($response, $template);
        $this->plugins = $plugins;
    }

    public function index(): void
    {
        Common::server();

        $this->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'server/plugins.js']);

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

        $this->render('server/plugins/index', [
            'plugins' => $plugins,
            'clean_types' => $cleanTypes,
        ]);
    }
}
