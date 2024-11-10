<?php
/**
 * Functions for the replication GUI
 */

declare(strict_types=1);

namespace PhpMyAdmin\Replication;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function sprintf;
use function str_replace;
use function strtok;
use function time;

/**
 * Functions for the replication GUI
 */
class ReplicationGui
{
    public function __construct(private Replication $replication, private Template $template)
    {
    }

    /**
     * returns HTML for error message
     *
     * @return string HTML code
     */
    public function getHtmlForErrorMessage(): string
    {
        $html = '';
        if (isset($_SESSION['replication']['sr_action_status'], $_SESSION['replication']['sr_action_info'])) {
            if ($_SESSION['replication']['sr_action_status'] === 'error') {
                $errorMessage = $_SESSION['replication']['sr_action_info'];
                $html .= Message::error($errorMessage)->getDisplay();
                $_SESSION['replication']['sr_action_status'] = 'unknown';
            } elseif ($_SESSION['replication']['sr_action_status'] === 'success') {
                $successMessage = $_SESSION['replication']['sr_action_info'];
                $html .= Message::success($successMessage)->getDisplay();
                $_SESSION['replication']['sr_action_status'] = 'unknown';
            }
        }

        return $html;
    }

    /**
     * returns HTML for primary replication
     *
     * @return string HTML code
     */
    public function getHtmlForPrimaryReplication(
        string|null $connection,
        bool $hasReplicaClearScreen,
        string|null $primaryAddUser,
        string|null $username,
        string|null $hostname,
    ): string {
        if (! $hasReplicaClearScreen) {
            $primaryStatusTable = $this->getHtmlForReplicationStatusTable($connection, 'primary', true, false);
            $dbi = DatabaseInterface::getInstance();
            if ($dbi->isMySql() && $dbi->getVersion() >= 80022) {
                $replicas = $dbi->fetchResult('SHOW REPLICAS', null, null);
            } elseif ($dbi->isMariaDB() && $dbi->getVersion() >= 100501) {
                $replicas = $dbi->fetchResult('SHOW REPLICA HOSTS', null, null);
            } else {
                $replicas = $dbi->fetchResult('SHOW SLAVE HOSTS');
            }

            $urlParams = UrlParams::$params;
            $urlParams['primary_add_user'] = true;
            $urlParams['replica_clear_screen'] = true;
        }

        if ($primaryAddUser !== null) {
            $primaryAddReplicaUser = $this->getHtmlForReplicationPrimaryAddReplicaUser($username, $hostname);
        }

        return $this->template->render('server/replication/primary_replication', [
            'clear_screen' => $hasReplicaClearScreen,
            'primary_status_table' => $primaryStatusTable ?? '',
            'replicas' => $replicas ?? [],
            'url_params' => $urlParams ?? [],
            'primary_add_user' => $primaryAddUser !== null,
            'primary_add_replica_user' => $primaryAddReplicaUser ?? '',
        ]);
    }

    /**
     * returns HTML for primary replication configuration
     *
     * @return string HTML code
     */
    public function getHtmlForPrimaryConfiguration(): string
    {
        $databaseMultibox = $this->getHtmlForReplicationDbMultibox();

        return $this->template->render(
            'server/replication/primary_configuration',
            ['database_multibox' => $databaseMultibox],
        );
    }

