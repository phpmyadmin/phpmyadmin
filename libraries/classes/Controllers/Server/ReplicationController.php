<?php
/**
 * Server replications
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\ResponseRenderer;
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

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        ReplicationGui $replicationGui,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->replicationGui = $replicationGui;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $params = [
            'url_params' => $_POST['url_params'] ?? null,
            'primary_configure' => $_POST['primary_configure'] ?? null,
            'replica_configure' => $_POST['replica_configure'] ?? null,
            'repl_clear_scr' => $_POST['repl_clear_scr'] ?? null,
        ];
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $replicationInfo->load($_POST['primary_connection'] ?? null);

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $this->addScriptFiles(['server/privileges.js', 'replication.js', 'vendor/zxcvbn-ts.js']);

        if (isset($params['url_params']) && is_array($params['url_params'])) {
            $GLOBALS['urlParams'] = $params['url_params'];
        }

        if ($this->dbi->isSuperUser()) {
            $this->replicationGui->handleControlRequest(
                isset($_POST['sr_take_action']),
                isset($_POST['replica_changeprimary']),
                isset($_POST['sr_replica_server_control']),
                $_POST['sr_replica_action'] ?? null,
                isset($_POST['sr_replica_skip_error']),
                isset($_POST['sr_skip_errors_count']) ? (int) $_POST['sr_skip_errors_count'] : 1,
                $_POST['sr_replica_control_param'] ?? null,
                [
                    'username' => $GLOBALS['dbi']->escapeString($_POST['username']),
                    'pma_pw' => $GLOBALS['dbi']->escapeString($_POST['pma_pw']),
                    'hostname' => $GLOBALS['dbi']->escapeString($_POST['hostname']),
                    'port' => (int) $GLOBALS['dbi']->escapeString($_POST['text_port']),
                ]
            );
        }

        $errorMessages = $this->replicationGui->getHtmlForErrorMessage();

        if ($primaryInfo['status']) {
            $primaryReplicationHtml = $this->replicationGui->getHtmlForPrimaryReplication(
                $_POST['primary_connection'] ?? null,
                $params['repl_clear_scr'],
                $_POST['primary_add_user'] ?? null,
                $_POST['username'] ?? null,
                $_POST['hostname'] ?? null
            );
        }

        if (isset($params['primary_configure'])) {
            $primaryConfigurationHtml = $this->replicationGui->getHtmlForPrimaryConfiguration();
        } else {
            if (! isset($params['repl_clear_scr'])) {
                $replicaConfigurationHtml = $this->replicationGui->getHtmlForReplicaConfiguration(
                    $_POST['primary_connection'] ?? null,
                    $replicaInfo['status'],
                    $replicationInfo->getReplicaStatus(),
                    isset($_POST['replica_configure'])
                );
            }

            if (isset($params['replica_configure'])) {
                $changePrimaryHtml = $this->replicationGui->getHtmlForReplicationChangePrimary('replica_changeprimary');
            }
        }

        $this->render('server/replication/index', [
            'url_params' => $GLOBALS['urlParams'],
            'is_super_user' => $this->dbi->isSuperUser(),
            'error_messages' => $errorMessages,
            'is_primary' => $primaryInfo['status'],
            'primary_configure' => $params['primary_configure'],
            'replica_configure' => $params['replica_configure'],
            'clear_screen' => $params['repl_clear_scr'],
            'primary_replication_html' => $primaryReplicationHtml ?? '',
            'primary_configuration_html' => $primaryConfigurationHtml ?? '',
            'replica_configuration_html' => $replicaConfigurationHtml ?? '',
            'change_primary_html' => $changePrimaryHtml ?? '',
        ]);
    }
}
