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
        ResponseRenderer  $response,
        Template          $template,
        ReplicationGui    $replicationGui,
        DatabaseInterface $dbi
    )
    {
        parent::__construct($response, $template);
        $this->replicationGui = $replicationGui;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $params = [
            'url_params' => $request->getParsedBodyParam('url_params'),
            'primary_configure' => $request->getParsedBodyParam('primary_configure'),
            'replica_configure' => $request->getParsedBodyParam('replica_configure'),
            'repl_clear_scr' => $request->getParsedBodyParam('repl_clear_scr'),
        ];
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $replicationInfo->load($request->getParsedBodyParam('primary_connection'));

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $this->addScriptFiles(['server/privileges.js', 'replication.js', 'vendor/zxcvbn-ts.js']);

        if (isset($params['url_params']) && is_array($params['url_params'])) {
            $GLOBALS['urlParams'] = $params['url_params'];
        }

        if ($this->dbi->isSuperUser()) {
            $this->replicationGui->handleControlRequest(
                $request->getParsedBodyParam('sr_take_action') !== null,
                $request->getParsedBodyParam('replica_changeprimary') !== null,
                $request->getParsedBodyParam('sr_replica_server_control') !== null,
                $request->getParsedBodyParam('sr_replica_action'),
                $request->getParsedBodyParam('sr_replica_skip_error') !== null,
                (int)$request->getParsedBodyParam('sr_skip_errors_count', 1),
                $request->getParsedBodyParam('sr_replica_control_param'),
                [
                    'username' => $GLOBALS['dbi']->escapeString($request->getParsedBodyParam('username')),
                    'pma_pw' => $GLOBALS['dbi']->escapeString($request->getParsedBodyParam('pma_pw')),
                    'hostname' => $GLOBALS['dbi']->escapeString($request->getParsedBodyParam('hostname')),
                    'port' => (int) $GLOBALS['dbi']->escapeString($request->getParsedBodyParam('text_port')),
                ]
            );
        }

        $errorMessages = $this->replicationGui->getHtmlForErrorMessage();

        if ($primaryInfo['status']) {
            $primaryReplicationHtml = $this->replicationGui->getHtmlForPrimaryReplication(
                $request->getParsedBodyParam('primary_connection'),
                $params['repl_clear_scr'],
                $request->getParsedBodyParam('primary_add_user'),
                 $request->getParsedBodyParam('username'),
                 $request->getParsedBodyParam('hostname')
            );
        }

        if (isset($params['primary_configure'])) {
            $primaryConfigurationHtml = $this->replicationGui->getHtmlForPrimaryConfiguration();
        } else {
            if (!isset($params['repl_clear_scr'])) {
                $replicaConfigurationHtml = $this->replicationGui->getHtmlForReplicaConfiguration(
                    $request->getParsedBodyParam('primary_connection'),
                    $replicaInfo['status'],
                    $replicationInfo->getReplicaStatus(),
                    $request->getParsedBodyParam('replica_configure') !== null
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