    /**
     * returns HTML for replica replication configuration
     *
     * @param string|null $connection               Primary connection
     * @param bool        $serverReplicaStatus      Whether it is Primary or Replica
     * @param mixed[]     $serverReplicaReplication Replica replication
     * @param bool        $replicaConfigure         Replica configure
     *
     * @return string HTML code
     */
    public function getHtmlForReplicaConfiguration(
        string|null $connection,
        bool $serverReplicaStatus,
        array $serverReplicaReplication,
        bool $replicaConfigure,
    ): string {
        $dbi = DatabaseInterface::getInstance();

        $serverReplicaMultiReplication = [];
        if ($dbi->isMariaDB() && $dbi->getVersion() >= 100501) {
            $serverReplicaMultiReplication = $dbi->fetchResult('SHOW ALL REPLICAS STATUS');
        } elseif ($dbi->isMariaDB()) {
            $serverReplicaMultiReplication = $dbi->fetchResult('SHOW ALL SLAVES STATUS');
        }

        $isReplicaIoRunning = false;
        $isReplicaSqlRunning = false;

        if ($serverReplicaStatus) {
            $urlParams = UrlParams::$params;
            $urlParams['sr_take_action'] = true;
            $urlParams['sr_replica_server_control'] = true;

            $isReplicaIoRunning = isset($serverReplicaReplication[0]['Slave_IO_Running'])
                && $serverReplicaReplication[0]['Slave_IO_Running'] !== 'No'
                || isset($serverReplicaReplication[0]['Replica_IO_Running'])
                && $serverReplicaReplication[0]['Replica_SQL_Running'] !== 'No';

            $isReplicaSqlRunning = isset($serverReplicaReplication[0]['Slave_SQL_Running'])
                && $serverReplicaReplication[0]['Slave_SQL_Running'] !== 'No'
                || isset($serverReplicaReplication[0]['Replica_SQL_Running'])
                && $serverReplicaReplication[0]['Replica_SQL_Running'] !== 'No';

            if (! $isReplicaIoRunning) {
                $urlParams['sr_replica_action'] = 'start';
            } else {
                $urlParams['sr_replica_action'] = 'stop';
            }

            $urlParams['sr_replica_control_param'] = 'IO_THREAD';
            $replicaControlIoLink = Url::getCommon($urlParams, '', false);

            if (! $isReplicaSqlRunning) {
                $urlParams['sr_replica_action'] = 'start';
            } else {
                $urlParams['sr_replica_action'] = 'stop';
            }

            $urlParams['sr_replica_control_param'] = 'SQL_THREAD';
            $replicaControlSqlLink = Url::getCommon($urlParams, '', false);

            if (! $isReplicaIoRunning || ! $isReplicaSqlRunning) {
                $urlParams['sr_replica_action'] = 'start';
            } else {
                $urlParams['sr_replica_action'] = 'stop';
            }

            $urlParams['sr_replica_control_param'] = null;
            $replicaControlFullLink = Url::getCommon($urlParams, '', false);

            $urlParams['sr_replica_action'] = 'reset';
            $replicaControlResetLink = Url::getCommon($urlParams, '', false);

            $urlParams = UrlParams::$params;
            $urlParams['sr_take_action'] = true;
            $urlParams['sr_replica_skip_error'] = true;
            $replicaSkipErrorLink = Url::getCommon($urlParams, '', false);

            $urlParams = UrlParams::$params;
            $urlParams['replica_configure'] = true;
            $urlParams['replica_clear_screen'] = true;

            $reconfigurePrimaryLink = Url::getCommon($urlParams, '', false);

            $replicaStatusTable = $this->getHtmlForReplicationStatusTable($connection, 'replica', true, false);
        }

        return $this->template->render('server/replication/replica_configuration', [
            'server_replica_multi_replication' => $serverReplicaMultiReplication,
            'url_params' => UrlParams::$params,
            'primary_connection' => $connection ?? '',
            'server_replica_status' => $serverReplicaStatus,
            'replica_status_table' => $replicaStatusTable ?? '',
            'replica_sql_running' => $isReplicaIoRunning,
            'replica_io_running' => $isReplicaSqlRunning,
            'replica_control_full_link' => $replicaControlFullLink ?? '',
            'replica_control_reset_link' => $replicaControlResetLink ?? '',
            'replica_control_sql_link' => $replicaControlSqlLink ?? '',
            'replica_control_io_link' => $replicaControlIoLink ?? '',
            'replica_skip_error_link' => $replicaSkipErrorLink ?? '',
            'reconfigure_primary_link' => $reconfigurePrimaryLink ?? '',
            'has_replica_configure' => $replicaConfigure,
        ]);
    }

