<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Plugins;

use function array_keys;
use function ksort;
use function mb_strtolower;
use function preg_replace;

/**
 * Handles viewing server plugin details
 */
#[Route('/server/plugins', ['GET'])]
final class PluginsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Plugins $plugins,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->response->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'server/plugins.js']);

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

        $this->response->render('server/plugins/index', ['plugins' => $plugins, 'clean_types' => $cleanTypes]);

        return $this->response->response();
    }
}
