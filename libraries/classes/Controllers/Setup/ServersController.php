<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Setup\ServersController
 *
 * @package PhpMyAdmin\Controllers\Setup
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\ServersForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\FormProcessing;

/**
 * Class ServersController
 * @package PhpMyAdmin\Controllers\Setup
 */
class ServersController extends AbstractController
{
    /**
     * @param array $params Request parameters
     * @return string HTML
     */
    public function index(array $params): string
    {
        $pages = $this->getPages();

        $id = Core::isValid($params['id'], 'numeric') ? (int) $params['id'] : null;
        $hasServer = ! empty($id) && $this->config->get("Servers/$id") !== null;

        if (! $hasServer && ($params['mode'] !== 'revert' && $params['mode'] !== 'edit')) {
            $id = 0;
        }

        ob_start();
        FormProcessing::process(new ServersForm($this->config, $id));
        $page = ob_get_clean();

        return $this->template->render('setup/servers/index', [
            'formset' => $params['formset'] ?? '',
            'pages' => $pages,
            'has_server' => $hasServer,
            'mode' => $params['mode'],
            'server_id' => $id,
            'server_dsn' => $this->config->getServerDSN($id),
            'page' => $page,
        ]);
    }

    /**
     * @param array $params Request parameters
     * @return void
     */
    public function destroy(array $params): void
    {
        $id = Core::isValid($params['id'], 'numeric') ? (int) $params['id'] : null;

        $hasServer = ! empty($id) && $this->config->get("Servers/$id") !== null;

        if ($hasServer) {
            $this->config->removeServer($id);
        }
    }
}