    /**
     * returns HTML code for selecting databases
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationDbMultibox(): string
    {
        $databases = [];
        foreach (DatabaseInterface::getInstance()->getDatabaseList() as $database) {
            if (Utilities::isSystemSchema($database)) {
                continue;
            }

            $databases[] = $database;
        }

        return $this->template->render('server/replication/database_multibox', ['databases' => $databases]);
    }

    /**
     * returns HTML for changing primary
     *
     * @param string $submitName submit button name
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationChangePrimary(string $submitName): string
    {
        [$usernameLength, $hostnameLength] = $this->getUsernameHostnameLength();

        return $this->template->render('server/replication/change_primary', [
            'server_id' => time(),
            'username_length' => $usernameLength,
            'hostname_length' => $hostnameLength,
            'submit_name' => $submitName,
        ]);
    }

    /**
     * This function returns html code for table with replication status.
     *
     * @param string|null $connection primary connection
     * @param string      $type       either primary or replica
     * @param bool        $isHidden   if true, then default style is set to hidden, default value false
     * @param bool        $hasTitle   if true, then title is displayed, default true
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationStatusTable(
        string|null $connection,
        string $type,
        bool $isHidden = false,
        bool $hasTitle = true,
    ): string {
        $replicationInfo = new ReplicationInfo(DatabaseInterface::getInstance());
        $replicationInfo->load($connection);

        $replicationVariables = $replicationInfo->primaryVariables;
        $variablesAlerts = [];
        $variablesOks = [];
        $serverReplication = $replicationInfo->getPrimaryStatus();
        if ($type === 'replica') {
            $replicationVariables = $replicationInfo->replicaVariables;
            $variablesAlerts = [
                'Slave_IO_Running' => 'No',
                'Slave_SQL_Running' => 'No',
                'Replica_IO_Running' => 'No',
                'Replica_SQL_Running' => 'No',
            ];
            $variablesOks = [
                'Slave_IO_Running' => 'Yes',
                'Slave_SQL_Running' => 'Yes',
                'Replica_IO_Running' => 'Yes',
                'Replica_SQL_Running' => 'Yes',
            ];
            $serverReplication = $replicationInfo->getReplicaStatus();
        }

        $variables = [];
        foreach ($replicationVariables as $variable) {
            if (! isset($serverReplication[0], $serverReplication[0][$variable])) {
                continue;
            }

            $serverReplicationVariable = $serverReplication[0][$variable];

            $variables[$variable] = ['name' => $variable, 'status' => '', 'value' => $serverReplicationVariable];

            if (isset($variablesAlerts[$variable]) && $variablesAlerts[$variable] === $serverReplicationVariable) {
                $variables[$variable]['status'] = 'text-danger';
            } elseif (isset($variablesOks[$variable]) && $variablesOks[$variable] === $serverReplicationVariable) {
                $variables[$variable]['status'] = 'text-success';
            }

            $variablesWrap = [
                'Replicate_Do_DB',
                'Replicate_Ignore_DB',
                'Replicate_Do_Table',
                'Replicate_Ignore_Table',
                'Replicate_Wild_Do_Table',
                'Replicate_Wild_Ignore_Table',
            ];
            if (! in_array($variable, $variablesWrap, true)) {
                continue;
            }

            $variables[$variable]['value'] = str_replace(',', ', ', $serverReplicationVariable);
        }

        return $this->template->render('server/replication/status_table', [
            'type' => $type,
            'is_hidden' => $isHidden,
            'has_title' => $hasTitle,
            'variables' => $variables,
        ]);
    }

    /**
     * get the correct username and hostname lengths for this MySQL server
     *
     * @return array<int,int> username length, hostname length
     */
    public function getUsernameHostnameLength(): array
    {
        $fieldsInfo = DatabaseInterface::getInstance()->getColumns('mysql', 'user');
        $usernameLength = 16;
        $hostnameLength = 41;
        foreach ($fieldsInfo as $val) {
            if ($val->field === 'User') {
                strtok($val->type, '()');
                $v = strtok('()');
                if (Util::isInteger($v)) {
                    $usernameLength = (int) $v;
                }
            } elseif ($val->field === 'Host') {
                strtok($val->type, '()');
                $v = strtok('()');
                if (Util::isInteger($v)) {
                    $hostnameLength = (int) $v;
                }
            }
        }

        return [$usernameLength, $hostnameLength];
    }

