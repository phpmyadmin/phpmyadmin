<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function count;
use function explode;
use function sprintf;

final class ReplicationInfo
{
    /** @var string[] */
    public static $primaryVariables = [
        'File',
        'Position',
        'Binlog_Do_DB',
        'Binlog_Ignore_DB',
    ];

    /** @var string[] */
    public static $replicaVariables = [
        'Slave_IO_State',
        'Master_Host',
        'Master_User',
        'Master_Port',
        'Connect_Retry',
        'Master_Log_File',
        'Read_Master_Log_Pos',
        'Relay_Log_File',
        'Relay_Log_Pos',
        'Relay_Master_Log_File',
        'Slave_IO_Running',
        'Slave_SQL_Running',
        'Replicate_Do_DB',
        'Replicate_Ignore_DB',
        'Replicate_Do_Table',
        'Replicate_Ignore_Table',
        'Replicate_Wild_Do_Table',
        'Replicate_Wild_Ignore_Table',
        'Last_Errno',
        'Last_Error',
        'Skip_Counter',
        'Exec_Master_Log_Pos',
        'Relay_Log_Space',
        'Until_Condition',
        'Until_Log_File',
        'Until_Log_Pos',
        'Master_SSL_Allowed',
        'Master_SSL_CA_File',
        'Master_SSL_CA_Path',
        'Master_SSL_Cert',
        'Master_SSL_Cipher',
        'Master_SSL_Key',
        'Seconds_Behind_Master',
    ];

    public static function load(): void
    {
        global $url_params;
        global $server_master_replication, $server_slave_replication, $server_slave_multi_replication;
        global $replication_info;

        $server_master_replication = self::getPrimaryStatus();

        if (! empty($_POST['master_connection'])) {
            $server_slave_multi_replication = self::getMultiPrimaryStatus();

            if ($server_slave_multi_replication) {
                self::setDefaultPrimaryConnection($_POST['master_connection']);
                $url_params['master_connection'] = $_POST['master_connection'];
            }
        }

        $server_slave_replication = self::getReplicaStatus();

        $replication_info = [
            'master' => self::getPrimaryInfo($server_master_replication),
            'slave' => self::getReplicaInfo($server_slave_replication),
        ];
    }

    /**
     * @return array
     */
    private static function getPrimaryStatus()
    {
        global $dbi;

        return $dbi->fetchResult('SHOW MASTER STATUS');
    }

    /**
     * @return array
     */
    private static function getReplicaStatus()
    {
        global $dbi;

        return $dbi->fetchResult('SHOW SLAVE STATUS');
    }

    /**
     * @return array
     */
    private static function getMultiPrimaryStatus()
    {
        global $dbi;

        return $dbi->fetchResult('SHOW ALL SLAVES STATUS');
    }

    private static function setDefaultPrimaryConnection(string $connection): void
    {
        global $dbi;

        $dbi->query(sprintf('SET @@default_master_connection = \'%s\'', $dbi->escapeString($connection)));
    }

    private static function fill(array $status, string $key): array
    {
        if (empty($status[0][$key])) {
            return [];
        }

        return explode(',', $status[0][$key]);
    }

    private static function getPrimaryInfo(array $status): array
    {
        $primaryInfo = ['status' => false];

        if (count($status) > 0) {
            $primaryInfo['status'] = true;
        }

        if (! $primaryInfo['status']) {
            return $primaryInfo;
        }

        $primaryInfo['Do_DB'] = self::fill($status, 'Binlog_Do_DB');
        $primaryInfo['Ignore_DB'] = self::fill($status, 'Binlog_Ignore_DB');

        return $primaryInfo;
    }

    private static function getReplicaInfo(array $status): array
    {
        $replicaInfo = ['status' => false];

        if (count($status) > 0) {
            $replicaInfo['status'] = true;
        }

        if (! $replicaInfo['status']) {
            return $replicaInfo;
        }

        $replicaInfo['Do_DB'] = self::fill($status, 'Replicate_Do_DB');
        $replicaInfo['Ignore_DB'] = self::fill($status, 'Replicate_Ignore_DB');
        $replicaInfo['Do_Table'] = self::fill($status, 'Replicate_Do_Table');
        $replicaInfo['Ignore_Table'] = self::fill($status, 'Replicate_Ignore_Table');
        $replicaInfo['Wild_Do_Table'] = self::fill($status, 'Replicate_Wild_Do_Table');
        $replicaInfo['Wild_Ignore_Table'] = self::fill($status, 'Replicate_Wild_Ignore_Table');

        return $replicaInfo;
    }
}
