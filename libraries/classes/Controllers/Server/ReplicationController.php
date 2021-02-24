<?php
/**
 * Server replications
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use function is_array;

/**
 * Server replications
 */
class ReplicationController extends AbstractController
{
    /** @var ReplicationGui */
    private $replicationGui;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, ReplicationGui $replicationGui, $dbi)
    {
        parent::__construct($response, $template);
        $this->replicationGui = $replicationGui;
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $url_params, $err_url;

        $params = [
            'url_params' => $_POST['url_params'] ?? null,
            'mr_configure' => $_POST['mr_configure'] ?? null,
            'sl_configure' => $_POST['sl_configure'] ?? null,
            'repl_clear_scr' => $_POST['repl_clear_scr'] ?? null,
        ];
        $err_url = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $replicationInfo->load($_POST['master_connection'] ?? null);

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $this->addScriptFiles(['server/privileges.js', 'replication.js', 'vendor/zxcvbn.js']);

        if (isset($params['url_params']) && is_array($params['url_params'])) {
            $url_params = $params['url_params'];
        }

        if ($this->dbi->isSuperUser()) {
            $this->replicationGui->handleControlRequest();
        }

        $errorMessages = $this->replicationGui->getHtmlForErrorMessage();

        if ($primaryInfo['status']) {
            $masterReplicationHtml = $this->replicationGui->getHtmlForMasterReplication();
        }

        if (isset($params['mr_configure'])) {
            $masterConfigurationHtml = $this->replicationGui->getHtmlForMasterConfiguration();
        } else {
            if (! isset($params['repl_clear_scr'])) {
                $slaveConfigurationHtml = $this->replicationGui->getHtmlForSlaveConfiguration(
                    $replicaInfo['status'],
                    $replicationInfo->getReplicaStatus()
                );
            }
            if (isset($params['sl_configure'])) {
                $changeMasterHtml = $this->replicationGui->getHtmlForReplicationChangeMaster('slave_changemaster');
            }
        }

        $this->render('server/replication/index', [
            'url_params' => $url_params,
            'is_super_user' => $this->dbi->isSuperUser(),
            'error_messages' => $errorMessages,
            'is_master' => $primaryInfo['status'],
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