    /**
     * returns html code to add a replication replica user to the primary
     *
     * @return string HTML code
     */
    public function getHtmlForReplicationPrimaryAddReplicaUser(string|null $postUsername, string|null $hostname): string
    {
        [$usernameLength, $hostnameLength] = $this->getUsernameHostnameLength();

        $username = '';
        if ($postUsername === '') {
            $GLOBALS['pred_username'] = 'any';
        } elseif ($postUsername !== null && $postUsername !== '0') {
            $username = $GLOBALS['new_username'] ?? $postUsername;
        }

        $currentUser = DatabaseInterface::getInstance()->fetchValue('SELECT USER();');
        if ($currentUser !== '' && $currentUser !== false && $currentUser !== null) {
            $userHost = str_replace(
                "'",
                '',
                mb_substr(
                    $currentUser,
                    mb_strrpos($currentUser, '@') + 1,
                ),
            );
            if ($userHost !== 'localhost' && $userHost !== '127.0.0.1') {
                $thisHost = $userHost;
            }
        }

        // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
        if (! isset($GLOBALS['pred_hostname']) && $hostname !== null) {
            $GLOBALS['pred_hostname'] = match (mb_strtolower($hostname)) {
                'localhost', '127.0.0.1' => 'localhost',
                '%' => 'any',
                default => 'userdefined',
            };
        }

        return $this->template->render('server/replication/primary_add_replica_user', [
            'username_length' => $usernameLength,
            'hostname_length' => $hostnameLength,
            'has_username' => $postUsername !== null,
            'username' => $username,
            'hostname' => $hostname ?? '',
            'predefined_username' => $GLOBALS['pred_username'] ?? '',
            'predefined_hostname' => $GLOBALS['pred_hostname'] ?? '',
            'this_host' => $thisHost ?? null,
        ]);
    }

