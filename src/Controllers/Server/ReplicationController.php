<?php
/**
 * Server replications
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Replication\ReplicationGui;
use PhpMyAdmin\Replication\ReplicationInfo;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\UrlParams;

use function is_array;

/**
 * Server replications
 */
#[Route('/server/replication', ['GET', 'POST'])]
final class ReplicationController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ReplicationGui $replicationGui,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $hasReplicaClearScreen = (bool) $request->getParsedBodyParamAsStringOrNull('replica_clear_screen');
        $replicaConfigure = $request->getParsedBodyParam('replica_configure');
        $primaryConfigure = $request->getParsedBodyParam('primary_configure');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $primaryConnection = $request->getParsedBodyParamAsStringOrNull('primary_connection');
        $replicationInfo->load($primaryConnection);

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $this->response->addScriptFiles(['server/privileges.js', 'replication.js', 'vendor/zxcvbn-ts.js']);

        $urlParams = $request->getParsedBodyParam('url_params');
        if (is_array($urlParams)) {
            UrlParams::$params = $urlParams;
        }

        if ($this->dbi->isSuperUser()) {
            $srReplicaAction = $request->getParsedBodyParamAsStringOrNull('sr_replica_action');
            $srSkipErrorsCount = $request->getParsedBodyParamAsStringOrNull('sr_skip_errors_count', '1');
            $srReplicaControlParam = $request->getParsedBodyParamAsStringOrNull('sr_replica_control_param');

            $this->replicationGui->handleControlRequest(
                $request->getParsedBodyParam('sr_take_action') !== null,
                $request->getParsedBodyParam('replica_changeprimary') !== null,
                $request->getParsedBodyParam('sr_replica_server_control') !== null,
                $srReplicaAction,
                $request->getParsedBodyParam('sr_replica_skip_error') !== null,
                (int) $srSkipErrorsCount,
                $srReplicaControlParam,
                $request->getParsedBodyParamAsString('username', ''),
                $request->getParsedBodyParamAsString('pma_pw', ''),
                $request->getParsedBodyParamAsString('hostname', ''),
                (int) $request->getParsedBodyParamAsStringOrNull('text_port'),
            );
        }

        $errorMessages = $this->replicationGui->getHtmlForErrorMessage();

        if ($primaryInfo['status']) {
            $primaryAddUser = $request->getParsedBodyParamAsStringOrNull('primary_add_user');
            $username = $request->getParsedBodyParamAsStringOrNull('username');
            $hostname = $request->getParsedBodyParamAsStringOrNull('hostname');

            $primaryReplicationHtml = $this->replicationGui->getHtmlForPrimaryReplication(
                $primaryConnection,
                $hasReplicaClearScreen,
                $primaryAddUser,
                $username,
                $hostname,
            );
        }

        if ($primaryConfigure !== null) {
            $primaryConfigurationHtml = $this->replicationGui->getHtmlForPrimaryConfiguration();
        } else {
            if (! $hasReplicaClearScreen) {
                $replicaConfigurationHtml = $this->replicationGui->getHtmlForReplicaConfiguration(
                    $primaryConnection,
                    $replicaInfo['status'],
                    $replicationInfo->getReplicaStatus(),
                    $replicaConfigure !== null,
                );
            }

            if ($replicaConfigure !== null) {
                $changePrimaryHtml = $this->replicationGui->getHtmlForReplicationChangePrimary('replica_changeprimary');
            }
        }

        $this->response->render('server/replication/index', [
            'url_params' => UrlParams::$params,
            'is_super_user' => $this->dbi->isSuperUser(),
            'error_messages' => $errorMessages,
            'is_primary' => $primaryInfo['status'],
            'primary_configure' => $primaryConfigure,
            'replica_configure' => $replicaConfigure,
            'clear_screen' => $hasReplicaClearScreen,
            'primary_replication_html' => $primaryReplicationHtml ?? '',
            'primary_configuration_html' => $primaryConfigurationHtml ?? '',
            'replica_configuration_html' => $replicaConfigurationHtml ?? '',
            'change_primary_html' => $changePrimaryHtml ?? '',
        ]);

        return $this->response->response();
    }
}
