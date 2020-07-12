<?php
/**
 * Server replications
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use function is_array;

/**
 * Server replications
 */
class ReplicationController extends AbstractController
{
    /** @var ReplicationGui */
    private $replicationGui;

    /**
     * @param Response          $response       Response object
     * @param DatabaseInterface $dbi            DatabaseInterface object
     * @param Template          $template       Template that should be used
     * @param ReplicationGui    $replicationGui ReplicationGui instance
     */
    public function __construct($response, $dbi, Template $template, ReplicationGui $replicationGui)
    {
        parent::__construct($response, $dbi, $template);
        $this->replicationGui = $replicationGui;
    }

    public function index(): void
    {
        global $replication_info, $server_slave_replication, $url_params;

        $params = [
            'url_params' => $_POST['url_params'] ?? null,
            'mr_configure' => $_POST['mr_configure'] ?? null,
            'sl_configure' => $_POST['sl_configure'] ?? null,
            'repl_clear_scr' => $_POST['repl_clear_scr'] ?? null,
        ];

        Common::server();
        ReplicationInfo::load();

        $this->addScriptFiles(['server/privileges.js', 'replication.js', 'vendor/zxcvbn.js']);

        if (isset($params['url_params']) && is_array($params['url_params'])) {
            $url_params = $params['url_params'];
        }

        if ($this->dbi->isSuperuser()) {
            $this->replicationGui->handleControlRequest();
        }

        $errorMessages = $this->replicationGui->getHtmlForErrorMessage();

        if ($replication_info['master']['status']) {
            $masterReplicationHtml = $this->replicationGui->getHtmlForMasterReplication();
        }

        if (isset($params['mr_configure'])) {
            $masterConfigurationHtml = $this->replicationGui->getHtmlForMasterConfiguration();
        } else {
            if (! isset($params['repl_clear_scr'])) {
                $slaveConfigurationHtml = $this->replicationGui->getHtmlForSlaveConfiguration(
                    $replication_info['slave']['status'],
                    $server_slave_replication
                );
            }
            if (isset($params['sl_configure'])) {
                $changeMasterHtml = $this->replicationGui->getHtmlForReplicationChangeMaster('slave_changemaster');
            }
        }

        $this->render('server/replication/index', [
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