    /**
     * handle control requests
     */
    public function handleControlRequest(
        bool $srTakeAction,
        bool $replicaChangePrimary,
        bool $srReplicaServerControl,
        string|null $srReplicaAction,
        bool $srReplicaSkipError,
        int $srSkipErrorsCount,
        string|null $srReplicaControlParam,
        string $username,
        string $pmaPassword,
        string $hostname,
        int $port,
    ): void {
        if (! $srTakeAction) {
            return;
        }

        $refresh = false;
        $result = false;
        $messageSuccess = '';
        $messageError = '';

        if ($replicaChangePrimary && ! Config::getInstance()->settings['AllowArbitraryServer']) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info'] = __(
                'Connection to server is disabled, please enable'
                . ' $cfg[\'AllowArbitraryServer\'] in phpMyAdmin configuration.',
            );
        } elseif ($replicaChangePrimary) {
            $result = $this->handleRequestForReplicaChangePrimary($username, $pmaPassword, $hostname, $port);
        } elseif ($srReplicaServerControl) {
            $result = $this->handleRequestForReplicaServerControl($srReplicaAction, $srReplicaControlParam);
            $refresh = true;

            switch ($srReplicaAction) {
                case 'start':
                    $messageSuccess = __('Replication started successfully.');
                    $messageError = __('Error starting replication.');
                    break;
                case 'stop':
                    $messageSuccess = __('Replication stopped successfully.');
                    $messageError = __('Error stopping replication.');
                    break;
                case 'reset':
                    $messageSuccess = __('Replication resetting successfully.');
                    $messageError = __('Error resetting replication.');
                    break;
                default:
                    $messageSuccess = __('Success.');
                    $messageError = __('Error.');
                    break;
            }
        } elseif ($srReplicaSkipError) {
            $result = $this->handleRequestForReplicaSkipError($srSkipErrorsCount);
        }

        if ($refresh) {
            $response = ResponseRenderer::getInstance();
            if ($response->isAjax()) {
                $response->setRequestStatus($result);
                $response->addJSON(
                    'message',
                    $result
                    ? Message::success($messageSuccess)
                    : Message::error($messageError),
                );
            } else {
                $response->redirect(
                    './index.php?route=/server/replication' . Url::getCommonRaw(UrlParams::$params, '&'),
                );
            }
        }

        unset($refresh);
    }

    public function handleRequestForReplicaChangePrimary(
        string $username,
        string $pmaPassword,
        string $hostname,
        int $port,
    ): bool {
        $_SESSION['replication']['m_username'] = $username;
        $_SESSION['replication']['m_password'] = $pmaPassword;
        $_SESSION['replication']['m_hostname'] = $hostname;
        $_SESSION['replication']['m_port'] = $port;
        $_SESSION['replication']['m_correct'] = '';

        // Attempt to connect to the new primary server
        $connectionToPrimary = $this->replication->connectToPrimary($username, $pmaPassword, $hostname, $port);

        if ($connectionToPrimary === null) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info'] = sprintf(
                __('Unable to connect to primary %s.'),
                htmlspecialchars($hostname),
            );
        } else {
            // Read the current primary position
            $position = $this->replication->replicaBinLogPrimary(ConnectionType::Auxiliary);

            if ($position === []) {
                $_SESSION['replication']['sr_action_status'] = 'error';
                $_SESSION['replication']['sr_action_info'] = __(
                    'Unable to read primary log position. Possible privilege problem on primary.',
                );
            } else {
                $_SESSION['replication']['m_correct'] = true;

                if (
                    ! $this->replication->replicaChangePrimary(
                        $username,
                        $pmaPassword,
                        $hostname,
                        $port,
                        $position,
                        true,
                        false,
                        ConnectionType::User,
                    )
                ) {
                    $_SESSION['replication']['sr_action_status'] = 'error';
                    $_SESSION['replication']['sr_action_info'] = __('Unable to change primary!');
                } else {
                    $_SESSION['replication']['sr_action_status'] = 'success';
                    $_SESSION['replication']['sr_action_info'] = sprintf(
                        __('Primary server changed successfully to %s.'),
                        htmlspecialchars($hostname),
                    );
                }
            }
        }

        return $_SESSION['replication']['sr_action_status'] === 'success';
    }

    public function handleRequestForReplicaServerControl(string|null $srReplicaAction, string|null $control): bool
    {
        if ($srReplicaAction === 'reset') {
            $qStop = $this->replication->replicaControl('STOP', null, ConnectionType::User);
            $dbi = DatabaseInterface::getInstance();
            if ($dbi->isMySql() && $dbi->getVersion() >= 80022 || $dbi->isMariaDB() && $dbi->getVersion() >= 100501) {
                $qReset = $dbi->tryQuery('RESET REPLICA;');
            } else {
                $qReset = $dbi->tryQuery('RESET SLAVE;');
            }

            $qStart = $this->replication->replicaControl('START', null, ConnectionType::User);

            return $qStop !== false && $qStop !== -1 && $qReset !== false && $qStart !== false && $qStart !== -1;
        }

        $qControl = $this->replication->replicaControl($srReplicaAction, $control, ConnectionType::User);

        return $qControl !== false && $qControl !== -1;
    }

    public function handleRequestForReplicaSkipError(int $srSkipErrorsCount): bool
    {
        $dbi = DatabaseInterface::getInstance();
        $qStop = $this->replication->replicaControl('STOP', null, ConnectionType::User);
        if ($dbi->isMySql() && $dbi->getVersion() >= 80400) {
            $qSkip = $dbi->tryQuery('SET GLOBAL SQL_REPLICA_SKIP_COUNTER = ' . $srSkipErrorsCount . ';');
        } else {
            $qSkip = $dbi->tryQuery('SET GLOBAL SQL_SLAVE_SKIP_COUNTER = ' . $srSkipErrorsCount . ';');
        }

        $qStart = $this->replication->replicaControl('START', null, ConnectionType::User);

        return $qStop !== false && $qStop !== -1 && $qSkip !== false && $qStart !== false && $qStart !== -1;
    }
}
