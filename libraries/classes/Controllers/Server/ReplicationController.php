<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server replications
 * @package PhpMyAdmin\Controllers\Server
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\ReplicationGui;
use Throwable;

/**
 * Server replications
 * @package PhpMyAdmin\Controllers\Server
 */
class ReplicationController extends AbstractController
{
    /**
     * @param array          $params         Request parameters
     * @param ReplicationGui $replicationGui ReplicationGui instance
     *
     * @return string HTML
     * @throws Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function index(array $params, ReplicationGui $replicationGui): string
    {
        global $replication_info, $server_slave_replication, $url_params;

        $errorMessages = $replicationGui->getHtmlForErrorMessage();

        if ($replication_info['master']['status']) {
            $masterReplicationHtml = $replicationGui->getHtmlForMasterReplication();
        }

        if (isset($params['mr_configure'])) {
            $masterConfigurationHtml = $replicationGui->getHtmlForMasterConfiguration();
        } else {
            if (! isset($params['repl_clear_scr'])) {
                $slaveConfigurationHtml = $replicationGui->getHtmlForSlaveConfiguration(
                    $replication_info['slave']['status'],
                    $server_slave_replication
                );
            }
            if (isset($params['sl_configure'])) {
                $changeMasterHtml = $replicationGui->getHtmlForReplicationChangeMaster('slave_changemaster');
            }
        }

        return $this->template->render('server/replication/index', [
            'url_params' => $url_params,
            'is_super_user' => $this->dbi->isSuperuser(),
            'error_messages' => $errorMessages,
            'is_master' => $replication_info['master']['status'],
            'master_configure' => $params['mr_configure'],
            'slave_configure' => $params['sl_configure'],
            'clear_screen' => $params['repl_clear_scr'],
            'master_replication_html' => $masterReplicationHtml ?? '',
            'master_configuration_html' => $masterConfigurationHtml ?? '',
            'slave_configuration_html' => $slaveConfigurationHtml ?? '',
            'change_master_html' => $changeMasterHtml ?? '',
        ]);
    }
}
