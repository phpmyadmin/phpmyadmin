<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function array_keys;
use function ksort;
use function mb_strtolower;
use function preg_replace;

/**
 * Handles viewing server plugin details
 */
class PluginsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Plugins $plugins,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

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
                mb_strtolower($type),
            );
        }

        $this->render('server/plugins/index', ['plugins' => $plugins, 'clean_types' => $cleanTypes]);
    }
}
